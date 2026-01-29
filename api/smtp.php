<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../models/SMTPModel.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Encrypt password before saving
    $encryptedPassword = openssl_encrypt(
        $data['password'], 
        'AES-256-CBC', 
        'your-secret-key', 
        0, 
        'your-iv-vector'
    );
    
    $smtpModel = new SMTPModel($pdo);
    $result = $smtpModel->saveConfig([
        'user_id' => $_SESSION['user_id'],
        'name' => $data['name'],
        'smtp_host' => $data['host'],
        'smtp_port' => $data['port'],
        'username' => $data['username'],
        'password' => $encryptedPassword,
        'encryption' => $data['encryption'],
        'from_name' => $data['from_name'],
        'from_email' => $data['from_email']
    ]);
    
    echo json_encode(['success' => $result]);
}
?>