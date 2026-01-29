<?php
header('Content-Type: application/json');
require_once '../config/database.php';

// Get campaign ID from query parameter
$campaignId = $_GET['campaign_id'] ?? 0;

try {
    // Connect to database
    $pdo = new PDO(
        "mysql:host=localhost;dbname=zigtex;charset=utf8mb4",
        "root", // Change to your DB username
        ""      // Change to your DB password
    );
    
    // Get campaign progress
    $stmt = $pdo->prepare("
        SELECT 
            total_contacts,
            sent_count,
            failed_count,
            status
        FROM campaigns 
        WHERE id = ?
    ");
    $stmt->execute([$campaignId]);
    $campaign = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$campaign) {
        echo json_encode(['error' => 'Campaign not found']);
        exit;
    }
    
    // Calculate percentage
    $total = $campaign['total_contacts'];
    $sent = $campaign['sent_count'];
    $failed = $campaign['failed_count'];
    $percent = $total > 0 ? round((($sent + $failed) / $total) * 100) : 0;
    
    echo json_encode([
        'success' => true,
        'total' => $total,
        'sent' => $sent,
        'failed' => $failed,
        'percent' => $percent,
        'status' => $campaign['status']
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>