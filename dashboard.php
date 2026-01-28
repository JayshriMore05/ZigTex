<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ZigTex</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <?php include 'components/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="main-header">
                <div class="header-left">
                    <h1>Dashboard</h1>
                    <div class="date-display">
                        <i class="fas fa-calendar-alt"></i>
                        <span><?php echo date('F j, Y'); ?></span>
                    </div>
                </div>
            </header>

            <!-- Stats Grid - EXACT as in image -->
            <section class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <h3>Total Prospects</h3>
                    </div>
                    <div class="stat-value">127</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <h3>Emails Sent</h3>
                    </div>
                    <div class="stat-value">37</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <h3>Delivered</h3>
                    </div>
                    <div class="stat-value">36</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <h3>Replied</h3>
                    </div>
                    <div class="stat-value">2</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <h3>Bounced</h3>
                    </div>
                    <div class="stat-value">1</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <h3>Active Campaign</h3>
                    </div>
                    <div class="stat-value">3</div>
                </div>
            </section>

            <!-- Activity Trend Section - EXACT as in image -->
            <section class="chart-section">
                <div class="section-header">
                    <h2>Activity Trend</h2>
                    <div class="section-actions">
                        <select class="time-select">
                            <option>Last 30 Days</option>
                            <option>Last 7 Days</option>
                            <option>Last 24 Hours</option>
                        </select>
                    </div>
                </div>
                
                <!-- Chart Container -->
                <div class="chart-container-exact">
                    <!-- Y-axis (Number of Emails) -->
                    <div class="y-axis">
                        <div class="y-label">Number of Emails</div>
                        <div class="y-values">
                            <div>32</div>
                            <div>24</div>
                            <div>16</div>
                            <div>8</div>
                            <div>0</div>
                        </div>
                    </div>

                    <!-- Chart Area -->
                    <div class="chart-area-exact">
                        <!-- Grid Lines -->
                        <div class="grid-lines">
                            <div class="grid-line"></div>
                            <div class="grid-line"></div>
                            <div class="grid-line"></div>
                            <div class="grid-line"></div>
                            <div class="grid-line"></div>
                        </div>

                        <!-- Data Points - Exactly matching image -->
                        <div class="data-points">
                            <!-- Sent (Blue) -->
                            <div class="data-point sent" style="left: 10%; bottom: 25%;" data-value="24"></div>
                            <div class="data-point sent" style="left: 30%; bottom: 50%;" data-value="16"></div>
                            <div class="data-point sent" style="left: 50%; bottom: 75%;" data-value="8"></div>
                            
                            <!-- Replied (Yellow) -->
                            <div class="data-point replied" style="left: 15%; bottom: 12.5%;" data-value="3"></div>
                            <div class="data-point replied" style="left: 35%; bottom: 6.25%;" data-value="1"></div>
                            
                            <!-- Bounced (Red) -->
                            <div class="data-point bounced" style="left: 25%; bottom: 3%;" data-value="0"></div>
                        </div>

                        <!-- Connection Lines -->
                        <svg class="connection-lines" width="100%" height="100%">
                            <!-- Sent line (Blue) -->
                            <polyline class="line sent-line" points="10,75 30,50 50,25" />
                            
                            <!-- Replied line (Yellow) -->
                            <polyline class="line replied-line" points="15,87.5 35,93.75" />
                            
                            <!-- Bounced line (Red) -->
                            <circle class="bounced-point" cx="25%" cy="97%" r="2" />
                        </svg>

                        <!-- X-axis (Time) -->
                        <div class="x-axis">
                            <div class="x-label">Time</div>
                            <div class="x-values">
                                <div>08 Jan</div>
                                <div>09 Jan</div>
                                <div>10 Jan</div>
                                <div>11 Jan</div>
                                <div>12 Jan</div>
                            </div>
                        </div>
                    </div>

                    <!-- Legend -->
                    <div class="chart-legend-exact">
                        <div class="legend-item">
                            <span class="legend-dot sent-dot"></span>
                            <span class="legend-text">Sent</span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-dot replied-dot"></span>
                            <span class="legend-text">Replied</span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-dot bounced-dot"></span>
                            <span class="legend-text">Bounced</span>
                        </div>
                    </div>
                </div>

                <!-- Chart Annotations -->
                <div class="chart-annotations">
                    <div class="annotation">
                        <span class="annotation-label">Last 30 Days</span>
                    </div>
                    <div class="annotation">
                        <span class="annotation-label">Dates of month</span>
                    </div>
                </div>
            </section>

        </main>
    </div>

    <script src="js/dashboard.js"></script>
    <script src="js/script.js"></script>
</body>
</html>