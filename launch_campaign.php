<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$campaignId = $input['campaign_id'] ?? 0;

if ($campaignId) {
    try {
        $db = db();
        $stmt = $db->prepare("UPDATE campaigns SET status = 'running', launched_at = NOW() WHERE id = ? AND user_id = ?");
        $stmt->execute([$campaignId, $_SESSION['user_id']]);
        
        echo json_encode(['success' => true, 'message' => 'Campaign launched']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid campaign']);
}
?>