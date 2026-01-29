<?php
// api/smtp_list.php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    $pdo = Database::getInstance();
    $stmt = $pdo->query("SELECT id, name, from_email FROM smtp_configs WHERE is_active = 1");
    $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($configs);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>