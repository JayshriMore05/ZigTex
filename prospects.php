<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Logout functionality
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prospects - ZigTex</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Additional styles for Prospects page */
        .prospects-container {
            padding: 20px;
            background: #f8f9fa;
            min-height: calc(100vh - 60px);
        }

        .prospects-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding: 0 16px;
        }

        .prospects-title h1 {
            font-size: 28px;
            font-weight: 600;
            color: #1a1a1a;
            margin: 0;
        }

        .prospects-subtitle {
            font-size: 14px;
            color: #666;
            margin-top: 4px;
        }

        .prospects-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .search-box {
            position: relative;
            width: 300px;
        }

        .search-box input {
            width: 100%;
            padding: 10px 40px 10px 16px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            background: white;
            transition: all 0.3s ease;
        }

        .search-box input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .search-box i {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            pointer-events: none;
        }

        .action-button {
            padding: 10px 20px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .action-button:hover {
            background: #2563eb;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .action-button.csv-btn {
            background: #10b981;
        }

        .action-button.csv-btn:hover {
            background: #059669;
        }

        /* Table Container with Scroll */
        .prospects-table-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
            margin: 0 16px;
            max-width: 100%;
            overflow-x: auto;
        }

        .prospects-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px; /* Ensure all columns are visible */
        }

        .prospects-table thead {
            background: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .prospects-table th {
            padding: 16px;
            text-align: left;
            font-weight: 600;
            color: #495057;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            user-select: none;
            white-space: nowrap;
        }

        .prospects-table th.sortable {
            cursor: pointer;
            position: relative;
        }

        .prospects-table th.sortable:hover {
            background: #e9ecef;
        }

        .prospects-table th.sortable:after {
            content: '↕';
            margin-left: 8px;
            opacity: 0.5;
            font-size: 12px;
        }

        .prospects-table th.sortable.asc:after {
            content: '↑';
            opacity: 1;
        }

        .prospects-table th.sortable.desc:after {
            content: '↓';
            opacity: 1;
        }

        .prospects-table tbody tr {
            border-bottom: 1px solid #e9ecef;
            transition: all 0.2s ease;
        }

        .prospects-table tbody tr:hover {
            background: #f8f9fa;
        }

        .prospects-table tbody tr.clickable-row {
            cursor: pointer;
        }

        .prospects-table tbody tr.clickable-row:hover td {
            background: #f1f5f9;
        }

        .prospects-table td {
            padding: 16px;
            font-size: 14px;
            color: #212529;
            transition: background 0.2s ease;
            vertical-align: middle;
            white-space: nowrap;
        }

        /* Set specific column widths */
        .prospects-table th:nth-child(1),
        .prospects-table td:nth-child(1) {
            min-width: 200px; /* Prospect column */
        }

        .prospects-table th:nth-child(2),
        .prospects-table td:nth-child(2) {
            min-width: 150px; /* Company column */
        }

        .prospects-table th:nth-child(3),
        .prospects-table td:nth-child(3) {
            min-width: 100px; /* Status column */
        }

        .prospects-table th:nth-child(4),
        .prospects-table td:nth-child(4) {
            min-width: 120px; /* Last Contact column */
        }

        .prospects-table th:nth-child(5),
        .prospects-table td:nth-child(5) {
            min-width: 180px; /* Campaign column */
        }

        .prospects-table th:nth-child(6),
        .prospects-table td:nth-child(6) {
            min-width: 100px; /* Engagement column */
        }

        .prospects-table th:nth-child(7),
        .prospects-table td:nth-child(7) {
            min-width: 150px; /* Actions column */
        }

        /* Prospect Info */
        .prospect-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .prospect-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: white;
            font-size: 16px;
            flex-shrink: 0;
        }

        .prospect-details {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .prospect-name {
            font-weight: 600;
            color: #1a1a1a;
            transition: color 0.2s ease;
            font-size: 14px;
            margin-bottom: 2px;
        }

        .clickable-row:hover .prospect-name {
            color: #3b82f6;
        }

        .prospect-email {
            font-size: 12px;
            color: #666;
        }

        /* Status Badge */
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
            min-width: 70px;
            text-align: center;
        }

        .status-active {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        /* Engagement Indicator */
        .engagement-indicator {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .engagement-high {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .engagement-medium {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        .engagement-low {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        /* Actions Column - Fixed width and visible */
        .table-actions {
            display: flex;
            gap: 6px;
            justify-content: center;
            width: 100%;
        }

        .action-icon {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            cursor: pointer;
            transition: all 0.2s ease;
            background: white;
            border: 1px solid #e5e7eb;
            flex-shrink: 0;
        }

        .action-icon:hover {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
            transform: translateY(-2px);
        }

        .action-icon.delete:hover {
            background: #ef4444;
            border-color: #ef4444;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 16px;
            border-top: 1px solid #e9ecef;
            background: white;
            min-width: 1000px;
        }

        .pagination-info {
            font-size: 14px;
            color: #666;
        }

        .pagination-controls {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .page-button {
            padding: 8px 12px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            color: #666;
            transition: all 0.2s ease;
            min-width: 40px;
            text-align: center;
        }

        .page-button:hover:not(:disabled) {
            background: #f8f9fa;
            border-color: #ccc;
            transform: translateY(-1px);
        }

        .page-button.active {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
            font-weight: 500;
        }

        .page-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Stats Cards for Prospects */
        .prospect-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
            padding: 0 16px;
        }

        .prospect-stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .prospect-stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .prospect-stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .prospect-stat-header h3 {
            font-size: 14px;
            color: #666;
            font-weight: 500;
            margin: 0;
        }

        .prospect-stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #1a1a1a;
            margin: 8px 0;
        }

        .prospect-stat-change {
            font-size: 12px;
            color: #10b981;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .prospect-stat-change.negative {
            color: #ef4444;
        }

        /* LOGOUT BUTTON - CHANGED TO WHITE */
        .logout-btn {
            padding: 10px 20px;
            background: white; /* Changed to white */
            color: #374151; /* Changed to dark gray for contrast */
            border: 1px solid #e5e7eb; /* Added subtle border */
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-left: 10px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .logout-btn:hover {
            background: #f3f4f6; /* Light gray on hover */
            color: #1f2937; /* Darker text on hover */
            border-color: #d1d5db; /* Slightly darker border */
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); /* Subtle shadow */
        }

        /* Version Info */
        .version-info {
            position: fixed;
            bottom: 20px;
            right: 20px;
            font-size: 12px;
            color: #666;
            background: white;
            padding: 4px 8px;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-header h2 {
            margin: 0;
            color: #1a1a1a;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
            transition: color 0.2s;
        }

        .close-btn:hover {
            color: #ef4444;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #374151;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 30px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background: #3b82f6;
            color: white;
        }

        .btn-primary:hover {
            background: #2563eb;
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }
        
        /* Left Bottom Logout Button Container */
        .left-bottom-logout {
            position: fixed;
            bottom: 20px;
            left: 20px;
            z-index: 1000;
        }
        
        .left-bottom-logout .logout-btn {
            background: white;
            color: #374151;
            border: 1px solid #e5e7eb;
            padding: 8px 16px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
        }
        
        .left-bottom-logout .logout-btn:hover {
            background: #f3f4f6;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        
        .left-bottom-logout .logout-btn i {
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <?php include 'components/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Prospects Header -->
            <header class="prospects-header">
                <div class="prospects-title">
                    <h1>Prospects</h1>
                    <div class="prospects-subtitle">Manage and track your prospect interactions</div>
                </div>
                <div class="prospects-actions">
                    <div class="search-box">
                        <input type="text" placeholder="Search prospects..." id="searchInput">
                        <i class="fas fa-search"></i>
                    </div>
                    <button class="action-button" id="addProspectBtn">
                        <i class="fas fa-plus"></i>
                        Add Prospect
                    </button>
                    <button class="action-button csv-btn" id="importCSVBtn">
                        <i class="fas fa-upload"></i>
                        Import CSV
                    </button>
                    <!-- Remove the logout button from here since we're adding it to bottom left -->
                </div>
            </header>

            <!-- Prospect Stats -->
            <section class="prospect-stats">
                <div class="prospect-stat-card">
                    <div class="prospect-stat-header">
                        <h3>Total Prospects</h3>
                        <i class="fas fa-users" style="color: #3b82f6;"></i>
                    </div>
                    <div class="prospect-stat-value">6</div>
                    <div class="prospect-stat-change">
                        <i class="fas fa-arrow-up"></i>
                        20% from last week
                    </div>
                </div>
                <div class="prospect-stat-card">
                    <div class="prospect-stat-header">
                        <h3>Engaged</h3>
                        <i class="fas fa-comment-alt" style="color: #10b981;"></i>
                    </div>
                    <div class="prospect-stat-value">4</div>
                    <div class="prospect-stat-change">
                        <i class="fas fa-arrow-up"></i>
                        33% from last week
                    </div>
                </div>
                <div class="prospect-stat-card">
                    <div class="prospect-stat-header">
                        <h3>Response Rate</h3>
                        <i class="fas fa-chart-line" style="color: #f59e0b;"></i>
                    </div>
                    <div class="prospect-stat-value">66%</div>
                    <div class="prospect-stat-change negative">
                        <i class="fas fa-arrow-down"></i>
                        5% from last week
                    </div>
                </div>
                <div class="prospect-stat-card">
                    <div class="prospect-stat-header">
                        <h3>Avg. Response Time</h3>
                        <i class="fas fa-clock" style="color: #8b5cf6;"></i>
                    </div>
                    <div class="prospect-stat-value">2.1 days</div>
                    <div class="prospect-stat-change">
                        <i class="fas fa-arrow-down"></i>
                        0.3 days faster
                    </div>
                </div>
            </section>

            <!-- Prospects Table -->
            <section class="prospects-container">
                <div class="prospects-table-container">
                    <table class="prospects-table">
                        <thead>
                            <tr>
                                <th class="sortable" data-sort="prospect">PROSPECT</th>
                                <th class="sortable" data-sort="company">COMPANY</th>
                                <th class="sortable" data-sort="status">STATUS</th>
                                <th class="sortable" data-sort="lastContact">LAST CONTACT</th>
                                <th class="sortable" data-sort="campaign">CAMPAIGN</th>
                                <th class="sortable" data-sort="engagement">ENGAGEMENT</th>
                                <th>ACTION</th>
                            </tr>
                        </thead>
                        <tbody id="prospectsTableBody">
                            <?php
                            // Sample prospect data matching your screenshot EXACTLY
                            $prospects = [
                                [
                                    'name' => 'Mike Wilson', 
                                    'email' => 'mike@digitaltech.com', 
                                    'initials' => 'M', 
                                    'company' => 'Digital Tech', 
                                    'status' => 'active', 
                                    'last_contact' => 'Yesterday', 
                                    'campaign' => 'Enterprise Solutions', 
                                    'engagement' => 'High'
                                ],
                                [
                                    'name' => 'Emma Davis', 
                                    'email' => 'emma@cloudsys.com', 
                                    'initials' => 'E', 
                                    'company' => 'Cloud Systems', 
                                    'status' => 'inactive', 
                                    'last_contact' => '1 week ago', 
                                    'campaign' => 'Q1 Tech Outreach', 
                                    'engagement' => 'Low'
                                ],
                                [
                                    'name' => 'David Brown', 
                                    'email' => 'david@futuretech.io', 
                                    'initials' => 'D', 
                                    'company' => 'Future Tech', 
                                    'status' => 'active', 
                                    'last_contact' => 'Today', 
                                    'campaign' => 'Startup Outreach', 
                                    'engagement' => 'Medium'
                                ],
                                [
                                    'name' => 'Lisa Anderson', 
                                    'email' => 'lisa@webdev.com', 
                                    'initials' => 'L', 
                                    'company' => 'WebDev Pro', 
                                    'status' => 'pending', 
                                    'last_contact' => '3 days ago', 
                                    'campaign' => 'Q1 Tech Outreach', 
                                    'engagement' => 'Medium'
                                ],
                                [
                                    'name' => 'Robert Taylor', 
                                    'email' => 'robert@datascience.ai', 
                                    'initials' => 'R', 
                                    'company' => 'DataScience AI', 
                                    'status' => 'active', 
                                    'last_contact' => 'Yesterday', 
                                    'campaign' => 'AI Solutions', 
                                    'engagement' => 'High'
                                ],
                                [
                                    'name' => 'Jennifer Lee', 
                                    'email' => 'jennifer@mobileapps.co', 
                                    'initials' => 'J', 
                                    'company' => 'Mobile Apps Inc.', 
                                    'status' => 'active', 
                                    'last_contact' => 'Today', 
                                    'campaign' => 'Q1 Tech Outreach', 
                                    'engagement' => 'High'
                                ],
                            ];

                            foreach ($prospects as $index => $prospect) {
                                $statusClass = 'status-' . $prospect['status'];
                                $statusText = strtoupper($prospect['status']);
                                $engagementClass = 'engagement-' . strtolower($prospect['engagement']);
                            ?>
                            <tr class="clickable-row" data-id="<?php echo $index + 1; ?>">
                                <td>
                                    <div class="prospect-info">
                                        <div class="prospect-avatar"><?php echo $prospect['initials']; ?></div>
                                        <div class="prospect-details">
                                            <div class="prospect-name"><?php echo $prospect['name']; ?></div>
                                            <div class="prospect-email"><?php echo $prospect['email']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo $prospect['company']; ?></td>
                                <td>
                                    <span class="status-badge <?php echo $statusClass; ?>">
                                        <?php echo $statusText; ?>
                                    </span>
                                </td>
                                <td><?php echo $prospect['last_contact']; ?></td>
                                <td>
                                    <div class="campaign-info">
                                        <?php echo $prospect['campaign']; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="engagement-indicator <?php echo $engagementClass; ?>">
                                        <i class="fas fa-chart-<?php echo strtolower($prospect['engagement']) === 'high' ? 'line' : (strtolower($prospect['engagement']) === 'medium' ? 'bar' : 'pie'); ?>"></i>
                                        <?php echo $prospect['engagement']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <div class="action-icon" title="Send Email" onclick="sendEmail('<?php echo $prospect['email']; ?>', event)">
                                            <i class="fas fa-envelope"></i>
                                        </div>
                                        <div class="action-icon" title="Edit" onclick="editProspect(<?php echo $index + 1; ?>, event)">
                                            <i class="fas fa-edit"></i>
                                        </div>
                                        <div class="action-icon" title="View Details" onclick="viewDetails(<?php echo $index + 1; ?>, event)">
                                            <i class="fas fa-eye"></i>
                                        </div>
                                        <div class="action-icon delete" title="Delete" onclick="deleteProspect('<?php echo $prospect['name']; ?>', <?php echo $index + 1; ?>, event)">
                                            <i class="fas fa-trash"></i>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div class="pagination">
                        <div class="pagination-info" id="paginationInfo">
                            Showing 1-6 of 6 prospects
                        </div>
                        <div class="pagination-controls">
                            <button class="page-button" id="prevPage" disabled>
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <button class="page-button active">1</button>
                            <button class="page-button">2</button>
                            <button class="page-button">3</button>
                            <button class="page-button">4</button>
                            <button class="page-button">5</button>
                            <button class="page-button" id="nextPage">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </section>
            
            

    <!-- Add Prospect Modal -->
    <div class="modal" id="addProspectModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Prospect</h2>
                <button class="close-btn" onclick="closeModal('addProspectModal')">&times;</button>
            </div>
            <form id="addProspectForm">
                <div class="form-group">
                    <label for="prospectName">Full Name</label>
                    <input type="text" id="prospectName" name="name" required placeholder="Enter full name">
                </div>
                <div class="form-group">
                    <label for="prospectEmail">Email Address</label>
                    <input type="email" id="prospectEmail" name="email" required placeholder="Enter email address">
                </div>
                <div class="form-group">
                    <label for="prospectCompany">Company</label>
                    <input type="text" id="prospectCompany" name="company" required placeholder="Enter company name">
                </div>
                <div class="form-group">
                    <label for="prospectStatus">Status</label>
                    <select id="prospectStatus" name="status" required>
                        <option value="">Select status</option>
                        <option value="active">Active</option>
                        <option value="pending">Pending</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="prospectCampaign">Campaign</label>
                    <select id="prospectCampaign" name="campaign" required>
                        <option value="">Select campaign</option>
                        <option value="Q1 Tech Outreach">Q1 Tech Outreach</option>
                        <option value="Enterprise Solutions">Enterprise Solutions</option>
                        <option value="Startup Outreach">Startup Outreach</option>
                        <option value="AI Solutions">AI Solutions</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addProspectModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Prospect</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Import CSV Modal -->
    <div class="modal" id="importCSVModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Import Prospects from CSV</h2>
                <button class="close-btn" onclick="closeModal('importCSVModal')">&times;</button>
            </div>
            <form id="importCSVForm">
                <div class="form-group">
                    <label for="csvFile">Select CSV File</label>
                    <input type="file" id="csvFile" name="csvFile" accept=".csv" required>
                    <small style="display: block; margin-top: 8px; color: #666;">
                        Supported format: Name, Email, Company, Status, Campaign
                    </small>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('importCSVModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Import File</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Version Info -->
    <div class="version-info">v1.0.0</div>

    <script src="js/dashboard.js"></script>
    <script src="js/script.js"></script>
    <script>
        // Main JavaScript for Prospects page
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize variables
            let currentPage = 1;
            const rowsPerPage = 10;
            let allProspects = <?php echo json_encode($prospects); ?>;
            let filteredProspects = [...allProspects];
            
            // Search functionality
            const searchInput = document.getElementById('searchInput');
            
            searchInput.addEventListener('input', function(e) {
                const searchTerm = e.target.value.toLowerCase().trim();
                
                if (searchTerm === '') {
                    filteredProspects = [...allProspects];
                } else {
                    filteredProspects = allProspects.filter(prospect => {
                        return (
                            prospect.name.toLowerCase().includes(searchTerm) ||
                            prospect.email.toLowerCase().includes(searchTerm) ||
                            prospect.company.toLowerCase().includes(searchTerm) ||
                            prospect.campaign.toLowerCase().includes(searchTerm)
                        );
                    });
                }
                
                renderTable();
                updatePaginationInfo();
            });

            // Table sorting
            const sortableHeaders = document.querySelectorAll('.sortable');
            let currentSort = { column: 'prospect', direction: 'asc' };

            sortableHeaders.forEach(header => {
                header.addEventListener('click', function() {
                    const column = this.dataset.sort;
                    
                    // Update sort direction
                    if (currentSort.column === column) {
                        currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
                    } else {
                        currentSort.column = column;
                        currentSort.direction = 'asc';
                    }
                    
                    // Update header classes
                    sortableHeaders.forEach(h => {
                        h.classList.remove('asc', 'desc');
                    });
                    this.classList.add(currentSort.direction);
                    
                    // Sort prospects
                    sortProspects(column, currentSort.direction);
                    renderTable();
                });
            });

            // Pagination
            const prevPageBtn = document.getElementById('prevPage');
            const nextPageBtn = document.getElementById('nextPage');
            const pageButtons = document.querySelectorAll('.page-button:not(#prevPage):not(#nextPage)');
            
            prevPageBtn.addEventListener('click', () => {
                if (currentPage > 1) {
                    currentPage--;
                    updatePagination();
                    renderTable();
                }
            });

            nextPageBtn.addEventListener('click', () => {
                const totalPages = Math.ceil(filteredProspects.length / rowsPerPage);
                if (currentPage < totalPages) {
                    currentPage++;
                    updatePagination();
                    renderTable();
                }
            });

            pageButtons.forEach((button, index) => {
                button.addEventListener('click', () => {
                    currentPage = index + 1;
                    updatePagination();
                    renderTable();
                });
            });

            function updatePagination() {
                const totalPages = Math.ceil(filteredProspects.length / rowsPerPage);
                
                // Update active button
                pageButtons.forEach((button, index) => {
                    button.classList.toggle('active', index + 1 === currentPage);
                    button.style.display = index < totalPages ? '' : 'none';
                });
                
                // Update prev/next button states
                prevPageBtn.disabled = currentPage === 1;
                nextPageBtn.disabled = currentPage === totalPages || totalPages === 0;
                
                // Update pagination info
                updatePaginationInfo();
            }

            function updatePaginationInfo() {
                const totalProspects = filteredProspects.length;
                const startIndex = (currentPage - 1) * rowsPerPage + 1;
                const endIndex = Math.min(startIndex + rowsPerPage - 1, totalProspects);
                
                const infoElement = document.getElementById('paginationInfo');
                infoElement.textContent = `Showing ${startIndex}-${endIndex} of ${totalProspects} prospects`;
            }

            // Render table function
            function renderTable() {
                const tbody = document.getElementById('prospectsTableBody');
                const startIndex = (currentPage - 1) * rowsPerPage;
                const endIndex = startIndex + rowsPerPage;
                const pageProspects = filteredProspects.slice(startIndex, endIndex);
                
                tbody.innerHTML = '';
                
                pageProspects.forEach((prospect, index) => {
                    const globalIndex = startIndex + index + 1;
                    const statusClass = 'status-' + prospect.status;
                    const statusText = prospect.status.toUpperCase();
                    const engagementClass = 'engagement-' + prospect.engagement.toLowerCase();
                    
                    const row = document.createElement('tr');
                    row.className = 'clickable-row';
                    row.dataset.id = globalIndex;
                    row.innerHTML = `
                        <td>
                            <div class="prospect-info">
                                <div class="prospect-avatar">${prospect.initials}</div>
                                <div class="prospect-details">
                                    <div class="prospect-name">${prospect.name}</div>
                                    <div class="prospect-email">${prospect.email}</div>
                                </div>
                            </div>
                        </td>
                        <td>${prospect.company}</td>
                        <td>
                            <span class="status-badge ${statusClass}">
                                ${statusText}
                            </span>
                        </td>
                        <td>${prospect.last_contact}</td>
                        <td>
                            <div class="campaign-info">
                                ${prospect.campaign}
                            </div>
                        </td>
                        <td>
                            <span class="engagement-indicator ${engagementClass}">
                                <i class="fas fa-chart-${prospect.engagement.toLowerCase() === 'high' ? 'line' : (prospect.engagement.toLowerCase() === 'medium' ? 'bar' : 'pie')}"></i>
                                ${prospect.engagement}
                            </span>
                        </td>
                        <td>
                            <div class="table-actions">
                                <div class="action-icon" title="Send Email" onclick="sendEmail('${prospect.email}', event)">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div class="action-icon" title="Edit" onclick="editProspect(${globalIndex}, event)">
                                    <i class="fas fa-edit"></i>
                                </div>
                                <div class="action-icon" title="View Details" onclick="viewDetails(${globalIndex}, event)">
                                    <i class="fas fa-eye"></i>
                                </div>
                                <div class="action-icon delete" title="Delete" onclick="deleteProspect('${prospect.name}', ${globalIndex}, event)">
                                    <i class="fas fa-trash"></i>
                                </div>
                            </div>
                        </td>
                    `;
                    
                    // Add click event to the entire row
                    row.addEventListener('click', function(e) {
                        if (!e.target.closest('.table-actions')) {
                            viewDetails(globalIndex, e);
                        }
                    });
                    
                    tbody.appendChild(row);
                });
                
                updatePagination();
            }

            // Sort prospects function
            function sortProspects(column, direction) {
                filteredProspects.sort((a, b) => {
                    let aValue, bValue;
                    
                    switch(column) {
                        case 'prospect':
                            aValue = a.name;
                            bValue = b.name;
                            break;
                        case 'company':
                            aValue = a.company;
                            bValue = b.company;
                            break;
                        case 'status':
                            aValue = a.status;
                            bValue = b.status;
                            break;
                        case 'lastContact':
                            aValue = a.last_contact;
                            bValue = b.last_contact;
                            break;
                        case 'campaign':
                            aValue = a.campaign;
                            bValue = b.campaign;
                            break;
                        case 'engagement':
                            aValue = a.engagement;
                            bValue = b.engagement;
                            break;
                        default:
                            aValue = a.name;
                            bValue = b.name;
                    }
                    
                    if (direction === 'asc') {
                        return aValue.localeCompare(bValue);
                    } else {
                        return bValue.localeCompare(aValue);
                    }
                });
            }

            // Modal handling
            document.getElementById('addProspectBtn').addEventListener('click', () => {
                document.getElementById('addProspectModal').classList.add('show');
            });

            document.getElementById('importCSVBtn').addEventListener('click', () => {
                document.getElementById('importCSVModal').classList.add('show');
            });

            // Close modal when clicking outside
            window.addEventListener('click', (e) => {
                const modals = document.querySelectorAll('.modal');
                modals.forEach(modal => {
                    if (e.target === modal) {
                        modal.classList.remove('show');
                    }
                });
            });

            // Form submissions
            document.getElementById('addProspectForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const newProspect = {
                    name: formData.get('name'),
                    email: formData.get('email'),
                    initials: formData.get('name').charAt(0).toUpperCase(),
                    company: formData.get('company'),
                    status: formData.get('status'),
                    last_contact: 'Just now',
                    campaign: formData.get('campaign'),
                    engagement: 'Medium'
                };
                
                // Add to arrays
                allProspects.unshift(newProspect);
                filteredProspects.unshift(newProspect);
                
                alert(`Prospect "${newProspect.name}" added successfully!`);
                closeModal('addProspectModal');
                this.reset();
                
                // Update table
                renderTable();
                updatePaginationInfo();
            });

            document.getElementById('importCSVForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const fileInput = document.getElementById('csvFile');
                const file = fileInput.files[0];
                
                if (file) {
                    // Simulate importing 3 new prospects from CSV
                    const importedProspects = [
                        {
                            name: 'Alex Johnson',
                            email: 'alex@techstartup.com',
                            initials: 'A',
                            company: 'Tech Startup',
                            status: 'active',
                            last_contact: 'Today',
                            campaign: 'Q1 Tech Outreach',
                            engagement: 'High'
                        },
                        {
                            name: 'Maria Garcia',
                            email: 'maria@designstudio.com',
                            initials: 'M',
                            company: 'Design Studio',
                            status: 'pending',
                            last_contact: '2 days ago',
                            campaign: 'Startup Outreach',
                            engagement: 'Medium'
                        },
                        {
                            name: 'James Wilson',
                            email: 'james@datatech.com',
                            initials: 'J',
                            company: 'Data Tech',
                            status: 'active',
                            last_contact: 'Yesterday',
                            campaign: 'AI Solutions',
                            engagement: 'High'
                        }
                    ];
                    
                    // Add imported prospects
                    importedProspects.forEach(prospect => {
                        allProspects.push(prospect);
                        filteredProspects.push(prospect);
                    });
                    
                    alert(`Successfully imported ${importedProspects.length} prospects from CSV!`);
                    closeModal('importCSVModal');
                    this.reset();
                    
                    // Update table
                    renderTable();
                    updatePaginationInfo();
                }
            });

            // Initialize
            renderTable();
            updatePaginationInfo();
        });

        // Action functions
        function sendEmail(email, event) {
            event.stopPropagation();
            console.log(`Sending email to: ${email}`);
            alert(`Opening email composer for: ${email}`);
        }

        function editProspect(id, event) {
            event.stopPropagation();
            console.log(`Editing prospect ID: ${id}`);
            document.getElementById('addProspectModal').classList.add('show');
        }

        function viewDetails(id, event) {
            if (event) event.stopPropagation();
            console.log(`Viewing details for prospect ID: ${id}`);
            alert(`Viewing details for prospect ID: ${id}\n\nThis would open a detailed view with:\n- Full contact information\n- Interaction history\n- Notes\n- Activity timeline\n- Campaign participation`);
        }

        function deleteProspect(name, id, event) {
            event.stopPropagation();
            if (confirm(`Are you sure you want to delete "${name}"?\n\nThis action cannot be undone.`)) {
                console.log(`Deleting prospect ID: ${id}`);
                alert(`Prospect "${name}" has been deleted.`);
            }
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
        }
    </script>
</body>
</html>