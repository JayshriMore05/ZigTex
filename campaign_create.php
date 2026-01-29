<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$campaign_name = isset($_GET['name']) ? htmlspecialchars($_GET['name']) : 'Campaign ' . date('Y-m-d');

// Load existing campaign data if editing
$campaign_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_edit = $campaign_id > 0;
$existing_campaign = null;
$campaign_steps = [];

// Get user's email accounts
try {
    $db = db();
    $stmt = $db->prepare("SELECT * FROM user_email_accounts WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $email_accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get default email if exists
    $default_email = '';
    foreach ($email_accounts as $account) {
        if ($account['is_default']) {
            $default_email = $account['email'];
            break;
        }
    }
    if (empty($default_email) && !empty($email_accounts)) {
        $default_email = $email_accounts[0]['email'];
    }
    
} catch (Exception $e) {
    error_log("Email accounts error: " . $e->getMessage());
    $email_accounts = [];
    $default_email = '';
}

if ($is_edit) {
    try {
        $stmt = $db->prepare("SELECT * FROM campaigns WHERE id = ? AND user_id = ?");
        $stmt->execute([$campaign_id, $user_id]);
        $existing_campaign = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing_campaign) {
            $campaign_name = $existing_campaign['name'];
            
            // Load campaign steps
            $stmt = $db->prepare("SELECT * FROM campaign_steps WHERE campaign_id = ? ORDER BY step_number");
            $stmt->execute([$campaign_id]);
            $campaign_steps = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        error_log("Campaign load error: " . $e->getMessage());
    }
}

// Initialize session data
if (!isset($_SESSION['campaign_data'])) {
    $_SESSION['campaign_data'] = [
        'campaign_id' => $campaign_id,
        'campaign_name' => $campaign_name,
        'step1' => $existing_campaign ? [
            'email_account' => $existing_campaign['email_account'],
            'unsubscribe_text' => $existing_campaign['unsubscribe_text'] ?? '',
            'email_priority' => $existing_campaign['email_priority'] ?? 'equally_divided'
        ] : [
            'email_account' => $default_email,
            'unsubscribe_text' => '',
            'email_priority' => 'equally_divided'
        ],
        'step2' => $existing_campaign ? [
            'timezone' => $existing_campaign['timezone'] ?? 'Europe/London',
            'weekly_schedule' => explode(',', $existing_campaign['weekly_schedule'] ?? 'Mon,Tue,Wed,Thu,Fri'),
            'start_time' => $existing_campaign['start_time'] ?? '09:00',
            'end_time' => $existing_campaign['end_time'] ?? '19:00'
        ] : [
            'timezone' => 'Europe/London',
            'weekly_schedule' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
            'start_time' => '09:00',
            'end_time' => '19:00'
        ],
        'step3' => [],
        'steps' => []  // Store multiple email steps
    ];
    
    // Load existing steps into session
    if (!empty($campaign_steps)) {
        foreach ($campaign_steps as $step_data) {
            $_SESSION['campaign_data']['steps'][$step_data['step_number']] = [
                'type' => $step_data['step_type'],
                'delay_days' => $step_data['delay_days'],
                'delay_hours' => $step_data['delay_hours'],
                'subject' => $step_data['subject'],
                'message' => $step_data['email_body']
            ];
        }
    }
} else {
    // Update campaign name if changed
    $_SESSION['campaign_data']['campaign_name'] = $campaign_name;
    
    // Ensure step2 has all required keys with defaults
    if (!isset($_SESSION['campaign_data']['step2'])) {
        $_SESSION['campaign_data']['step2'] = [
            'timezone' => 'Europe/London',
            'weekly_schedule' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
            'start_time' => '09:00',
            'end_time' => '19:00'
        ];
    } else {
        // Ensure weekly_schedule is an array
        if (!isset($_SESSION['campaign_data']['step2']['weekly_schedule']) || !is_array($_SESSION['campaign_data']['step2']['weekly_schedule'])) {
            $_SESSION['campaign_data']['step2']['weekly_schedule'] = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'];
        }
        // Ensure other keys exist
        if (!isset($_SESSION['campaign_data']['step2']['timezone'])) {
            $_SESSION['campaign_data']['step2']['timezone'] = 'Europe/London';
        }
        if (!isset($_SESSION['campaign_data']['step2']['start_time'])) {
            $_SESSION['campaign_data']['step2']['start_time'] = '09:00';
        }
        if (!isset($_SESSION['campaign_data']['step2']['end_time'])) {
            $_SESSION['campaign_data']['step2']['end_time'] = '19:00';
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $step = isset($_POST['step']) ? (int)$_POST['step'] : 1;
    $action = $_POST['action'] ?? 'next';
    
    // Process data based on current step
    switch ($step) {
        case 1:
            // Only save if data is provided, otherwise keep existing
            $email_account = $_POST['email_account'] ?? ($_SESSION['campaign_data']['step1']['email_account'] ?? $default_email);
            $unsubscribe_text = $_POST['unsubscribe_text'] ?? ($_SESSION['campaign_data']['step1']['unsubscribe_text'] ?? '');
            $email_priority = $_POST['email_priority'] ?? ($_SESSION['campaign_data']['step1']['email_priority'] ?? 'equally_divided');
            
            $_SESSION['campaign_data']['step1'] = [
                'email_account' => $email_account,
                'unsubscribe_text' => $unsubscribe_text,
                'email_priority' => $email_priority
            ];
            break;
            
        case 2:
            // Use existing values if not provided
            $timezone = $_POST['timezone'] ?? ($_SESSION['campaign_data']['step2']['timezone'] ?? 'Europe/London');
            $weekly_schedule = $_POST['weekly_schedule'] ?? ($_SESSION['campaign_data']['step2']['weekly_schedule'] ?? ['Mon', 'Tue', 'Wed', 'Thu', 'Fri']);
            $start_time = $_POST['start_time'] ?? ($_SESSION['campaign_data']['step2']['start_time'] ?? '09:00');
            $end_time = $_POST['end_time'] ?? ($_SESSION['campaign_data']['step2']['end_time'] ?? '19:00');
            
            // Ensure weekly_schedule is an array
            if (!is_array($weekly_schedule)) {
                $weekly_schedule = explode(',', $weekly_schedule);
            }
            
            $_SESSION['campaign_data']['step2'] = [
                'timezone' => $timezone,
                'weekly_schedule' => $weekly_schedule,
                'start_time' => $start_time,
                'end_time' => $end_time
            ];
            break;
            
        case 3:
            // CSV upload processing - FIXED VERSION
            if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
                $file_info = [
                    'name' => $_FILES['csv_file']['name'],
                    'size' => $_FILES['csv_file']['size'],
                    'type' => $_FILES['csv_file']['type']
                ];
                
                // Debug: Log file upload
                error_log("CSV file uploaded: " . $_FILES['csv_file']['name'] . ", Size: " . $_FILES['csv_file']['size']);
                
                // Process CSV and count prospects
                $csv_path = $_FILES['csv_file']['tmp_name'];
                $prospect_count = 0;
                $csv_contacts = [];
                
                if (($handle = fopen($csv_path, "r")) !== FALSE) {
                    // Read the first line as headers
                    $headers = fgetcsv($handle);
                    if ($headers === FALSE) {
                        error_log("CSV file is empty or invalid");
                        $_SESSION['error'] = 'CSV file is empty or invalid';
                    } else {
                        // Debug: Log headers
                        error_log("CSV Headers: " . print_r($headers, true));
                        
                        // Convert headers to lowercase for consistency
                        $headers = array_map('strtolower', $headers);
                        error_log("Normalized Headers: " . print_r($headers, true));
                        
                        while (($data = fgetcsv($handle)) !== FALSE) {
                            // Skip empty rows
                            if (empty(array_filter($data))) {
                                continue;
                            }
                            
                            // Ensure data has same number of columns as headers
                            if (count($data) !== count($headers)) {
                                error_log("Row data count mismatch: headers=" . count($headers) . ", data=" . count($data));
                                continue;
                            }
                            
                            $contact = array_combine($headers, $data);
                            
                            // Debug: Log contact data
                            error_log("Contact data: " . print_r($contact, true));
                            
                            // Validate email
                            if (!isset($contact['email']) || empty(trim($contact['email']))) {
                                error_log("No email found in row");
                                continue;
                            }
                            
                            $email = trim($contact['email']);
                            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                error_log("Invalid email: " . $email);
                                continue;
                            }
                            
                            $prospect_count++;
                            $csv_contacts[] = $contact;
                        }
                        fclose($handle);
                        
                        error_log("Total valid contacts: " . $prospect_count);
                    }
                } else {
                    error_log("Failed to open CSV file: " . $csv_path);
                    $_SESSION['error'] = 'Failed to process CSV file';
                }
                
                $_SESSION['campaign_data']['step3'] = [
                    'csv_uploaded' => true,
                    'file_info' => $file_info,
                    'prospect_count' => $prospect_count,
                    'csv_data' => $csv_contacts  // Store CSV data for later processing
                ];
                
                // Debug: Log session data
                error_log("Step3 session data: " . print_r($_SESSION['campaign_data']['step3'], true));
                
            } elseif (isset($_POST['use_existing']) && $_POST['use_existing'] == '1') {
                $_SESSION['campaign_data']['step3'] = [
                    'use_existing' => true,
                    'prospect_count' => 5
                ];
            } else {
                // If no CSV uploaded and no existing list used, keep existing data
                if (!isset($_SESSION['campaign_data']['step3'])) {
                    $_SESSION['campaign_data']['step3'] = [];
                }
            }
            break;
            
        case 4:
            $step_number = $_POST['step_number'] ?? 1;
            // Only save if data is provided
            if (isset($_POST['subject']) || isset($_POST['message'])) {
                $_SESSION['campaign_data']['steps'][$step_number] = [
                    'type' => $step_number == 1 ? 'initial' : 'follow_up',
                    'delay_days' => $_POST['delay_days'] ?? ($_SESSION['campaign_data']['steps'][$step_number]['delay_days'] ?? 0),
                    'delay_hours' => $_POST['delay_hours'] ?? ($_SESSION['campaign_data']['steps'][$step_number]['delay_hours'] ?? 0),
                    'subject' => $_POST['subject'] ?? ($_SESSION['campaign_data']['steps'][$step_number]['subject'] ?? ''),
                    'message' => $_POST['message'] ?? ($_SESSION['campaign_data']['steps'][$step_number]['message'] ?? '')
                ];
            }
            
            // Handle "Add Another Step" action
            if ($action === 'add_step') {
                // Stay on step 4 but with next step number
                $step = 4;
                $new_step = count($_SESSION['campaign_data']['steps']) + 1;
                header("Location: campaign_create.php?step=4&substep=" . $new_step . "&name=" . urlencode($campaign_name) . ($is_edit ? "&id=$campaign_id" : ""));
                exit();
            } elseif ($action === 'delete_step') {
                $step_to_delete = $_POST['delete_step'] ?? 0;
                if ($step_to_delete > 1 && isset($_SESSION['campaign_data']['steps'][$step_to_delete])) {
                    unset($_SESSION['campaign_data']['steps'][$step_to_delete]);
                    // Reindex steps
                    $steps = $_SESSION['campaign_data']['steps'];
                    $new_steps = [];
                    $i = 1;
                    foreach ($steps as $step_data) {
                        $new_steps[$i] = $step_data;
                        $i++;
                    }
                    $_SESSION['campaign_data']['steps'] = $new_steps;
                    
                    // Redirect to first step
                    header("Location: campaign_create.php?step=4&substep=1&name=" . urlencode($campaign_name) . ($is_edit ? "&id=$campaign_id" : ""));
                    exit();
                }
            }
            break;
    }
    
    // Handle navigation
    if ($action === 'next' && $step < 5) {
        $step++;
        header("Location: campaign_create.php?step=$step&name=" . urlencode($campaign_name) . ($is_edit ? "&id=$campaign_id" : ""));
        exit();
    } elseif ($action === 'prev' && $step > 1) {
        $step--;
        header("Location: campaign_create.php?step=$step&name=" . urlencode($campaign_name) . ($is_edit ? "&id=$campaign_id" : ""));
        exit();
    } elseif ($action === 'goto_step') {
        $goto_step = $_POST['goto_step'] ?? 1;
        header("Location: campaign_create.php?step=$goto_step&name=" . urlencode($campaign_name) . ($is_edit ? "&id=$campaign_id" : ""));
        exit();
    } elseif ($action === 'save_draft') {
        if (saveCampaign('draft')) {
            // Process CSV contacts into queue if uploaded
            if (isset($_SESSION['campaign_data']['step3']['csv_uploaded']) && 
                $_SESSION['campaign_data']['step3']['csv_uploaded'] &&
                isset($_SESSION['campaign_data']['step3']['csv_data'])) {
                processCSVToQueue($campaign_id, $_SESSION['campaign_data']['step3']['csv_data']);
            }
            
            unset($_SESSION['campaign_data']);
            // Redirect to campaigns.php with success message
            header("Location: campaign.php?success=draft_saved");
            exit();
        } else {
            header("Location: campaign_create.php?step=$step&name=" . urlencode($campaign_name) . ($is_edit ? "&id=$campaign_id" : "") . "&error=draft_failed");
            exit();
        }
    } elseif ($action === 'launch') {
        if (saveCampaign('running')) {
            // Process CSV contacts into queue if uploaded
            if (isset($_SESSION['campaign_data']['step3']['csv_uploaded']) && 
                $_SESSION['campaign_data']['step3']['csv_uploaded'] &&
                isset($_SESSION['campaign_data']['step3']['csv_data'])) {
                processCSVToQueue($campaign_id, $_SESSION['campaign_data']['step3']['csv_data']);
            }
            
            unset($_SESSION['campaign_data']);
            // Redirect to campaigns.php with success message
            header("Location: campaign.php?success=campaign_launched");
            exit();
        } else {
            header("Location: campaign_create.php?step=$step&name=" . urlencode($campaign_name) . ($is_edit ? "&id=$campaign_id" : "") . "&error=launch_failed");
            exit();
        }
    }
}

// Check for errors from file upload
$upload_error = '';
if (isset($_SESSION['error'])) {
    $upload_error = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Function to process CSV contacts and add to campaign queue WITH FOLLOW-UPS
function processCSVToQueue($campaignId, $csvContacts) {
    global $user_id, $db;
    
    try {
        // Get email account from session
        $emailAccount = $_SESSION['campaign_data']['step1']['email_account'] ?? '';
        
        // Get SMTP config ID for this email account
        $stmt = $db->prepare("SELECT id FROM user_email_accounts WHERE email = ? AND user_id = ?");
        $stmt->execute([$emailAccount, $user_id]);
        $smtpConfig = $stmt->fetch(PDO::FETCH_ASSOC);
        $smtpConfigId = $smtpConfig['id'] ?? 1;
        
        // Get ALL email sequences ordered by step number
        $stmt = $db->prepare("SELECT * FROM campaign_steps WHERE campaign_id = ? ORDER BY step_number");
        $stmt->execute([$campaignId]);
        $sequences = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($sequences)) {
            error_log("No email sequences found for campaign $campaignId");
            return false;
        }
        
        // Prepare insert statements
        $insertQueueStmt = $db->prepare("
            INSERT INTO campaign_queue 
            (campaign_id, sequence_id, smtp_config_id, contact_email, first_name, last_name, company, 
             subject, body, status, scheduled_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)
        ");
        
        $inserted = 0;
        $currentDateTime = new DateTime();
        
        foreach ($csvContacts as $contact) {
            // Normalize contact data
            $first = isset($contact['first_name']) ? $contact['first_name'] : 
                    (isset($contact['firstname']) ? $contact['firstname'] : '');
            $last = isset($contact['last_name']) ? $contact['last_name'] : 
                   (isset($contact['lastname']) ? $contact['lastname'] : '');
            $company = isset($contact['company']) ? $contact['company'] : '';
            $email = isset($contact['email']) ? trim($contact['email']) : '';
            
            // Validate email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            
            $totalDelayDays = 0;
            $totalDelayHours = 0;
            
            // Process each sequence step
            foreach ($sequences as $sequenceIndex => $sequence) {
                $sequenceNumber = $sequenceIndex + 1;
                
                // Calculate scheduled time for this step
                if ($sequenceNumber == 1) {
                    // First email: send immediately
                    $scheduledDate = clone $currentDateTime;
                } else {
                    // Follow-up emails: add cumulative delay
                    $previousSequence = $sequences[$sequenceIndex - 1];
                    $delayDays = $previousSequence['delay_days'] ?? 0;
                    $delayHours = $previousSequence['delay_hours'] ?? 0;
                    
                    $totalDelayDays += $delayDays;
                    $totalDelayHours += $delayHours;
                    
                    $scheduledDate = clone $currentDateTime;
                    $scheduledDate->modify("+$totalDelayDays days");
                    $scheduledDate->modify("+$totalDelayHours hours");
                }
                
                // Replace merge tags in subject and body
                $subject = replaceMergeTags($sequence['subject'], $contact);
                $body = replaceMergeTags($sequence['email_body'], $contact);
                
                // Add unsubscribe link to body
                $unsubscribeText = $_SESSION['campaign_data']['step1']['unsubscribe_text'] ?? '';
                if (!empty($unsubscribeText)) {
                    $body .= "\n\n---\n" . $unsubscribeText;
                }
                
                // Insert into queue
                $success = $insertQueueStmt->execute([
                    $campaignId,
                    $sequence['id'],
                    $smtpConfigId,
                    $email,
                    $first,
                    $last,
                    $company,
                    $subject,
                    $body,
                    $scheduledDate->format('Y-m-d H:i:s')
                ]);
                
                if ($success) {
                    if ($sequenceNumber == 1) {
                        $inserted++; // Count unique prospects
                    }
                } else {
                    error_log("Failed to insert into queue for email: $email, sequence: $sequenceNumber");
                }
            }
        }
        
        // Update total prospects count
        $updateStmt = $db->prepare("UPDATE campaigns SET total_prospects = ? WHERE id = ?");
        $updateStmt->execute([$inserted, $campaignId]);
        
        // Calculate total emails in queue (prospects × sequences)
        $totalEmails = $inserted * count($sequences);
        
        error_log("CSV processing completed: $inserted prospects, " . count($sequences) . " sequences, $totalEmails total emails");
        
        return [
            'prospects' => $inserted,
            'sequences' => count($sequences),
            'total_emails' => $totalEmails
        ];
        
    } catch (Exception $e) {
        error_log("CSV to queue processing error: " . $e->getMessage());
        return false;
    }
}

// Function to replace merge tags in content
function replaceMergeTags($content, $contact) {
    // Normalize contact keys (case-insensitive)
    $contactNormalized = [];
    foreach ($contact as $key => $value) {
        $contactNormalized[strtolower($key)] = $value;
    }
    
    $tags = [
        '{{first_name}}' => $contactNormalized['first_name'] ?? 
                          $contactNormalized['firstname'] ?? '',
        '{{last_name}}' => $contactNormalized['last_name'] ?? 
                         $contactNormalized['lastname'] ?? '',
        '{{company}}' => $contactNormalized['company'] ?? '',
        '{{email}}' => $contactNormalized['email'] ?? '',
        '{{linkedin_link}}' => $contactNormalized['linkedin_link'] ?? 
                             $contactNormalized['linkedin'] ?? 
                             $contactNormalized['linkedin_url'] ?? '',
        '{{job_position}}' => $contactNormalized['job_position'] ?? 
                            $contactNormalized['position'] ?? 
                            $contactNormalized['job_title'] ?? '',
        '{{industry}}' => $contactNormalized['industry'] ?? '',
        '{{country}}' => $contactNormalized['country'] ?? '',
        '{{lead_status}}' => $contactNormalized['lead_status'] ?? '',
        '{{notes}}' => $contactNormalized['notes'] ?? ''
    ];
    
    foreach ($tags as $tag => $value) {
        $content = str_replace($tag, $value, $content);
    }
    
    return $content;
}

// Function to save campaign to database
function saveCampaign($status = 'draft') {
    global $user_id, $campaign_id, $is_edit, $db;
    
    if (!isset($_SESSION['campaign_data'])) {
        return false;
    }
    
    try {
        // Get data from session
        $data = $_SESSION['campaign_data'];
        $step1 = $data['step1'] ?? [];
        $step2 = $data['step2'] ?? [];
        $step3 = $data['step3'] ?? [];
        $steps = $data['steps'] ?? [];
        
        // Only validate email account if it's required for launch
        if ($status === 'running' && empty($step1['email_account'])) {
            $_SESSION['error'] = 'Please select an email account to launch campaign';
            return false;
        }
        
        // Prepare campaign data with defaults
        $campaign_data = [
            'user_id' => $user_id,
            'name' => $data['campaign_name'] ?? 'Unnamed Campaign',
            'email_account' => $step1['email_account'] ?? '',
            'unsubscribe_text' => $step1['unsubscribe_text'] ?? '',
            'email_priority' => $step1['email_priority'] ?? 'equally_divided',
            'timezone' => $step2['timezone'] ?? 'Europe/London',
            'weekly_schedule' => !empty($step2['weekly_schedule']) ? 
                (is_array($step2['weekly_schedule']) ? 
                    implode(',', $step2['weekly_schedule']) : 
                    $step2['weekly_schedule']) : 
                'Mon,Tue,Wed,Thu,Fri',
            'start_time' => $step2['start_time'] ?? '09:00',
            'end_time' => $step2['end_time'] ?? '19:00',
            'total_prospects' => $step3['prospect_count'] ?? 0,
            'status' => $status,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        if ($is_edit) {
            // Update existing campaign
            $sql = "UPDATE campaigns SET 
                    name = :name,
                    email_account = :email_account,
                    unsubscribe_text = :unsubscribe_text,
                    email_priority = :email_priority,
                    timezone = :timezone,
                    weekly_schedule = :weekly_schedule,
                    start_time = :start_time,
                    end_time = :end_time,
                    total_prospects = :total_prospects,
                    status = :status,
                    updated_at = NOW()
                    WHERE id = :id AND user_id = :user_id";
                    
            $stmt = $db->prepare($sql);
            $campaign_data['id'] = $campaign_id;
            $campaign_data['user_id'] = $user_id;
            $success = $stmt->execute($campaign_data);
            
            if ($success && !empty($steps)) {
                // Delete existing steps
                $db->prepare("DELETE FROM campaign_steps WHERE campaign_id = ?")->execute([$campaign_id]);
                
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
                }
            }
            
            return $success;
        } else {
            // Insert new campaign
            $sql = "INSERT INTO campaigns (
                    user_id, name, email_account, unsubscribe_text, email_priority,
                    timezone, weekly_schedule, start_time, end_time, total_prospects,
                    status, created_at
                ) VALUES (
                    :user_id, :name, :email_account, :unsubscribe_text, :email_priority,
                    :timezone, :weekly_schedule, :start_time, :end_time, :total_prospects,
                    :status, :created_at
                )";
                
            $stmt = $db->prepare($sql);
            $success = $stmt->execute($campaign_data);
            
            if ($success) {
                $campaign_id = $db->lastInsertId();
                
                // Insert steps
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
                }
            }
            
            return $success;
        }
    } catch (Exception $e) {
        error_log("Campaign save error: " . $e->getMessage());
        $_SESSION['error'] = 'Failed to save campaign: ' . $e->getMessage();
        return false;
    }
}

// Get current step for step 4
$current_step_number = isset($_GET['substep']) ? (int)$_GET['substep'] : 1;
$total_steps = max(1, count($_SESSION['campaign_data']['steps'] ?? []));

// Debug: Check session data
error_log("Current Step: $step");
error_log("Session data step3: " . print_r($_SESSION['campaign_data']['step3'] ?? 'No step3 data', true));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_edit ? 'Edit Campaign' : 'Create Campaign'; ?> - Email Campaigns</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ===== GLOBAL STYLES ===== */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

body {
    background: #f5f7fa;
    min-height: 100vh;
    color: #333;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 30px;
}

/* ===== HEADER ===== */
.header {
    background: white;
    border-radius: 16px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    border: 1px solid #eef1f5;
}

.header h1 {
    font-size: 32px;
    font-weight: 700;
    color: #1a1a1a;
    margin-bottom: 8px;
}

.header h2 {
    font-size: 18px;
    font-weight: 500;
    color: #666;
}

/* ===== MAIN LAYOUT ===== */
.main-layout {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 30px;
    margin-bottom: 40px;
}

@media (max-width: 1024px) {
    .main-layout {
        grid-template-columns: 1fr;
    }
}

/* ===== SIDEBAR ===== */
.sidebar {
    background: white;
    border-radius: 16px;
    padding: 25px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    border: 1px solid #eef1f5;
    height: fit-content;
}

.sidebar-title {
    font-size: 18px;
    font-weight: 600;
    color: #1a1a1a;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f5f7fa;
}

.progress-steps {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.progress-step {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
    border: 1px solid transparent;
}

.progress-step:hover {
    background: #f8fafc;
}

.progress-step.active {
    background: #f0f9ff;
    border-color: #007bff;
}

.progress-step.completed {
    background: #f8fff9;
    border-color: #28a745;
}

.step-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 14px;
    background: #f5f7fa;
    color: #666;
    transition: all 0.3s ease;
}

.progress-step.active .step-icon {
    background: #007bff;
    color: white;
}

.progress-step.completed .step-icon {
    background: #28a745;
    color: white;
}

.step-info h4 {
    font-size: 15px;
    font-weight: 600;
    color: #1a1a1a;
    margin-bottom: 4px;
}

.step-info p {
    font-size: 13px;
    color: #666;
    line-height: 1.4;
}

/* ===== CONTENT AREA ===== */
.content-area {
    background: white;
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    border: 1px solid #eef1f5;
    min-height: 600px;
}

.content-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #f5f7fa;
}

.content-header h3 {
    font-size: 24px;
    font-weight: 600;
    color: #1a1a1a;
}

.step-indicator {
    font-size: 14px;
    color: #666;
    background: #f5f7fa;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 500;
}

/* ===== FORM STYLES ===== */
.form-section {
    display: none;
    animation: fadeIn 0.3s ease;
}

.form-section.active {
    display: block;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.form-group {
    margin-bottom: 25px;
}

.form-group label {
    display: block;
    font-weight: 500;
    margin-bottom: 10px;
    color: #444;
    font-size: 14px;
}

.form-group label.required::after {
    content: ' *';
    color: #dc3545;
}

input, select, textarea {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #e0e6ed;
    border-radius: 10px;
    font-size: 14px;
    color: #333;
    background: white;
    transition: all 0.2s ease;
}

input:focus, select:focus, textarea:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
}

textarea {
    min-height: 180px;
    resize: vertical;
    line-height: 1.5;
    font-family: inherit;
}

/* ===== EMAIL ACCOUNT SELECTOR ===== */
.email-selector {
    margin-bottom: 25px;
}

.email-options {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 15px;
}

.email-option {
    background: white;
    border: 1px solid #e0e6ed;
    border-radius: 10px;
    padding: 18px;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
}

.email-option:hover {
    border-color: #007bff;
}

.email-option.selected {
    background: #f0f9ff;
    border-color: #007bff;
}

.email-option .checkmark {
    position: absolute;
    top: 15px;
    right: 15px;
    color: #007bff;
    font-size: 18px;
    opacity: 0;
    transition: opacity 0.2s ease;
}

.email-option.selected .checkmark {
    opacity: 1;
}

.email-address {
    font-weight: 500;
    color: #1a1a1a;
    margin-bottom: 5px;
    font-size: 15px;
}

.email-status {
    font-size: 13px;
    color: #666;
}

.email-status.verified {
    color: #28a745;
}

.add-email-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 18px;
    background: white;
    border: 1px dashed #e0e6ed;
    border-radius: 10px;
    color: #666;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 14px;
}

.add-email-btn:hover {
    border-color: #007bff;
    color: #007bff;
}

.no-email-msg {
    background: #fff3cd;
    border: 1px solid #ffecb5;
    color: #856404;
    padding: 20px;
    border-radius: 10px;
    text-align: center;
    margin-bottom: 20px;
    font-size: 14px;
}

/* ===== UPLOAD AREA ===== */
.upload-area {
    border: 2px dashed #e0e6ed;
    border-radius: 16px;
    padding: 50px 30px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s ease;
    margin-bottom: 25px;
}

.upload-area:hover, .upload-area.dragover {
    border-color: #007bff;
    background: #f8fafc;
}

.upload-icon {
    font-size: 48px;
    color: #adb5bd;
    margin-bottom: 20px;
}

.upload-text {
    font-size: 18px;
    font-weight: 600;
    color: #1a1a1a;
    margin-bottom: 8px;
}

.upload-subtext {
    font-size: 14px;
    color: #666;
    margin-bottom: 20px;
}

/* ===== STEP NAVIGATION ===== */
.step-navigation {
    display: flex;
    gap: 8px;
    margin-bottom: 25px;
    padding-bottom: 20px;
    border-bottom: 2px solid #f5f7fa;
    flex-wrap: wrap;
}

.step-tab {
    padding: 10px 18px;
    background: white;
    border: 1px solid #e0e6ed;
    border-radius: 10px;
    color: #666;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 110px;
}

.step-tab:hover {
    border-color: #007bff;
    color: #007bff;
}

.step-tab.active {
    background: #f0f9ff;
    border-color: #007bff;
    color: #007bff;
}

.step-tab.completed {
    background: #f8fff9;
    border-color: #28a745;
    color: #28a745;
}

.step-number {
    background: #f5f7fa;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 600;
}

.step-tab.active .step-number {
    background: #007bff;
    color: white;
}

.step-tab.completed .step-number {
    background: #28a745;
    color: white;
}

/* ===== MERGE TAGS ===== */
.merge-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 25px;
    padding: 18px;
    background: #f8fafc;
    border-radius: 10px;
}

.merge-tag {
    padding: 6px 12px;
    background: white;
    border: 1px solid #e0e6ed;
    color: #333;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.merge-tag:hover {
    background: #007bff;
    color: white;
    border-color: #007bff;
}

/* ===== DELAY DROPDOWN ===== */
.delay-selector {
    display: flex;
    gap: 10px;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.delay-selector select {
    min-width: 110px;
}

.delay-selector input {
    width: 70px;
}

/* ===== PREVIEW SECTION ===== */
.preview-container {
    background: #f8fafc;
    border-radius: 16px;
    padding: 25px;
}

.preview-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
    margin-bottom: 25px;
}

@media (max-width: 768px) {
    .preview-grid {
        grid-template-columns: 1fr;
    }
}

.preview-item {
    background: white;
    padding: 18px;
    border-radius: 10px;
    border: 1px solid #eef1f5;
}

.preview-label {
    font-size: 12px;
    color: #666;
    margin-bottom: 6px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 500;
}

.preview-value {
    font-size: 15px;
    font-weight: 500;
    color: #1a1a1a;
}

.email-sequence-preview {
    margin-top: 25px;
}

.email-step-preview {
    background: white;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 16px;
    border: 1px solid #eef1f5;
    border-left: 3px solid #007bff;
}

.email-step-preview:nth-child(n+2) {
    border-left-color: #28a745;
}

.step-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.step-title {
    font-size: 16px;
    font-weight: 600;
    color: #1a1a1a;
}

.step-delay {
    font-size: 12px;
    color: #666;
    background: #f5f7fa;
    padding: 4px 10px;
    border-radius: 20px;
}

.email-subject {
    font-size: 14px;
    font-weight: 500;
    color: #007bff;
    margin-bottom: 12px;
    padding-bottom: 8px;
    border-bottom: 1px solid #f5f7fa;
}

.email-body {
    color: #444;
    line-height: 1.6;
    white-space: pre-wrap;
    font-size: 14px;
}

/* ===== NAVIGATION BUTTONS ===== */
.navigation-buttons {
    display: flex;
    justify-content: space-between;
    margin-top: 30px;
    padding-top: 25px;
    border-top: 2px solid #f5f7fa;
}

.btn {
    padding: 12px 24px;
    border-radius: 10px;
    font-weight: 500;
    font-size: 14px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    border: none;
    transition: all 0.2s ease;
    min-width: 120px;
    justify-content: center;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.btn-prev {
    background: white;
    color: #666;
    border: 1px solid #e0e6ed;
}

.btn-prev:hover {
    background: #f8fafc;
    border-color: #007bff;
    color: #007bff;
}

.btn-next {
    background: #007bff;
    color: white;
}

.btn-next:hover {
    background: #0056b3;
}

.btn-save {
    background: #28a745;
    color: white;
}

.btn-save:hover {
    background: #218838;
}

.btn-launch {
    background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
    color: white;
}

.btn-launch:hover {
    background: linear-gradient(135deg, #5c0db4 0%, #1c68e3 100%);
}

.btn-add {
    background: white;
    color: #007bff;
    border: 1px dashed #007bff;
}

.btn-add:hover {
    background: #f0f9ff;
    border-style: solid;
}

/* ===== RESPONSIVE ADJUSTMENTS ===== */
@media (max-width: 768px) {
    .container {
        padding: 15px;
    }
    
    .header {
        padding: 20px;
    }
    
    .header h1 {
        font-size: 24px;
    }
    
    .content-area {
        padding: 20px;
    }
    
    .step-tab {
        min-width: 90px;
        padding: 8px 12px;
        font-size: 12px;
    }
    
    .btn {
        padding: 10px 18px;
        min-width: 100px;
        font-size: 13px;
    }
    
    .navigation-buttons {
        flex-direction: column;
        gap: 12px;
    }
    
    .navigation-buttons .btn {
        width: 100%;
    }
}

/* ===== MODAL STYLES ===== */
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
    border-radius: 16px;
    padding: 30px;
    max-width: 700px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    border: 1px solid #eef1f5;
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    padding-bottom: 20px;
    border-bottom: 2px solid #f5f7fa;
}

.modal-header h3 {
    font-size: 20px;
    font-weight: 600;
    color: #1a1a1a;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    color: #666;
    cursor: pointer;
    padding: 0;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.modal-close:hover {
    background: #f5f7fa;
    color: #dc3545;
}

/* ===== FORM ELEMENTS ENHANCEMENTS ===== */
.checkbox-group {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.checkbox-group label {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
    background: white;
    border: 1px solid #e0e6ed;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.2s ease;
}

.checkbox-group label:hover {
    border-color: #007bff;
}

.checkbox-group input[type="checkbox"] {
    width: auto;
    margin: 0;
}

.file-info {
    background: white;
    border: 1px solid #e0e6ed;
    border-radius: 10px;
    padding: 16px;
    margin-top: 20px;
}

.file-name {
    font-weight: 500;
    color: #1a1a1a;
    margin-bottom: 4px;
}

.file-details {
    font-size: 13px;
    color: #666;
}

/* ===== ERROR MESSAGE ===== */
.error-message {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 20px;
    font-size: 14px;
}
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php echo $is_edit ? 'Edit Campaign' : 'Create Campaign'; ?></h1>
            <h2><?php echo htmlspecialchars($campaign_name); ?></h2>
        </div>

        <div class="main-layout">
            <!-- Sidebar with progress steps -->
            <div class="sidebar">
                <h3 class="sidebar-title">Setting up your campaign</h3>
                <div class="progress-steps">
                    <?php
                    $steps_info = [
                        1 => [
                            'title' => 'Channel Setup',
                            'desc' => 'Connect email accounts & setup channels',
                            'icon' => '📧'
                        ],
                        2 => [
                            'title' => 'Campaign Settings',
                            'desc' => 'Configure campaign name, goal, and rules',
                            'icon' => '⚙️'
                        ],
                        3 => [
                            'title' => 'Prospect',
                            'desc' => 'Add or import your prospects list',
                            'icon' => '👥'
                        ],
                        4 => [
                            'title' => 'Content',
                            'desc' => 'Write the email sequence',
                            'icon' => '✏️'
                        ],
                        5 => [
                            'title' => 'Preview & Start',
                            'desc' => 'Review everything before launching',
                            'icon' => '🚀'
                        ]
                    ];
                    
                    foreach ($steps_info as $step_num => $info):
                        $is_active = $step == $step_num;
                        $is_completed = $step > $step_num;
                    ?>
                    <div class="progress-step <?php echo $is_active ? 'active' : ''; ?> <?php echo $is_completed ? 'completed' : ''; ?>"
                         onclick="navigateToStep(<?php echo $step_num; ?>)"
                         style="cursor: pointer;">
                        <div class="step-icon">
                            <?php if ($is_completed): ?>
                                <i class="fas fa-check"></i>
                            <?php else: ?>
                                <?php echo $info['icon']; ?>
                            <?php endif; ?>
                        </div>
                        <div class="step-info">
                            <h4><?php echo $info['title']; ?></h4>
                            <p><?php echo $info['desc']; ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="content-area">
                <div class="content-header">
                    <h3>
                        <?php
                        if ($step == 1) echo 'Channel Setup';
                        elseif ($step == 2) echo 'Campaign Settings';
                        elseif ($step == 3) echo 'Import Prospects';
                        elseif ($step == 4) echo 'Create Message Sequence';
                        elseif ($step == 5) echo 'Preview & Launch Campaign';
                        ?>
                    </h3>
                    <div class="step-indicator">
                        Step <?php echo $step; ?> of 5
                    </div>
                </div>

                <?php if (!empty($upload_error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($upload_error); ?>
                </div>
                <?php endif; ?>

                <!-- Step 1: Channel Setup -->
                <form id="step1Form" class="form-section <?php echo $step == 1 ? 'active' : ''; ?>" method="POST" action="">
                    <input type="hidden" name="step" value="1">
                    
                    <div class="form-group">
                        <label class="required">Select Email Account</label>
                        
                        <?php if (empty($email_accounts)): ?>
                            <div class="no-email-msg">
                                <p><i class="fas fa-exclamation-circle"></i> No email accounts found.</p>
                                <p>Please add an email account to continue.</p>
                                <button type="button" class="btn btn-next" onclick="window.location.href='add_email.php?redirect=campaign_create.php'" style="margin-top: 10px;">
                                    <i class="fas fa-plus"></i> Add Email Account
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="email-options">
                                <?php foreach ($email_accounts as $account): ?>
                                <div class="email-option <?php echo ($_SESSION['campaign_data']['step1']['email_account'] ?? '') == $account['email'] ? 'selected' : ''; ?>"
                                     onclick="selectEmailAccount('<?php echo $account['email']; ?>')">
                                    <div class="checkmark">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <div class="email-address">
                                        <?php echo htmlspecialchars($account['email']); ?>
                                    </div>
                                    <div class="email-status <?php echo $account['is_verified'] ? 'verified' : ''; ?>">
                                        <?php echo $account['is_verified'] ? '✓ Verified' : 'Not verified'; ?>
                                    </div>
                                    <input type="radio" name="email_account" value="<?php echo $account['email']; ?>" 
                                           <?php echo ($_SESSION['campaign_data']['step1']['email_account'] ?? '') == $account['email'] ? 'checked' : ''; ?>
                                           style="display: none;">
                                </div>
                                <?php endforeach; ?>
                                
                                <div class="add-email-btn" onclick="window.location.href='add_email.php?redirect=campaign_create.php'">
                                    <i class="fas fa-plus-circle"></i>
                                    <span>Add New Email Account</span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="required">Unsubscribe Email Text/Link</label>
                        <input type="text" name="unsubscribe_text" id="unsubscribeText" 
                               placeholder="Example: Unsubscribe from our emails"
                               value="<?php echo htmlspecialchars($_SESSION['campaign_data']['step1']['unsubscribe_text'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>Email Priority</label>
                        <select name="email_priority" id="emailPriority">
                            <option value="equally_divided" <?php echo ($_SESSION['campaign_data']['step1']['email_priority'] ?? 'equally_divided') == 'equally_divided' ? 'selected' : ''; ?>>Equally divided between opening and follow-up emails</option>
                            <option value="openings_first" <?php echo ($_SESSION['campaign_data']['step1']['email_priority'] ?? '') == 'openings_first' ? 'selected' : ''; ?>>Prioritize opening emails</option>
                            <option value="followups_first" <?php echo ($_SESSION['campaign_data']['step1']['email_priority'] ?? '') == 'followups_first' ? 'selected' : ''; ?>>Prioritize follow-up emails</option>
                        </select>
                    </div>
                </form>
                

                <!-- Step 2: Campaign Settings -->
                <form id="step2Form" class="form-section <?php echo $step == 2 ? 'active' : ''; ?>" method="POST" action="">
                    <input type="hidden" name="step" value="2">
                    
                    <div class="form-group">
                        <label class="required">Timezone</label>
                        <select name="timezone" id="timezone">

                            <option value="Europe/London" <?php echo ($_SESSION['campaign_data']['step2']['timezone'] ?? 'Europe/London') == 'Europe/London' ? 'selected' : ''; ?>>GMT +0:00 Europe/London</option>
                            <option value="Asia/Kolkata" <?php echo ($_SESSION['campaign_data']['step2']['timezone'] ?? '') == 'Asia/Kolkata' ? 'selected' : ''; ?>>GMT +5:30 Asia/Kolkata</option>
                            <option value="America/New_York" <?php echo ($_SESSION['campaign_data']['step2']['timezone'] ?? '') == 'America/New_York' ? 'selected' : ''; ?>>GMT -5:00 America/New_York</option>
                            <option value="America/Los_Angeles" <?php echo ($_SESSION['campaign_data']['step2']['timezone'] ?? '') == 'America/Los_Angeles' ? 'selected' : ''; ?>>GMT -8:00 America/Los_Angeles</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="required">Weekly Schedule</label>
                        <p style="color: #666; font-size: 14px; margin-bottom: 15px;">Choose which days your campaign should run</p>
                        <div class="checkbox-group" style="display: flex; flex-wrap: wrap; gap: 10px;">
                            <?php
                            $days = [
                                'Mon' => 'Monday',
                                'Tue' => 'Tuesday',
                                'Wed' => 'Wednesday',
                                'Thu' => 'Thursday',
                                'Fri' => 'Friday',
                                'Sat' => 'Saturday',
                                'Sun' => 'Sunday'
                            ];
                            $selected_days = $_SESSION['campaign_data']['step2']['weekly_schedule'] ?? ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'];
                            foreach ($days as $short => $full):
                            ?>
                            <label style="display: flex; align-items: center; gap: 8px; padding: 10px 15px; background: #f8f9fa; border: 2px solid #e0e0e0; border-radius: 8px; cursor: pointer;">
                                <input type="checkbox" name="weekly_schedule[]" value="<?php echo $short; ?>"
                                    <?php echo in_array($short, $selected_days) ? 'checked' : ''; ?>>
                                <span><?php echo $full; ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="required">Sending Hours</label>
                        <p style="color: #666; font-size: 14px; margin-bottom: 15px;">Define when messages should be sent</p>
                        <div style="display: flex; gap: 20px; align-items: center;">
                            <div style="flex: 1;">
                                <label style="font-size: 13px; color: #666; margin-bottom: 5px; display: block;">Start Time</label>
                                <select name="start_time" id="startTime" style="width: 100%;">
                                    <?php
                                    $start_time = $_SESSION['campaign_data']['step2']['start_time'] ?? '09:00';
                                    for ($hour = 6; $hour <= 11; $hour++) {
                                        $time = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00';
                                        $display = date('h:i A', strtotime($time));
                                        $selected = $start_time == $time ? 'selected' : '';
                                        echo "<option value=\"$time\" $selected>$display</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <span style="color: #666; font-weight: 600;">to</span>
                            
                            <div style="flex: 1;">
                                <label style="font-size: 13px; color: #666; margin-bottom: 5px; display: block;">End Time</label>
                                <select name="end_time" id="endTime" style="width: 100%;">
                                    <?php
                                    $end_time = $_SESSION['campaign_data']['step2']['end_time'] ?? '19:00';
                                    for ($hour = 12; $hour <= 22; $hour++) {
                                        if ($hour == 12) {
                                            $time = '12:00';
                                            $display = '12:00 PM';
                                        } else {
                                            $time = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00';
                                            $display = date('h:i A', strtotime($time));
                                        }
                                        $selected = $end_time == $time ? 'selected' : '';
                                        echo "<option value=\"$time\" $selected>$display</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- Step 3: Prospect Import -->
                <form id="step3Form" class="form-section <?php echo $step == 3 ? 'active' : ''; ?>" method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="step" value="3">
                    
                    <div style="text-align: center;">
                        <div class="upload-area" id="uploadArea">
                            <div class="upload-icon">
                                <i class="fas fa-file-csv"></i>
                            </div>
                            <div class="upload-text">Upload CSV file</div>
                            <div class="upload-subtext">Drag and drop or click to choose file</div>
                            <input type="file" id="csvFile" name="csv_file" accept=".csv" style="display: none;" onchange="handleFileSelect(this)">
                            <button type="button" class="btn btn-next" onclick="document.getElementById('csvFile').click()" style="margin-top: 20px;">
                                <i class="fas fa-upload"></i> Upload File
                            </button>
                        </div>
                        
                        <?php if (isset($_SESSION['campaign_data']['step3']['csv_uploaded']) && $_SESSION['campaign_data']['step3']['csv_uploaded']): ?>
                        <div class="file-info" id="fileInfo" style="display: block; max-width: 500px; margin: 30px auto;">
                            <div class="file-name" id="fileName">
                                <i class="fas fa-file-csv"></i> <?php echo htmlspecialchars($_SESSION['campaign_data']['step3']['file_info']['name'] ?? ''); ?>
                            </div>
                            <div class="file-details" id="fileDetails">
                                <?php 
                                $fileSize = $_SESSION['campaign_data']['step3']['file_info']['size'] ?? 0;
                                $prospectCount = $_SESSION['campaign_data']['step3']['prospect_count'] ?? 0;
                                
                                // Format file size
                                if ($fileSize >= 1048576) {
                                    $size = round($fileSize / 1048576, 2) . ' MB';
                                } elseif ($fileSize >= 1024) {
                                    $size = round($fileSize / 1024, 2) . ' KB';
                                } else {
                                    $size = $fileSize . ' Bytes';
                                }
                                
                                echo "CSV file - " . $size . " - " . $prospectCount . " contacts found";
                                ?>
                            </div>
                            <div style="margin-top: 10px; color: #28a745;">
                                <i class="fas fa-check-circle"></i> File uploaded successfully
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="file-info" id="fileInfo" style="display: none; max-width: 500px; margin: 30px auto;">
                            <div class="file-name" id="fileName"></div>
                            <div class="file-details" id="fileDetails"></div>
                        </div>
                        <?php endif; ?>
                        
                        <div style="margin: 30px 0; text-align: center;">
                            <div style="color: #666; margin-bottom: 15px; font-size: 16px;">Or</div>
                            <div class="form-group" style="max-width: 400px; margin: 0 auto;">
                                <label style="text-align: left;">Use Existing Prospects</label>
                                <div style="display: flex; gap: 15px; align-items: center;">
                                    <select name="existing_list" id="existingList" style="flex: 1;">
                                        <option value="">Select existing list...</option>
                                        <option value="list1">Recent Leads (50 contacts)</option>
                                        <option value="list2">Hot Prospects (30 contacts)</option>
                                        <option value="list3">All Contacts (200 contacts)</option>
                                    </select>
                                    <button type="button" class="btn" onclick="useExistingList()">
                                        <i class="fas fa-users"></i> Use
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div style="margin-top: 30px; display: flex; gap: 15px; justify-content: center;">
                            <button type="button" class="btn" onclick="downloadSample()">
                                <i class="fas fa-download"></i> Download Sample CSV
                            </button>
                            <button type="button" class="btn" onclick="showSampleData()">
                                <i class="fas fa-eye"></i> View Sample Data
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Step 4: Content -->
                <form id="step4Form" class="form-section <?php echo $step == 4 ? 'active' : ''; ?>" method="POST" action="">
                    <input type="hidden" name="step" value="4">
                    <input type="hidden" name="step_number" id="stepNumber" value="<?php echo $current_step_number; ?>">
                    
                    <!-- Step Navigation Tabs -->
                    <div class="step-navigation" id="stepTabs">
                        <?php
                        $total_steps_session = count($_SESSION['campaign_data']['steps'] ?? []);
                        $display_steps = max(1, $total_steps_session);
                        
                        for ($i = 1; $i <= $display_steps; $i++):
                            $step_data = $_SESSION['campaign_data']['steps'][$i] ?? null;
                            $is_active = $i == $current_step_number;
                            $is_completed = $step_data && $step_data['subject'] && $step_data['message'];
                        ?>
                        <div class="step-tab <?php echo $is_active ? 'active' : ''; ?> <?php echo $is_completed ? 'completed' : ''; ?>"
                             onclick="switchStep(<?php echo $i; ?>)">
                            <span class="step-number"><?php echo $i; ?></span>
                            <span class="step-type"><?php echo $i == 1 ? 'Initial Email' : 'Follow-up'; ?></span>
                        </div>
                        <?php endfor; ?>
                        
                        <button type="button" class="btn-add" onclick="addNewStep()">
                            <i class="fas fa-plus"></i> Add Step
                        </button>
                    </div>

                    <?php if ($current_step_number > 1): ?>
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 12px; margin-bottom: 25px;">
                        <h4 style="margin-bottom: 15px; color: #333;">Follow-up Settings</h4>
                        <div class="delay-selector">
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <span>Send after</span>
                                <select name="delay_days" id="delayDays" style="min-width: 100px;">
                                    <option value="0" <?php echo ($_SESSION['campaign_data']['steps'][$current_step_number]['delay_days'] ?? 0) == 0 ? 'selected' : ''; ?>>0</option>
                                    <option value="1" <?php echo ($_SESSION['campaign_data']['steps'][$current_step_number]['delay_days'] ?? 2) == 1 ? 'selected' : ''; ?>>1</option>
                                    <option value="2" <?php echo ($_SESSION['campaign_data']['steps'][$current_step_number]['delay_days'] ?? 2) == 2 ? 'selected' : ''; ?>>2</option>
                                    <option value="3" <?php echo ($_SESSION['campaign_data']['steps'][$current_step_number]['delay_days'] ?? 2) == 3 ? 'selected' : ''; ?>>3</option>
                                    <option value="5" <?php echo ($_SESSION['campaign_data']['steps'][$current_step_number]['delay_days'] ?? 2) == 5 ? 'selected' : ''; ?>>5</option>
                                    <option value="7" <?php echo ($_SESSION['campaign_data']['steps'][$current_step_number]['delay_days'] ?? 2) == 7 ? 'selected' : ''; ?>>7</option>
                                </select>
                                <span>days and</span>
                                <input type="number" name="delay_hours" id="delayHours" 
                                       value="<?php echo $_SESSION['campaign_data']['steps'][$current_step_number]['delay_hours'] ?? 0; ?>"
                                       min="0" max="23" placeholder="0" style="width: 70px;">
                                <span>hours</span>
                            </div>
                            <button type="button" class="btn" style="background: #dc3545; color: white; margin-left: auto;" onclick="deleteStep(<?php echo $current_step_number; ?>)">
                                <i class="fas fa-trash"></i> Remove Step
                            </button>
                        </div>
                        <p style="color: #666; font-size: 13px; margin-top: 10px;">
                            <i class="fas fa-info-circle"></i> This delay is calculated from when the previous email was sent.
                        </p>
                    </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label>Available Merge Tags</label>
                        <div class="merge-tags">
                            <?php
                            $mergeTags = [
                                'first_name' => 'First Name',
                                'last_name' => 'Last Name',
                                'company' => 'Company',
                                'email' => 'Email',
                                'linkedin_link' => 'LinkedIn',
                                'job_position' => 'Job Position',
                                'industry' => 'Industry',
                                'country' => 'Country',
                                'lead_status' => 'Lead Status',
                                'notes' => 'Notes'
                            ];
                            foreach ($mergeTags as $tag => $label):
                            ?>
                            <span class="merge-tag" data-tag="{{<?php echo $tag; ?>}}" title="<?php echo $label; ?>">
                                {{<?php echo $tag; ?>}}
                            </span>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="required">Subject Line</label>
                        <input type="text" name="subject" id="subject" 
                               placeholder="Enter email subject"
                               value="<?php echo htmlspecialchars($_SESSION['campaign_data']['steps'][$current_step_number]['subject'] ?? 
                               ($current_step_number == 1 ? 'Hello' : 'Following up')); ?>">
                    </div>

                    <div class="form-group">
                        <label class="required">Message</label>
                        <textarea name="message" id="message" rows="10" 
                                  placeholder="Write your email message here... Use {{merge_tags}} for personalization."><?php echo htmlspecialchars($_SESSION['campaign_data']['steps'][$current_step_number]['message'] ?? 
                                  ($current_step_number == 1 ? 
                                  "Hi {{first_name}},\n\nI hope this email finds you well. I noticed your work at {{company}} and was impressed with your contributions to the {{industry}} industry.\n\nBest regards,\nArpan" : 
                                  "Hi {{first_name}},\n\nFollowing up on my previous email. I'd love to connect and discuss how we might work together.\n\nBest regards,\nArpan")); ?></textarea>
                    </div>
                </form>

                <!-- Step 5: Preview & Start -->
                <form id="step5Form" class="form-section <?php echo $step == 5 ? 'active' : ''; ?>" method="POST" action="">
                    <input type="hidden" name="step" value="5">
                    
                    <div class="preview-container">
                        <div class="preview-grid">
                            <div class="preview-item">
                                <div class="preview-label">Email Account</div>
                                <div class="preview-value" id="previewEmailAccount">
                                    <?php echo htmlspecialchars($_SESSION['campaign_data']['step1']['email_account'] ?? 'No email selected'); ?>
                                </div>
                            </div>
                            <div class="preview-item">
                                <div class="preview-label">Timezone</div>
                                <div class="preview-value" id="previewTimezone">
                                    <?php echo $_SESSION['campaign_data']['step2']['timezone'] ?? 'Europe/London'; ?>
                                </div>
                            </div>
                            <div class="preview-item">
                                <div class="preview-label">Weekly Schedule</div>
                                <div class="preview-value" id="previewSchedule">
                                    <?php 
                                    $schedule = $_SESSION['campaign_data']['step2']['weekly_schedule'] ?? ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'];
                                    if (is_array($schedule)) {
                                        echo implode(', ', $schedule);
                                    } else {
                                        echo $schedule;
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="preview-item">
                                <div class="preview-label">Sending Hours</div>
                                <div class="preview-value" id="previewHours">
                                    <?php 
                                    $start = $_SESSION['campaign_data']['step2']['start_time'] ?? '09:00';
                                    $end = $_SESSION['campaign_data']['step2']['end_time'] ?? '19:00';
                                    echo date('h:i A', strtotime($start)) . ' - ' . date('h:i A', strtotime($end));
                                    ?>
                                </div>
                            </div>
                            <div class="preview-item">
                                <div class="preview-label">Total Prospects</div>
                                <div class="preview-value" id="previewProspects">
                                    <?php 
                                    $prospectCount = $_SESSION['campaign_data']['step3']['prospect_count'] ?? 0;
                                    echo $prospectCount . ' prospect' . ($prospectCount != 1 ? 's' : '');
                                    ?>
                                </div>
                            </div>
                            <div class="preview-item">
                                <div class="preview-label">Email Sequence</div>
                                <div class="preview-value" id="previewSequence">
                                    <?php 
                                    $totalSteps = count($_SESSION['campaign_data']['steps'] ?? []) ?: 1;
                                    echo $totalSteps . ' step' . ($totalSteps > 1 ? 's' : '');
                                    ?>
                                </div>
                            </div>
                            <?php if (isset($_SESSION['campaign_data']['step3']['prospect_count']) && $_SESSION['campaign_data']['step3']['prospect_count'] > 0): ?>
                            <div class="preview-item">
                                <div class="preview-label">Total Emails</div>
                                <div class="preview-value" id="previewTotalEmails" style="color: #007bff; font-weight: 600;">
                                    <?php 
                                    $prospects = $_SESSION['campaign_data']['step3']['prospect_count'] ?? 0;
                                    $stepsCount = count($_SESSION['campaign_data']['steps'] ?? []) ?: 1;
                                    echo ($prospects * $stepsCount) . ' email' . (($prospects * $stepsCount) != 1 ? 's' : '');
                                    ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="email-sequence-preview">
                            <h4 style="margin-bottom: 20px; color: #333;">Email Sequence Preview</h4>
                            <div id="sequencePreview">
                                <?php
                                $steps = $_SESSION['campaign_data']['steps'] ?? [];
                                if (empty($steps)) {
                                    $steps[1] = [
                                        'type' => 'initial',
                                        'subject' => 'Hello',
                                        'message' => 'Hi {{first_name}},\n\nI hope this email finds you well...'
                                    ];
                                }
                                
                                $cumulativeDelayDays = 0;
                                $cumulativeDelayHours = 0;
                                
                                foreach ($steps as $step_num => $step_data):
                                    $delayText = '';
                                    if ($step_num == 1) {
                                        $delayText = 'Sends immediately';
                                    } else {
                                        $prevStep = $steps[$step_num - 1];
                                        $cumulativeDelayDays += $prevStep['delay_days'] ?? 0;
                                        $cumulativeDelayHours += $prevStep['delay_hours'] ?? 0;
                                        
                                        $delayText = 'Sends after ';
                                        if ($cumulativeDelayDays > 0) {
                                            $delayText .= $cumulativeDelayDays . ' day' . ($cumulativeDelayDays > 1 ? 's' : '');
                                            if ($cumulativeDelayHours > 0) {
                                                $delayText .= ', ';
                                            }
                                        }
                                        if ($cumulativeDelayHours > 0) {
                                            $delayText .= $cumulativeDelayHours . ' hour' . ($cumulativeDelayHours > 1 ? 's' : '');
                                        }
                                    }
                                ?>
                                <div class="email-step-preview">
                                    <div class="step-header">
                                        <div class="step-title">
                                            Step <?php echo $step_num; ?>: <?php echo $step_num == 1 ? 'Initial Email' : 'Follow-up Email'; ?>
                                        </div>
                                        <div class="step-delay">
                                            <?php echo $delayText; ?>
                                        </div>
                                    </div>
                                    <div class="email-subject">
                                        Subject: <?php echo htmlspecialchars($step_data['subject'] ?? 'No subject'); ?>
                                    </div>
                                    <div class="email-body">
                                        <?php echo nl2br(htmlspecialchars($step_data['message'] ?? 'No message')); ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div style="margin-top: 30px; padding: 20px; background: white; border-radius: 12px;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label style="margin-bottom: 10px; color: #333; font-weight: 600;">Unsubscribe Text / Link</label>
                                <div id="previewUnsubscribe" style="color: #666;">
                                    <?php echo htmlspecialchars($_SESSION['campaign_data']['step1']['unsubscribe_text'] ?? 'Enter unsubscribe email text/link'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- Navigation Buttons -->
                <div class="navigation-buttons">
                    <?php if ($step > 1): ?>
                    <button type="button" class="btn btn-prev" onclick="prevStep()">
                        <i class="fas fa-arrow-left"></i> Previous
                    </button>
                    <?php else: ?>
                    <div></div>
                    <?php endif; ?>
                    
                    <div style="display: flex; gap: 15px;">
                        <?php if ($step == 5): ?>
                        <button type="button" class="btn btn-save" onclick="saveAsDraft()">
                            <i class="fas fa-save"></i> Save as Draft
                        </button>
                        <button type="button" class="btn btn-launch" onclick="launchCampaign()">
                            <i class="fas fa-rocket"></i> Launch Campaign
                        </button>
                        <?php elseif ($step == 4): ?>
                        <button type="button" class="btn btn-save" onclick="saveStep()">
                            <i class="fas fa-save"></i> Save Step
                        </button>
                        <button type="button" class="btn btn-next" onclick="nextStep()">
                            Next <i class="fas fa-arrow-right"></i>
                        </button>
                        <?php else: ?>
                        <button type="button" class="btn btn-next" onclick="nextStep()">
                            Next <i class="fas fa-arrow-right"></i>
                        </button>
                        <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Sample Data Modal -->
    <div class="modal" id="sampleModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; padding: 20px;">
        <div class="modal-content" style="background: white; border-radius: 20px; padding: 40px; max-width: 800px; width: 100%; max-height: 90vh; overflow-y: auto;">
            <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #f0f0f0;">
                <h3 style="font-size: 24px; font-weight: 700; color: #333;">Sample CSV Format</h3>
                <button class="modal-close" onclick="closeSampleModal()" style="background: none; border: none; font-size: 28px; color: #666; cursor: pointer;">×</button>
            </div>
            
            <div style="margin-bottom: 24px;">
                <table style="width: 100%; border-collapse: collapse; border: 1px solid #e0e0e0;">
                    <thead>
                        <tr style="background: #f8f9fa;">
                            <th style="text-align: left; padding: 12px; font-weight: 600; color: #374151; border-bottom: 2px solid #e0e0e0;">CSV TITLE</th>
                            <th style="text-align: left; padding: 12px; font-weight: 600; color: #374151; border-bottom: 2px solid #e0e0e0;">SAMPLE DATA</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sampleData = [
                            'first_name' => 'Aarav',
                            'last_name' => 'Shah',
                            'company' => 'TechNova',
                            'email' => 'aarav.shah@technova.com',
                            'linkedin_link' => 'https://linkedin.com/in/aaravshah',
                            'job_position' => 'Marketing Manager',
                            'industry' => 'SaaS',
                            'country' => 'India',
                            'lead_status' => 'New',
                            'notes' => 'Interested in automation tools'
                        ];
                        
                        foreach ($sampleData as $title => $data):
                        ?>
                        <tr style="border-bottom: 1px solid #e0e0e0;">
                            <td style="padding: 12px; font-weight: 500; color: #374151; border-right: 1px solid #e0e0e0;">
                                {{<?php echo $title; ?>}}
                            </td>
                            <td style="padding: 12px; color: #6b7280;"><?php echo $data; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div style="margin-top: 24px;">
                <h4 style="font-size: 16px; font-weight: 600; color: #1f2937; margin-bottom: 12px;">Available Merge Tags:</h4>
                <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                    <?php
                    $mergeTags = ['first_name', 'last_name', 'company', 'email', 'linkedin_link', 'job_position', 'industry', 'country', 'lead_status', 'notes'];
                    foreach ($mergeTags as $tag) {
                        echo '<span style="padding: 6px 12px; background: #e5e7eb; border-radius: 4px; font-size: 12px; color: #374151;">{{' . $tag . '}}</span>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentStep = <?php echo $step; ?>;
        let currentStepNumber = <?php echo $current_step_number; ?>;
        let totalSteps = <?php echo $total_steps; ?>;
        let fileUploaded = <?php echo isset($_SESSION['campaign_data']['step3']['csv_uploaded']) && $_SESSION['campaign_data']['step3']['csv_uploaded'] ? 'true' : 'false'; ?>;
        let existingListUsed = <?php echo isset($_SESSION['campaign_data']['step3']['use_existing']) && $_SESSION['campaign_data']['step3']['use_existing'] ? 'true' : 'false'; ?>;
        
        document.addEventListener('DOMContentLoaded', function() {
            showStep(currentStep);
            updatePreview();
            
            // Setup file upload
            setupFileUpload();
            
            // Setup merge tags
            setupMergeTags();
            
            // Show file info if already uploaded
            if (fileUploaded) {
                document.getElementById('fileInfo').style.display = 'block';
            }
        });
        
        function showStep(step) {
            // Hide all forms
            for (let i = 1; i <= 5; i++) {
                const form = document.getElementById('step' + i + 'Form');
                if (form) {
                    form.classList.remove('active');
                }
            }
            
            // Show current form
            const currentForm = document.getElementById('step' + step + 'Form');
            if (currentForm) {
                currentForm.classList.add('active');
            }
            
            // Update sidebar active state
            updateSidebarSteps(step);
            
            // Update step indicator
            const indicator = document.querySelector('.step-indicator');
            if (indicator) {
                indicator.textContent = `Step ${step} of 5`;
            }
        }
        
        function updateSidebarSteps(step) {
            const steps = document.querySelectorAll('.progress-step');
            steps.forEach((stepElement, index) => {
                const stepNumber = index + 1;
                stepElement.classList.remove('active', 'completed');
                
                if (stepNumber < step) {
                    stepElement.classList.add('completed');
                } else if (stepNumber === step) {
                    stepElement.classList.add('active');
                }
            });
        }
        
        function navigateToStep(step) {
            // Always allow navigation to any step
            // Submit form with goto_step action
            const form = document.getElementById('step' + currentStep + 'Form');
            if (form) {
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'goto_step';
                
                const gotoInput = document.createElement('input');
                gotoInput.type = 'hidden';
                gotoInput.name = 'goto_step';
                gotoInput.value = step;
                
                form.appendChild(actionInput);
                form.appendChild(gotoInput);
                form.submit();
            }
        }
        
        function prevStep() {
            if (currentStep > 1) {
                // For step 4, handle substeps
                if (currentStep === 4 && currentStepNumber > 1) {
                    switchStep(currentStepNumber - 1);
                    return;
                }
                
                // Submit form with prev action
                const form = document.getElementById('step' + currentStep + 'Form');
                if (form) {
                    const actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'action';
                    actionInput.value = 'prev';
                    form.appendChild(actionInput);
                    form.submit();
                }
            }
        }
        
        function nextStep() {
            // Check if current step is incomplete
            if (isStepIncomplete(currentStep)) {
                if (!confirm('This step is incomplete. Continue anyway?')) {
                    return;
                }
            }
            
            if (currentStep < 5) {
                // For step 3, we need to submit the form to process the file
                if (currentStep === 3) {
                    const form = document.getElementById('step3Form');
                    if (form) {
                        const actionInput = document.createElement('input');
                        actionInput.type = 'hidden';
                        actionInput.name = 'action';
                        actionInput.value = 'next';
                        form.appendChild(actionInput);
                        form.submit();
                        return;
                    }
                }
                
                // For other steps, submit form with next action
                const form = document.getElementById('step' + currentStep + 'Form');
                if (form) {
                    const actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'action';
                    actionInput.value = 'next';
                    
                    form.appendChild(actionInput);
                    form.submit();
                }
            }
        }
        
        function saveStep() {
            // Always allow saving step even if incomplete
            saveCurrentStepData();
            alert('Step saved successfully!');
            
            // Mark step as completed in UI
            const currentTab = document.querySelector(`.step-tab[onclick*="${currentStepNumber}"]`);
            if (currentTab) {
                currentTab.classList.add('completed');
            }
        }
        
        function saveCurrentStepData() {
            // Get form data
            const form = document.getElementById('step4Form');
            const formData = new FormData(form);
            
            // Create hidden inputs for AJAX submission (simulated)
            const stepData = {
                type: currentStepNumber === 1 ? 'initial' : 'follow_up',
                delay_days: document.getElementById('delayDays') ? parseInt(document.getElementById('delayDays').value) : 0,
                delay_hours: document.getElementById('delayHours') ? parseInt(document.getElementById('delayHours').value) : 0,
                subject: document.getElementById('subject').value,
                message: document.getElementById('message').value
            };
            
            // Store in session storage
            let stepsData = JSON.parse(sessionStorage.getItem('campaign_steps') || '{}');
            stepsData[currentStepNumber] = stepData;
            sessionStorage.setItem('campaign_steps', JSON.stringify(stepsData));
        }
        
        function validateStep(step) {
            // Return true for all steps to allow navigation without validation
            return true;
        }
        
        function isStepIncomplete(step) {
            switch (step) {
                case 1:
                    const emailAccount = document.querySelector('input[name="email_account"]:checked');
                    const unsubscribeText = document.getElementById('unsubscribeText');
                    return !emailAccount || !unsubscribeText.value.trim();
                    
                case 2:
                    const checkedDays = document.querySelectorAll('#step2Form input[name="weekly_schedule[]"]:checked');
                    return checkedDays.length === 0;
                    
                case 3:
                    // For step 3, check if file is uploaded or existing list is used
                    if (!fileUploaded && !existingListUsed) {
                        // Check if there's a file selected
                        const fileInput = document.getElementById('csvFile');
                        if (fileInput.files.length === 0) {
                            return true;
                        }
                    }
                    return false;
                    
                case 4:
                    const subject = document.getElementById('subject');
                    const message = document.getElementById('message');
                    return !subject.value.trim() || !message.value.trim();
                    
                default:
                    return false;
            }
        }
        
        function selectEmailAccount(email) {
            // Update radio button
            const radio = document.querySelector(`input[name="email_account"][value="${email}"]`);
            if (radio) {
                radio.checked = true;
                
                // Update UI
                document.querySelectorAll('.email-option').forEach(option => {
                    option.classList.remove('selected');
                });
                radio.closest('.email-option').classList.add('selected');
            }
        }
        
        function handleFileSelect(input) {
            if (input.files.length) {
                const file = input.files[0];
                handleFile(file);
            }
        }
        
        function setupFileUpload() {
            const uploadArea = document.getElementById('uploadArea');
            const fileInput = document.getElementById('csvFile');
            const fileInfo = document.getElementById('fileInfo');
            const fileName = document.getElementById('fileName');
            const fileDetails = document.getElementById('fileDetails');
            
            if (!uploadArea) return;
            
            // Drag and drop events
            uploadArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('dragover');
            });
            
            uploadArea.addEventListener('dragleave', function() {
                this.classList.remove('dragover');
            });
            
            uploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('dragover');
                
                if (e.dataTransfer.files.length) {
                    const file = e.dataTransfer.files[0];
                    fileInput.files = e.dataTransfer.files;
                    handleFile(file);
                }
            });
        }
        
        function handleFile(file) {
            if (file.type !== 'text/csv' && !file.name.toLowerCase().endsWith('.csv')) {
                alert('Please upload a CSV file');
                return;
            }
            
            const fileInfo = document.getElementById('fileInfo');
            const fileName = document.getElementById('fileName');
            const fileDetails = document.getElementById('fileDetails');
            
            fileInfo.style.display = 'block';
            fileUploaded = true;
            existingListUsed = false;
            fileName.innerHTML = '<i class="fas fa-file-csv"></i> ' + file.name;
            fileDetails.textContent = 'CSV file - ' + formatFileSize(file.size) + ' - Processing...';
            
            // Show count of contacts
            const reader = new FileReader();
            reader.onload = function(e) {
                const content = e.target.result;
                const lines = content.split('\n').filter(line => line.trim() !== '');
                const contactCount = Math.max(0, lines.length - 1); // Subtract header
                fileDetails.textContent = 'CSV file - ' + formatFileSize(file.size) + ' - ' + contactCount + ' contacts found';
                
                // Auto-submit form to save file
                setTimeout(() => {
                    const form = document.getElementById('step3Form');
                    const actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'action';
                    actionInput.value = 'next';
                    
                    form.appendChild(actionInput);
                    form.submit();
                }, 1000);
            };
            reader.readAsText(file);
        }
        
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
        }
        
        function useExistingList() {
            const select = document.getElementById('existingList');
            if (!select.value) {
                alert('Please select a list');
                return;
            }
            
            existingListUsed = true;
            fileUploaded = false;
            
            // Submit form to save selection
            const form = document.getElementById('step3Form');
            const useExistingInput = document.createElement('input');
            useExistingInput.type = 'hidden';
            useExistingInput.name = 'use_existing';
            useExistingInput.value = '1';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'next';
            
            form.appendChild(useExistingInput);
            form.appendChild(actionInput);
            form.submit();
        }
        
        function downloadSample() {
            const csvContent = "first_name,last_name,company,email,linkedin_link,job_position,industry,country,lead_status,notes\n" +
                             "Aarav,Shah,TechNova,aarav.shah@technova.com,https://linkedin.com/in/aaravshah,Marketing Manager,SaaS,India,New,Interested in automation tools\n" +
                             "Priya,Patel,CloudTech,priya.patel@cloudtech.com,https://linkedin.com/in/priyapatel,Sales Director,Cloud Computing,USA,Qualified,Looking for email automation\n" +
                             "Rohan,Gupta,DataSoft,rohan.gupta@datasoft.com,https://linkedin.com/in/rohangupta,CTO,AI & ML,India,Hot,Interested in AI-powered outreach";
            
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'Sample_Prospect.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }
        
        function showSampleData() {
            document.getElementById('sampleModal').style.display = 'flex';
        }
        
        function closeSampleModal() {
            document.getElementById('sampleModal').style.display = 'none';
        }
        
        function switchStep(stepNumber) {
            // Save current step data first
            if (currentStep === 4) {
                saveCurrentStepData();
            }
            
            // Navigate to the selected step
            window.location.href = `campaign_create.php?step=4&substep=${stepNumber}&name=<?php echo urlencode($campaign_name); ?><?php echo $is_edit ? "&id=$campaign_id" : ""; ?>`;
        }
        
        function addNewStep() {
            // Save current step data
            if (currentStep === 4) {
                saveCurrentStepData();
            }
            
            // Add new step
            const newStepNumber = totalSteps + 1;
            
            // Create a form submission to add step
            const form = document.getElementById('step4Form');
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'add_step';
            
            form.appendChild(actionInput);
            form.submit();
        }
        
        function deleteStep(stepNumber) {
            if (totalSteps <= 1) {
                alert('You must have at least one email step.');
                return;
            }
            
            if (!confirm('Are you sure you want to remove this step?')) {
                return;
            }
            
            // Create form and submit
            const form = document.getElementById('step4Form');
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'delete_step';
            
            const deleteInput = document.createElement('input');
            deleteInput.type = 'hidden';
            deleteInput.name = 'delete_step';
            deleteInput.value = stepNumber;
            
            form.appendChild(actionInput);
            form.appendChild(deleteInput);
            form.submit();
        }
        
        function setupMergeTags() {
            document.querySelectorAll('.merge-tag').forEach(tag => {
                tag.addEventListener('click', function() {
                    const textarea = document.getElementById('message');
                    if (!textarea) return;
                    
                    const tagText = this.getAttribute('data-tag');
                    const start = textarea.selectionStart;
                    const end = textarea.selectionEnd;
                    
                    textarea.value = textarea.value.substring(0, start) + 
                                   tagText + 
                                   textarea.value.substring(end);
                    
                    textarea.focus();
                    textarea.selectionStart = textarea.selectionEnd = start + tagText.length;
                });
            });
        }
        
        function saveAsDraft() {
            if (confirm('Save this campaign as a draft? You can launch it later from the campaigns page.')) {
                // Submit form with save_draft action
                const form = document.getElementById('step5Form');
                if (form) {
                    // Add action input
                    const actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'action';
                    actionInput.value = 'save_draft';
                    form.appendChild(actionInput);
                    
                    // Submit the form
                    form.submit();
                }
            }
        }
        
        function launchCampaign() {
            // Check if required fields are filled for launch
            if (!validateCampaignForLaunch()) {
                return;
            }
            
            if (confirm('Launch this campaign? Each prospect will receive the full email sequence with follow-ups.')) {
                // Submit form with launch action
                const form = document.getElementById('step5Form');
                if (form) {
                    // Add action input
                    const actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'action';
                    actionInput.value = 'launch';
                    form.appendChild(actionInput);
                    
                    // Submit the form
                    form.submit();
                }
            }
        }
        
        function validateCampaignForLaunch() {
            // Check email account
            const emailAccount = document.querySelector('input[name="email_account"]:checked');
            if (!emailAccount) {
                alert('Please select an email account to launch the campaign');
                return false;
            }
            
            // Check unsubscribe text
            const unsubscribeText = document.getElementById('previewUnsubscribe')?.textContent;
            if (!unsubscribeText || unsubscribeText.trim() === 'Enter unsubscribe email text/link') {
                if (!confirm('You haven\'t set an unsubscribe text/link. Continue anyway?')) {
                    return false;
                }
            }
            
            // Check if there are email steps
            const stepCount = document.getElementById('previewSequence')?.textContent;
            if (!stepCount || stepCount.includes('0 steps')) {
                alert('Please add at least one email to your sequence');
                return false;
            }
            
            // Check if there are prospects
            const prospectCount = <?php echo $_SESSION['campaign_data']['step3']['prospect_count'] ?? 0; ?>;
            if (prospectCount === 0) {
                alert('Please upload a CSV file or select existing prospects');
                return false;
            }
            
            return true;
        }
        
        function updatePreview() {
            if (currentStep === 5) {
                // Update preview values
                const previewValues = {
                    'previewEmailAccount': document.querySelector('input[name="email_account"]:checked')?.value || 'No email selected',
                    'previewUnsubscribe': document.getElementById('unsubscribeText')?.value || 'Enter unsubscribe email text/link'
                };
                
                for (const [id, value] of Object.entries(previewValues)) {
                    const element = document.getElementById(id);
                    if (element) {
                        element.textContent = value;
                    }
                }
                
                // Update total emails count
                const prospectCount = <?php echo $_SESSION['campaign_data']['step3']['prospect_count'] ?? 0; ?>;
                const stepsCount = <?php echo count($_SESSION['campaign_data']['steps'] ?? []) ?: 1; ?>;
                const totalEmailsElement = document.getElementById('previewTotalEmails');
                if (totalEmailsElement && prospectCount > 0) {
                    totalEmailsElement.textContent = (prospectCount * stepsCount) + ' email' + ((prospectCount * stepsCount) !== 1 ? 's' : '');
                }
            }
        }
        
        // Close modal when clicking outside
        document.getElementById('sampleModal').addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });
    </script>
</body>
</html>
