<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Get database connection
    $pdo = db();
    
    // Get campaign stats from database
    $campaign_stats_query = "
        SELECT 
            status,
            COUNT(*) as count,
            SUM(total_prospects) as total_prospects,
            SUM(emails_sent) as emails_sent,
            SUM(emails_replied) as emails_replied,
            SUM(emails_bounced) as emails_bounced
        FROM campaigns 
        WHERE user_id = :user_id 
        GROUP BY status
    ";
    
    $stmt = $pdo->prepare($campaign_stats_query);
    $stmt->execute([':user_id' => $user_id]);
    $campaign_stats = $stmt->fetchAll();
    
    // Get campaign counts by status
    $status_counts = [
        'running' => 0,
        'paused' => 0,
        'completed' => 0,
        'draft' => 0,
        'scheduled' => 0
    ];
    
    foreach ($campaign_stats as $stat) {
        $status_counts[$stat['status']] = $stat['count'];
    }
    
    // Get all campaigns for the table
    $campaigns_query = "
        SELECT 
            c.*,
            (SELECT COUNT(*) FROM campaign_prospects WHERE campaign_id = c.id) as total_prospects_count,
            (SELECT COUNT(*) FROM campaign_prospects WHERE campaign_id = c.id AND status IN ('sent', 'delivered', 'opened', 'clicked', 'replied')) as emails_sent_count,
            (SELECT COUNT(*) FROM campaign_prospects WHERE campaign_id = c.id AND status = 'replied') as replies_count,
            (SELECT COUNT(*) FROM campaign_prospects WHERE campaign_id = c.id AND status = 'bounced') as bounced_count
        FROM campaigns c
        WHERE c.user_id = :user_id
        ORDER BY c.created_at DESC
        LIMIT 50
    ";
    
    $stmt = $pdo->prepare($campaigns_query);
    $stmt->execute([':user_id' => $user_id]);
    $campaigns = $stmt->fetchAll();
    
    // Calculate open rates
    foreach ($campaigns as &$campaign) {
        $emails_sent = $campaign['emails_sent'] > 0 ? $campaign['emails_sent'] : $campaign['emails_sent_count'];
        $replies = $campaign['emails_replied'] > 0 ? $campaign['emails_replied'] : $campaign['replies_count'];
        $bounced = $campaign['emails_bounced'] > 0 ? $campaign['emails_bounced'] : $campaign['bounced_count'];
        
        // Calculate open rate (for demo, using random values)
        $campaign['open_rate'] = $emails_sent > 0 ? rand(20, 50) : 0;
    }
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campaigns - ZigTex</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/campaign.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <style>
    /* Campaign-specific styles matching dashboard */
    .campaign-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 32px;
        padding-bottom: 16px;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .campaign-header h1 {
        font-size: 24px;
        color: #1f2937;
        font-weight: 700;
        margin: 0;
    }
    
    .create-campaign-btn {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 12px 24px;
        background: #2563eb;
        color: white;
        border: none;
        border-radius: 6px;
        font-weight: 500;
        cursor: pointer;
        text-decoration: none;
        transition: all 0.2s;
        font-size: 14px;
    }
    
    .create-campaign-btn:hover {
        background: #1d4ed8;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
    }
    
    .campaign-table-section {
        background: white;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
        overflow: hidden;
    }
    
    .campaign-table-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 24px;
        border-bottom: 1px solid #f3f4f6;
    }
    
    .campaign-table-header h2 {
        font-size: 18px;
        color: #1f2937;
        font-weight: 600;
        margin: 0;
    }
    
    .campaign-search-box {
        position: relative;
        width: 300px;
    }
    
    .campaign-search-box input {
        width: 100%;
        padding: 10px 16px 10px 40px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        color: #374151;
        background: white;
    }
    
    .campaign-search-box i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
        font-size: 14px;
    }
    
    /* Campaign Table Styles */
    .campaign-table-container {
        overflow-x: auto;
    }
    
    .campaign-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 1000px;
    }
    
    .campaign-table thead {
        background: #f9fafb;
    }
    
    .campaign-table th {
        padding: 14px 16px;
        text-align: left;
        font-weight: 500;
        color: #6b7280;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .campaign-table td {
        padding: 16px;
        border-bottom: 1px solid #f3f4f6;
        font-size: 14px;
        color: #374151;
    }
    
    .campaign-table tbody tr:hover {
        background: #f9fafb;
    }
    
    .campaign-name-cell {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    
    .campaign-name-text {
        font-weight: 500;
        color: #1f2937;
        font-size: 15px;
    }
    
    .campaign-name-subtext {
        font-size: 12px;
        color: #6b7280;
    }
    
    .campaign-status-badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    
    .status-new {
        background: #dcfce7;
        color: #166534;
    }
    
    .status-running {
        background: #dbeafe;
        color: #1e40af;
    }
    
    .status-paused {
        background: #fef3c7;
        color: #92400e;
    }
    
    .status-completed {
        background: #f3f4f6;
        color: #4b5563;
    }
    
    .campaign-stats-cell {
        font-weight: 500;
        color: #1f2937;
        font-size: 14px;
    }
    
    .open-rate-cell {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .open-rate-indicator {
        width: 40px;
        height: 4px;
        background: #e5e7eb;
        border-radius: 2px;
        overflow: hidden;
    }
    
    .open-rate-fill {
        height: 100%;
        background: #10b981;
    }
    
    .replies-cell {
        color: #3b82f6;
        font-weight: 500;
    }
    
    .bounced-cell {
        color: #ef4444;
        font-weight: 500;
    }
    
    .campaign-date-cell {
        font-size: 13px;
        color: #6b7280;
    }
    
    .campaign-actions-cell {
        display: flex;
        gap: 8px;
    }
    
    .campaign-action-btn {
        width: 32px;
        height: 32px;
        border-radius: 4px;
        border: 1px solid #e5e7eb;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 13px;
        transition: all 0.2s;
        background: white;
        color: #6b7280;
    }
    
    .campaign-action-btn:hover {
        background: #f9fafb;
        border-color: #d1d5db;
    }
    
    /* Pagination */
    .pagination-section {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 20px 24px;
        border-top: 1px solid #e5e7eb;
    }
    
    .pagination-info {
        font-size: 14px;
        color: #6b7280;
    }
    
    .pagination-controls {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .pagination-btn {
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        background: white;
        color: #374151;
        border-radius: 4px;
        cursor: pointer;
        font-size: 13px;
        display: flex;
        align-items: center;
        gap: 4px;
        transition: all 0.2s;
    }
    
    .pagination-btn:hover {
        background: #f3f4f6;
        border-color: #9ca3af;
    }
    
    .pagination-btn.active {
        background: #3b82f6;
        color: white;
        border-color: #3b82f6;
    }
    
    .pagination-btn.disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    .pagination-btn.disabled:hover {
        background: white;
        border-color: #d1d5db;
    }
    
    .page-numbers {
        display: flex;
        gap: 4px;
    }
    
    .campaign-empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #6b7280;
    }
    
    .campaign-empty-state i {
        font-size: 48px;
        color: #d1d5db;
        margin-bottom: 20px;
    }
    
    .campaign-empty-state h3 {
        font-size: 18px;
        color: #374151;
        margin-bottom: 8px;
    }
    
    .campaign-empty-state p {
        font-size: 14px;
        margin-bottom: 20px;
    }
    
    /* Sidebar Navigation Styles */
    .sidebar-nav {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .sidebar-nav li {
        margin-bottom: 4px;
    }
    
    .sidebar-nav a {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        color: #6b7280;
        text-decoration: none;
        border-radius: 6px;
        transition: all 0.2s;
        font-size: 14px;
        font-weight: 500;
    }
    
    .sidebar-nav a:hover {
        background: #f3f4f6;
        color: #374151;
    }
    
    .sidebar-nav a.active {
        background: #eff6ff;
        color: #2563eb;
        font-weight: 600;
    }
    
    .sidebar-nav a i {
        width: 20px;
        text-align: center;
    }
    
    /* REMOVE ALL FOCUS BORDERS AND OUTLINES */
    *:focus {
        outline: none !important;
        box-shadow: none !important;
    }
    
    input:focus,
    select:focus,
    button:focus,
    a:focus,
    .campaign-action-btn:focus,
    .pagination-btn:focus,
    .create-campaign-btn:focus {
        outline: none !important;
        box-shadow: none !important;
        border-color: #d1d5db !important;
    }
    
    @media (max-width: 768px) {
        .campaign-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 16px;
        }
        
        .campaign-table-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 16px;
        }
        
        .campaign-search-box {
            width: 100%;
        }
        
        .pagination-section {
            flex-direction: column;
            gap: 16px;
            align-items: flex-start;
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
            <!-- Campaign Header -->
            <div class="campaign-header">
                <h1>Campaign</h1>
                <a href="campaign_create.php" class="create-campaign-btn">
                    <i class="fas fa-plus"></i> Create Campaign
                </a>
            </div>

            <!-- Campaign Table Section -->
            <section class="campaign-table-section">
                <div class="campaign-table-header">
                    <h2>Search campaigns</h2>
                    <div class="campaign-search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="campaignSearch" placeholder="Search campaigns..." autocomplete="off">
                    </div>
                </div>

                <div class="campaign-table-container">
                    <table class="campaign-table">
                        <thead>
                            <tr>
                                <th style="width: 50px;"></th>
                                <th>CAMPAIGN NAME</th>
                                <th>STATUS</th>
                                <th>OPEN RATE (%)</th>
                                <th>REPLIES</th>
                                <th>BOUNCED</th>
                                <th>CREATED DATE</th>
                            </tr>
                        </thead>
                        <tbody id="campaignsTableBody">
                            <?php if (empty($campaigns)): ?>
                            <tr>
                                <td colspan="7">
                                    <div class="campaign-empty-state">
                                        <i class="fas fa-inbox"></i>
                                        <h3>No campaigns yet</h3>
                                        <p>Create your first campaign to start sending emails</p>
                                        <a href="campaign_create.php" class="create-campaign-btn" style="display: inline-flex;">
                                            <i class="fas fa-plus"></i> Create Campaign
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php 
                                // Map your status to screenshots status
                                $status_map = [
                                    'draft' => 'NEW',
                                    'scheduled' => 'NEW',
                                    'running' => 'ACTIVE',
                                    'paused' => 'PAUSED',
                                    'completed' => 'COMPLETED'
                                ];
                                
                                $status_class_map = [
                                    'draft' => 'new',
                                    'scheduled' => 'new',
                                    'running' => 'running',
                                    'paused' => 'paused',
                                    'completed' => 'completed'
                                ];
                                
                                foreach ($campaigns as $campaign): 
                                    $total_prospects = $campaign['total_prospects'] > 0 ? $campaign['total_prospects'] : $campaign['total_prospects_count'];
                                    $emails_sent = $campaign['emails_sent'] > 0 ? $campaign['emails_sent'] : $campaign['emails_sent_count'];
                                    $replies_count = $campaign['emails_replied'] > 0 ? $campaign['emails_replied'] : $campaign['replies_count'];
                                    $bounced_count = $campaign['emails_bounced'] > 0 ? $campaign['emails_bounced'] : $campaign['bounced_count'];
                                    $open_rate = $campaign['open_rate'];
                                    
                                    $display_status = $status_map[$campaign['status']] ?? 'NEW';
                                    $status_class = $status_class_map[$campaign['status']] ?? 'new';
                                ?>
                                <tr data-campaign-id="<?php echo $campaign['id']; ?>" data-status="<?php echo $campaign['status']; ?>">
                                    <td>
                                        <input type="checkbox" class="campaign-checkbox" value="<?php echo $campaign['id']; ?>">
                                    </td>
                                    <td>
                                        <div class="campaign-name-cell">
                                            <div class="campaign-name-text">
                                                <?php echo htmlspecialchars($campaign['name']); ?>
                                            </div>
                                            <div class="campaign-name-subtext">
                                                <?php 
                                                // Show template count or mapped prospects based on your data
                                                if ($campaign['status'] == 'draft') {
                                                    echo '0 Templates';
                                                } elseif ($campaign['status'] == 'scheduled') {
                                                    echo '0 Mapped';
                                                } else {
                                                    echo $total_prospects . ' Prospects';
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="campaign-status-badge status-<?php echo $status_class; ?>">
                                            <?php echo $display_status; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="open-rate-cell">
                                            <span><?php echo $open_rate; ?>%</span>
                                            <div class="open-rate-indicator">
                                                <div class="open-rate-fill" style="width: <?php echo $open_rate; ?>%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="replies-cell">
                                        <?php echo $replies_count; ?>
                                    </td>
                                    <td class="bounced-cell">
                                        <?php echo $bounced_count; ?>
                                    </td>
                                    <td class="campaign-date-cell">
                                        <?php echo date('M d, Y', strtotime($campaign['created_at'])); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if (count($campaigns) > 0): ?>
                <div class="pagination-section">
                    <div class="pagination-info">
                        Showing 1-12 of 12
                    </div>
                    <div class="pagination-controls">
                        <div class="page-numbers">
                            <button class="pagination-btn">1</button>
                            <button class="pagination-btn">2</button>
                            <button class="pagination-btn">3</button>
                        </div>
                        <button class="pagination-btn">
                            More videos
                        </button>
                    </div>
                </div>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
    // Search functionality
    let searchTimeout;
    document.getElementById('campaignSearch').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#campaignsTableBody tr[data-campaign-id]');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }, 300);
    });
    
    // Checkbox selection
    document.querySelectorAll('.campaign-checkbox').forEach(cb => {
        cb.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });
    
    // Row click (for selection)
    document.querySelectorAll('#campaignsTableBody tr[data-campaign-id]').forEach(row => {
        row.addEventListener('click', function(e) {
            if (e.target.type !== 'checkbox') {
                const checkbox = this.querySelector('.campaign-checkbox');
                checkbox.checked = !checkbox.checked;
            }
        });
    });
    
    function showNotification(message, type = 'success') {
        const colors = {
            success: '#10b981',
            error: '#ef4444',
            warning: '#f59e0b',
            info: '#3b82f6'
        };
        
        if (typeof Toastify !== 'undefined') {
            Toastify({
                text: message,
                duration: 3000,
                close: true,
                gravity: "bottom",
                position: "right",
                backgroundColor: colors[type] || colors.info,
                stopOnFocus: true,
            }).showToast();
        } else {
            alert(message);
        }
    }
    
    // Prevent focus on all elements
    document.addEventListener('DOMContentLoaded', function() {
        // Remove focus from all elements
        const allElements = document.querySelectorAll('*');
        allElements.forEach(element => {
            element.addEventListener('focus', function(e) {
                this.blur();
            });
        });
    });
    </script>
</body>
</html>

<?php
// Helper function for time ago
function time_ago($date) {
    $timestamp = strtotime($date);
    $current_time = time();
    $diff = $current_time - $timestamp;
    
    $intervals = array(
        31536000 => 'year',
        2592000 => 'month',
        604800 => 'week',
        86400 => 'day',
        3600 => 'hour',
        60 => 'minute',
        1 => 'second'
    );
    
    foreach ($intervals as $seconds => $label) {
        $div = $diff / $seconds;
        if ($div >= 1) {
            $rounded = floor($div);
            return $rounded . ' ' . $label . ($rounded > 1 ? 's' : '') . ' ago';
        }
    }
    
    return 'just now';
}
?>