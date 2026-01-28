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
    <title>Create Campaign - Step 2: Campaign Settings</title>
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

        .section {
            margin-bottom: 32px;
        }

        .section h3 {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 16px;
        }

        .section p {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 16px;
        }

        .timezone-select {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            color: #374151;
            background: white;
        }

        .schedule-days {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }

        .day-checkbox {
            display: none;
        }

        .day-label {
            padding: 10px 16px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            color: #374151;
            transition: all 0.2s;
        }

        .day-checkbox:checked + .day-label {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }

        .time-selector {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .time-group {
            flex: 1;
        }

        .time-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #374151;
            font-size: 14px;
        }

        .time-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            color: #374151;
            background: white;
        }

        .time-separator {
            color: #6b7280;
            font-size: 20px;
            margin-top: 24px;
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
            <div class="section">
                <h3>Timezone</h3>
                <select class="timezone-select" id="timezone">
                    <option value="Europe/London">GMT +0:00 Europe/London</option>
                    <option value="Asia/Kolkata">GMT +5:30 Asia/Kolkata</option>
                    <option value="America/New_York">GMT -5:00 America/New_York</option>
                    <option value="America/Los_Angeles">GMT -8:00 America/Los_Angeles</option>
                </select>
            </div>

            <div class="section">
                <h3>Weekly Schedule</h3>
                <p>Choose which days your campaign should run</p>
                <div class="schedule-days">
                    <?php
                    $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                    foreach ($days as $day) {
                        echo '
                        <div>
                            <input type="checkbox" id="day_' . strtolower($day) . '" class="day-checkbox" value="' . $day . '" checked>
                            <label for="day_' . strtolower($day) . '" class="day-label">' . $day . '</label>
                        </div>';
                    }
                    ?>
                </div>
            </div>

            <div class="section">
                <h3>Sending Hours</h3>
                <p>Define when messages should be sent</p>
                <div class="time-selector">
                    <div class="time-group">
                        <label>Start Time</label>
                        <select id="startTime">
                            <option value="09:00">09:00 AM</option>
                            <?php
                            for ($i = 6; $i <= 12; $i++) {
                                $time = str_pad($i, 2, '0', STR_PAD_LEFT);
                                echo '<option value="' . $time . ':00">' . $time . ':00 AM</option>';
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="time-separator">→</div>
                    
                    <div class="time-group">
                        <label>End Time</label>
                        <select id="endTime">
                            <option value="19:00">07:00 PM</option>
                            <?php
                            for ($i = 1; $i <= 11; $i++) {
                                $time = str_pad($i, 2, '0', STR_PAD_LEFT);
                                echo '<option value="' . $time . ':00">' . $time . ':00 PM</option>';
                            }
                            ?>
                        </select>
                    </div>
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
                <li class="step-item active">
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
            window.location.href = 'step1_channel.php?name=<?php echo urlencode($campaign_name); ?>';
        }
        
        function nextStep() {
            const timezone = document.getElementById('timezone').value;
            
            const selectedDays = [];
            document.querySelectorAll('.day-checkbox:checked').forEach(cb => {
                selectedDays.push(cb.value);
            });
            
            const startTime = document.getElementById('startTime').value;
            const endTime = document.getElementById('endTime').value;
            
            if (selectedDays.length === 0) {
                alert('Please select at least one day for the weekly schedule');
                return;
            }
            
            // Save step 2 data
            const stepData = {
                timezone: timezone,
                weekly_schedule: selectedDays,
                start_time: startTime,
                end_time: endTime
            };
            
            localStorage.setItem('campaign_step2', JSON.stringify(stepData));
            
            // Go to step 3
            window.location.href = 'step3_prospect.php?name=<?php echo urlencode($campaign_name); ?>';
        }
        
        // Load saved data
        document.addEventListener('DOMContentLoaded', function() {
            const savedData = localStorage.getItem('campaign_step2');
            if (savedData) {
                const data = JSON.parse(savedData);
                
                document.getElementById('timezone').value = data.timezone || 'Europe/London';
                document.getElementById('startTime').value = data.start_time || '09:00';
                document.getElementById('endTime').value = data.end_time || '19:00';
                
                // Check selected days
                if (data.weekly_schedule) {
                    document.querySelectorAll('.day-checkbox').forEach(cb => {
                        cb.checked = data.weekly_schedule.includes(cb.value);
                    });
                }
            }
        });
    </script>
</body>
</html>