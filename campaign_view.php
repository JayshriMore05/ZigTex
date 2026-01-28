<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$campaign_id = isset($_GET['id']) ? intval($_GET['id']) : 1;

// Sample campaign data - In real app, fetch from database
$campaign = [
    'id' => $campaign_id,
    'name' => 'Q1 Tech Outreach',
    'description' => 'Outreach campaign targeting technology companies for Q1 2024',
    'status' => 'running',
    'sender_name' => 'Arpan from ZigTex',
    'sender_email' => 'arpan@zigtex.com',
    'subject' => 'AI Automation Tools for Your Business',
    'created_at' => '2024-01-15 10:30:00',
    'started_at' => '2024-01-15 14:00:00',
    'total_prospects' => 127,
    'emails_sent' => 37,
    'emails_delivered' => 36,
    'emails_opened' => 15,
    'emails_clicked' => 8,
    'emails_replied' => 2,
    'emails_bounced' => 1,
    'email_body' => '<p>Hi {first_name},</p><p>I noticed your work at {company} and was impressed with what you\'re doing in the tech space.</p><p>We\'ve helped companies like yours automate their outreach with our AI tools, resulting in 3x more replies.</p><p>Would you be open to a quick 15-minute chat next week?</p><p>Best regards,<br>Arpan</p>'
];

// Calculate rates
$delivery_rate = $campaign['total_prospects'] > 0 ? 
    round(($campaign['emails_delivered'] / $campaign['emails_sent']) * 100, 2) : 0;
$open_rate = $campaign['emails_delivered'] > 0 ? 
    round(($campaign['emails_opened'] / $campaign['emails_delivered']) * 100, 2) : 0;
$reply_rate = $campaign['emails_sent'] > 0 ? 
    round(($campaign['emails_replied'] / $campaign['emails_sent']) * 100, 2) : 0;
$click_rate = $campaign['emails_delivered'] > 0 ? 
    round(($campaign['emails_clicked'] / $campaign['emails_delivered']) * 100, 2) : 0;
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
        /* Enhanced Card Styles */
        .card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin-bottom: 24px;
        }
        
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 2px solid #f3f4f6;
        }
        
        .card-header h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card-header h3 i {
            color: #6366f1;
        }
        
        /* Enhanced Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 16px;
            padding: 24px;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:nth-child(2) {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .stat-card:nth-child(3) {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        .stat-card:nth-child(4) {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }
        
        .stat-card:nth-child(5) {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }
        
        .stat-card:nth-child(6) {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: rgba(255, 255, 255, 0.1);
            transform: rotate(30deg);
        }
        
        .stat-card-content {
            position: relative;
            z-index: 1;
        }
        
        .stat-label {
            font-size: 0.875rem;
            opacity: 0.9;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 4px;
        }
        
        .stat-rate {
            font-size: 1rem;
            opacity: 0.9;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        /* Campaign Header */
        .campaign-header-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .campaign-info {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        
        .campaign-title-section h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .campaign-description {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 20px;
        }
        
        .campaign-meta {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .status-badge {
            padding: 6px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.875rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .status-running {
            background: rgba(34, 197, 94, 0.2);
            color: #22c55e;
            border: 1px solid rgba(34, 197, 94, 0.3);
        }
        
        .campaign-actions {
            display: flex;
            gap: 12px;
        }
        
        .action-btn {
            padding: 10px 20px;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .action-btn.primary {
            background: white;
            color: #6366f1;
        }
        
        .action-btn.secondary {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            backdrop-filter: blur(10px);
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        /* Email Preview Card */
        .email-preview-card {
            height: 100%;
        }
        
        .email-preview-header {
            background: #f8fafc;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
        
        .email-body-preview {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 25px;
            min-height: 300px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
        }
        
        /* Settings Card */
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .setting-item {
            background: #f8fafc;
            padding: 15px;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .setting-item:hover {
            background: #f1f5f9;
            transform: translateX(5px);
        }
        
        .setting-label {
            display: block;
            font-size: 0.875rem;
            color: #64748b;
            margin-bottom: 5px;
        }
        
        .setting-value {
            font-weight: 600;
            color: #1f2937;
        }
        
        /* Quick Actions */
        .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }
        
        .quick-action-btn {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .quick-action-btn:hover {
            border-color: #6366f1;
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.1);
        }
        
        .quick-action-btn i {
            font-size: 1.5rem;
            color: #6366f1;
        }
        
        .quick-action-btn span {
            font-weight: 600;
            color: #1f2937;
        }
        
        .quick-action-btn.delete {
            border-color: #fecaca;
        }
        
        .quick-action-btn.delete:hover {
            border-color: #ef4444;
            background: #fef2f2;
        }
        
        /* Activity Timeline */
        .activity-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            padding: 15px;
            border-radius: 12px;
            transition: all 0.3s ease;
            margin-bottom: 10px;
        }
        
        .activity-item:hover {
            background: #f8fafc;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .activity-content p {
            font-weight: 500;
            margin-bottom: 4px;
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
            border-radius: 8px 8px 0 0;
            position: relative;
            transition: height 0.3s ease;
        }
        
        .bar-label {
            position: absolute;
            bottom: -25px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 0.875rem;
            color: #64748b;
            white-space: nowrap;
        }
        
        /* Grid Layout */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 24px;
        }
        
        .grid-col-8 {
            grid-column: span 8;
        }
        
        .grid-col-4 {
            grid-column: span 4;
        }
        
        @media (max-width: 1024px) {
            .grid-col-8, .grid-col-4 {
                grid-column: span 12;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 640px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .campaign-info {
                flex-direction: column;
                gap: 20px;
            }
            
            .campaign-actions {
                width: 100%;
                flex-wrap: wrap;
            }
            
            .action-btn {
                flex: 1;
                min-width: 120px;
                justify-content: center;
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
            <!-- Enhanced Campaign Header -->
            <div class="campaign-header-card">
                <div class="campaign-info">
                    <div class="campaign-title-section">
                        <h1><?php echo htmlspecialchars($campaign['name']); ?></h1>
                        <p class="campaign-description"><?php echo htmlspecialchars($campaign['description']); ?></p>
                        <div class="campaign-meta">
                            <span class="status-badge status-running">
                                <i class="fas fa-circle"></i>
                                <?php echo ucfirst($campaign['status']); ?>
                            </span>
                            <span>
                                <i class="fas fa-calendar-alt"></i>
                                Created: <?php echo date('F j, Y', strtotime($campaign['created_at'])); ?>
                            </span>
                            <span>
                                <i class="fas fa-hashtag"></i>
                                ID: #<?php echo $campaign['id']; ?>
                            </span>
                        </div>
                    </div>
                    <div class="campaign-actions">
                        <a href="campaign_edit.php?id=<?php echo $campaign['id']; ?>" class="action-btn primary">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <?php if($campaign['status'] == 'running'): ?>
                        <button class="action-btn secondary" id="pauseCampaign">
                            <i class="fas fa-pause"></i> Pause
                        </button>
                        <?php elseif($campaign['status'] == 'paused'): ?>
                        <button class="action-btn secondary" id="resumeCampaign">
                            <i class="fas fa-play"></i> Resume
                        </button>
                        <?php endif; ?>
                        <a href="campaign.php" class="action-btn secondary">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stats Overview Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-content">
                        <div class="stat-label">Total Prospects</div>
                        <div class="stat-value"><?php echo $campaign['total_prospects']; ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-content">
                        <div class="stat-label">Emails Sent</div>
                        <div class="stat-value"><?php echo $campaign['emails_sent']; ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-content">
                        <div class="stat-label">Delivery Rate</div>
                        <div class="stat-value"><?php echo $delivery_rate; ?>%</div>
                        <div class="stat-rate">
                            <?php echo $campaign['emails_delivered']; ?> delivered
                        </div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-content">
                        <div class="stat-label">Open Rate</div>
                        <div class="stat-value"><?php echo $open_rate; ?>%</div>
                        <div class="stat-rate">
                            <?php echo $campaign['emails_opened']; ?> opened
                        </div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-content">
                        <div class="stat-label">Reply Rate</div>
                        <div class="stat-value"><?php echo $reply_rate; ?>%</div>
                        <div class="stat-rate">
                            <?php echo $campaign['emails_replied']; ?> replies
                        </div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-content">
                        <div class="stat-label">Click Rate</div>
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
                            <h3><i class="fas fa-envelope-open-text"></i> Email Preview</h3>
                            <button class="action-btn secondary" id="sendTest">
                                <i class="fas fa-paper-plane"></i> Send Test
                            </button>
                        </div>
                        <div class="email-preview-header">
                            <div class="email-sender-info">
                                <div class="sender-avatar">A</div>
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
                        <div class="personalization-tags mt-4 p-3 bg-blue-50 rounded-lg">
                            <small class="text-blue-600">
                                <i class="fas fa-tags"></i>
                                Personalization tags available: {first_name}, {last_name}, {company}, {job_title}
                            </small>
                        </div>
                    </div>

                    <!-- Performance Chart Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-chart-line"></i> Campaign Performance</h3>
                            <select class="action-btn secondary" style="padding: 8px 16px;">
                                <option>Last 7 days</option>
                                <option>Last 30 days</option>
                                <option>All time</option>
                            </select>
                        </div>
                        <div class="chart-container">
                            <div class="chart-bars">
                                <div class="bar sent-bar" style="height: 80%; background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);">
                                    <div class="bar-label">Sent</div>
                                </div>
                                <div class="bar delivered-bar" style="height: 75%; background: linear-gradient(180deg, #f093fb 0%, #f5576c 100%);">
                                    <div class="bar-label">Delivered</div>
                                </div>
                                <div class="bar opened-bar" style="height: 40%; background: linear-gradient(180deg, #4facfe 0%, #00f2fe 100%);">
                                    <div class="bar-label">Opened</div>
                                </div>
                                <div class="bar clicked-bar" style="height: 25%; background: linear-gradient(180deg, #43e97b 0%, #38f9d7 100%);">
                                    <div class="bar-label">Clicked</div>
                                </div>
                                <div class="bar replied-bar" style="height: 10%; background: linear-gradient(180deg, #fa709a 0%, #fee140 100%);">
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
                            <h3><i class="fas fa-cogs"></i> Campaign Settings</h3>
                        </div>
                        <div class="settings-grid">
                            <div class="setting-item">
                                <span class="setting-label">Sender Name</span>
                                <span class="setting-value"><?php echo htmlspecialchars($campaign['sender_name']); ?></span>
                            </div>
                            <div class="setting-item">
                                <span class="setting-label">Sender Email</span>
                                <span class="setting-value"><?php echo htmlspecialchars($campaign['sender_email']); ?></span>
                            </div>
                            <div class="setting-item">
                                <span class="setting-label">Daily Limit</span>
                                <span class="setting-value">100 emails/day</span>
                            </div>
                            <div class="setting-item">
                                <span class="setting-label">Follow-ups</span>
                                <span class="setting-value">3 automated</span>
                            </div>
                            <div class="setting-item">
                                <span class="setting-label">Stop on Reply</span>
                                <span class="setting-value">Enabled</span>
                            </div>
                            <div class="setting-item">
                                <span class="setting-label">Timezone</span>
                                <span class="setting-value">EST</span>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                        </div>
                        <div class="quick-actions-grid">
                            <button class="quick-action-btn" id="addProspects">
                                <i class="fas fa-user-plus"></i>
                                <span>Add Prospects</span>
                            </button>
                            <button class="quick-action-btn" id="duplicateCampaign">
                                <i class="fas fa-copy"></i>
                                <span>Duplicate</span>
                            </button>
                            <button class="quick-action-btn" id="exportData">
                                <i class="fas fa-download"></i>
                                <span>Export Data</span>
                            </button>
                            <button class="quick-action-btn" id="scheduleCampaign">
                                <i class="fas fa-calendar-alt"></i>
                                <span>Schedule</span>
                            </button>
                            <button class="quick-action-btn" id="viewReports">
                                <i class="fas fa-chart-bar"></i>
                                <span>Reports</span>
                            </button>
                            <a href="campaign_delete.php?id=<?php echo $campaign['id']; ?>" class="quick-action-btn delete">
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
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <i class="fas fa-play-circle" style="color: #10b981;"></i>
                                </div>
                                <div class="activity-content">
                                    <p>Campaign started sending</p>
                                    <small>Today, 2:30 PM</small>
                                </div>
                            </div>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <i class="fas fa-reply" style="color: #3b82f6;"></i>
                                </div>
                                <div class="activity-content">
                                    <p>New reply from john@example.com</p>
                                    <small>Today, 1:45 PM</small>
                                </div>
                            </div>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <i class="fas fa-envelope-open" style="color: #8b5cf6;"></i>
                                </div>
                                <div class="activity-content">
                                    <p>25 emails opened</p>
                                    <small>Today, 12:30 PM</small>
                                </div>
                            </div>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <i class="fas fa-user-plus" style="color: #f59e0b;"></i>
                                </div>
                                <div class="activity-content">
                                    <p>50 new prospects added</p>
                                    <small>Yesterday, 4:20 PM</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="js/campaign_view.js"></script>
    <script src="js/script.js"></script>
</body>
</html>
