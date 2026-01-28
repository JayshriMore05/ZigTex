<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$campaign_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// In real app, delete from database
// DELETE FROM campaigns WHERE id = ? AND user_id = ?

// For demo, just redirect with success message
$_SESSION['message'] = 'Campaign deleted successfully';
$_SESSION['message_type'] = 'success';

header("Location: campaign.php");
exit();
?>