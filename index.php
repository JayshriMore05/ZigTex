<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZigTex - AI Sales Automation Platform</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/landing.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        /* Landing Page Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            color: #1f2937;
            background: #f9fafb;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Navigation */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid #e5e7eb;
            z-index: 1000;
            padding: 16px 0;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 24px;
            font-weight: 800;
            color: #3b82f6;
            text-decoration: none;
        }

        .nav-brand i {
            font-size: 28px;
        }

        .brand-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 32px;
        }

        .nav-link {
            color: #4b5563;
            text-decoration: none;
            font-weight: 500;
            font-size: 15px;
            transition: color 0.3s;
        }

        .nav-link:hover {
            color: #3b82f6;
        }

        .nav-btn {
            padding: 10px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
        }

        .login-btn {
            color: #3b82f6;
            border: 1px solid #3b82f6;
        }

        .login-btn:hover {
            background: #eff6ff;
        }

        .demo-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .demo-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            color: #4b5563;
            cursor: pointer;
        }

        /* Hero Section */
        .hero {
            padding: 160px 0 80px;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            overflow: hidden;
        }

        .hero-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 80px;
            align-items: center;
        }

        .hero-content {
            max-width: 600px;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            background: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            color: #6b7280;
            margin-bottom: 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .badge-dot {
            width: 6px;
            height: 6px;
            background: #3b82f6;
            border-radius: 50%;
        }

        .hero-title {
            font-size: 48px;
            font-weight: 800;
            line-height: 1.1;
            color: #111827;
            margin-bottom: 24px;
        }

        .hero-subtitle {
            font-size: 18px;
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 40px;
        }

        .hero-actions {
            display: flex;
            gap: 16px;
            margin-bottom: 48px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 16px 32px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: white;
            color: #374151;
            border: 1px solid #d1d5db;
        }

        .btn-secondary:hover {
            background: #f9fafb;
            border-color: #9ca3af;
        }

        .hero-stats {
            display: flex;
            gap: 40px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 4px;
        }

        .stat-label {
            font-size: 14px;
            color: #6b7280;
            font-weight: 500;
        }

        /* Dashboard Preview */
        .dashboard-preview {
            background: white;
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            transform: perspective(1000px) rotateY(-10deg);
            transition: transform 0.5s;
        }

        .dashboard-preview:hover {
            transform: perspective(1000px) rotateY(0deg);
        }

        .preview-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .preview-controls {
            display: flex;
            gap: 8px;
        }

        .control-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }

        .control-dot.red { background: #ef4444; }
        .control-dot.yellow { background: #f59e0b; }
        .control-dot.green { background: #10b981; }

        .preview-title {
            font-size: 14px;
            font-weight: 600;
            color: #4b5563;
        }

        .preview-body {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .preview-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .preview-stat {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            background: #f9fafb;
            border-radius: 12px;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .stat-details {
            flex: 1;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
        }

        .stat-label {
            font-size: 13px;
            color: #6b7280;
        }

        .preview-chart {
            background: #f9fafb;
            border-radius: 12px;
            padding: 20px;
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .chart-header span:first-child {
            font-weight: 600;
            color: #374151;
        }

        .chart-time {
            font-size: 13px;
            color: #9ca3af;
        }

        .chart-bars {
            display: flex;
            align-items: flex-end;
            gap: 12px;
            height: 120px;
            padding-top: 20px;
        }

        .bar {
            flex: 1;
            background: linear-gradient(to top, #667eea, #764ba2);
            border-radius: 4px;
            min-height: 10px;
        }

        /* Features Section */
        .features {
            padding: 100px 0;
            background: white;
        }

        .section-header {
            text-align: center;
            max-width: 800px;
            margin: 0 auto 60px;
        }

        .section-title {
            font-size: 40px;
            font-weight: 800;
            color: #111827;
            margin-bottom: 16px;
        }

        .section-subtitle {
            font-size: 18px;
            color: #6b7280;
            line-height: 1.6;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 32px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .feature-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 32px;
            transition: all 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            border-color: transparent;
        }

        .feature-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
        }

        .feature-icon i {
            font-size: 28px;
            color: #0369a1;
        }

        .feature-title {
            font-size: 22px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 12px;
        }

        .feature-description {
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .feature-highlights {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .feature-highlights span {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #4b5563;
        }

        .feature-highlights i {
            color: #10b981;
            font-size: 12px;
        }

        /* CTA Section */
        .cta {
            padding: 100px 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .cta-content {
            text-align: center;
            max-width: 800px;
            margin: 0 auto;
        }

        .cta-title {
            font-size: 48px;
            font-weight: 800;
            margin-bottom: 16px;
        }

        .cta-subtitle {
            font-size: 20px;
            opacity: 0.9;
            margin-bottom: 40px;
        }

        .cta-actions {
            display: flex;
            gap: 16px;
            justify-content: center;
        }

        .btn-large {
            padding: 18px 36px;
            font-size: 18px;
        }

        .btn-outline {
            background: transparent;
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: white;
        }

        /* Footer */
        .footer {
            background: #111827;
            color: white;
            padding: 80px 0 40px;
        }

        .footer-content {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 60px;
            margin-bottom: 60px;
        }

        .footer-brand .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 16px;
        }

        .footer-brand i {
            color: #3b82f6;
        }

        .footer-tagline {
            color: #9ca3af;
            margin-bottom: 24px;
            line-height: 1.6;
        }

        .social-links {
            display: flex;
            gap: 16px;
        }

        .social-link {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
        }

        .social-link:hover {
            background: #3b82f6;
            transform: translateY(-2px);
        }

        .footer-links {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 40px;
        }

        .link-group-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 20px;
            color: white;
        }

        .footer-link {
            display: block;
            color: #9ca3af;
            text-decoration: none;
            margin-bottom: 12px;
            transition: color 0.3s;
        }

        .footer-link:hover {
            color: white;
        }

        .footer-bottom {
            padding-top: 40px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .copyright {
            color: #9ca3af;
            font-size: 14px;
        }

        .legal-links {
            display: flex;
            gap: 24px;
        }

        .legal-link {
            color: #9ca3af;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s;
        }

        .legal-link:hover {
            color: white;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .hero-container {
                grid-template-columns: 1fr;
                gap: 60px;
            }
            
            .hero-content {
                text-align: center;
                max-width: 100%;
            }
            
            .hero-stats {
                justify-content: center;
            }
            
            .hero-actions {
                justify-content: center;
            }
        }

        @media (max-width: 768px) {
            .nav-menu {
                display: none;
            }
            
            .mobile-menu-btn {
                display: block;
            }
            
            .hero-title {
                font-size: 36px;
            }
            
            .hero-subtitle {
                font-size: 16px;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
            }
            
            .footer-content {
                grid-template-columns: 1fr;
                gap: 40px;
            }
            
            .footer-links {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .footer-bottom {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .legal-links {
                justify-content: center;
            }
            
            .cta-actions {
                flex-direction: column;
                align-items: center;
            }
            
            .btn-large {
                width: 100%;
                max-width: 300px;
            }
        }

        @media (max-width: 480px) {
            .hero-title {
                font-size: 32px;
            }
            
            .hero-stats {
                flex-direction: column;
                gap: 24px;
            }
            
            .section-title {
                font-size: 32px;
            }
            
            .cta-title {
                font-size: 36px;
            }
            
            .footer-links {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <i class="fas fa-robot"></i>
                <span class="brand-text">ZIGTEX</span>
            </div>
            <div class="nav-menu">
                <a href="#features" class="nav-link">Features</a>
                <a href="#how-it-works" class="nav-link">How It Works</a>
                <a href="#pricing" class="nav-link">Pricing</a>
                <a href="login.php" class="nav-btn login-btn">Login</a>
                <a href="login.html" class="nav-btn demo-btn">Try Demo</a>
            </div>
            <button class="mobile-menu-btn">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-container">
            <div class="hero-content">
                <div class="hero-badge">
                    <span>AI-POWERED</span>
                    <div class="badge-dot"></div>
                    <span>SALES AUTOMATION</span>
                </div>
                <h1 class="hero-title">Automate Your Sales Outreach with Intelligent AI</h1>
                <p class="hero-subtitle">Send personalized cold emails, track replies, and close more deals—all on autopilot. Experience the future of sales automation.</p>
                <div class="hero-actions">
                    <a href="login.html" class="btn btn-primary">
                        <i class="fas fa-rocket"></i>
                        <span>Start Free Trial</span>
                    </a>
                    <a href="#demo" class="btn btn-secondary">
                        <i class="fas fa-play-circle"></i>
                        <span>Watch Demo</span>
                    </a>
                </div>
                <div class="hero-stats">
                    <div class="stat-item">
                        <div class="stat-number">10,000+</div>
                        <div class="stat-label">Emails Sent Daily</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">45%</div>
                        <div class="stat-label">Higher Reply Rate</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">3.5x</div>
                        <div class="stat-label">More Meetings Booked</div>
                    </div>
                </div>
            </div>
            <div class="hero-visual">
                <div class="dashboard-preview">
                    <div class="preview-header">
                        <div class="preview-controls">
                            <div class="control-dot red"></div>
                            <div class="control-dot yellow"></div>
                            <div class="control-dot green"></div>
                        </div>
                        <div class="preview-title">Dashboard Preview</div>
                    </div>
                    <div class="preview-body">
                        <div class="preview-stats">
                            <div class="preview-stat">
                                <div class="stat-icon">
                                    <i class="fas fa-paper-plane"></i>
                                </div>
                                <div class="stat-details">
                                    <div class="stat-value">1,247</div>
                                    <div class="stat-label">Emails Sent</div>
                                </div>
                            </div>
                            <div class="preview-stat">
                                <div class="stat-icon">
                                    <i class="fas fa-reply"></i>
                                </div>
                                <div class="stat-details">
                                    <div class="stat-value">186</div>
                                    <div class="stat-label">Replies</div>
                                </div>
                            </div>
                        </div>
                        <div class="preview-chart">
                            <div class="chart-header">
                                <span>Activity Trend</span>
                                <span class="chart-time">Last 7 days</span>
                            </div>
                            <div class="chart-bars">
                                <div class="bar" style="height: 60%;"></div>
                                <div class="bar" style="height: 80%;"></div>
                                <div class="bar" style="height: 45%;"></div>
                                <div class="bar" style="height: 90%;"></div>
                                <div class="bar" style="height: 70%;"></div>
                                <div class="bar" style="height: 85%;"></div>
                                <div class="bar" style="height: 65%;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Powerful Features for Modern Sales Teams</h2>
                <p class="section-subtitle">Everything you need to automate and scale your sales outreach</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-envelope-open-text"></i>
                    </div>
                    <h3 class="feature-title">AI Email Writing</h3>
                    <p class="feature-description">Generate personalized cold emails that get replies using advanced AI algorithms</p>
                    <div class="feature-highlights">
                        <span><i class="fas fa-check"></i> Personalization at scale</span>
                        <span><i class="fas fa-check"></i> Multiple variants</span>
                        <span><i class="fas fa-check"></i> Tone optimization</span>
                    </div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3 class="feature-title">Campaign Analytics</h3>
                    <p class="feature-description">Track opens, clicks, and replies in real-time with detailed reporting</p>
                    <div class="feature-highlights">
                        <span><i class="fas fa-check"></i> Real-time tracking</span>
                        <span><i class="fas fa-check"></i> Detailed insights</span>
                        <span><i class="fas fa-check"></i> Performance metrics</span>
                    </div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <h3 class="feature-title">Smart Automation</h3>
                    <p class="feature-description">Automated follow-ups based on prospect behavior and engagement</p>
                    <div class="feature-highlights">
                        <span><i class="fas fa-check"></i> Behavior-based triggers</span>
                        <span><i class="fas fa-check"></i> Smart sequencing</span>
                        <span><i class="fas fa-check"></i> Time optimization</span>
                    </div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="feature-title">Prospect Management</h3>
                    <p class="feature-description">Organize and segment your leads effectively with smart CRM</p>
                    <div class="feature-highlights">
                        <span><i class="fas fa-check"></i> Smart segmentation</span>
                        <span><i class="fas fa-check"></i> Lead scoring</span>
                        <span><i class="fas fa-check"></i> Pipeline tracking</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <div class="container">
            <div class="cta-content">
                <h2 class="cta-title">Ready to Transform Your Sales Process?</h2>
                <p class="cta-subtitle">Join thousands of sales teams automating their outreach with ZigTex</p>
                <div class="cta-actions">
                    <a href="login.html" class="btn btn-primary btn-large">
                        <i class="fas fa-rocket"></i>
                        <span>Start Free Trial</span>
                    </a>
                    <a href="#demo" class="btn btn-outline btn-large">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Book a Demo</span>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-brand">
                    <div class="brand">
                        <i class="fas fa-robot"></i>
                        <span class="brand-text">ZIGTEX</span>
                    </div>
                    <p class="footer-tagline">AI-Powered Sales Automation Platform</p>
                    <div class="social-links">
                        <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-linkedin"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-github"></i></a>
                    </div>
                </div>
                <div class="footer-links">
                    <div class="link-group">
                        <h4 class="link-group-title">Product</h4>
                        <a href="#features" class="footer-link">Features</a>
                        <a href="#pricing" class="footer-link">Pricing</a>
                        <a href="#demo" class="footer-link">Demo</a>
                        <a href="#" class="footer-link">Updates</a>
                    </div>
                    <div class="link-group">
                        <h4 class="link-group-title">Company</h4>
                        <a href="#" class="footer-link">About</a>
                        <a href="#" class="footer-link">Blog</a>
                        <a href="#" class="footer-link">Careers</a>
                        <a href="#" class="footer-link">Press</a>
                    </div>
                    <div class="link-group">
                        <h4 class="link-group-title">Support</h4>
                        <a href="#" class="footer-link">Help Center</a>
                        <a href="#" class="footer-link">Contact</a>
                        <a href="#" class="footer-link">API Docs</a>
                        <a href="#" class="footer-link">Status</a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p class="copyright">&copy; 2024 ZigTex AI. All rights reserved.</p>
                <div class="legal-links">
                    <a href="#" class="legal-link">Privacy Policy</a>
                    <a href="#" class="legal-link">Terms of Service</a>
                    <a href="#" class="legal-link">Cookie Policy</a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Mobile Menu Toggle
        document.querySelector('.mobile-menu-btn').addEventListener('click', function() {
            const navMenu = document.querySelector('.nav-menu');
            navMenu.style.display = navMenu.style.display === 'flex' ? 'none' : 'flex';
        });

        // Smooth Scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Navbar Scroll Effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.style.background = 'rgba(255, 255, 255, 0.98)';
                navbar.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.1)';
            } else {
                navbar.style.background = 'rgba(255, 255, 255, 0.95)';
                navbar.style.boxShadow = 'none';
            }
        });
    </script>
</body>
</html>