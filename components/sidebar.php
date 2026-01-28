<?php
// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
?>
<style>
</style>
<nav class="sidebar">
    <!-- Logo -->
    <div class="sidebar-logo">
        <h2><i class="fas fa-robot"></i> ZigTex</h2>
        <div class="user-info">
            <i class="fas fa-user-circle"></i>
            <div>
                <strong><?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Demo User'; ?></strong>
                <span>Sales Manager</span>
            </div>
        </div>
    </div>

    <!-- Navigation - Only requested items -->
    <ul class="sidebar-nav">
        <li class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
            <a href="dashboard.php">
                <i class="fas fa-chart-bar"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="<?php echo ($current_page == 'campaign.php') ? 'active' : ''; ?>">
            <a href="campaign.php">
                <i class="fas fa-bullhorn"></i>
                <span>Campaigns</span>
            </a>
        </li>
        <li class="<?php echo ($current_page == 'inbox.php') ? 'active' : ''; ?>">
            <a href="inbox.php">
                <i class="fas fa-inbox"></i>
                <span>Inbox</span>
            </a>
        </li>
        <li class="<?php echo ($current_page == 'prospects.php') ? 'active' : ''; ?>">
            <a href="prospects.php">
                <i class="fas fa-users"></i>
                <span>Prospects</span>
            </a>
        </li>
        <li class="<?php echo ($current_page == 'companies.php') ? 'active' : ''; ?>">
            <a href="companies.php">
                <i class="fas fa-building"></i>
                <span>Companies</span>
            </a>
        </li>
    </ul>

    <!-- Logout Button -->
    <div class="sidebar-footer">
        <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
        <div class="version">v1.0.0</div>
    </div>
</nav>