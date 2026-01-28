<?php
session_start();
require_once 'config/db.php';

// Debug: Log everything
error_log("=== CAMPAIGN_SAVE.PHP STARTED ===");
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Session User ID: " . ($_SESSION['user_id'] ?? 'NOT SET'));

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    error_log("User not logged in, redirecting...");
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

// Get database connection
try {
    $db = db();
    error_log("Database connection successful");
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("Processing POST request");
    
    try {
        // Get step from POST data
        $step = isset($_POST['step']) ? (int)$_POST['step'] : 1;
        $campaignId = isset($_POST['campaign_id']) ? (int)$_POST['campaign_id'] : 0;
        $isEdit = $campaignId > 0;
        
        // Initialize campaign data in session if not exists
        if (!isset($_SESSION['campaign_data'])) {
            $_SESSION['campaign_data'] = [
                'campaign_name' => isset($_POST['campaign_name']) ? trim($_POST['campaign_name']) : 'Campaign ' . date('Y-m-d'),
                'step1' => [],
                'step2' => [],
                'step3' => [],
                'steps' => []
            ];
        }
        
        // Process data based on current step
        switch ($step) {
            case 1:
                // Save step 1: Channel Setup
                $emailAccount = trim($_POST['email_account'] ?? '');
                $unsubscribeText = trim($_POST['unsubscribe_text'] ?? '');
                $emailPriority = trim($_POST['email_priority'] ?? 'equally_divided');
                
                // Validate step 1
                if (empty($emailAccount)) {
                    echo json_encode(['success' => false, 'message' => 'Please select an email account']);
                    exit();
                }
                
                if (empty($unsubscribeText)) {
                    echo json_encode(['success' => false, 'message' => 'Unsubscribe text is required']);
                    exit();
                }
                
                $step1Data = [
                    'email_account' => $emailAccount,
                    'unsubscribe_text' => $unsubscribeText,
                    'email_priority' => $emailPriority
                ];
                
                $_SESSION['campaign_data']['step1'] = $step1Data;
                error_log("Step 1 data saved: " . json_encode($step1Data));
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Step 1 saved successfully',
                    'next_step' => 2
                ]);
                break;
                
            case 2:
                // Save step 2: Campaign Settings
                $timezone = trim($_POST['timezone'] ?? 'Europe/London');
                $weeklySchedule = isset($_POST['weekly_schedule']) ? (array)$_POST['weekly_schedule'] : [];
                $startTime = trim($_POST['start_time'] ?? '09:00');
                $endTime = trim($_POST['end_time'] ?? '19:00');
                
                // Validate step 2
                if (empty($weeklySchedule)) {
                    echo json_encode(['success' => false, 'message' => 'Please select at least one day for the weekly schedule']);
                    exit();
                }
                
                $step2Data = [
                    'timezone' => $timezone,
                    'weekly_schedule' => $weeklySchedule,
                    'start_time' => $startTime,
                    'end_time' => $endTime
                ];
                
                $_SESSION['campaign_data']['step2'] = $step2Data;
                error_log("Step 2 data saved: " . json_encode($step2Data));
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Step 2 saved successfully',
                    'next_step' => 3
                ]);
                break;
                
            case 3:
                // Save step 3: Prospect Import
                // For demo, we'll simulate a CSV upload with 3 prospects
                $step3Data = [
                    'csv_uploaded' => true,
                    'prospect_count' => 3, // Mock count for demo
                    'file_name' => isset($_FILES['csv_file']) ? $_FILES['csv_file']['name'] : 'Sample_Prospect.csv'
                ];
                
                $_SESSION['campaign_data']['step3'] = $step3Data;
                error_log("Step 3 data saved");
                
                echo json_encode([
                    'success' => true,
                    'message' => 'CSV file uploaded successfully. 3 prospects found.',
                    'next_step' => 4
                ]);
                break;
                
            case 4:
                // Save step 4: Content
                $stepNumber = isset($_POST['step_number']) ? (int)$_POST['step_number'] : 1;
                $subject = trim($_POST['subject'] ?? '');
                $message = trim($_POST['message'] ?? '');
                $delayDays = isset($_POST['delay_days']) ? (int)$_POST['delay_days'] : 0;
                $delayHours = isset($_POST['delay_hours']) ? (int)$_POST['delay_hours'] : 0;
                
                // Validate step 4
                if (empty($subject)) {
                    echo json_encode(['success' => false, 'message' => 'Email subject is required']);
                    exit();
                }
                
                if (empty($message)) {
                    echo json_encode(['success' => false, 'message' => 'Email message is required']);
                    exit();
                }
                
                $step4Data = [
                    'type' => $stepNumber == 1 ? 'initial' : 'follow_up',
                    'delay_days' => $delayDays,
                    'delay_hours' => $delayHours,
                    'subject' => $subject,
                    'message' => $message
                ];
                
                $_SESSION['campaign_data']['steps'][$stepNumber] = $step4Data;
                error_log("Step 4 data saved for step {$stepNumber}");
                
                // Check if this is the last step or if we should add another
                $action = $_POST['action'] ?? 'save';
                if ($action === 'add_step') {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Step saved. Adding new step...',
                        'next_step' => 4,
                        'new_step_number' => $stepNumber + 1
                    ]);
                } else {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Step 4 saved successfully',
                        'next_step' => 5
                    ]);
                }
                break;
                
            case 5:
                // Save and launch campaign
                $action = $_POST['action'] ?? 'save'; // 'save' or 'launch'
                
                // Use the saveCampaign function to handle the database save
                $status = $action === 'launch' ? 'active' : 'draft';
                $success = saveCampaign($status, $db);
                
                if ($success) {
                    $campaignId = $_SESSION['campaign_data']['campaign_id'] ?? 0;
                    
                    echo json_encode([
                        'success' => true,
                        'message' => $action === 'launch' 
                            ? "Campaign launched successfully! Ready to send emails."
                            : "Campaign saved successfully!",
                        'campaign_id' => $campaignId,
                        'redirect' => 'campaigns.php'
                    ]);
                    error_log("=== CAMPAIGN SAVE COMPLETED SUCCESSFULLY ===");
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => isset($_SESSION['error']) ? $_SESSION['error'] : 'Failed to save campaign'
                    ]);
                }
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid step']);
                exit();
        }
        
    } catch (Exception $e) {
        error_log("Campaign save error: " . $e->getMessage());
        error_log("Error trace: " . $e->getTraceAsString());
        echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
    }
} else {
    error_log("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

/**
 * Save campaign to database - Updated function that works with your database schema
 */
function saveCampaign($status = 'draft', $db) {
    global $user_id, $campaign_id, $is_edit;
    
    if (!isset($_SESSION['campaign_data'])) {
        return false;
    }
    
    try {
        // Get user_id from session
        $user_id = $_SESSION['user_id'];
        
        // Get data from session
        $data = $_SESSION['campaign_data'];
        $step1 = $data['step1'] ?? [];
        $step2 = $data['step2'] ?? [];
        $step3 = $data['step3'] ?? [];
        $steps = $data['steps'] ?? [];
        
        // Check if we're editing an existing campaign
        $campaign_id = isset($_SESSION['campaign_data']['campaign_id']) ? $_SESSION['campaign_data']['campaign_id'] : 0;
        $is_edit = $campaign_id > 0;
        
        // Validate required fields for active campaigns
        if ($status === 'active') {
            if (empty($step1['email_account'])) {
                $_SESSION['error'] = 'Please select an email account to launch campaign';
                return false;
            }
            if (empty($steps[1]['subject']) || empty($steps[1]['message'])) {
                $_SESSION['error'] = 'Please add at least one email step to launch campaign';
                return false;
            }
        }
        
        // Get sender email and name
        $senderEmail = $step1['email_account'] ?? '';
        $senderName = 'Campaign Sender'; // You might want to get this from user settings
        
        // Prepare campaign data for your database schema
        $campaign_data = [
            'user_id' => $user_id,
            'name' => $data['campaign_name'] ?? 'Unnamed Campaign',
            'description' => '', // Add description field if needed
            'status' => $status === 'active' ? 'running' : 'draft',
            'sender_name' => $senderName,
            'sender_email' => $senderEmail,
            'subject' => $steps[1]['subject'] ?? 'No Subject',
            'email_body' => $steps[1]['message'] ?? '',
            'email_body_plain' => strip_tags($steps[1]['message'] ?? ''),
            'schedule_type' => 'immediate', // Default
            'scheduled_at' => null,
            'enable_followup' => count($steps) > 1 ? 1 : 0,
            'followup_delay_days' => $steps[2]['delay_days'] ?? 3,
            'max_followups' => max(0, count($steps) - 1),
            'stop_on_reply' => 1,
            'track_opens' => 1,
            'track_clicks' => 1,
            'track_replies' => 1,
            'total_prospects' => $step3['prospect_count'] ?? 0,
            'emails_sent' => 0,
            'emails_delivered' => 0,
            'emails_opened' => 0,
            'emails_clicked' => 0,
            'emails_replied' => 0,
            'emails_bounced' => 0,
            'emails_unsubscribed' => 0
        ];
        
        // Add custom schedule data (these columns might not exist in your campaigns table)
        // If they don't exist, you'll need to create a separate table or modify your schema
        $schedule_data = [
            'email_account' => $step1['email_account'] ?? '',
            'unsubscribe_text' => $step1['unsubscribe_text'] ?? '',
            'email_priority' => $step1['email_priority'] ?? 'equally_divided',
            'timezone' => $step2['timezone'] ?? 'UTC',
            'weekly_schedule' => is_array($step2['weekly_schedule'] ?? []) ? 
                implode(',', $step2['weekly_schedule']) : 
                ($step2['weekly_schedule'] ?? 'Mon,Tue,Wed,Thu,Fri'),
            'start_time' => $step2['start_time'] ?? '09:00:00',
            'end_time' => $step2['end_time'] ?? '17:00:00'
        ];
        
        // Start transaction
        $db->beginTransaction();
        error_log("Transaction started for campaign save");
        
        if ($is_edit && $campaign_id > 0) {
            // Update existing campaign
            $sql = "UPDATE campaigns SET 
                    name = :name,
                    description = :description,
                    status = :status,
                    sender_name = :sender_name,
                    sender_email = :sender_email,
                    subject = :subject,
                    email_body = :email_body,
                    email_body_plain = :email_body_plain,
                    enable_followup = :enable_followup,
                    followup_delay_days = :followup_delay_days,
                    max_followups = :max_followups,
                    total_prospects = :total_prospects,
                    emails_sent = :emails_sent,
                    emails_delivered = :emails_delivered,
                    emails_opened = :emails_opened,
                    emails_clicked = :emails_clicked,
                    emails_replied = :emails_replied,
                    emails_bounced = :emails_bounced,
                    emails_unsubscribed = :emails_unsubscribed,
                    updated_at = NOW()
                    WHERE id = :id AND user_id = :user_id";
                    
            $stmt = $db->prepare($sql);
            $campaign_data['id'] = $campaign_id;
            $success = $stmt->execute($campaign_data);
            error_log("Campaign #{$campaign_id} updated: " . ($success ? 'success' : 'failed'));
            
            if ($success && !empty($steps)) {
                // Delete existing steps
                $db->prepare("DELETE FROM campaign_steps WHERE campaign_id = ?")->execute([$campaign_id]);
                error_log("Existing steps deleted for campaign #{$campaign_id}");
                
                // Insert new steps
                foreach ($steps as $step_num => $step_data) {
                    $step_type = ($step_num == 1) ? 'initial' : 'follow_up';
                    $stmt = $db->prepare("INSERT INTO campaign_steps 
                                        (campaign_id, step_number, step_type, delay_days, delay_hours, subject, email_body)
                                        VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $campaign_id,
                        $step_num,
                        $step_type,
                        $step_data['delay_days'] ?? 0,
                        $step_data['delay_hours'] ?? 0,
                        $step_data['subject'] ?? '',
                        $step_data['message'] ?? ''
                    ]);
                    error_log("Step {$step_num} inserted for campaign #{$campaign_id}");
                }
            }
            
        } else {
            // Insert new campaign
            $sql = "INSERT INTO campaigns (
                    user_id, name, description, status,
                    sender_name, sender_email, subject, email_body, email_body_plain,
                    schedule_type, scheduled_at,
                    enable_followup, followup_delay_days, max_followups,
                    stop_on_reply, track_opens, track_clicks, track_replies,
                    total_prospects, emails_sent, emails_delivered, emails_opened,
                    emails_clicked, emails_replied, emails_bounced, emails_unsubscribed,
                    created_at, updated_at
                ) VALUES (
                    :user_id, :name, :description, :status,
                    :sender_name, :sender_email, :subject, :email_body, :email_body_plain,
                    :schedule_type, :scheduled_at,
                    :enable_followup, :followup_delay_days, :max_followups,
                    :stop_on_reply, :track_opens, :track_clicks, :track_replies,
                    :total_prospects, :emails_sent, :emails_delivered, :emails_opened,
                    :emails_clicked, :emails_replied, :emails_bounced, :emails_unsubscribed,
                    NOW(), NOW()
                )";
                
            $stmt = $db->prepare($sql);
            $success = $stmt->execute($campaign_data);
            
            if ($success) {
                $campaign_id = $db->lastInsertId();
                $_SESSION['campaign_data']['campaign_id'] = $campaign_id;
                error_log("New campaign created with ID: " . $campaign_id);
                
                // Insert steps
                if (!empty($steps)) {
                    foreach ($steps as $step_num => $step_data) {
                        $step_type = ($step_num == 1) ? 'initial' : 'follow_up';
                        $stmt = $db->prepare("INSERT INTO campaign_steps 
                                            (campaign_id, step_number, step_type, delay_days, delay_hours, subject, email_body)
                                            VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([
                            $campaign_id,
                            $step_num,
                            $step_type,
                            $step_data['delay_days'] ?? 0,
                            $step_data['delay_hours'] ?? 0,
                            $step_data['subject'] ?? '',
                            $step_data['message'] ?? ''
                        ]);
                        error_log("Step {$step_num} inserted for campaign #{$campaign_id}");
                    }
                }
                
                // Save schedule data to a separate table (if you need to store it)
                saveCampaignSchedule($campaign_id, $schedule_data, $db);
            }
        }
        
        // If launching campaign, create email queue
        if ($status === 'active') {
            createEmailQueue($campaign_id, $db, $user_id, $step3['prospect_count'] ?? 0, 
                           $steps[1]['subject'] ?? '', $steps[1]['message'] ?? '');
            
            // Set launched_at timestamp
            $updateLaunchStmt = $db->prepare("UPDATE campaigns SET launched_at = NOW(), status = 'running' WHERE id = ?");
            $updateLaunchStmt->execute([$campaign_id]);
            error_log("Campaign #{$campaign_id} marked as launched");
        }
        
        // Commit transaction
        $db->commit();
        error_log("Transaction committed successfully");
        
        // Clear session data
        unset($_SESSION['campaign_data']);
        
        // Log activity
        $actionType = $status === 'active' ? 'launched' : ($is_edit ? 'updated' : 'created');
        logActivity("Campaign #{$campaign_id} '{$campaign_data['name']}' {$actionType}", $user_id, $db);
        
        return true;
        
    } catch (Exception $e) {
        // Rollback on error
        if ($db->inTransaction()) {
            $db->rollBack();
            error_log("Error occurred, transaction rolled back");
        }
        
        error_log("Campaign save error in saveCampaign(): " . $e->getMessage());
        error_log("Error trace: " . $e->getTraceAsString());
        $_SESSION['error'] = 'Failed to save campaign: ' . $e->getMessage();
        return false;
    }
}

/**
 * Save campaign schedule data to a separate table
 * (Create this table if it doesn't exist)
 */
function saveCampaignSchedule($campaign_id, $schedule_data, $db) {
    try {
        // Create campaign_settings table if it doesn't exist
        $createTableSQL = "
            CREATE TABLE IF NOT EXISTS campaign_settings (
                id INT PRIMARY KEY AUTO_INCREMENT,
                campaign_id INT NOT NULL UNIQUE,
                email_account VARCHAR(255),
                unsubscribe_text TEXT,
                email_priority VARCHAR(50) DEFAULT 'equally_divided',
                timezone VARCHAR(50) DEFAULT 'UTC',
                weekly_schedule VARCHAR(255),
                start_time VARCHAR(10),
                end_time VARCHAR(10),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
                INDEX idx_campaign (campaign_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        $db->exec($createTableSQL);
        
        // Insert or update schedule data
        $stmt = $db->prepare("
            INSERT INTO campaign_settings 
            (campaign_id, email_account, unsubscribe_text, email_priority, timezone, weekly_schedule, start_time, end_time)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            email_account = VALUES(email_account),
            unsubscribe_text = VALUES(unsubscribe_text),
            email_priority = VALUES(email_priority),
            timezone = VALUES(timezone),
            weekly_schedule = VALUES(weekly_schedule),
            start_time = VALUES(start_time),
            end_time = VALUES(end_time),
            updated_at = NOW()
        ");
        
        $stmt->execute([
            $campaign_id,
            $schedule_data['email_account'],
            $schedule_data['unsubscribe_text'],
            $schedule_data['email_priority'],
            $schedule_data['timezone'],
            $schedule_data['weekly_schedule'],
            $schedule_data['start_time'],
            $schedule_data['end_time']
        ]);
        
        error_log("Schedule data saved for campaign #{$campaign_id}");
        return true;
        
    } catch (Exception $e) {
        error_log("Failed to save campaign schedule: " . $e->getMessage());
        return false;
    }
}

/**
 * Create email queue for campaign
 */
function createEmailQueue($campaignId, $db, $userId, $prospectCount, $subject, $body) {
    error_log("Creating email queue for campaign #{$campaignId}");
    
    // For demo, create mock queue entries (limited to 10 for demo)
    $mockEmails = min($prospectCount, 10);
    
    // Check if email_queue table exists, create if not
    $createQueueSQL = "
        CREATE TABLE IF NOT EXISTS email_queue (
            id INT PRIMARY KEY AUTO_INCREMENT,
            campaign_id INT NOT NULL,
            user_id INT NOT NULL,
            prospect_email VARCHAR(255) NOT NULL,
            subject TEXT NOT NULL,
            body LONGTEXT NOT NULL,
            status ENUM('pending', 'sending', 'sent', 'failed', 'bounced') DEFAULT 'pending',
            scheduled_time DATETIME,
            sent_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_campaign (campaign_id),
            INDEX idx_status (status),
            INDEX idx_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    $db->exec($createQueueSQL);
    
    $queueStmt = $db->prepare("
        INSERT INTO email_queue (campaign_id, user_id, prospect_email, subject, body, status, created_at)
        VALUES (?, ?, ?, ?, ?, 'pending', NOW())
    ");
    
    $emailsAdded = 0;
    for ($i = 0; $i < $mockEmails; $i++) {
        $email = "prospect" . ($i + 1) . "@example.com";
        try {
            $queueStmt->execute([
                $campaignId,
                $userId,
                $email,
                $subject,
                $body,
            ]);
            $emailsAdded++;
        } catch (Exception $e) {
            error_log("Failed to add email to queue: " . $e->getMessage());
        }
    }
    
    // Update emails sent count in campaign
    $updateSentStmt = $db->prepare("UPDATE campaigns SET emails_sent = ? WHERE id = ?");
    $updateSentStmt->execute([$emailsAdded, $campaignId]);
    
    error_log("{$emailsAdded} emails queued for campaign #{$campaignId}");
    return $emailsAdded;
}

/**
 * Log activity
 */
function logActivity($message, $userId, $db) {
    try {
        // Check if activity_log table exists (use your existing table name)
        $stmt = $db->prepare("
            INSERT INTO activity_log (user_id, activity_type, description, ip_address, created_at)
            VALUES (?, 'campaign', ?, ?, NOW())
        ");
        
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        $stmt->execute([$userId, $message, $ipAddress]);
        error_log("Activity logged: " . $message);
        
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

/**
 * Create necessary tables if they don't exist
 * (Fallback function - kept for compatibility)
 */
function createTables($db) {
    // Check if campaigns table exists with all required columns
    // If not, you may need to alter the table to add missing columns
    error_log("Ensuring database tables are ready...");
    
    try {
        // Check if campaign_settings table exists, create if not
        $createSettingsSQL = "
            CREATE TABLE IF NOT EXISTS campaign_settings (
                id INT PRIMARY KEY AUTO_INCREMENT,
                campaign_id INT NOT NULL UNIQUE,
                email_account VARCHAR(255),
                unsubscribe_text TEXT,
                email_priority VARCHAR(50) DEFAULT 'equally_divided',
                timezone VARCHAR(50) DEFAULT 'UTC',
                weekly_schedule VARCHAR(255),
                start_time VARCHAR(10),
                end_time VARCHAR(10),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
                INDEX idx_campaign (campaign_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        $db->exec($createSettingsSQL);
        
        error_log("Tables ensured successfully");
        return true;
        
    } catch (Exception $e) {
        error_log("Failed to create tables: " . $e->getMessage());
        return false;
    }
}
?>