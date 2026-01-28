<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$campaign_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($campaign_id <= 0) {
    header("Location: campaigns.php");
    exit();
}

try {
    $pdo = db();
    
    // Verify campaign belongs to user
    $stmt = $pdo->prepare("SELECT * FROM campaigns WHERE id = ? AND user_id = ?");
    $stmt->execute([$campaign_id, $user_id]);
    $campaign = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$campaign) {
        header("Location: campaigns.php");
        exit();
    }
    
    // Fetch campaign name for display
    $campaign_name = $campaign['name'];
    
    // Handle search
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $search_condition = '';
    $search_params = [];
    
    if (!empty($search)) {
        $search_condition = "AND (p.email LIKE ? OR p.first_name LIKE ? OR p.last_name LIKE ? OR p.company LIKE ?)";
        $search_term = "%$search%";
        $search_params = [$search_term, $search_term, $search_term, $search_term];
    }
    
    // Pagination setup
    $per_page = 20;
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    if ($page < 1) $page = 1;
    $offset = ($page - 1) * $per_page;
    
    // Get total prospects count
    $count_stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM campaign_prospects cp
        LEFT JOIN prospects p ON cp.prospect_id = p.id
        WHERE cp.campaign_id = ? $search_condition
    ");
    $count_stmt->execute(array_merge([$campaign_id], $search_params));
    $total_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_count / $per_page);
    
    // Fetch prospects with pagination
    $stmt = $pdo->prepare("
        SELECT 
            cp.*,
            p.email,
            p.first_name,
            p.last_name,
            p.company,
            p.job_title,
            p.phone,
            p.linkedin_url,
            p.website
        FROM campaign_prospects cp
        LEFT JOIN prospects p ON cp.prospect_id = p.id
        WHERE cp.campaign_id = ? $search_condition
        ORDER BY cp.id DESC
        LIMIT ? OFFSET ?
    ");
    
    $limit_params = array_merge([$campaign_id], $search_params, [$per_page, $offset]);
    $stmt->execute($limit_params);
    $prospects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get status counts
    $status_counts_stmt = $pdo->prepare("
        SELECT 
            status,
            COUNT(*) as count
        FROM campaign_prospects 
        WHERE campaign_id = ?
        GROUP BY status
    ");
    $status_counts_stmt->execute([$campaign_id]);
    $status_counts = $status_counts_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Prepare status counts for display
    $status_stats = [
        'pending' => 0,
        'sent' => 0,
        'delivered' => 0,
        'opened' => 0,
        'clicked' => 0,
        'replied' => 0,
        'bounced' => 0,
        'unsubscribed' => 0
    ];
    
    foreach ($status_counts as $stat) {
        if (isset($status_stats[$stat['status']])) {
            $status_stats[$stat['status']] = $stat['count'];
        }
    }
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add_prospects':
                // Handle bulk prospect addition (you can expand this)
                $prospect_ids = $_POST['prospect_ids'] ?? [];
                if (!empty($prospect_ids)) {
                    foreach ($prospect_ids as $prospect_id) {
                        $check_stmt = $pdo->prepare("
                            SELECT id FROM campaign_prospects 
                            WHERE campaign_id = ? AND prospect_id = ?
                        ");
                        $check_stmt->execute([$campaign_id, $prospect_id]);
                        
                        if (!$check_stmt->fetch()) {
                            $insert_stmt = $pdo->prepare("
                                INSERT INTO campaign_prospects (campaign_id, prospect_id, status, created_at)
                                VALUES (?, ?, 'pending', NOW())
                            ");
                            $insert_stmt->execute([$campaign_id, $prospect_id]);
                        }
                    }
                    $_SESSION['success'] = 'Prospects added successfully!';
                }
                break;
                
            case 'remove_prospects':
                $prospect_ids = $_POST['prospect_ids'] ?? [];
                if (!empty($prospect_ids)) {
                    $placeholders = str_repeat('?,', count($prospect_ids) - 1) . '?';
                    $stmt = $pdo->prepare("
                        DELETE FROM campaign_prospects 
                        WHERE campaign_id = ? AND prospect_id IN ($placeholders)
                    ");
                    $stmt->execute(array_merge([$campaign_id], $prospect_ids));
                    $_SESSION['success'] = 'Prospects removed successfully!';
                }
                break;
                
            case 'update_status':
                $prospect_id = $_POST['prospect_id'] ?? 0;
                $new_status = $_POST['status'] ?? '';
                $valid_statuses = ['pending', 'sent', 'delivered', 'opened', 'clicked', 'replied', 'bounced', 'unsubscribed'];
                
                if ($prospect_id > 0 && in_array($new_status, $valid_statuses)) {
                    $stmt = $pdo->prepare("
                        UPDATE campaign_prospects 
                        SET status = ?, updated_at = NOW()
                        WHERE campaign_id = ? AND prospect_id = ?
                    ");
                    $stmt->execute([$new_status, $campaign_id, $prospect_id]);
                    $_SESSION['success'] = 'Status updated successfully!';
                }
                break;
        }
        
        // Refresh page
        header("Location: campaign_prospects.php?id=$campaign_id");
        exit();
        
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Action failed: ' . $e->getMessage();
        header("Location: campaign_prospects.php?id=$campaign_id");
        exit();
    }
}

// Check for success/error messages
$success_message = '';
$error_message = '';

if (isset($_SESSION['success'])) {
    $success_message = $_SESSION['success'];
    unset($_SESSION['success']);
} elseif (isset($_SESSION['error'])) {
    $error_message = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Prospects - <?php echo htmlspecialchars($campaign_name); ?> - ZigTex</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Clean White UI Styles */
        body {
            background: #f8fafc;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .main-content {
            padding: 24px;
            background: #f8fafc;
        }
        
        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        
        .page-title-section h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1f2937;
            margin: 0;
        }
        
        .page-description {
            color: #6b7280;
            margin-top: 4px;
            font-size: 0.95rem;
        }
        
        /* White Cards */
        .card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
            margin-bottom: 20px;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .card-header h3 {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }
        
        .card-header h3 i {
            color: #6366f1;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
            margin-bottom: 24px;
        }
        
        .stat-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
            transition: all 0.2s ease;
        }
        
        .stat-card:hover {
            border-color: #d1d5db;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .stat-label {
            font-size: 0.75rem;
            color: #6b7280;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
            line-height: 1;
        }
        
        /* Search and Filters */
        .search-container {
            background: white;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 20px;
            border: 1px solid #e5e7eb;
        }
        
        .search-form {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        
        .search-input {
            flex: 1;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        /* Table */
        .table-container {
            overflow-x: auto;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
        }
        
        .data-table th {
            background: #f9fafb;
            padding: 12px 16px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 1px solid #e5e7eb;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .data-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #f3f4f6;
            color: #1f2937;
        }
        
        .data-table tbody tr:hover {
            background: #f9fafb;
        }
        
        /* Status Badges */
        .status-badge {
            padding: 4px 8px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.75rem;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-pending {
            background: #f3f4f6;
            color: #374151;
        }
        
        .status-sent {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .status-delivered {
            background: #dcfce7;
            color: #166534;
        }
        
        .status-opened {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-clicked {
            background: #fce7f3;
            color: #9d174d;
        }
        
        .status-replied {
            background: #ede9fe;
            color: #5b21b6;
        }
        
        .status-bounced {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .status-unsubscribed {
            background: #f3f4f6;
            color: #6b7280;
        }
        
        /* Action Buttons */
        .action-btn {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            cursor: pointer;
            border: 1px solid #d1d5db;
            background: white;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        
        .action-btn:hover {
            background: #f3f4f6;
        }
        
        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.875rem;
            cursor: pointer;
            border: 1px solid #d1d5db;
            background: white;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }
        
        .btn-primary {
            background: #6366f1;
            color: white;
            border-color: #6366f1;
        }
        
        .btn-primary:hover {
            background: #4f46e5;
            border-color: #4f46e5;
        }
        
        .btn-secondary {
            background: white;
            color: #374151;
            border-color: #d1d5db;
        }
        
        .btn-secondary:hover {
            background: #f9fafb;
            border-color: #9ca3af;
        }
        
        .btn-danger {
            background: #ef4444;
            color: white;
            border-color: #ef4444;
        }
        
        .btn-danger:hover {
            background: #dc2626;
            border-color: #dc2626;
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin-top: 24px;
        }
        
        .pagination a {
            padding: 6px 12px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            text-decoration: none;
            color: #374151;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        
        .pagination a:hover {
            background: #f3f4f6;
        }
        
        .pagination .active {
            background: #6366f1;
            color: white;
            border-color: #6366f1;
        }
        
        /* Alert Messages */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: #dcfce7;
            border: 1px solid #bbf7d0;
            color: #166534;
        }
        
        .alert-error {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }
        
        .alert i {
            font-size: 1rem;
        }
        
        /* Checkbox */
        .checkbox-cell {
            width: 40px;
            text-align: center;
        }
        
        /* Bulk Actions */
        .bulk-actions {
            background: #f9fafb;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            display: flex;
            gap: 12px;
            align-items: center;
            border: 1px solid #e5e7eb;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .main-content {
                padding: 16px;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .search-form {
                flex-direction: column;
            }
            
            .bulk-actions {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <?php include 'components/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Page Header -->
            <div class="page-header">
                <div class="page-title-section">
                    <h1>Manage Prospects</h1>
                    <p class="page-description">Campaign: <?php echo htmlspecialchars($campaign_name); ?></p>
                </div>
                <div class="header-actions">
                    <a href="campaign_view.php?id=<?php echo $campaign_id; ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Campaign
                    </a>
                    <a href="prospects.php?campaign=<?php echo $campaign_id; ?>" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Add Prospects
                    </a>
                </div>
            </div>

            <!-- Success/Error Messages -->
            <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $success_message; ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error_message; ?></span>
            </div>
            <?php endif; ?>

            <!-- Status Overview -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">
                        <i class="fas fa-clock"></i> Pending
                    </div>
                    <div class="stat-value"><?php echo $status_stats['pending']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">
                        <i class="fas fa-paper-plane"></i> Sent
                    </div>
                    <div class="stat-value"><?php echo $status_stats['sent']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">
                        <i class="fas fa-check-circle"></i> Delivered
                    </div>
                    <div class="stat-value"><?php echo $status_stats['delivered']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">
                        <i class="fas fa-envelope-open"></i> Opened
                    </div>
                    <div class="stat-value"><?php echo $status_stats['opened']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">
                        <i class="fas fa-mouse-pointer"></i> Clicked
                    </div>
                    <div class="stat-value"><?php echo $status_stats['clicked']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">
                        <i class="fas fa-reply"></i> Replied
                    </div>
                    <div class="stat-value"><?php echo $status_stats['replied']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">
                        <i class="fas fa-exclamation-circle"></i> Bounced
                    </div>
                    <div class="stat-value"><?php echo $status_stats['bounced']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">
                        <i class="fas fa-ban"></i> Unsubscribed
                    </div>
                    <div class="stat-value"><?php echo $status_stats['unsubscribed']; ?></div>
                </div>
            </div>

            <!-- Search and Filters -->
            <div class="search-container">
                <form method="GET" class="search-form">
                    <input type="hidden" name="id" value="<?php echo $campaign_id; ?>">
                    <input type="text" 
                           name="search" 
                           class="search-input" 
                           placeholder="Search by email, name, or company..."
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <?php if (!empty($search)): ?>
                    <a href="campaign_prospects.php?id=<?php echo $campaign_id; ?>" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Bulk Actions -->
            <form method="POST" id="bulkActionForm">
                <input type="hidden" name="action" id="bulkAction">
                <div class="bulk-actions">
                    <div>
                        <select name="status" class="search-input" style="width: auto;" id="bulkStatusSelect">
                            <option value="">Bulk Status Update</option>
                            <option value="pending">Pending</option>
                            <option value="sent">Sent</option>
                            <option value="delivered">Delivered</option>
                            <option value="opened">Opened</option>
                            <option value="clicked">Clicked</option>
                            <option value="replied">Replied</option>
                            <option value="bounced">Bounced</option>
                            <option value="unsubscribed">Unsubscribed</option>
                        </select>
                        <button type="button" class="btn btn-secondary" onclick="applyBulkStatus()">
                            <i class="fas fa-sync-alt"></i> Apply
                        </button>
                    </div>
                    <button type="button" class="btn btn-danger" onclick="removeSelected()">
                        <i class="fas fa-trash"></i> Remove Selected
                    </button>
                    <span id="selectedCount">0 prospects selected</span>
                </div>

                <!-- Prospects Table -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-users"></i> Campaign Prospects</h3>
                        <div class="table-info">
                            Total: <?php echo $total_count; ?> prospects
                            <?php if ($total_pages > 1): ?>
                            | Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th class="checkbox-cell">
                                        <input type="checkbox" id="selectAll">
                                    </th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Company</th>
                                    <th>Job Title</th>
                                    <th>Status</th>
                                    <th>Added</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($prospects)): ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 40px; color: #6b7280;">
                                        <i class="fas fa-users" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                                        No prospects found in this campaign.
                                        <a href="prospects.php?campaign=<?php echo $campaign_id; ?>" class="btn btn-primary" style="margin-top: 10px;">
                                            Add Prospects
                                        </a>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($prospects as $prospect): ?>
                                <tr>
                                    <td class="checkbox-cell">
                                        <input type="checkbox" name="prospect_ids[]" value="<?php echo $prospect['prospect_id']; ?>" class="prospect-checkbox">
                                    </td>
                                    <td>
                                        <?php if (!empty($prospect['first_name']) || !empty($prospect['last_name'])): ?>
                                            <?php echo htmlspecialchars($prospect['first_name'] . ' ' . $prospect['last_name']); ?>
                                        <?php else: ?>
                                            <span style="color: #9ca3af;">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($prospect['email'] ?? 'N/A'); ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($prospect['company'] ?? 'N/A'); ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($prospect['job_title'] ?? 'N/A'); ?>
                                    </td>
                                    <td>
                                        <?php
                                        $status_class = 'status-' . $prospect['status'];
                                        ?>
                                        <span class="status-badge <?php echo $status_class; ?>">
                                            <i class="fas fa-circle"></i>
                                            <?php echo ucfirst($prospect['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo date('M j, Y', strtotime($prospect['created_at'])); ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons" style="display: flex; gap: 4px;">
                                            <select class="action-btn status-select" data-prospect-id="<?php echo $prospect['prospect_id']; ?>">
                                                <option value="">Change Status</option>
                                                <option value="pending" <?php echo $prospect['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="sent" <?php echo $prospect['status'] == 'sent' ? 'selected' : ''; ?>>Sent</option>
                                                <option value="delivered" <?php echo $prospect['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                                <option value="opened" <?php echo $prospect['status'] == 'opened' ? 'selected' : ''; ?>>Opened</option>
                                                <option value="clicked" <?php echo $prospect['status'] == 'clicked' ? 'selected' : ''; ?>>Clicked</option>
                                                <option value="replied" <?php echo $prospect['status'] == 'replied' ? 'selected' : ''; ?>>Replied</option>
                                                <option value="bounced" <?php echo $prospect['status'] == 'bounced' ? 'selected' : ''; ?>>Bounced</option>
                                                <option value="unsubscribed" <?php echo $prospect['status'] == 'unsubscribed' ? 'selected' : ''; ?>>Unsubscribed</option>
                                            </select>
                                            <button type="button" class="action-btn" onclick="removeProspect(<?php echo $prospect['prospect_id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </form>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                <a href="campaign_prospects.php?id=<?php echo $campaign_id; ?>&page=1&search=<?php echo urlencode($search); ?>">
                    <i class="fas fa-angle-double-left"></i>
                </a>
                <a href="campaign_prospects.php?id=<?php echo $campaign_id; ?>&page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">
                    <i class="fas fa-angle-left"></i>
                </a>
                <?php endif; ?>
                
                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                for ($i = $start_page; $i <= $end_page; $i++):
                ?>
                <a href="campaign_prospects.php?id=<?php echo $campaign_id; ?>&page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"
                   class="<?php echo $i == $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                <a href="campaign_prospects.php?id=<?php echo $campaign_id; ?>&page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">
                    <i class="fas fa-angle-right"></i>
                </a>
                <a href="campaign_prospects.php?id=<?php echo $campaign_id; ?>&page=<?php echo $total_pages; ?>&search=<?php echo urlencode($search); ?>">
                    <i class="fas fa-angle-double-right"></i>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
    // Checkbox selection
    document.addEventListener('DOMContentLoaded', function() {
        const selectAllCheckbox = document.getElementById('selectAll');
        const prospectCheckboxes = document.querySelectorAll('.prospect-checkbox');
        const selectedCount = document.getElementById('selectedCount');
        
        // Select all functionality
        selectAllCheckbox.addEventListener('change', function() {
            prospectCheckboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
            updateSelectedCount();
        });
        
        // Individual checkbox change
        prospectCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                updateSelectedCount();
                updateSelectAllCheckbox();
            });
        });
        
        // Status select change
        document.querySelectorAll('.status-select').forEach(select => {
            select.addEventListener('change', function() {
                if (this.value) {
                    updateProspectStatus(this.dataset.prospectId, this.value);
                }
            });
        });
        
        function updateSelectedCount() {
            const selected = document.querySelectorAll('.prospect-checkbox:checked').length;
            selectedCount.textContent = selected + ' prospects selected';
        }
        
        function updateSelectAllCheckbox() {
            const allChecked = prospectCheckboxes.length > 0 && 
                Array.from(prospectCheckboxes).every(checkbox => checkbox.checked);
            selectAllCheckbox.checked = allChecked;
        }
        
        updateSelectedCount();
    });
    
    // Update prospect status
    function updateProspectStatus(prospectId, status) {
        if (!confirm('Are you sure you want to update the status?')) {
            return;
        }
        
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'update_status';
        
        const prospectInput = document.createElement('input');
        prospectInput.type = 'hidden';
        prospectInput.name = 'prospect_id';
        prospectInput.value = prospectId;
        
        const statusInput = document.createElement('input');
        statusInput.type = 'hidden';
        statusInput.name = 'status';
        statusInput.value = status;
        
        form.appendChild(actionInput);
        form.appendChild(prospectInput);
        form.appendChild(statusInput);
        
        document.body.appendChild(form);
        form.submit();
    }
    
    // Bulk status update
    function applyBulkStatus() {
        const status = document.getElementById('bulkStatusSelect').value;
        const selected = document.querySelectorAll('.prospect-checkbox:checked');
        
        if (!status) {
            alert('Please select a status');
            return;
        }
        
        if (selected.length === 0) {
            alert('Please select at least one prospect');
            return;
        }
        
        if (!confirm('Update status for ' + selected.length + ' prospects?')) {
            return;
        }
        
        const form = document.getElementById('bulkActionForm');
        const prospectIdsInput = document.createElement('input');
        prospectIdsInput.type = 'hidden';
        prospectIdsInput.name = 'prospect_ids';
        prospectIdsInput.value = Array.from(selected).map(cb => cb.value).join(',');
        
        form.appendChild(prospectIdsInput);
        document.getElementById('bulkAction').value = 'update_status';
        form.submit();
    }
    
    // Remove selected prospects
    function removeSelected() {
        const selected = document.querySelectorAll('.prospect-checkbox:checked');
        
        if (selected.length === 0) {
            alert('Please select at least one prospect');
            return;
        }
        
        if (!confirm('Are you sure you want to remove ' + selected.length + ' prospects from this campaign?')) {
            return;
        }
        
        const form = document.getElementById('bulkActionForm');
        const prospectIdsInput = document.createElement('input');
        prospectIdsInput.type = 'hidden';
        prospectIdsInput.name = 'prospect_ids';
        prospectIdsInput.value = Array.from(selected).map(cb => cb.value).join(',');
        
        form.appendChild(prospectIdsInput);
        document.getElementById('bulkAction').value = 'remove_prospects';
        form.submit();
    }
    
    // Remove single prospect
    function removeProspect(prospectId) {
        if (!confirm('Are you sure you want to remove this prospect from the campaign?')) {
            return;
        }
        
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'remove_prospects';
        
        const prospectInput = document.createElement('input');
        prospectInput.type = 'hidden';
        prospectInput.name = 'prospect_ids[]';
        prospectInput.value = prospectId;
        
        form.appendChild(actionInput);
        form.appendChild(prospectInput);
        
        document.body.appendChild(form);
        form.submit();
    }
    
    // Prevent form resubmission on refresh
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
    </script>
</body>
</html>