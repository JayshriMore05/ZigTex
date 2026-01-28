<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$campaign_name = isset($_GET['name']) ? htmlspecialchars($_GET['name']) : 'Arpan\'s Campaign 13';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Campaign - Step 5: Preview & Start</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        body {
            background-color: #ffffff;
            color: #1f2937;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .header h1 {
            font-size: 32px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .header h2 {
            font-size: 24px;
            font-weight: 600;
            color: #3b82f6;
            margin-bottom: 24px;
        }

        .campaign-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 32px;
            margin-bottom: 32px;
        }

        .summary-section {
            margin-bottom: 32px;
        }

        .summary-section h3 {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid #f3f4f6;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .summary-item {
            padding: 16px;
            background: #f9fafb;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }

        .summary-label {
            display: block;
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 4px;
        }

        .summary-value {
            font-weight: 600;
            color: #1f2937;
            font-size: 14px;
        }

        .email-preview {
            margin-top: 24px;
            padding: 20px;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }

        .email-preview h4 {
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 12px;
        }

        .email-subject {
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e5e7eb;
        }

        .email-body {
            color: #4b5563;
            line-height: 1.6;
            white-space: pre-wrap;
        }

        .additional-settings {
            margin-top: 32px;
            padding: 24px;
            background: #f9fafb;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }

        .additional-settings h4 {
            font-size: 16px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 16px;
        }

        .settings-item {
            margin-bottom: 16px;
        }

        .settings-item:last-child {
            margin-bottom: 0;
        }

        .settings-label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #374151;
            font-size: 14px;
        }

        .settings-value {
            color: #6b7280;
            font-size: 14px;
        }

        .setup-steps {
            background: #f9fafb;
            border-radius: 8px;
            padding: 24px;
            margin-top: 32px;
        }

        .setup-steps h3 {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 20px;
        }

        .step-list {
            list-style: none;
        }

        .step-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .step-item:last-child {
            border-bottom: none;
        }

        .step-number {
            width: 32px;
            height: 32px;
            background: #10b981;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
        }

        .step-item.completed .step-number {
            background: #10b981;
        }

        .step-item.active .step-number {
            background: #3b82f6;
        }

        .step-item.pending .step-number {
            background: #9ca3af;
        }

        .step-details {
            flex: 1;
        }

        .step-details h4 {
            font-size: 14px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 4px;
        }

        .step-details p {
            font-size: 12px;
            color: #6b7280;
        }

        .navigation {
            display: flex;
            justify-content: space-between;
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid #e5e7eb;
        }

        .btn {
            padding: 12px 24px;
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

        .btn:hover {
            background: #f3f4f6;
        }

        .btn-primary {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }

        .btn-primary:hover {
            background: #2563eb;
        }

        .btn-success {
            background: #10b981;
            color: white;
            border-color: #10b981;
        }

        .btn-success:hover {
            background: #059669;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Create Campaign</h1>
            <h2><?php echo $campaign_name; ?></h2>
        </div>

        <div class="campaign-card">
            <div class="summary-section">
                <h3>Campaign Summary</h3>
                
                <div class="summary-grid">
                    <div class="summary-item">
                        <span class="summary-label">Email Account</span>
                        <span class="summary-value" id="previewEmailAccount">arpan@vconnectideas.com</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Timezone</span>
                        <span class="summary-value" id="previewTimezone">Europe/London</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Weekly Schedule</span>
                        <span class="summary-value" id="previewSchedule">Mon, Tue, Wed, Thu, Fri</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Sending Hours</span>
                        <span class="summary-value" id="previewHours">09:00 - 19:00</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Total Prospects</span>
                        <span class="summary-value" id="previewProspects">3</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Email Sequence</span>
                        <span class="summary-value" id="previewSequence">1 Initial Email</span>
                    </div>
                </div>
                
                <div class="email-preview">
                    <h4>Email Preview</h4>
                    <div class="email-subject" id="previewSubject">Hello</div>
                    <div class="email-body" id="previewBody">Hi {{first_name}},

I hope this email finds you well. I noticed your work at {{company}} and was impressed with your contributions to the {{industry}} industry.

Best regards,
Arpan</div>
                </div>
            </div>
            
            <div class="additional-settings">
                <h4>Additional Settings</h4>
                
                <div class="settings-item">
                    <span class="settings-label">Unsubscribe Text / Link</span>
                    <div class="settings-value" id="previewUnsubscribe">Enter unsubscribe email text/link</div>
                </div>
                
                <div class="settings-item">
                    <span class="settings-label">Email Priority</span>
                    <div class="settings-value">Equally divided between opening and follow-up emails</div>
                </div>
            </div>
        </div>

        <div class="setup-steps">
            <h3>Setting up your campaign</h3>
            <ul class="step-list">
                <li class="step-item completed">
                    <div class="step-number"><i class="fas fa-check"></i></div>
                    <div class="step-details">
                        <h4>Channel Setup</h4>
                        <p>Connect email accounts & setup channels.</p>
                    </div>
                </li>
                <li class="step-item completed">
                    <div class="step-number"><i class="fas fa-check"></i></div>
                    <div class="step-details">
                        <h4>Campaign Settings</h4>
                        <p>Configure campaign name, goal, and rules.</p>
                    </div>
                </li>
                <li class="step-item completed">
                    <div class="step-number"><i class="fas fa-check"></i></div>
                    <div class="step-details">
                        <h4>Prospect</h4>
                        <p>Add or import your prospects list.</p>
                    </div>
                </li>
                <li class="step-item completed">
                    <div class="step-number"><i class="fas fa-check"></i></div>
                    <div class="step-details">
                        <h4>Content</h4>
                        <p>Write the email sequence.</p>
                    </div>
                </li>
                <li class="step-item active">
                    <div class="step-number">5</div>
                    <div class="step-details">
                        <h4>Preview & Start</h4>
                        <p>Review everything before launching.</p>
                    </div>
                </li>
            </ul>
        </div>

        <div class="navigation">
            <button type="button" class="btn" onclick="prevStep()">
                <i class="fas fa-arrow-left"></i> Previous
            </button>
            <div>
                <button type="button" class="btn" onclick="saveAsDraft()">
                    <i class="fas fa-save"></i> Save as Draft
                </button>
                <button type="button" class="btn btn-success" onclick="launchCampaign()">
                    <i class="fas fa-rocket"></i> Launch Campaign
                </button>
            </div>
        </div>
    </div>

    <script>
        function prevStep() {
            window.location.href = 'step4_content.php?name=<?php echo urlencode($campaign_name); ?>';
        }
        
        function saveAsDraft() {
            if (confirm('Save this campaign as a draft? You can launch it later from the campaigns page.')) {
                saveCampaign('draft');
            }
        }
        
        function launchCampaign() {
            if (confirm('Launch this campaign? Emails will start sending according to your schedule.')) {
                saveCampaign('launch');
            }
        }
        
        function saveCampaign(action) {
            // Collect all data from all steps
            const step1Data = JSON.parse(localStorage.getItem('campaign_step1') || '{}');
            const step2Data = JSON.parse(localStorage.getItem('campaign_step2') || '{}');
            const step3Data = JSON.parse(localStorage.getItem('campaign_step3') || '{}');
            const step4Data = JSON.parse(localStorage.getItem('campaign_step4') || '{}');
            
            // Prepare data for submission
            const campaignData = {
                campaign_name: '<?php echo $campaign_name; ?>',
                unsubscribe_text: step1Data.unsubscribe_text || '',
                timezone: step2Data.timezone || 'Europe/London',
                weekly_schedule: step2Data.weekly_schedule || ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
                start_time: step2Data.start_time || '09:00',
                end_time: step2Data.end_time || '19:00',
                csv_uploaded: step3Data.csv_uploaded || false,
                file_name: step3Data.file_name || '',
                subject: step4Data.subject || 'Hello',
                message: step4Data.message || '',
                status: action === 'launch' ? 'running' : 'draft',
                launch_action: action
            };
            
            // Submit via AJAX
            fetch('campaign_save.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(campaignData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    
                    // Clear saved data
                    localStorage.removeItem('campaign_step1');
                    localStorage.removeItem('campaign_step2');
                    localStorage.removeItem('campaign_step3');
                    localStorage.removeItem('campaign_step4');
                    
                    // Redirect to campaigns page
                    window.location.href = 'campaign.php';
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error saving campaign: ' + error.message);
            });
        }
        
        // Load data from all steps and populate preview
        document.addEventListener('DOMContentLoaded', function() {
            // Step 1 data
            const step1Data = JSON.parse(localStorage.getItem('campaign_step1') || '{}');
            document.getElementById('previewUnsubscribe').textContent = 
                step1Data.unsubscribe_text || 'Enter unsubscribe email text/link';
            
            // Step 2 data
            const step2Data = JSON.parse(localStorage.getItem('campaign_step2') || '{}');
            document.getElementById('previewTimezone').textContent = 
                step2Data.timezone || 'Europe/London';
            document.getElementById('previewSchedule').textContent = 
                (step2Data.weekly_schedule || ['Mon', 'Tue', 'Wed', 'Thu', 'Fri']).join(', ');
            document.getElementById('previewHours').textContent = 
                (step2Data.start_time || '09:00') + ' - ' + (step2Data.end_time || '19:00');
            
            // Step 3 data
            const step3Data = JSON.parse(localStorage.getItem('campaign_step3') || '{}');
            document.getElementById('previewProspects').textContent = 
                step3Data.csv_uploaded ? '3' : '0';
            
            // Step 4 data
            const step4Data = JSON.parse(localStorage.getItem('campaign_step4') || '{}');
            document.getElementById('previewSubject').textContent = 
                step4Data.subject || 'Hello';
            document.getElementById('previewBody').textContent = 
                step4Data.message || 'Hi {{first_name}},\n\nI hope this email finds you well...';
            
            // Count email steps
            const stepCount = step4Data.steps ? step4Data.steps.length : 1;
            document.getElementById('previewSequence').textContent = 
                stepCount + (stepCount === 1 ? ' Initial Email' : ' Email Steps');
        });
    </script>
</body>
</html>