<?php
session_start();

// Clear specific session data
if (isset($_GET['campaign_data'])) {
    unset($_SESSION['campaign_data']);
}

echo json_encode(['success' => true, 'message' => 'Session cleared']);
?>