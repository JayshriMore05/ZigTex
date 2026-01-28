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
        
        // Fetch email content from steps
        $stmt = $pdo->prepare("
            SELECT subject, email_body 
            FROM campaign_steps 
            WHERE campaign_id = ? 
            ORDER BY step_number 
            LIMIT 1
        ");
        $stmt->execute([$campaign_id]);
        $email_step = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($email_step) {
            $campaign['subject'] = $email_step['subject'] ?? 'No subject';
            $campaign['email_body'] = $email_step['email_body'] ?? 'No content';
        } else {
            $campaign['subject'] = $campaign['name'] ?? 'Campaign';
            $campaign['email_body'] = '<p>No email content available.</p>';
        }
        
        // Set default values if null
        $campaign['total_prospects'] = $campaign['total_prospects'] ?? 0;
        $campaign['emails_sent'] = $campaign['emails_sent'] ?? 0;
        $campaign['emails_delivered'] = $campaign['emails_delivered'] ?? 0;
        $campaign['emails_opened'] = $campaign['emails_opened'] ?? 0;
        $campaign['emails_clicked'] = $campaign['emails_clicked'] ?? 0;
        $campaign['emails_replied'] = $campaign['emails_replied'] ?? 0;
        $campaign['emails_bounced'] = $campaign['emails_bounced'] ?? 0;
        
        // Calculate rates
        $delivery_rate = $campaign['emails_sent'] > 0 ? 
            round(($campaign['emails_delivered'] / $campaign['emails_sent']) * 100, 2) : 0;
        $open_rate = $campaign['emails_delivered'] > 0 ? 
            round(($campaign['emails_opened'] / $campaign['emails_delivered']) * 100, 2) : 0;
        $reply_rate = $campaign['emails_sent'] > 0 ? 
            round(($campaign['emails_replied'] / $campaign['emails_sent']) * 100, 2) : 0;
        $click_rate = $campaign['emails_delivered'] > 0 ? 
            round(($campaign['emails_clicked'] / $campaign['emails_delivered']) * 100, 2) : 0;
            
        // Format dates
        $campaign['created_at_formatted'] = date('F j, Y', strtotime($campaign['created_at'] ?? 'now'));
        $campaign['updated_at_formatted'] = isset($campaign['updated_at']) ? 
            date('F j, Y', strtotime($campaign['updated_at'])) : 'Not updated yet';
            
        // Get sender info (use email_account as sender email)
        $campaign['sender_email'] = $campaign['email_account'] ?? 'Not set';
        $campaign['sender_name'] = explode('@', $campaign['sender_email'])[0] ?? 'Sender';
        
        // Get campaign description (use name if description not set)
        $campaign['description'] = $campaign['name'] . ' campaign targeting selected prospects';
        
        // Fetch recent activity - UPDATED QUERY
        $stmt = $pdo->prepare("
            SELECT 
                cp.prospect_id,
                cp.status,
                cp.updated_at as activity_time,
                CASE 
                    WHEN cp.status = 'sent' THEN 'Email sent to prospect'
                    WHEN cp.status = 'delivered' THEN 'Email delivered to prospect'
                    WHEN cp.status = 'opened' THEN 'Email opened by prospect'
                    WHEN cp.status = 'clicked' THEN 'Link clicked by prospect'
                    WHEN cp.status = 'replied' THEN 'Reply received from prospect'
                    WHEN cp.status = 'bounced' THEN 'Email bounced from prospect'
                    ELSE 'Activity from prospect'
                END as activity_text
            FROM campaign_prospects cp
            WHERE cp.campaign_id = ? 
            AND cp.status IN ('sent', 'delivered', 'opened', 'clicked', 'replied', 'bounced')
            ORDER BY cp.updated_at DESC
            LIMIT 5
        ");
        $stmt->execute([$campaign_id]);
        $recent_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
} else {
    header("Location: campaigns.php");
    exit();
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'pause':
                $stmt = $pdo->prepare("UPDATE campaigns SET status = 'paused' WHERE id = ? AND user_id = ? AND status = 'running'");
                $stmt->execute([$campaign_id, $user_id]);
                $_SESSION['success'] = 'Campaign paused successfully!';
                break;
                
            case 'resume':
                $stmt = $pdo->prepare("UPDATE campaigns SET status = 'running' WHERE id = ? AND user_id = ? AND status = 'paused'");
                $stmt->execute([$campaign_id, $user_id]);
                $_SESSION['success'] = 'Campaign resumed successfully!';
                break;
                
            case 'send_test':
                $test_email = $_POST['test_email'] ?? '';
                if (filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
                    $_SESSION['success'] = "Test email sent to $test_email";
                } else {
                    $_SESSION['error'] = 'Please enter a valid email address';
                }
                break;
                
            case 'duplicate':
                // Duplicate campaign
                $new_name = $campaign['name'] . ' (Copy)';
                $stmt = $pdo->prepare("
                    INSERT INTO campaigns (user_id, name, email_account, unsubscribe_text, email_priority, 
                                           timezone, weekly_schedule, start_time, end_time, total_prospects, 
                                           status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft', NOW())
                ");
                $stmt->execute([
                    $user_id,
                    $new_name,
                    $campaign['email_account'] ?? '',
                    $campaign['unsubscribe_text'] ?? '',
                    $campaign['email_priority'] ?? 'equally_divided',
                    $campaign['timezone'] ?? 'Europe/London',
                    $campaign['weekly_schedule'] ?? 'Mon,Tue,Wed,Thu,Fri',
                    $campaign['start_time'] ?? '09:00',
                    $campaign['end_time'] ?? '19:00',
                    $campaign['total_prospects'] ?? 0
                ]);
                
                $new_campaign_id = $pdo->lastInsertId();
                
                // Duplicate campaign steps
                $stmt = $pdo->prepare("SELECT * FROM campaign_steps WHERE campaign_id = ?");
                $stmt->execute([$campaign_id]);
                $steps = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($steps as $step) {
                    $stmt = $pdo->prepare("
                        INSERT INTO campaign_steps (campaign_id, step_number, step_type, delay_days, delay_hours, subject, email_body)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $new_campaign_id,
                        $step['step_number'],
                        $step['step_type'],
                        $step['delay_days'],
                        $step['delay_hours'],
                        $step['subject'],
                        $step['email_body']
                    ]);
                }
                
                $_SESSION['success'] = 'Campaign duplicated successfully!';
                header("Location: campaign_view.php?id=$new_campaign_id");
                exit();
                break;
        }
        
        // Refresh page
        header("Location: campaign_view.php?id=$campaign_id");
        exit();
        
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Action failed: ' . $e->getMessage();
        header("Location: campaign_view.php?id=$campaign_id");
        exit();
    }
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
    <title>View Campaign - ZigTex</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/campaign.css">
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
            margin-bottom: 30px;
        }
        
        .page-title-section h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
            margin: 0;
        }
        
        .page-description {
            color: #6b7280;
            margin-top: 8px;
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
        
        /* Status Badge */
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.75rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-running {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        
        .status-paused {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }
        
        .status-draft {
            background: #f3f4f6;
            color: #374151;
            border: 1px solid #e5e7eb;
        }
        
        .status-completed {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .status-scheduled {
            background: #ede9fe;
            color: #5b21b6;
            border: 1px solid #ddd6fe;
        }
        
        /* Campaign Info Bar */
        .campaign-info-bar {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
            border: 1px solid #e5e7eb;
        }
        
        .campaign-meta {
            display: flex;
            gap: 24px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .meta-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .meta-label {
            font-size: 0.75rem;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .meta-value {
            font-weight: 600;
            color: #1f2937;
            font-size: 0.95rem;
        }
        
        /* Stats Cards - Clean White Version */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .stat-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            position: relative;
            transition: all 0.2s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border-color: #d1d5db;
        }
        
        .stat-card-content {
            position: relative;
        }
        
        .stat-label {
            font-size: 0.75rem;
            color: #6b7280;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 4px;
            line-height: 1;
        }
        
        .stat-rate {
            font-size: 0.875rem;
            color: #6b7280;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        /* Email Preview */
        .email-preview-card {
            height: 100%;
        }
        
        .email-preview-header {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #e5e7eb;
        }
        
        .email-sender-info {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
        }
        
        .sender-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.875rem;
        }
        
        .sender-name {
            font-weight: 600;
            color: #1f2937;
            font-size: 0.95rem;
        }
        
        .sender-email {
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        .email-subject {
            color: #1f2937;
            font-weight: 500;
            font-size: 0.95rem;
        }
        
        .email-body-preview {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 25px;
            min-height: 300px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #374151;
            font-size: 0.95rem;
        }
        
        .personalization-tags {
            background: #eff6ff;
            padding: 12px 16px;
            border-radius: 8px;
            margin-top: 20px;
            border: 1px solid #dbeafe;
        }
        
        .personalization-tags small {
            color: #1d4ed8;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        /* Settings Grid */
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
        }
        
        .setting-item {
            background: #f9fafb;
            padding: 16px;
            border-radius: 8px;
            transition: all 0.2s ease;
            border: 1px solid #e5e7eb;
        }
        
        .setting-item:hover {
            background: #f3f4f6;
            transform: translateX(2px);
        }
        
        .setting-label {
            display: block;
            font-size: 0.75rem;
            color: #6b7280;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .setting-value {
            font-weight: 600;
            color: #1f2937;
            font-size: 0.95rem;
            word-break: break-all;
        }
        
        /* Quick Actions */
        .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 12px;
        }
        
        .quick-action-btn {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            color: inherit;
        }
        
        .quick-action-btn:hover {
            border-color: #6366f1;
            background: #f5f5ff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.08);
        }
        
        .quick-action-btn i {
            font-size: 1.25rem;
            color: #6366f1;
        }
        
        .quick-action-btn span {
            font-weight: 500;
            color: #1f2937;
            font-size: 0.875rem;
            text-align: center;
        }
        
        .quick-action-btn.delete {
            border-color: #fee2e2;
            color: #dc2626;
        }
        
        .quick-action-btn.delete:hover {
            border-color: #fecaca;
            background: #fef2f2;
        }
        
        /* Activity Timeline */
        .activity-timeline {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .activity-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        
        .activity-item:hover {
            background: #f9fafb;
        }
        
        .activity-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .activity-content p {
            font-weight: 500;
            color: #1f2937;
            margin-bottom: 2px;
            font-size: 0.875rem;
        }
        
        .activity-content small {
            color: #6b7280;
            font-size: 0.75rem;
        }
        
        /* Performance Chart */
        .chart-container {
            height: 200px;
            position: relative;
        }
        
        .chart-bars {
            display: flex;
            align-items: flex-end;
            justify-content: space-around;
            height: 150px;
            padding: 0 20px;
        }
        
        .bar {
            width: 40px;
            border-radius: 6px 6px 0 0;
            position: relative;
            transition: height 0.3s ease;
        }
        
        .bar-label {
            position: absolute;
            bottom: -25px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 0.75rem;
            color: #6b7280;
            white-space: nowrap;
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
        
        /* Success/Error Messages */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: fadeIn 0.3s ease;
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
        
        /* Grid Layout */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 20px;
        }
        
        .grid-col-8 {
            grid-column: span 8;
        }
        
        .grid-col-4 {
            grid-column: span 4;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 24px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .modal-header {
            margin-bottom: 20px;
        }
        
        .modal-header h3 {
            font-size: 1.125rem;
            color: #1f2937;
            font-weight: 600;
            margin: 0;
        }
        
        .modal-body {
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 16px;
        }
        
        .form-group label {
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
        
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .grid-col-8, .grid-col-4 {
                grid-column: span 12;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 640px) {
            .main-content {
                padding: 16px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }
            
            .campaign-meta {
                flex-direction: column;
                gap: 12px;
                align-items: flex-start;
            }
            
            .quick-actions-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
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
                    <h1><?php echo htmlspecialchars($campaign['name']); ?></h1>
                    <p class="page-description">Campaign dashboard and performance metrics</p>
                </div>
                <div class="header-actions">
                    <a href="campaigns.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Campaigns
                    </a>
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

            <!-- Campaign Info Bar -->
            <div class="campaign-info-bar">
                <div class="campaign-meta">
                    <div class="meta-item">
                        <span class="meta-label">Status</span>
                        <?php
                        $status_class = 'status-' . strtolower($campaign['status']);
                        ?>
                        <span class="status-badge <?php echo $status_class; ?>">
                            <i class="fas fa-circle"></i>
                            <?php echo ucfirst($campaign['status']); ?>
                        </span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Created</span>
                        <span class="meta-value"><?php echo $campaign['created_at_formatted']; ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Last Updated</span>
                        <span class="meta-value"><?php echo $campaign['updated_at_formatted']; ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Campaign ID</span>
                        <span class="meta-value">#<?php echo $campaign['id']; ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Sender</span>
                        <span class="meta-value"><?php echo htmlspecialchars($campaign['sender_email']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-content">
                        <div class="stat-label">
                            <i class="fas fa-users"></i> Total Prospects
                        </div>
                        <div class="stat-value"><?php echo $campaign['total_prospects']; ?></div>
                        <div class="stat-rate">Target audience size</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-content">
                        <div class="stat-label">
                            <i class="fas fa-paper-plane"></i> Emails Sent
                        </div>
                        <div class="stat-value"><?php echo $campaign['emails_sent']; ?></div>
                        <div class="stat-rate">Total emails delivered</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-content">
                        <div class="stat-label">
                            <i class="fas fa-check-circle"></i> Delivery Rate
                        </div>
                        <div class="stat-value"><?php echo $delivery_rate; ?>%</div>
                        <div class="stat-rate">
                            <?php echo $campaign['emails_delivered']; ?> delivered
                        </div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-content">
                        <div class="stat-label">
                            <i class="fas fa-envelope-open"></i> Open Rate
                        </div>
                        <div class="stat-value"><?php echo $open_rate; ?>%</div>
                        <div class="stat-rate">
                            <?php echo $campaign['emails_opened']; ?> opened
                        </div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-content">
                        <div class="stat-label">
                            <i class="fas fa-reply"></i> Reply Rate
                        </div>
                        <div class="stat-value"><?php echo $reply_rate; ?>%</div>
                        <div class="stat-rate">
                            <?php echo $campaign['emails_replied']; ?> replies
                        </div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-content">
                        <div class="stat-label">
                            <i class="fas fa-mouse-pointer"></i> Click Rate
                        </div>
                        <div class="stat-value"><?php echo $click_rate; ?>%</div>
                        <div class="stat-rate">
                            <?php echo $campaign['emails_clicked']; ?> clicks
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dashboard Grid -->
            <div class="dashboard-grid">
                <!-- Left Column -->
                <div class="grid-col-8">
                    <!-- Email Preview Card -->
                    <div class="card email-preview-card">
                        <div class="card-header">
                            <h3><i class="fas fa-envelope"></i> Email Preview</h3>
                            <div class="action-buttons">
                                <a href="campaign_create.php?step=1&id=<?php echo $campaign['id']; ?>" class="btn btn-secondary">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <?php if($campaign['status'] == 'running'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="pause">
                                    <button type="submit" class="btn btn-secondary">
                                        <i class="fas fa-pause"></i> Pause
                                    </button>
                                </form>
                                <?php elseif($campaign['status'] == 'paused'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="resume">
                                    <button type="submit" class="btn btn-secondary">
                                        <i class="fas fa-play"></i> Resume
                                    </button>
                                </form>
                                <?php endif; ?>
                                <button class="btn btn-primary" id="openSendTestModal">
                                    <i class="fas fa-paper-plane"></i> Send Test
                                </button>
                            </div>
                        </div>
                        <div class="email-preview-header">
                            <div class="email-sender-info">
                                <div class="sender-avatar"><?php echo strtoupper(substr($campaign['sender_name'], 0, 1)); ?></div>
                                <div>
                                    <div class="sender-name"><?php echo htmlspecialchars($campaign['sender_name']); ?></div>
                                    <div class="sender-email"><?php echo htmlspecialchars($campaign['sender_email']); ?></div>
                                </div>
                            </div>
                            <div class="email-subject">
                                <strong>Subject:</strong> <?php echo htmlspecialchars($campaign['subject']); ?>
                            </div>
                        </div>
                        <div class="email-body-preview">
                            <?php echo $campaign['email_body']; ?>
                        </div>
                        <div class="personalization-tags">
                            <small>
                                <i class="fas fa-tags"></i>
                                Personalization tags available: {first_name}, {last_name}, {company}, {job_title}, {email}
                            </small>
                        </div>
                    </div>

                    <!-- Performance Chart Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-chart-line"></i> Campaign Performance</h3>
                            <div style="font-size: 0.875rem; color: #6b7280;">
                                Real-time metrics overview
                            </div>
                        </div>
                        <div class="chart-container">
                            <div class="chart-bars">
                                <?php
                                // Calculate bar heights based on data
                                $max_value = max($campaign['emails_sent'], $campaign['emails_delivered'], 
                                                 $campaign['emails_opened'], $campaign['emails_clicked'], 
                                                 $campaign['emails_replied']);
                                
                                $sent_height = $max_value > 0 ? ($campaign['emails_sent'] / $max_value) * 80 : 0;
                                $delivered_height = $max_value > 0 ? ($campaign['emails_delivered'] / $max_value) * 80 : 0;
                                $opened_height = $max_value > 0 ? ($campaign['emails_opened'] / $max_value) * 80 : 0;
                                $clicked_height = $max_value > 0 ? ($campaign['emails_clicked'] / $max_value) * 80 : 0;
                                $replied_height = $max_value > 0 ? ($campaign['emails_replied'] / $max_value) * 80 : 0;
                                ?>
                                <div class="bar sent-bar" style="height: <?php echo $sent_height; ?>%; background: linear-gradient(180deg, #6366f1 0%, #8b5cf6 100%);">
                                    <div class="bar-label">Sent</div>
                                </div>
                                <div class="bar delivered-bar" style="height: <?php echo $delivered_height; ?>%; background: linear-gradient(180deg, #10b981 0%, #059669 100%);">
                                    <div class="bar-label">Delivered</div>
                                </div>
                                <div class="bar opened-bar" style="height: <?php echo $opened_height; ?>%; background: linear-gradient(180deg, #f59e0b 0%, #d97706 100%);">
                                    <div class="bar-label">Opened</div>
                                </div>
                                <div class="bar clicked-bar" style="height: <?php echo $clicked_height; ?>%; background: linear-gradient(180deg, #ef4444 0%, #dc2626 100%);">
                                    <div class="bar-label">Clicked</div>
                                </div>
                                <div class="bar replied-bar" style="height: <?php echo $replied_height; ?>%; background: linear-gradient(180deg, #8b5cf6 0%, #7c3aed 100%);">
                                    <div class="bar-label">Replied</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="grid-col-4">
                    <!-- Settings Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-cog"></i> Campaign Settings</h3>
                        </div>
                        <div class="settings-grid">
                            <div class="setting-item">
                                <span class="setting-label">Timezone</span>
                                <span class="setting-value"><?php echo htmlspecialchars($campaign['timezone'] ?? 'Not set'); ?></span>
                            </div>
                            <div class="setting-item">
                                <span class="setting-label">Schedule Days</span>
                                <span class="setting-value"><?php echo htmlspecialchars($campaign['weekly_schedule'] ?? 'Mon-Fri'); ?></span>
                            </div>
                            <div class="setting-item">
                                <span class="setting-label">Daily Hours</span>
                                <span class="setting-value"><?php echo htmlspecialchars($campaign['start_time'] ?? '09:00'); ?> - <?php echo htmlspecialchars($campaign['end_time'] ?? '19:00'); ?></span>
                            </div>
                            <div class="setting-item">
                                <span class="setting-label">Email Priority</span>
                                <span class="setting-value"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $campaign['email_priority'] ?? 'equally_divided'))); ?></span>
                            </div>
                            <div class="setting-item">
                                <span class="setting-label">Unsubscribe Text</span>
                                <span class="setting-value"><?php echo htmlspecialchars(substr($campaign['unsubscribe_text'] ?? 'Not set', 0, 30)) . (strlen($campaign['unsubscribe_text'] ?? '') > 30 ? '...' : ''); ?></span>
                            </div>
                            <div class="setting-item">
                                <span class="setting-label">Total Prospects</span>
                                <span class="setting-value"><?php echo $campaign['total_prospects']; ?> contacts</span>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                        </div>
                        <div class="quick-actions-grid">
                            <a href="campaign_prospects.php?id=<?php echo $campaign['id']; ?>" class="quick-action-btn">
                                <i class="fas fa-user-plus"></i>
                                <span>Manage Prospects</span>
                            </a>
                            <form method="POST" style="display: contents;">
                                <input type="hidden" name="action" value="duplicate">
                                <button type="submit" class="quick-action-btn" onclick="return confirm('Duplicate this campaign?')">
                                    <i class="fas fa-copy"></i>
                                    <span>Duplicate</span>
                                </button>
                            </form>
                            <a href="campaign_export.php?id=<?php echo $campaign['id']; ?>" class="quick-action-btn">
                                <i class="fas fa-download"></i>
                                <span>Export Data</span>
                            </a>
                            <a href="campaign_schedule.php?id=<?php echo $campaign['id']; ?>" class="quick-action-btn">
                                <i class="fas fa-calendar-alt"></i>
                                <span>Schedule</span>
                            </a>
                            <a href="campaign_reports.php?id=<?php echo $campaign['id']; ?>" class="quick-action-btn">
                                <i class="fas fa-chart-bar"></i>
                                <span>Reports</span>
                            </a>
                            <a href="campaign_delete.php?id=<?php echo $campaign['id']; ?>" class="quick-action-btn delete" onclick="return confirm('Are you sure you want to delete this campaign? This action cannot be undone.')">
                                <i class="fas fa-trash"></i>
                                <span>Delete</span>
                            </a>
                        </div>
                    </div>

                    <!-- Recent Activity Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-history"></i> Recent Activity</h3>
                        </div>
                        <div class="activity-timeline">
                            <?php if (!empty($recent_activity)): ?>
                                <?php foreach ($recent_activity as $activity): ?>
                                <div class="activity-item">
                                    <?php
                                    $icon_color = '#6b7280';
                                    $icon = 'fas fa-circle';
                                    switch($activity['status']) {
                                        case 'sent': $icon = 'fas fa-paper-plane'; $icon_color = '#6366f1'; break;
                                        case 'delivered': $icon = 'fas fa-check-circle'; $icon_color = '#10b981'; break;
                                        case 'opened': $icon = 'fas fa-envelope-open'; $icon_color = '#f59e0b'; break;
                                        case 'clicked': $icon = 'fas fa-mouse-pointer'; $icon_color = '#ef4444'; break;
                                        case 'replied': $icon = 'fas fa-reply'; $icon_color = '#8b5cf6'; break;
                                        case 'bounced': $icon = 'fas fa-exclamation-circle'; $icon_color = '#dc2626'; break;
                                    }
                                    ?>
                                    <div class="activity-icon">
                                        <i class="<?php echo $icon; ?>" style="color: <?php echo $icon_color; ?>;"></i>
                                    </div>
                                    <div class="activity-content">
                                        <p><?php echo $activity['activity_text']; ?> #<?php echo htmlspecialchars($activity['prospect_id']); ?></p>
                                        <small><?php echo date('M j, g:i A', strtotime($activity['activity_time'])); ?></small>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas fa-info-circle" style="color: #6b7280;"></i>
                                    </div>
                                    <div class="activity-content">
                                        <p>No recent activity</p>
                                        <small>Activity will appear here once campaign starts</small>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Send Test Email Modal -->
    <div class="modal" id="sendTestModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Send Test Email</h3>
            </div>
            <form method="POST" id="sendTestForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="send_test">
                    <div class="form-group">
                        <label for="test_email">Email Address</label>
                        <input type="email" id="test_email" name="test_email" required 
                               placeholder="Enter email to send test" 
                               class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Send Test</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    // Modal functions
    function openSendTestModal() {
        document.getElementById('sendTestModal').style.display = 'flex';
    }
    
    function closeModal() {
        document.getElementById('sendTestModal').style.display = 'none';
    }
    
    // Event listeners
    document.addEventListener('DOMContentLoaded', function() {
        // Open send test modal
        document.getElementById('openSendTestModal').addEventListener('click', openSendTestModal);
        
        // Close modal when clicking outside
        document.getElementById('sendTestModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
        
        // Form submission
        document.getElementById('sendTestForm').addEventListener('submit', function(e) {
            const email = document.getElementById('test_email').value;
            if (!email || !email.includes('@')) {
                e.preventDefault();
                alert('Please enter a valid email address');
            }
        });
        
        // Escape key closes modal
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    });
    
    // Prevent form resubmission on refresh
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
    </script>
</body>
</html>