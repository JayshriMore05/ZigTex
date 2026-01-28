<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$campaign_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($campaign_id > 0) {
    try {
        $pdo = db();
        
        // Fetch campaign data
        $stmt = $pdo->prepare("
            SELECT c.*,
                   (SELECT COUNT(*) FROM campaign_prospects WHERE campaign_id = c.id) as total_prospects,
                   (SELECT COUNT(*) FROM campaign_prospects WHERE campaign_id = c.id AND status = 'sent') as emails_sent,
                   (SELECT COUNT(*) FROM campaign_prospects WHERE campaign_id = c.id AND status = 'delivered') as emails_delivered,
                   (SELECT COUNT(*) FROM campaign_prospects WHERE campaign_id = c.id AND status = 'opened') as emails_opened,
                   (SELECT COUNT(*) FROM campaign_prospects WHERE campaign_id = c.id AND status = 'clicked') as emails_clicked,
                   (SELECT COUNT(*) FROM campaign_prospects WHERE campaign_id = c.id AND status = 'replied') as emails_replied,
                   (SELECT COUNT(*) FROM campaign_prospects WHERE campaign_id = c.id AND status = 'bounced') as emails_bounced
            FROM campaigns c
            WHERE c.id = ? AND c.user_id = ?
        ");
        
        $stmt->execute([$campaign_id, $user_id]);
        $campaign = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$campaign) {
            // Campaign not found or doesn't belong to user
            header("Location: campaigns.php");
            exit();
        }
        
        // Get campaign steps for scheduling
        $stmt = $pdo->prepare("
            SELECT * FROM campaign_steps 
            WHERE campaign_id = ? 
            ORDER BY step_number
        ");
        $stmt->execute([$campaign_id]);
        $campaign_steps = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate scheduled times
        $scheduled_emails = [];
        $total_steps = count($campaign_steps);
        
        // Get timezone for calculations
        $timezone = $campaign['timezone'] ?? 'Europe/London';
        date_default_timezone_set($timezone);
        
        // Parse weekly schedule
        $weekly_schedule = explode(',', $campaign['weekly_schedule'] ?? 'Mon,Tue,Wed,Thu,Fri');
        $working_days = array_map('trim', $weekly_schedule);
        
        // Parse daily hours
        $start_time = strtotime($campaign['start_time'] ?? '09:00');
        $end_time = strtotime($campaign['end_time'] ?? '17:00');
        
        // Prepare schedule based on campaign settings
        if (!empty($campaign_steps)) {
            $current_time = time();
            
            // For each step, calculate when emails should be sent
            foreach ($campaign_steps as $index => $step) {
                $step_number = $step['step_number'];
                $delay_days = $step['delay_days'] ?? 0;
                $delay_hours = $step['delay_hours'] ?? 0;
                
                // Calculate base send time
                $send_time = strtotime("+{$delay_days} days +{$delay_hours} hours", $current_time);
                
                // Adjust to next working day/time if needed
                $send_time = $this->adjustToWorkingHours($send_time, $working_days, $start_time, $end_time);
                
                $scheduled_emails[] = [
                    'step' => $step_number,
                    'step_type' => $step['step_type'],
                    'subject' => $step['subject'],
                    'delay_days' => $delay_days,
                    'delay_hours' => $delay_hours,
                    'scheduled_date' => date('Y-m-d', $send_time),
                    'scheduled_time' => date('H:i', $send_time),
                    'day_of_week' => date('D', $send_time),
                    'total_recipients' => $campaign['total_prospects'],
                    'estimated_send' => date('Y-m-d H:i', $send_time)
                ];
                
                // Update current time for next step
                $current_time = $send_time;
            }
        }
        
        // Get upcoming scheduled sends for the next 7 days
        $upcoming_schedule = [];
        $today = strtotime('today');
        
        for ($i = 0; $i < 7; $i++) {
            $day = strtotime("+{$i} days", $today);
            $day_of_week = date('D', $day);
            
            if (in_array($day_of_week, $working_days)) {
                $date = date('Y-m-d', $day);
                $day_name = date('l', $day);
                
                // Calculate how many emails would be sent on this day based on campaign settings
                $estimated_sends = $this->calculateDailySends($campaign, $day);
                
                $upcoming_schedule[] = [
                    'date' => $date,
                    'day_name' => $day_name,
                    'day_of_week' => $day_of_week,
                    'working_day' => true,
                    'estimated_sends' => $estimated_sends,
                    'start_time' => date('H:i', $start_time),
                    'end_time' => date('H:i', $end_time)
                ];
            } else {
                $date = date('Y-m-d', $day);
                $day_name = date('l', $day);
                
                $upcoming_schedule[] = [
                    'date' => $date,
                    'day_name' => $day_name,
                    'day_of_week' => $day_of_week,
                    'working_day' => false,
                    'estimated_sends' => 0,
                    'start_time' => '--:--',
                    'end_time' => '--:--'
                ];
            }
        }
        
        // Get sent emails log for the past 7 days
        $seven_days_ago = date('Y-m-d 00:00:00', strtotime('-7 days'));
        
        $stmt = $pdo->prepare("
            SELECT 
                DATE(cp.updated_at) as send_date,
                COUNT(*) as emails_sent,
                SUM(CASE WHEN cp.status = 'delivered' THEN 1 ELSE 0 END) as emails_delivered,
                SUM(CASE WHEN cp.status = 'opened' THEN 1 ELSE 0 END) as emails_opened,
                SUM(CASE WHEN cp.status = 'clicked' THEN 1 ELSE 0 END) as emails_clicked,
                SUM(CASE WHEN cp.status = 'replied' THEN 1 ELSE 0 END) as emails_replied
            FROM campaign_prospects cp
            WHERE cp.campaign_id = ? 
            AND cp.status IN ('sent', 'delivered', 'opened', 'clicked', 'replied')
            AND cp.updated_at >= ?
            GROUP BY DATE(cp.updated_at)
            ORDER BY send_date DESC
        ");
        $stmt->execute([$campaign_id, $seven_days_ago]);
        $sent_log = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format dates
        $campaign['created_at_formatted'] = date('F j, Y', strtotime($campaign['created_at'] ?? 'now'));
        
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
} else {
    header("Location: campaigns.php");
    exit();
}

// Handle schedule updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'update_schedule':
                $weekly_schedule = $_POST['weekly_schedule'] ?? [];
                $start_time = $_POST['start_time'] ?? '09:00';
                $end_time = $_POST['end_time'] ?? '17:00';
                $timezone = $_POST['timezone'] ?? 'Europe/London';
                
                // Convert array to comma-separated string
                $schedule_string = implode(',', $weekly_schedule);
                
                $stmt = $pdo->prepare("
                    UPDATE campaigns 
                    SET weekly_schedule = ?, 
                        start_time = ?, 
                        end_time = ?, 
                        timezone = ?,
                        updated_at = NOW()
                    WHERE id = ? AND user_id = ?
                ");
                $stmt->execute([
                    $schedule_string,
                    $start_time,
                    $end_time,
                    $timezone,
                    $campaign_id,
                    $user_id
                ]);
                
                $_SESSION['success'] = 'Schedule updated successfully!';
                break;
                
            case 'pause_schedule':
                $stmt = $pdo->prepare("UPDATE campaigns SET status = 'paused' WHERE id = ? AND user_id = ? AND status = 'running'");
                $stmt->execute([$campaign_id, $user_id]);
                $_SESSION['success'] = 'Campaign schedule paused!';
                break;
                
            case 'resume_schedule':
                $stmt = $pdo->prepare("UPDATE campaigns SET status = 'running' WHERE id = ? AND user_id = ? AND status = 'paused'");
                $stmt->execute([$campaign_id, $user_id]);
                $_SESSION['success'] = 'Campaign schedule resumed!';
                break;
                
            case 'update_email_priority':
                $email_priority = $_POST['email_priority'] ?? 'equally_divided';
                $daily_limit = $_POST['daily_limit'] ?? 0;
                
                $stmt = $pdo->prepare("
                    UPDATE campaigns 
                    SET email_priority = ?, 
                        daily_email_limit = ?,
                        updated_at = NOW()
                    WHERE id = ? AND user_id = ?
                ");
                $stmt->execute([
                    $email_priority,
                    $daily_limit,
                    $campaign_id,
                    $user_id
                ]);
                
                $_SESSION['success'] = 'Email sending settings updated!';
                break;
        }
        
        // Refresh page
        header("Location: campaign_schedule.php?id=$campaign_id");
        exit();
        
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Action failed: ' . $e->getMessage();
        header("Location: campaign_schedule.php?id=$campaign_id");
        exit();
    }
}

// Helper functions
function adjustToWorkingHours($timestamp, $working_days, $start_time, $end_time) {
    $day_of_week = date('D', $timestamp);
    $hour = date('H:i', $timestamp);
    
    // If it's a non-working day, move to next working day at start time
    if (!in_array($day_of_week, $working_days)) {
        $timestamp = strtotime('next ' . $working_days[0], $timestamp);
        $timestamp = strtotime(date('Y-m-d', $timestamp) . ' ' . date('H:i', $start_time));
    }
    // If it's before start time, move to start time
    elseif ($hour < date('H:i', $start_time)) {
        $timestamp = strtotime(date('Y-m-d', $timestamp) . ' ' . date('H:i', $start_time));
    }
    // If it's after end time, move to next day at start time
    elseif ($hour > date('H:i', $end_time)) {
        $timestamp = strtotime('+1 day', $timestamp);
        $day_of_week = date('D', $timestamp);
        
        // Check if next day is a working day
        while (!in_array($day_of_week, $working_days)) {
            $timestamp = strtotime('+1 day', $timestamp);
            $day_of_week = date('D', $timestamp);
        }
        
        $timestamp = strtotime(date('Y-m-d', $timestamp) . ' ' . date('H:i', $start_time));
    }
    
    return $timestamp;
}

function calculateDailySends($campaign, $day_timestamp) {
    $total_prospects = $campaign['total_prospects'] ?? 0;
    $pending_prospects = $total_prospects - ($campaign['emails_sent'] ?? 0);
    
    if ($pending_prospects <= 0) {
        return 0;
    }
    
    $email_priority = $campaign['email_priority'] ?? 'equally_divided';
    $daily_limit = $campaign['daily_email_limit'] ?? 100;
    
    switch ($email_priority) {
        case 'asap':
            // Send as many as possible within daily limit
            return min($pending_prospects, $daily_limit);
            
        case 'equally_divided':
            // Divide remaining emails equally over next 7 working days
            $working_days = explode(',', $campaign['weekly_schedule'] ?? 'Mon,Tue,Wed,Thu,Fri');
            $day_of_week = date('D', $day_timestamp);
            
            if (in_array($day_of_week, $working_days)) {
                $remaining_working_days = $this->countRemainingWorkingDays($campaign);
                if ($remaining_working_days > 0) {
                    $daily_send = ceil($pending_prospects / $remaining_working_days);
                    return min($daily_send, $daily_limit);
                }
            }
            break;
            
        case 'random':
            // Random distribution
            $avg_daily = $daily_limit / 2;
            return min(rand($avg_daily - 10, $avg_daily + 10), $pending_prospects, $daily_limit);
    }
    
    return 0;
}

function countRemainingWorkingDays($campaign) {
    $working_days = explode(',', $campaign['weekly_schedule'] ?? 'Mon,Tue,Wed,Thu,Fri');
    $today = date('D');
    $count = 0;
    
    // Count working days from today for next 7 days
    for ($i = 0; $i < 7; $i++) {
        $day = date('D', strtotime("+{$i} days"));
        if (in_array($day, $working_days)) {
            $count++;
        }
    }
    
    return $count;
}

// Check for success/error messages
$success_message = '';
$error_message = '';

if (isset($_SESSION['success'])) {
    $success_message = $_SESSION['success'];
    unset($_SESSION['success']);
} elseif (isset($_SESSION['error'])) {
    $error_message = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campaign Schedule - <?php echo htmlspecialchars($campaign['name']); ?> - ZigTex</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Clean White UI Styles */
        body {
            background: #f8fafc;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .main-content {
            padding: 24px;
            background: #f8fafc;
        }
        
        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        
        .page-title-section h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1f2937;
            margin: 0;
        }
        
        .page-description {
            color: #6b7280;
            margin-top: 4px;
            font-size: 0.95rem;
        }
        
        /* White Cards */
        .card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
            margin-bottom: 20px;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .card-header h3 {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }
        
        .card-header h3 i {
            color: #6366f1;
        }
        
        /* Schedule Overview */
        .schedule-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .overview-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            transition: all 0.2s ease;
        }
        
        .overview-card:hover {
            border-color: #d1d5db;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .overview-label {
            font-size: 0.75rem;
            color: #6b7280;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .overview-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
            line-height: 1;
        }
        
        /* Calendar Grid */
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
            margin-bottom: 24px;
        }
        
        .calendar-day {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
            text-align: center;
            transition: all 0.2s ease;
        }
        
        .calendar-day.working-day {
            border-color: #dbeafe;
            background: #eff6ff;
        }
        
        .calendar-day.non-working-day {
            background: #f9fafb;
            color: #9ca3af;
        }
        
        .calendar-day:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        
        .day-name {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .day-date {
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 12px;
        }
        
        .day-schedule {
            font-size: 0.75rem;
            color: #374151;
            margin-bottom: 8px;
        }
        
        .day-estimate {
            font-size: 1.25rem;
            font-weight: 700;
            color: #6366f1;
        }
        
        /* Email Steps Timeline */
        .timeline {
            position: relative;
            padding: 20px 0;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 20px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e5e7eb;
        }
        
        .timeline-step {
            position: relative;
            padding-left: 50px;
            margin-bottom: 24px;
        }
        
        .timeline-step:last-child {
            margin-bottom: 0;
        }
        
        .timeline-step::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #6366f1;
            border: 2px solid white;
            box-shadow: 0 0 0 3px #6366f1;
        }
        
        .timeline-content {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
        }
        
        .step-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .step-title {
            font-weight: 600;
            color: #1f2937;
            font-size: 1rem;
        }
        
        .step-delay {
            font-size: 0.875rem;
            color: #6b7280;
            background: #f3f4f6;
            padding: 2px 8px;
            border-radius: 4px;
        }
        
        .step-details {
            display: flex;
            gap: 16px;
            font-size: 0.875rem;
            color: #6b7280;
        }
        
        /* Settings Form */
        .settings-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 16px;
        }
        
        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 6px;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }
        
        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .checkbox-item input[type="checkbox"] {
            width: 16px;
            height: 16px;
        }
        
        /* Buttons */
        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.875rem;
            cursor: pointer;
            border: 1px solid #d1d5db;
            background: white;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }
        
        .btn-primary {
            background: #6366f1;
            color: white;
            border-color: #6366f1;
        }
        
        .btn-primary:hover {
            background: #4f46e5;
            border-color: #4f46e5;
        }
        
        .btn-secondary {
            background: white;
            color: #374151;
            border-color: #d1d5db;
        }
        
        .btn-secondary:hover {
            background: #f9fafb;
            border-color: #9ca3af;
        }
        
        .btn-success {
            background: #10b981;
            color: white;
            border-color: #10b981;
        }
        
        .btn-success:hover {
            background: #059669;
            border-color: #059669;
        }
        
        .btn-danger {
            background: #ef4444;
            color: white;
            border-color: #ef4444;
        }
        
        .btn-danger:hover {
            background: #dc2626;
            border-color: #dc2626;
        }
        
        /* Alert Messages */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: #dcfce7;
            border: 1px solid #bbf7d0;
            color: #166534;
        }
        
        .alert-error {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }
        
        .alert i {
            font-size: 1rem;
        }
        
        /* Tabs */
        .tabs {
            display: flex;
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: 24px;
        }
        
        .tab {
            padding: 12px 24px;
            font-weight: 500;
            color: #6b7280;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
        }
        
        .tab:hover {
            color: #374151;
        }
        
        .tab.active {
            color: #6366f1;
            border-bottom-color: #6366f1;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .main-content {
                padding: 16px;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            
            .schedule-overview,
            .calendar-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .tabs {
                flex-wrap: wrap;
            }
            
            .tab {
                flex: 1;
                text-align: center;
                padding: 8px 12px;
                font-size: 0.875rem;
            }
        }
        
        @media (max-width: 480px) {
            .schedule-overview,
            .calendar-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <?php include 'components/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Page Header -->
            <div class="page-header">
                <div class="page-title-section">
                    <h1>Campaign Schedule</h1>
                    <p class="page-description"><?php echo htmlspecialchars($campaign['name']); ?> - Manage sending schedule and timing</p>
                </div>
                <div class="header-actions">
                    <a href="campaign_view.php?id=<?php echo $campaign_id; ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Campaign
                    </a>
                    <?php if($campaign['status'] == 'running'): ?>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="pause_schedule">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-pause"></i> Pause Schedule
                        </button>
                    </form>
                    <?php elseif($campaign['status'] == 'paused'): ?>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="resume_schedule">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-play"></i> Resume Schedule
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Success/Error Messages -->
            <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $success_message; ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error_message; ?></span>
            </div>
            <?php endif; ?>

            <!-- Tabs -->
            <div class="tabs">
                <div class="tab active" onclick="showTab('overview')">Schedule Overview</div>
                <div class="tab" onclick="showTab('calendar')">Weekly Calendar</div>
                <div class="tab" onclick="showTab('steps')">Email Steps</div>
                <div class="tab" onclick="showTab('settings')">Schedule Settings</div>
                <div class="tab" onclick="showTab('history')">Send History</div>
            </div>

            <!-- Overview Tab -->
            <div id="overviewTab" class="tab-content active">
                <div class="schedule-overview">
                    <div class="overview-card">
                        <div class="overview-label">
                            <i class="fas fa-calendar-alt"></i> Campaign Status
                        </div>
                        <div class="overview-value">
                            <?php echo ucfirst($campaign['status']); ?>
                        </div>
                    </div>
                    
                    <div class="overview-card">
                        <div class="overview-label">
                            <i class="fas fa-users"></i> Total Prospects
                        </div>
                        <div class="overview-value">
                            <?php echo $campaign['total_prospects']; ?>
                        </div>
                    </div>
                    
                    <div class="overview-card">
                        <div class="overview-label">
                            <i class="fas fa-paper-plane"></i> Emails Sent
                        </div>
                        <div class="overview-value">
                            <?php echo $campaign['emails_sent']; ?>
                        </div>
                    </div>
                    
                    <div class="overview-card">
                        <div class="overview-label">
                            <i class="fas fa-clock"></i> Working Days
                        </div>
                        <div class="overview-value">
                            <?php 
                            $working_days = explode(',', $campaign['weekly_schedule'] ?? 'Mon,Tue,Wed,Thu,Fri');
                            echo count($working_days); 
                            ?> days
                        </div>
                    </div>
                    
                    <div class="overview-card">
                        <div class="overview-label">
                            <i class="fas fa-hourglass-half"></i> Daily Hours
                        </div>
                        <div class="overview-value">
                            <?php echo date('g:i A', strtotime($campaign['start_time'] ?? '09:00')); ?> - 
                            <?php echo date('g:i A', strtotime($campaign['end_time'] ?? '17:00')); ?>
                        </div>
                    </div>
                    
                    <div class="overview-card">
                        <div class="overview-label">
                            <i class="fas fa-globe"></i> Timezone
                        </div>
                        <div class="overview-value">
                            <?php echo htmlspecialchars($campaign['timezone'] ?? 'Europe/London'); ?>
                        </div>
                    </div>
                </div>

                <!-- Campaign Progress -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-line"></i> Campaign Progress</h3>
                    </div>
                    <div style="padding: 20px;">
                        <?php
                        $total_prospects = $campaign['total_prospects'];
                        $emails_sent = $campaign['emails_sent'];
                        $progress = $total_prospects > 0 ? ($emails_sent / $total_prospects) * 100 : 0;
                        ?>
                        <div style="margin-bottom: 16px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                <span style="font-size: 0.875rem; color: #6b7280;">Progress</span>
                                <span style="font-weight: 600; color: #1f2937;"><?php echo round($progress, 1); ?>%</span>
                            </div>
                            <div style="height: 8px; background: #e5e7eb; border-radius: 4px; overflow: hidden;">
                                <div style="height: 100%; background: linear-gradient(90deg, #6366f1 0%, #8b5cf6 100%); width: <?php echo $progress; ?>%;"></div>
                            </div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px;">
                            <div>
                                <div style="font-size: 0.75rem; color: #6b7280; margin-bottom: 4px;">Pending</div>
                                <div style="font-size: 1.25rem; font-weight: 700; color: #6366f1;">
                                    <?php echo $total_prospects - $emails_sent; ?>
                                </div>
                            </div>
                            <div>
                                <div style="font-size: 0.75rem; color: #6b7280; margin-bottom: 4px;">Sent</div>
                                <div style="font-size: 1.25rem; font-weight: 700; color: #10b981;">
                                    <?php echo $emails_sent; ?>
                                </div>
                            </div>
                            <div>
                                <div style="font-size: 0.75rem; color: #6b7280; margin-bottom: 4px;">Delivered</div>
                                <div style="font-size: 1.25rem; font-weight: 700; color: #f59e0b;">
                                    <?php echo $campaign['emails_delivered']; ?>
                                </div>
                            </div>
                            <div>
                                <div style="font-size: 0.75rem; color: #6b7280; margin-bottom: 4px;">Opened</div>
                                <div style="font-size: 1.25rem; font-weight: 700; color: #8b5cf6;">
                                    <?php echo $campaign['emails_opened']; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Calendar Tab -->
            <div id="calendarTab" class="tab-content">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-calendar-week"></i> Weekly Schedule</h3>
                        <div style="font-size: 0.875rem; color: #6b7280;">
                            Next 7 days schedule
                        </div>
                    </div>
                    <div class="calendar-grid">
                        <?php foreach ($upcoming_schedule as $day): ?>
                        <div class="calendar-day <?php echo $day['working_day'] ? 'working-day' : 'non-working-day'; ?>">
                            <div class="day-name"><?php echo $day['day_name']; ?></div>
                            <div class="day-date"><?php echo date('M j', strtotime($day['date'])); ?></div>
                            <div class="day-schedule">
                                <?php if ($day['working_day']): ?>
                                <?php echo $day['start_time']; ?> - <?php echo $day['end_time']; ?>
                                <?php else: ?>
                                No sends scheduled
                                <?php endif; ?>
                            </div>
                            <div class="day-estimate">
                                <?php if ($day['working_day']): ?>
                                <?php echo $day['estimated_sends']; ?> emails
                                <?php else: ?>
                                —
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Steps Tab -->
            <div id="stepsTab" class="tab-content">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-list-ol"></i> Email Sequence Steps</h3>
                        <div style="font-size: 0.875rem; color: #6b7280;">
                            <?php echo count($campaign_steps); ?> steps in sequence
                        </div>
                    </div>
                    <div class="timeline">
                        <?php if (!empty($scheduled_emails)): ?>
                            <?php foreach ($scheduled_emails as $email): ?>
                            <div class="timeline-step">
                                <div class="timeline-content">
                                    <div class="step-header">
                                        <div class="step-title">
                                            Step <?php echo $email['step']; ?>: <?php echo ucfirst($email['step_type']); ?>
                                        </div>
                                        <div class="step-delay">
                                            Delay: <?php echo $email['delay_days']; ?>d <?php echo $email['delay_hours']; ?>h
                                        </div>
                                    </div>
                                    <div class="step-details">
                                        <div>
                                            <i class="fas fa-calendar"></i>
                                            <?php echo $email['scheduled_date']; ?>
                                        </div>
                                        <div>
                                            <i class="fas fa-clock"></i>
                                            <?php echo $email['scheduled_time']; ?>
                                        </div>
                                        <div>
                                            <i class="fas fa-users"></i>
                                            <?php echo $email['total_recipients']; ?> recipients
                                        </div>
                                    </div>
                                    <?php if (!empty($email['subject'])): ?>
                                    <div style="margin-top: 8px; font-size: 0.875rem; color: #374151;">
                                        Subject: <?php echo htmlspecialchars($email['subject']); ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="text-align: center; padding: 40px; color: #6b7280;">
                                <i class="fas fa-envelope" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                                No email steps configured yet.
                                <a href="campaign_create.php?step=1&id=<?php echo $campaign_id; ?>" class="btn btn-primary" style="margin-top: 10px;">
                                    <i class="fas fa-edit"></i> Edit Campaign Steps
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Settings Tab -->
            <div id="settingsTab" class="tab-content">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-cog"></i> Schedule Settings</h3>
                    </div>
                    <form method="POST" id="scheduleSettingsForm">
                        <input type="hidden" name="action" value="update_schedule">
                        
                        <div class="settings-form">
                            <!-- Working Days -->
                            <div>
                                <h4 style="font-size: 1rem; font-weight: 600; color: #1f2937; margin-bottom: 16px;">
                                    <i class="fas fa-calendar-day"></i> Working Days
                                </h4>
                                <div class="form-group">
                                    <div class="checkbox-group">
                                        <?php
                                        $days = ['Mon' => 'Monday', 'Tue' => 'Tuesday', 'Wed' => 'Wednesday', 
                                                'Thu' => 'Thursday', 'Fri' => 'Friday', 'Sat' => 'Saturday', 
                                                'Sun' => 'Sunday'];
                                        $current_schedule = explode(',', $campaign['weekly_schedule'] ?? 'Mon,Tue,Wed,Thu,Fri');
                                        ?>
                                        <?php foreach ($days as $short => $full): ?>
                                        <label class="checkbox-item">
                                            <input type="checkbox" 
                                                   name="weekly_schedule[]" 
                                                   value="<?php echo $short; ?>"
                                                   <?php echo in_array($short, $current_schedule) ? 'checked' : ''; ?>>
                                            <?php echo $full; ?>
                                        </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Daily Hours -->
                            <div>
                                <h4 style="font-size: 1rem; font-weight: 600; color: #1f2937; margin-bottom: 16px;">
                                    <i class="fas fa-clock"></i> Daily Sending Hours
                                </h4>
                                <div class="form-group">
                                    <label class="form-label">Start Time</label>
                                    <input type="time" 
                                           name="start_time" 
                                           class="form-control" 
                                           value="<?php echo $campaign['start_time'] ?? '09:00'; ?>"
                                           required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">End Time</label>
                                    <input type="time" 
                                           name="end_time" 
                                           class="form-control" 
                                           value="<?php echo $campaign['end_time'] ?? '17:00'; ?>"
                                           required>
                                </div>
                            </div>

                            <!-- Timezone -->
                            <div>
                                <h4 style="font-size: 1rem; font-weight: 600; color: #1f2937; margin-bottom: 16px;">
                                    <i class="fas fa-globe"></i> Timezone
                                </h4>
                                <div class="form-group">
                                    <select name="timezone" class="form-control" required>
                                        <?php
                                        $timezones = [
                                            'Europe/London' => 'London',
                                            'America/New_York' => 'New York',
                                            'America/Chicago' => 'Chicago',
                                            'America/Denver' => 'Denver',
                                            'America/Los_Angeles' => 'Los Angeles',
                                            'Europe/Paris' => 'Paris',
                                            'Europe/Berlin' => 'Berlin',
                                            'Asia/Tokyo' => 'Tokyo',
                                            'Asia/Singapore' => 'Singapore',
                                            'Australia/Sydney' => 'Sydney'
                                        ];
                                        $current_timezone = $campaign['timezone'] ?? 'Europe/London';
                                        ?>
                                        <?php foreach ($timezones as $tz => $label): ?>
                                        <option value="<?php echo $tz; ?>" <?php echo $current_timezone == $tz ? 'selected' : ''; ?>>
                                            <?php echo $label; ?> (<?php echo $tz; ?>)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Schedule Settings
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Email Sending Settings -->
                <div class="card" style="margin-top: 20px;">
                    <div class="card-header">
                        <h3><i class="fas fa-paper-plane"></i> Email Sending Settings</h3>
                    </div>
                    <form method="POST" id="emailSettingsForm">
                        <input type="hidden" name="action" value="update_email_priority">
                        
                        <div class="settings-form">
                            <!-- Email Priority -->
                            <div>
                                <h4 style="font-size: 1rem; font-weight: 600; color: #1f2937; margin-bottom: 16px;">
                                    <i class="fas fa-tachometer-alt"></i> Sending Priority
                                </h4>
                                <div class="form-group">
                                    <select name="email_priority" class="form-control" required>
                                        <?php
                                        $priorities = [
                                            'asap' => 'Send ASAP (As fast as possible within limits)',
                                            'equally_divided' => 'Equally Divided (Spread evenly over working days)',
                                            'random' => 'Random Distribution'
                                        ];
                                        $current_priority = $campaign['email_priority'] ?? 'equally_divided';
                                        ?>
                                        <?php foreach ($priorities as $value => $label): ?>
                                        <option value="<?php echo $value; ?>" <?php echo $current_priority == $value ? 'selected' : ''; ?>>
                                            <?php echo $label; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Daily Limit -->
                            <div>
                                <h4 style="font-size: 1rem; font-weight: 600; color: #1f2937; margin-bottom: 16px;">
                                    <i class="fas fa-chart-line"></i> Daily Sending Limit
                                </h4>
                                <div class="form-group">
                                    <label class="form-label">Maximum emails per day</label>
                                    <input type="number" 
                                           name="daily_limit" 
                                           class="form-control" 
                                           value="<?php echo $campaign['daily_email_limit'] ?? 100; ?>"
                                           min="1"
                                           max="1000"
                                           required>
                                    <small style="font-size: 0.75rem; color: #6b7280; display: block; margin-top: 4px;">
                                        Maximum number of emails to send per day
                                    </small>
                                </div>
                            </div>

                            <!-- Campaign Status -->
                            <div>
                                <h4 style="font-size: 1rem; font-weight: 600; color: #1f2937; margin-bottom: 16px;">
                                    <i class="fas fa-play-circle"></i> Campaign Status
                                </h4>
                                <div class="form-group">
                                    <div style="font-size: 1.25rem; font-weight: 700; color: #1f2937;">
                                        <?php echo ucfirst($campaign['status']); ?>
                                    </div>
                                    <div style="margin-top: 12px;">
                                        <?php if($campaign['status'] == 'running'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="pause_schedule">
                                            <button type="submit" class="btn btn-danger">
                                                <i class="fas fa-pause"></i> Pause Campaign
                                            </button>
                                        </form>
                                        <?php elseif($campaign['status'] == 'paused'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="resume_schedule">
                                            <button type="submit" class="btn btn-success">
                                                <i class="fas fa-play"></i> Resume Campaign
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Sending Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- History Tab -->
            <div id="historyTab" class="tab-content">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-history"></i> Recent Send History</h3>
                        <div style="font-size: 0.875rem; color: #6b7280;">
                            Last 7 days of email sending activity
                        </div>
                    </div>
                    <?php if (!empty($sent_log)): ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                            <thead>
                                <tr>
                                    <th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb; background: #f9fafb;">Date</th>
                                    <th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb; background: #f9fafb;">Sent</th>
                                    <th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb; background: #f9fafb;">Delivered</th>
                                    <th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb; background: #f9fafb;">Opened</th>
                                    <th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb; background: #f9fafb;">Clicked</th>
                                    <th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb; background: #f9fafb;">Replied</th>
                                    <th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb; background: #f9fafb;">Delivery Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sent_log as $log): ?>
                                <?php
                                $delivery_rate = $log['emails_sent'] > 0 ? 
                                    round(($log['emails_delivered'] / $log['emails_sent']) * 100, 1) : 0;
                                ?>
                                <tr style="border-bottom: 1px solid #f3f4f6;">
                                    <td style="padding: 12px 16px; color: #1f2937;">
                                        <?php echo date('M j, Y', strtotime($log['send_date'])); ?>
                                    </td>
                                    <td style="padding: 12px 16px; color: #1f2937; font-weight: 600;">
                                        <?php echo $log['emails_sent']; ?>
                                    </td>
                                    <td style="padding: 12px 16px; color: #10b981; font-weight: 600;">
                                        <?php echo $log['emails_delivered']; ?>
                                    </td>
                                    <td style="padding: 12px 16px; color: #f59e0b; font-weight: 600;">
                                        <?php echo $log['emails_opened']; ?>
                                    </td>
                                    <td style="padding: 12px 16px; color: #8b5cf6; font-weight: 600;">
                                        <?php echo $log['emails_clicked']; ?>
                                    </td>
                                    <td style="padding: 12px 16px; color: #6366f1; font-weight: 600;">
                                        <?php echo $log['emails_replied']; ?>
                                    </td>
                                    <td style="padding: 12px 16px;">
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <span style="font-weight: 600; color: #1f2937;"><?php echo $delivery_rate; ?>%</span>
                                            <div style="flex: 1; height: 6px; background: #e5e7eb; border-radius: 3px; overflow: hidden;">
                                                <div style="height: 100%; background: #10b981; width: <?php echo $delivery_rate; ?>%;"></div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div style="text-align: center; padding: 40px; color: #6b7280;">
                        <i class="fas fa-history" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                        No sending activity recorded in the last 7 days.
                        <div style="margin-top: 10px; font-size: 0.875rem;">
                            Activity will appear here once the campaign starts sending emails.
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
    // Tab functionality
    function showTab(tabName) {
        // Hide all tabs
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Remove active class from all tab buttons
        document.querySelectorAll('.tab').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Show selected tab
        document.getElementById(tabName + 'Tab').classList.add('active');
        
        // Add active class to clicked tab button
        event.target.classList.add('active');
    }
    
    // Form validation
    document.getElementById('scheduleSettingsForm').addEventListener('submit', function(e) {
        const checkboxes = this.querySelectorAll('input[name="weekly_schedule[]"]:checked');
        if (checkboxes.length === 0) {
            e.preventDefault();
            alert('Please select at least one working day');
            return false;
        }
        
        const startTime = this.querySelector('input[name="start_time"]').value;
        const endTime = this.querySelector('input[name="end_time"]').value;
        
        if (startTime >= endTime) {
            e.preventDefault();
            alert('End time must be after start time');
            return false;
        }
    });
    
    document.getElementById('emailSettingsForm').addEventListener('submit', function(e) {
        const dailyLimit = this.querySelector('input[name="daily_limit"]').value;
        if (dailyLimit < 1 || dailyLimit > 1000) {
            e.preventDefault();
            alert('Daily limit must be between 1 and 1000');
            return false;
        }
    });
    
    // Prevent form resubmission on refresh
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
    </script>
</body>
</html>