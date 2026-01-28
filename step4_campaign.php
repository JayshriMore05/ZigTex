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
    <title>Create Campaign - Step 4: Content</title>
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

        .step-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid #f3f4f6;
        }

        .step-header h3 {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
        }

        .followup-select {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .followup-select select {
            padding: 6px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
        }

        .merge-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 24px;
            padding: 16px;
            background: #f9fafb;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }

        .merge-tag {
            padding: 6px 12px;
            background: white;
            border: 1px solid #d1d5db;
            color: #374151;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .merge-tag:hover {
            background: #f3f4f6;
            border-color: #9ca3af;
        }

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

        .form-group input, .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            color: #374151;
        }

        .form-group input:focus, .form-group textarea:focus {
            outline: none;
            border-color: #3b82f6;
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .add-step-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
        }

        .add-step-btn:hover {
            background: #e5e7eb;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Create Campaign</h1>
            <h2><?php echo $campaign_name; ?></h2>
        </div>

        <div class="campaign-card">
            <div class="step-header">
                <h3>Step 1 - Initial Email</h3>
            </div>

            <div class="merge-tags">
                <?php
                $mergeTags = ['first_name', 'last_name', 'company', 'email', 'linkedin_link', 'job_position', 'industry', 'country', 'lead_status', 'notes'];
                foreach ($mergeTags as $tag) {
                    echo '<span class="merge-tag" data-tag="{{' . $tag . '}}">{{' . $tag . '}}</span>';
                }
                ?>
            </div>

            <div class="form-group">
                <label>Subject Line</label>
                <input type="text" id="subject" placeholder="Hello" value="Hello">
            </div>

            <div class="form-group">
                <label>Message</label>
                <textarea id="message" placeholder="Write your email message here...">Hi {{first_name}},\n\nI hope this email finds you well. I noticed your work at {{company}} and was impressed with your contributions to the {{industry}} industry.\n\nBest regards,\nArpan</textarea>
            </div>

            <div style="margin-top: 32px; padding-top: 24px; border-top: 1px solid #f3f4f6;">
                <button class="add-step-btn" onclick="addFollowupStep()">
                    <i class="fas fa-plus"></i> Add Another Step
                </button>
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
                <li class="step-item active">
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
            <button type="button" class="btn" onclick="prevStep()">
                <i class="fas fa-arrow-left"></i> Previous
            </button>
            <button type="button" class="btn btn-primary" onclick="nextStep()">
                Next <i class="fas fa-arrow-right"></i>
            </button>
        </div>
    </div>

    <script>
        function prevStep() {
            window.location.href = 'step3_prospect.php?name=<?php echo urlencode($campaign_name); ?>';
        }
        
        function nextStep() {
            const subject = document.getElementById('subject').value;
            const message = document.getElementById('message').value;
            
            if (!subject.trim() || !message.trim()) {
                alert('Please fill in both subject and message fields');
                return;
            }
            
            // Save step 4 data
            const stepData = {
                subject: subject,
                message: message,
                steps: [
                    {
                        type: 'initial',
                        subject: subject,
                        message: message
                    }
                ]
            };
            
            localStorage.setItem('campaign_step4', JSON.stringify(stepData));
            
            // Go to step 5
            window.location.href = 'step5_preview.php?name=<?php echo urlencode($campaign_name); ?>';
        }
        
        function addFollowupStep() {
            alert('Adding follow-up step. In a real application, this would add another email step to the sequence.');
        }
        
        // Merge tag insertion
        document.querySelectorAll('.merge-tag').forEach(tag => {
            tag.addEventListener('click', function() {
                const textarea = document.getElementById('message');
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
        
        // Load saved data
        document.addEventListener('DOMContentLoaded', function() {
            const savedData = localStorage.getItem('campaign_step4');
            if (savedData) {
                const data = JSON.parse(savedData);
                document.getElementById('subject').value = data.subject || 'Hello';
                document.getElementById('message').value = data.message || '';
            }
        });
    </script>
</body>
</html>