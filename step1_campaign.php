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
    <title>Create Campaign - Step 1: Channel Setup</title>
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

        .campaign-name {
            font-size: 20px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid #f3f4f6;
        }

        .email-account {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 24px;
        }

        .email-account h4 {
            font-size: 16px;
            font-weight: 600;
            color: #0369a1;
            margin-bottom: 8px;
        }

        .email-status {
            font-size: 14px;
            color: #0c4a6e;
        }

        .settings-section {
            margin-top: 32px;
        }

        .settings-section h3 {
            font-size: 16px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 12px;
        }

        .settings-section label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #374151;
            font-size: 14px;
        }

        .settings-section label span {
            color: #ef4444;
        }

        .settings-section input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            color: #374151;
        }

        .settings-section input:focus {
            outline: none;
            border-color: #3b82f6;
        }

        .email-priority {
            margin-top: 24px;
            padding: 16px;
            background: #f9fafb;
            border-radius: 8px;
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
            background: #3b82f6;
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
            justify-content: flex-end;
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

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
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
            <div class="campaign-name"><?php echo $campaign_name; ?></div>
            
            <div class="email-account">
                <h4>Connected arpan@vconnectideas.com</h4>
                <div class="email-status">
                    Your email account is successfully connected and ready to send campaigns.
                </div>
            </div>

            <div class="settings-section">
                <h3>Additional Settings</h3>
                
                <label>Unsubscribe Email Text/Link <span>*</span></label>
                <input type="text" id="unsubscribeText" placeholder="Enter unsubscribe email text/link">
                
                <div class="email-priority">
                    Email priority equally divided between opening and follow-up emails
                </div>
            </div>
        </div>

        <div class="setup-steps">
            <h3>Setting up your campaign</h3>
            <ul class="step-list">
                <li class="step-item active">
                    <div class="step-number">1</div>
                    <div class="step-details">
                        <h4>Channel Setup</h4>
                        <p>Connect email accounts & setup channels.</p>
                    </div>
                </li>
                <li class="step-item pending">
                    <div class="step-number">2</div>
                    <div class="step-details">
                        <h4>Campaign Settings</h4>
                        <p>Configure campaign name, goal, and rules.</p>
                    </div>
                </li>
                <li class="step-item pending">
                    <div class="step-number">3</div>
                    <div class="step-details">
                        <h4>Prospect</h4>
                        <p>Add or import your prospects list.</p>
                    </div>
                </li>
                <li class="step-item pending">
                    <div class="step-number">4</div>
                    <div class="step-details">
                        <h4>Content</h4>
                        <p>Write the email sequence.</p>
                    </div>
                </li>
                <li class="step-item pending">
                    <div class="step-number">5</div>
                    <div class="step-details">
                        <h4>Preview & Start</h4>
                        <p>Review everything before launching.</p>
                    </div>
                </li>
            </ul>
        </div>

        <div class="navigation">
            <button type="button" class="btn" onclick="window.location.href='campaign.php'">
                <i class="fas fa-times"></i> Cancel
            </button>
            <button type="button" class="btn btn-primary" id="nextBtn" onclick="nextStep()">
                Next <i class="fas fa-arrow-right"></i>
            </button>
        </div>
    </div>

    <script>
        function nextStep() {
            const unsubscribeText = document.getElementById('unsubscribeText').value;
            
            if (!unsubscribeText.trim()) {
                alert('Please enter unsubscribe email text/link');
                return;
            }
            
            // Save step 1 data (you can use localStorage or AJAX here)
            const stepData = {
                unsubscribe_text: unsubscribeText,
                campaign_name: '<?php echo $campaign_name; ?>'
            };
            
            localStorage.setItem('campaign_step1', JSON.stringify(stepData));
            
            // Go to step 2
            window.location.href = 'step2_settings.php?name=<?php echo urlencode($campaign_name); ?>';
        }
        
        // Load saved data if exists
        document.addEventListener('DOMContentLoaded', function() {
            const savedData = localStorage.getItem('campaign_step1');
            if (savedData) {
                const data = JSON.parse(savedData);
                document.getElementById('unsubscribeText').value = data.unsubscribe_text || '';
            }
        });
    </script>
</body>
</html>