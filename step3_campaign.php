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
    <title>Create Campaign - Step 3: Prospect</title>
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
            text-align: center;
        }

        .campaign-name {
            font-size: 20px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 32px;
        }

        .upload-area {
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            padding: 60px 40px;
            margin-bottom: 24px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .upload-area:hover {
            border-color: #3b82f6;
            background: #f0f9ff;
        }

        .upload-area.dragover {
            border-color: #3b82f6;
            background: #eff6ff;
        }

        .upload-icon {
            font-size: 48px;
            color: #9ca3af;
            margin-bottom: 16px;
        }

        .upload-text {
            font-size: 18px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }

        .upload-subtext {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 16px;
        }

        .upload-btn {
            display: inline-block;
            padding: 10px 24px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .upload-btn:hover {
            background: #2563eb;
        }

        .sample-section {
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid #e5e7eb;
        }

        .sample-btn {
            display: inline-block;
            padding: 10px 20px;
            background: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            text-decoration: none;
            margin-right: 12px;
            cursor: pointer;
        }

        .sample-btn:hover {
            background: #e5e7eb;
        }

        .file-info {
            display: none;
            margin-top: 20px;
            padding: 20px;
            background: #f9fafb;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }

        .file-info.show {
            display: block;
        }

        .file-name {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .file-details {
            font-size: 12px;
            color: #6b7280;
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
            
            <div class="upload-area" id="uploadArea">
                <div class="upload-icon">
                    <i class="fas fa-cloud-upload-alt"></i>
                </div>
                <div class="upload-text">Upload CSV file</div>
                <div class="upload-subtext">Drag and drop or click to choose file</div>
                <input type="file" id="csvFile" accept=".csv" style="display: none;">
                <div class="upload-btn" onclick="document.getElementById('csvFile').click()">
                    Upload File
                </div>
            </div>
            
            <div class="sample-section">
                <button class="sample-btn" onclick="downloadSample()">
                    <i class="fas fa-download"></i> Download Sample CSV
                </button>
                <button class="sample-btn" onclick="showSampleData()">
                    <i class="fas fa-eye"></i> View Sample Data
                </button>
            </div>
            
            <div class="file-info" id="fileInfo">
                <div class="file-name" id="fileName"></div>
                <div class="file-details" id="fileDetails"></div>
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
                <li class="step-item active">
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
            <button type="button" class="btn btn-primary" id="nextBtn" onclick="nextStep()" disabled>
                Next <i class="fas fa-arrow-right"></i>
            </button>
        </div>
    </div>

    <!-- Sample Data Modal -->
    <div id="sampleModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; padding: 20px;">
        <div style="background: white; border-radius: 12px; padding: 32px; max-width: 800px; width: 100%; max-height: 80vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <h3 style="font-size: 20px; font-weight: 600; color: #1f2937;">Sample_Prospect.csv</h3>
                <button onclick="closeSampleModal()" style="background: none; border: none; font-size: 24px; color: #6b7280; cursor: pointer;">×</button>
            </div>
            
            <div style="margin-bottom: 24px; background: #f9fafb; padding: 20px; border-radius: 8px; border: 1px solid #e5e7eb;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid #e5e7eb;">
                            <th style="text-align: left; padding: 12px; font-weight: 600; color: #374151;">CSV TITLE</th>
                            <th style="text-align: left; padding: 12px; font-weight: 600; color: #374151;">SAMPLE DATA</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sampleData = [
                            'First Name' => 'Aarav',
                            'Last Name' => 'Shah',
                            'Company' => 'TechNova',
                            'Email' => 'aarav.shah@technova.com',
                            'LinkedIn Link' => 'https://linkedin.com/in/aaravshah',
                            'Job Position' => 'Marketing Manager',
                            'Industry' => 'SaaS',
                            'Country' => 'India',
                            'Lead Status' => 'New',
                            'Notes' => 'Interested in automation tools'
                        ];
                        
                        foreach ($sampleData as $title => $data) {
                            echo '
                            <tr style="border-bottom: 1px solid #e5e7eb;">
                                <td style="padding: 12px; font-weight: 500; color: #374151;">' . $title . '</td>
                                <td style="padding: 12px; color: #6b7280;">' . $data . '</td>
                            </tr>';
                        }
                        ?>
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
        let fileUploaded = false;
        
        function prevStep() {
            window.location.href = 'step2_settings.php?name=<?php echo urlencode($campaign_name); ?>';
        }
        
        function nextStep() {
            if (!fileUploaded) {
                alert('Please upload a CSV file first');
                return;
            }
            
            // Save step 3 data (in real app, you'd save the CSV data)
            const stepData = {
                csv_uploaded: true,
                file_name: document.getElementById('fileName').textContent
            };
            
            localStorage.setItem('campaign_step3', JSON.stringify(stepData));
            
            // Go to step 4
            window.location.href = 'step4_content.php?name=<?php echo urlencode($campaign_name); ?>';
        }
        
        function downloadSample() {
            // Create sample CSV content
            const csvContent = "First Name,Last Name,Company,Email,LinkedIn Link,Job Position,Industry,Country,Lead Status,Notes\n" +
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
        
        // File upload handling
        document.addEventListener('DOMContentLoaded', function() {
            const uploadArea = document.getElementById('uploadArea');
            const fileInput = document.getElementById('csvFile');
            const fileInfo = document.getElementById('fileInfo');
            const nextBtn = document.getElementById('nextBtn');
            
            // Load saved data
            const savedData = localStorage.getItem('campaign_step3');
            if (savedData) {
                const data = JSON.parse(savedData);
                if (data.csv_uploaded) {
                    fileUploaded = true;
                    fileInfo.classList.add('show');
                    document.getElementById('fileName').textContent = data.file_name || 'Sample_Prospect.csv';
                    document.getElementById('fileDetails').textContent = 'Comma Separated Spreadsheet (.csv) - 1 KB';
                    nextBtn.disabled = false;
                }
            }
            
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
                    handleFile(e.dataTransfer.files[0]);
                }
            });
            
            // File input change
            fileInput.addEventListener('change', function(e) {
                if (this.files.length) {
                    handleFile(this.files[0]);
                }
            });
            
            function handleFile(file) {
                if (file.type !== 'text/csv' && !file.name.endsWith('.csv')) {
                    alert('Please upload a CSV file');
                    return;
                }
                
                // Simulate file processing
                fileUploaded = true;
                fileInfo.classList.add('show');
                document.getElementById('fileName').textContent = file.name;
                document.getElementById('fileDetails').textContent = 
                    'Comma Separated Spreadsheet (.csv) - ' + formatFileSize(file.size);
                nextBtn.disabled = false;
                
                // Simulate upload (in real app, you'd upload to server)
                setTimeout(() => {
                    alert('File uploaded successfully! 3 prospects found.');
                }, 500);
            }
            
            function formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
            }
            
            // Close modal when clicking outside
            document.getElementById('sampleModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    closeSampleModal();
                }
            });
        });
    </script>
</body>
</html>