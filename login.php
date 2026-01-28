<?php
session_start();

// Simple login logic
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Accept any non-empty credentials for demo
    if (!empty($email) && !empty($password)) {
        // Set session variables
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'Arpan';
        $_SESSION['email'] = $email;
        $_SESSION['logged_in'] = true;
        
        // Redirect to dashboard
        header('Location: dashboard.php');
        exit();
    } else {
        $error = "Please enter both email and password";
    }
}

// Redirect if already logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ZigTex</title>
    <link rel="stylesheet" href="css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <!-- Back to Home -->
        <a href="index.php" class="back-home">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>

        <!-- Login Card -->
        <div class="login-center">
            <div class="login-card">
                <!-- Logo -->
                <div class="login-logo">
                    <i class="fas fa-robot"></i>
                    <h1>ZigTex</h1>
                    <p>AI-Powered Sales Automation</p>
                </div>

                <!-- Demo Notice -->
                <div class="demo-notice">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        <strong>Demo Access</strong>
                        <p>Use any email and password to login</p>
                    </div>
                </div>

                <!-- Login Form -->
                <form method="POST" class="login-form">
                    <div class="form-group">
                        <label for="email">
                            <i class="fas fa-envelope"></i> Email Address
                        </label>
                        <input type="email" id="email" name="email" placeholder="demo@zigtex.com" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">
                            <i class="fas fa-lock"></i> Password
                        </label>
                        <input type="password" id="password" name="password" placeholder="Enter any password" required>
                    </div>

                    <div class="form-options">
                        <label class="checkbox">
                            <input type="checkbox" name="remember">
                            <span>Remember me</span>
                        </label>
                        <a href="#" class="forgot-password">Forgot Password?</a>
                    </div>

                    <button type="submit" class="login-btn">
                        <i class="fas fa-sign-in-alt"></i> Login to Dashboard
                    </button>

                    <?php if(isset($error)): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                </form>

                <!-- Demo Credentials Card -->
                <div class="demo-card">
                    <div class="demo-card-header">
                        <i class="fas fa-user-secret"></i>
                        <h3>Quick Login</h3>
                    </div>
                    <div class="demo-credentials">
                        <div class="credential-item">
                            <span class="cred-label">Email:</span>
                            <span class="cred-value" id="demo-email">demo@zigtex.com</span>
                            <button class="copy-btn" data-text="demo@zigtex.com">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                        <div class="credential-item">
                            <span class="cred-label">Password:</span>
                            <span class="cred-value" id="demo-password">demo123</span>
                            <button class="copy-btn" data-text="demo123">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                    <button class="auto-fill-btn" id="autoFillBtn">
                        <i class="fas fa-magic"></i> Auto-fill & Login
                    </button>
                </div>

                <!-- Signup Link -->
                <div class="signup-link">
                    <p>Don't have an account? <a href="#" class="signup-btn">Request Demo Access</a></p>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="login-footer">
            <p>&copy; 2024 ZigTex AI. All rights reserved.</p>
            <div class="footer-links">
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
                <a href="#">Contact</a>
            </div>
        </footer>
    </div>

    <script src="js/login.js"></script>
</body>
</html>