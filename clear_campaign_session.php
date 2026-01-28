<?php
session_start();

// Clear campaign session data
if (isset($_SESSION['campaign_data'])) {
    unset($_SESSION['campaign_data']);
}

echo json_encode(['success' => true]);
?>