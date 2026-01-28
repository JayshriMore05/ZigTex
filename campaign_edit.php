<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$campaign_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Sample campaign data - In real app, fetch from database
$campaign = [
    'id' => $campaign_id ?: 13,
    'name' => $campaign_id ? 'Arpan\'s Campaign 13' : 'Arpan\'s Campaign 13',
    'description' => $campaign_id ? 'Outreach campaign targeting technology companies' : '',
    'status' => $campaign_id ? 'draft' : 'draft',
    'timezone' => 'Europe/London',
    'weekly_schedule' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
    'sending_hours' => ['09:00', '19:00'],
    'email_account' => 'arpan@vconnectidees.com',
    'subject' => 'Hello',
    'email_body' => '{{first_name}} {{last_name}}',
    'unsubscribe_text' => '',
    'email_priority' => 'equally_divided'
];

$is_edit = $campaign_id > 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_edit ? 'Edit Campaign' : 'Create Campaign'; ?> - ZigTex</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/campaign.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ===== CREATE/EDIT CAMPAIGN STYLES ===== */
        .campaign-edit-container {
            background: white;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
        }

        .campaign-header {
            padding: 24px 32px;
            border-bottom: 1px solid #e5e7eb;
        }

        .campaign-header h2 {
            font-size: 20px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 4px;
        }

        .campaign-progress {
            padding: 0 32px 24px;
            border-bottom: 1px solid #e5e7eb;
        }

        .progress-steps {
            display: flex;
            gap: 40px;
        }

        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            position: relative;
        }

        .step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 18px;
            left: 50px;
            right: -40px;
            height: 2px;
            background: #e5e7eb;
            z-index: 1;
        }

        .step.completed:not(:last-child)::after {
            background: #10b981;
        }

        .step-circle {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: #9ca3af;
            background: #f3f4f6;
            border: 2px solid #e5e7eb;
            position: relative;
            z-index: 2;
        }

        .step.active .step-circle {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }

        .step.completed .step-circle {
            background: #10b981;
            color: white;
            border-color: #10b981;
        }

        .step-label {
            font-size: 12px;
            color: #6b7280;
            text-align: center;
            font-weight: 500;
        }

        .step.active .step-label {
            color: #3b82f6;
        }

        .step.completed .step-label {
            color: #10b981;
        }

        /* ===== FORM CONTENT ===== */
        .form-content {
            padding: 32px;
        }

        .form-step {
            display: none;
        }

        .form-step.active {
            display: block;
        }

        .form-section {
            margin-bottom: 32px;
        }

        .section-header {
            margin-bottom: 24px;
        }

        .section-header h3 {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .section-header p {
            color: #6b7280;
            font-size: 14px;
        }

        /* ===== FORM ELEMENTS ===== */
        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #374151;
            font-size: 14px;
        }

        .form-group label.required::after {
            content: ' *';
            color: #ef4444;
        }

        input, select, textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            color: #374151;
            background: white;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #3b82f6;
        }

        .form-help {
            display: block;
            margin-top: 6px;
            color: #6b7280;
            font-size: 12px;
        }

        /* ===== CHECKBOX GROUPS ===== */
        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 8px;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .checkbox-label:hover {
            background: #f3f4f6;
        }

        .checkbox-label input[type="checkbox"] {
            width: auto;
            margin: 0;
        }

        .checkbox-label span {
            font-size: 14px;
            color: #374151;
        }

        /* ===== TIME SELECTOR ===== */
        .time-selector {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .time-selector select {
            width: auto;
            min-width: 120px;
        }

        .time-separator {
            color: #6b7280;
            font-weight: 500;
        }

        /* ===== MERGE TAGS ===== */
        .merge-tags {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 24px;
        }

        .merge-tags h4 {
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 12px;
        }

        .tags-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 8px;
        }

        .tag-btn {
            padding: 8px 12px;
            background: white;
            border: 1px solid #d1d5db;
            color: #374151;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            text-align: center;
            transition: all 0.2s;
        }

        .tag-btn:hover {
            background: #f3f4f6;
            border-color: #9ca3af;
        }

        /* ===== EMAIL EDITOR ===== */
        .email-editor {
            border: 1px solid #d1d5db;
            border-radius: 6px;
            overflow: hidden;
        }

        .editor-content {
            min-height: 120px;
            padding: 16px;
        }

        .editor-content textarea {
            width: 100%;
            min-height: 100px;
            border: none;
            padding: 0;
            resize: vertical;
            font-size: 14px;
        }

        .editor-content textarea:focus {
            outline: none;
            border: none;
        }

        /* ===== EMAIL ACCOUNT CONNECTION ===== */
        .email-account {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 24px;
        }

        .email-account-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .email-account-header h4 {
            font-size: 16px;
            font-weight: 600;
            color: #0369a1;
        }

        .email-status {
            font-size: 12px;
            padding: 4px 8px;
            background: #dcfce7;
            color: #166534;
            border-radius: 4px;
            font-weight: 500;
        }

        .email-details {
            font-size: 14px;
            color: #0c4a6e;
        }

        /* ===== SETTINGS SUMMARY ===== */
        .settings-summary {
            background: #f9fafb;
            border-radius: 8px;
            padding: 20px;
            border: 1px solid #e5e7eb;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .summary-item:last-child {
            border-bottom: none;
        }

        .summary-label {
            color: #6b7280;
            font-size: 14px;
        }

        .summary-value {
            font-weight: 500;
            color: #1f2937;
            font-size: 14px;
        }

        /* ===== UNSUBSCRIBE SETTINGS ===== */
        .unsubscribe-settings {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 20px;
        }

        .unsubscribe-header {
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 12px;
        }

        /* ===== FORM ACTIONS ===== */
        .form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 24px 32px;
            border-top: 1px solid #e5e7eb;
            background: #f9fafb;
        }

        .action-btn {
            padding: 10px 24px;
            border-radius: 6px;
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            border: 1px solid #d1d5db;
            background: white;
            color: #374151;
        }

        .action-btn:hover {
            background: #f3f4f6;
        }

        .action-btn.primary {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }

        .action-btn.primary:hover {
            background: #2563eb;
        }

        .action-btn.secondary {
            background: #6b7280;
            color: white;
            border-color: #6b7280;
        }

        .action-btn.secondary:hover {
            background: #4b5563;
        }

        .action-btn.launch {
            background: #10b981;
            color: white;
            border-color: #10b981;
        }

        .action-btn.launch:hover {
            background: #059669;
        }

        .step-indicator {
            font-size: 14px;
            color: #6b7280;
        }

        /* ===== RESPONSIVE DESIGN ===== */
        @media (max-width: 768px) {
            .campaign-header,
            .campaign-progress,
            .form-content {
                padding: 20px;
            }

            .progress-steps {
                flex-direction: column;
                gap: 20px;
            }

            .step:not(:last-child)::after {
                display: none;
            }

            .form-actions {
                flex-direction: column;
                gap: 16px;
            }

            .action-btn {
                width: 100%;
                justify-content: center;
            }
        }

        /* ===== REMOVE FOCUS OUTLINES ===== */
        *:focus {
            outline: none !important;
            box-shadow: none !important;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <?php include 'components/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Campaign Edit Container -->
            <div class="campaign-edit-container">
                <!-- Header -->
                <div class="campaign-header">
                    <h2>Arpan's Campaign 13</h2>
                </div>

                <!-- Progress Steps -->
                <div class="campaign-progress">
                    <div class="progress-steps">
                        <div class="step completed" data-step="1">
                            <div class="step-circle"><i class="fas fa-check"></i></div>
                            <div class="step-label">Channel Setup</div>
                        </div>
                        <div class="step completed" data-step="2">
                            <div class="step-circle"><i class="fas fa-check"></i></div>
                            <div class="step-label">Campaign Settings</div>
                        </div>
                        <div class="step completed" data-step="3">
                            <div class="step-circle"><i class="fas fa-check"></i></div>
                            <div class="step-label">Prospect</div>
                        </div>
                        <div class="step active" data-step="4">
                            <div class="step-circle">4</div>
                            <div class="step-label">Content</div>
                        </div>
                        <div class="step" data-step="5">
                            <div class="step-circle">5</div>
                            <div class="step-label">Preview & Start</div>
                        </div>
                    </div>
                </div>

                <!-- Form Content -->
                <form id="campaignForm" method="POST" action="campaign_save.php">
                    <input type="hidden" name="campaign_id" value="<?php echo $campaign['id']; ?>">
                    <input type="hidden" name="is_edit" value="<?php echo $is_edit ? '1' : '0'; ?>">
                    
                    <div class="form-content">
                        <!-- Step 1: Channel Setup (Completed) -->
                        <div class="form-step" id="step1">
                            <div class="email-account">
                                <div class="email-account-header">
                                    <h4>Connected arpan@vconnectidees.com</h4>
                                    <span class="email-status">Connected</span>
                                </div>
                                <div class="email-details">
                                    Your email account is successfully connected and ready to send campaigns.
                                </div>
                            </div>

                            <div class="unsubscribe-settings">
                                <div class="unsubscribe-header">Unsubscribe Email Text/Link <span style="color: #ef4444;">*</span></div>
                                <input type="text" placeholder="Enter unsubscribe email text/link" style="margin-top: 8px;" 
                                       value="<?php echo htmlspecialchars($campaign['unsubscribe_text']); ?>">
                            </div>

                            <div style="margin-top: 24px;">
                                <div class="unsubscribe-header">Email Priority</div>
                                <div style="margin-top: 8px; color: #6b7280; font-size: 14px;">
                                    Equally divided between opening and follow-up emails
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Campaign Settings (Completed) -->
                        <div class="form-step" id="step2">
                            <div class="form-section">
                                <div class="section-header">
                                    <h3>Timezone <span style="color: #ef4444;">*</span></h3>
                                    <p>Select the timezone for your campaign</p>
                                </div>
                                
                                <div class="form-group">
                                    <select id="timezone">
                                        <option value="Europe/London" <?php echo $campaign['timezone'] == 'Europe/London' ? 'selected' : ''; ?>>GMT +0:00 Europe/London</option>
                                        <option value="Asia/Kolkata">GMT +5:30 Asia/Kolkata</option>
                                        <option value="America/Los_Angeles">GMT -8:00 America/Los_Angeles</option>
                                        <option value="America/New_York">GMT -5:00 America/New_York</option>
                                    </select>
                                </div>

                                <div class="section-header">
                                    <h3>Weekly Schedule <span style="color: #ef4444;">*</span></h3>
                                    <p>Choose which days your campaign should run</p>
                                </div>
                                
                                <div class="checkbox-group">
                                    <?php 
                                    $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                                    foreach ($days as $day): 
                                    ?>
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="weekly_schedule[]" value="<?php echo $day; ?>"
                                            <?php echo in_array($day, $campaign['weekly_schedule']) ? 'checked' : ''; ?>>
                                        <span><?php echo $day; ?></span>
                                    </label>
                                    <?php endforeach; ?>
                                </div>

                                <div class="section-header">
                                    <h3>Sending Hours <span style="color: #ef4444;">*</span></h3>
                                    <p>Define when messages should be sent</p>
                                </div>
                                
                                <div class="form-group">
                                    <div style="color: #6b7280; font-size: 14px; margin-bottom: 8px;">Start Time - End Time</div>
                                    <div class="time-selector">
                                        <select id="startTime">
                                            <option value="09:00" <?php echo $campaign['sending_hours'][0] == '09:00' ? 'selected' : ''; ?>>09:00 AM</option>
                                            <option value="08:00">08:00 AM</option>
                                            <option value="10:00">10:00 AM</option>
                                            <option value="11:00">11:00 AM</option>
                                        </select>
                                        <span class="time-separator">to</span>
                                        <select id="endTime">
                                            <option value="19:00" <?php echo $campaign['sending_hours'][1] == '19:00' ? 'selected' : ''; ?>>07:00 PM</option>
                                            <option value="18:00">06:00 PM</option>
                                            <option value="20:00">08:00 PM</option>
                                            <option value="21:00">09:00 PM</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Prospects (Completed) -->
                        <div class="form-step" id="step3">
                            <div class="form-section">
                                <div class="section-header">
                                    <h3>Prospect List</h3>
                                    <p>Add or import your prospects list</p>
                                </div>
                                
                                <div class="settings-summary">
                                    <div class="summary-item">
                                        <span class="summary-label">Total Prospects</span>
                                        <span class="summary-value">245</span>
                                    </div>
                                    <div class="summary-item">
                                        <span class="summary-label">Valid Emails</span>
                                        <span class="summary-value">238</span>
                                    </div>
                                    <div class="summary-item">
                                        <span class="summary-label">Missing Emails</span>
                                        <span class="summary-value">7</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 4: Content (Active) -->
                        <div class="form-step active" id="step4">
                            <div class="form-section">
                                <div class="section-header">
                                    <h3>Create Message Sequence</h3>
                                    <p>Write the email sequence for your campaign</p>
                                </div>

                                <div class="form-group">
                                    <label>Step 1: Initial Email</label>
                                    
                                    <div class="merge-tags">
                                        <h4>Available Merge Tags</h4>
                                        <div class="tags-grid">
                                            <button type="button" class="tag-btn" data-tag="{{first_name}}">{{first_name}}</button>
                                            <button type="button" class="tag-btn" data-tag="{{last_name}}">{{last_name}}</button>
                                            <button type="button" class="tag-btn" data-tag="{{company}}">{{company}}</button>
                                            <button type="button" class="tag-btn" data-tag="{{email}}">{{email}}</button>
                                            <button type="button" class="tag-btn" data-tag="{{linkedin_link}}">{{linkedin_link}}</button>
                                            <button type="button" class="tag-btn" data-tag="{{dob_position}}">{{dob_position}}</button>
                                            <button type="button" class="tag-btn" data-tag="{{industry}}">{{industry}}</button>
                                            <button type="button" class="tag-btn" data-tag="{{country}}">{{country}}</button>
                                            <button type="button" class="tag-btn" data-tag="{{level_status}}">{{level_status}}</button>
                                            <button type="button" class="tag-btn" data-tag="{{index}}">{{index}}</button>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Subject Line (Variant)</label>
                                    <input type="text" id="emailSubject" placeholder="Hello" 
                                           value="<?php echo htmlspecialchars($campaign['subject']); ?>">
                                </div>

                                <div class="form-group">
                                    <label>Message</label>
                                    <div class="email-editor">
                                        <div class="editor-content">
                                            <textarea id="emailBody" placeholder="Write your email content here..."><?php echo htmlspecialchars($campaign['email_body']); ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 5: Preview & Start -->
                        <div class="form-step" id="step5">
                            <div class="form-section">
                                <div class="section-header">
                                    <h3>Campaign Summary</h3>
                                    <p>Review everything before launching</p>
                                </div>

                                <div class="settings-summary">
                                    <div class="summary-item">
                                        <span class="summary-label">Email Account</span>
                                        <span class="summary-value">arpan@vconnectidees.com</span>
                                    </div>
                                    <div class="summary-item">
                                        <span class="summary-label">Timezone</span>
                                        <span class="summary-value">Europe/London</span>
                                    </div>
                                    <div class="summary-item">
                                        <span class="summary-label">Weekly Schedule</span>
                                        <span class="summary-value" id="summarySchedule">Mon, Tue, Wed, Thu, Fri</span>
                                    </div>
                                    <div class="summary-item">
                                        <span class="summary-label">Sending Hours</span>
                                        <span class="summary-value" id="summaryHours">09:00 – 19:00</span>
                                    </div>
                                    <div class="summary-item">
                                        <span class="summary-label">Total Prospects</span>
                                        <span class="summary-value">245</span>
                                    </div>
                                    <div class="summary-item">
                                        <span class="summary-label">Email Sequence</span>
                                        <span class="summary-value">1 Initial Email</span>
                                    </div>
                                </div>

                                <div class="unsubscribe-settings" style="margin-top: 24px;">
                                    <div class="unsubscribe-header">Additional Settings</div>
                                    <div style="margin-top: 12px;">
                                        <div style="color: #6b7280; font-size: 14px; margin-bottom: 8px;">Unsubscribe Text / Link</div>
                                        <input type="text" placeholder="Enter unsubscribe text or link">
                                    </div>
                                    <div style="margin-top: 12px;">
                                        <div style="color: #6b7280; font-size: 14px; margin-bottom: 8px;">Email Priority</div>
                                        <div style="color: #6b7280; font-size: 14px;">
                                            Equally divided between opening and follow-up emails
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="form-actions">
                        <button type="button" class="action-btn" id="prevBtn">
                            <i class="fas fa-arrow-left"></i> Previous
                        </button>
                        
                        <div class="step-indicator">
                            Step <span id="currentStep">4</span> of 5
                        </div>
                        
                        <button type="button" class="action-btn primary" id="nextBtn">
                            Next <i class="fas fa-arrow-right"></i>
                        </button>
                        
                        <button type="submit" class="action-btn launch" id="launchBtn" style="display: none;">
                            <i class="fas fa-rocket"></i> Launch
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Navigation logic
            const steps = document.querySelectorAll('.step');
            const formSteps = document.querySelectorAll('.form-step');
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');
            const launchBtn = document.getElementById('launchBtn');
            const currentStepEl = document.getElementById('currentStep');
            let currentStep = 4; // Start with step 4 (Content)

            function updateNavigation() {
                // Hide all form steps
                formSteps.forEach(step => {
                    step.classList.remove('active');
                });
                
                // Show current form step
                formSteps[currentStep].classList.add('active');
                
                // Update progress steps
                steps.forEach((step, index) => {
                    step.classList.remove('active', 'completed');
                    if (index < currentStep) {
                        step.classList.add('completed');
                    } else if (index === currentStep) {
                        step.classList.add('active');
                    }
                });
                
                // Update current step indicator
                currentStepEl.textContent = currentStep + 1;
                
                // Update buttons
                if (currentStep === 0) {
                    prevBtn.style.display = 'none';
                } else {
                    prevBtn.style.display = 'inline-flex';
                }
                
                if (currentStep === formSteps.length - 1) {
                    nextBtn.style.display = 'none';
                    launchBtn.style.display = 'inline-flex';
                } else {
                    nextBtn.style.display = 'inline-flex';
                    launchBtn.style.display = 'none';
                }
            }

            // Navigation button handlers
            prevBtn.addEventListener('click', () => {
                if (currentStep > 0) {
                    currentStep--;
                    updateNavigation();
                }
            });

            nextBtn.addEventListener('click', () => {
                if (currentStep < formSteps.length - 1) {
                    currentStep++;
                    updateNavigation();
                }
            });

            // Merge tag insertion
            document.querySelectorAll('.tag-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const tag = this.getAttribute('data-tag');
                    const textarea = document.getElementById('emailBody');
                    const start = textarea.selectionStart;
                    const end = textarea.selectionEnd;
                    
                    textarea.value = textarea.value.substring(0, start) + 
                                   tag + 
                                   textarea.value.substring(end);
                    
                    textarea.focus();
                    textarea.selectionStart = textarea.selectionEnd = start + tag.length;
                });
            });

            // Update summary
            function updateSummary() {
                // Update schedule summary
                const selectedDays = Array.from(document.querySelectorAll('input[name="weekly_schedule[]"]:checked'))
                    .map(cb => cb.value);
                document.getElementById('summarySchedule').textContent = selectedDays.join(', ') || 'No days selected';
                
                // Update hours summary
                const startTime = document.getElementById('startTime').value;
                const endTime = document.getElementById('endTime').value;
                document.getElementById('summaryHours').textContent = `${startTime} – ${endTime}`;
            }

            // Listen for changes to update summary
            document.querySelectorAll('input[name="weekly_schedule[]"], #startTime, #endTime').forEach(element => {
                element.addEventListener('change', updateSummary);
            });

            // Initialize summary
            updateSummary();
            updateNavigation();

            // Remove focus outlines
            document.addEventListener('focusin', function(e) {
                if (e.target.tagName === 'INPUT' || 
                    e.target.tagName === 'TEXTAREA' || 
                    e.target.tagName === 'SELECT' ||
                    e.target.tagName === 'BUTTON') {
                    e.target.blur();
                }
            });
        });
    </script>
</body>
</html>