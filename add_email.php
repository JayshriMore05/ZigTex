<?php
// add_email.php - Simplified version
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$redirect = $_GET['redirect'] ?? 'campaign_create.php';
$step = $_GET['step'] ?? 1;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $display_name = $_POST['display_name'] ?? '';
    $is_default = isset($_POST['is_default']) ? 1 : 0;
    
    if (!empty($email)) {
        try {
            $db = db();
            
            // Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['error'] = 'Please enter a valid email address';
            } else {
                // Check if email already exists for this user
                $stmt = $db->prepare("SELECT id FROM user_email_accounts WHERE user_id = ? AND email = ?");
                $stmt->execute([$user_id, $email]);
                if ($stmt->fetch()) {
                    $_SESSION['error'] = 'This email is already added to your account';
                } else {
                    // If setting as default, unset other defaults
                    if ($is_default) {
                        $stmt = $db->prepare("UPDATE user_email_accounts SET is_default = 0 WHERE user_id = ?");
                        $stmt->execute([$user_id]);
                    }
                    
                    // Insert new email account (simplified - no SMTP settings needed)
                    $stmt = $db->prepare("INSERT INTO user_email_accounts 
                                        (user_id, email, display_name, is_default, is_verified, created_at)
                                        VALUES (?, ?, ?, ?, 1, NOW())");
                    $stmt->execute([$user_id, $email, $display_name, $is_default]);
                    
                    $_SESSION['success'] = 'Email account added successfully!';
                    header("Location: $redirect?step=$step");
                    exit();
                }
            }
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'Failed to add email account: ' . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = 'Please enter email address';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Email Account - Email Campaigns</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            max-width: 500px;
            width: 100%;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .header h1 {
            font-size: 36px;
            font-weight: 800;
            color: #333;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .header p {
            font-size: 16px;
            color: #666;
        }

        .form-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
            font-size: 15px;
        }

        .form-group label.required::after {
            content: ' *';
            color: #f44336;
        }

        input[type="text"],
        input[type="email"] {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 15px;
            color: #333;
            background: white;
            transition: all 0.3s ease;
        }

        input:focus {
            outline: none;
            border-color: #2196f3;
            box-shadow: 0 5px 15px rgba(33, 150, 243, 0.2);
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 12px;
            border: 2px solid #e0e0e0;
        }

        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .checkbox-group label {
            margin-bottom: 0;
            cursor: pointer;
            flex: 1;
        }

        .btn {
            padding: 14px 28px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            border: none;
            transition: all 0.3s ease;
            width: 100%;
            justify-content: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            margin-top: 10px;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: #f8f9fa;
            color: #666;
            border: 2px solid #e0e0e0;
            margin-top: 15px;
        }

        .btn-secondary:hover {
            background: #e0e0e0;
            color: #333;
        }

        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #f44336;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .success-message {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #4caf50;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 14px;
            color: #1565c0;
        }

        .info-box i {
            margin-right: 10px;
        }
        
        .quick-guide {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 14px;
            color: #856404;
        }
        
        .quick-guide h4 {
            margin-bottom: 10px;
            color: #856404;
        }
        
        .quick-guide ul {
            padding-left: 20px;
        }
        
        .quick-guide li {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Add Email Account</h1>
            <p>Just add your email - no complicated setup needed!</p>
        </div>

        <div class="form-container">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; ?>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <div class="info-box">
                <i class="fas fa-info-circle"></i>
                <strong>Simple Setup:</strong> Just add your email address. We'll handle the rest!
            </div>
            
            <div class="quick-guide">
                <h4><i class="fas fa-bolt"></i> Quick Guide:</h4>
                <ul>
                    <li>Use any email address (Gmail, Outlook, Yahoo, etc.)</li>
                    <li>Display name appears as sender name in emails</li>
                    <li>Default email will be auto-selected for campaigns</li>
                    <li>You can add multiple emails and switch between them</li>
                </ul>
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="required">Email Address</label>
                    <input type="email" name="email" placeholder="your@email.com" required>
                </div>
                
                <div class="form-group">
                    <label>Display Name (Optional)</label>
                    <input type="text" name="display_name" placeholder="Your Name or Company Name">
                    <small style="color: #666; font-size: 13px; margin-top: 5px; display: block;">
                        This will appear as the sender name in sent emails
                    </small>
                </div>
                
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" name="is_default" id="is_default" value="1" checked>
                        <label for="is_default">Set as default email account</label>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i> Add Email Account
                </button>
                
                <button type="button" class="btn btn-secondary" onclick="window.location.href='campaign_create.php?step=<?php echo $step; ?>'">
                    <i class="fas fa-arrow-left"></i> Back to Campaign
                </button>
            </form>
            
            <div style="text-align: center; margin-top: 25px; padding-top: 20px; border-top: 2px solid #f0f0f0;">
                <p style="color: #666; font-size: 14px;">
                    <i class="fas fa-question-circle"></i> Need help? 
                    <a href="#" style="color: #667eea; text-decoration: none;">Learn more about email setup</a>
                </p>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-focus on email input
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('input[name="email"]').focus();
        });
    </script>
</body>
</html>