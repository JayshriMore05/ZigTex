<?php
// api/campaign_create.php
header('Content-Type: application/json');
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$userId = $_SESSION['user_id'] ?? 1; // Default to 1 for testing

try {
    $pdo = Database::getInstance();
    
    // 1. Create campaign
    $stmt = $pdo->prepare("
        INSERT INTO campaigns 
        (user_id, name, smtp_config_id, status, total_contacts) 
        VALUES (?, ?, ?, 'draft', 0)
    ");
    $stmt->execute([$userId, $data['name'], $data['smtp_config_id']]);
    $campaignId = $pdo->lastInsertId();
    
    // 2. Save email sequences
    foreach ($data['sequences'] as $index => $sequence) {
        $stmt = $pdo->prepare("
            INSERT INTO email_sequences 
            (campaign_id, step_order, subject, body) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $campaignId, 
            $index + 1, 
            $sequence['subject'], 
            $sequence['body']
        ]);
    }
    
    // 3. Import contacts from session or CSV
    if (isset($data['contacts']) && !empty($data['contacts'])) {
        $totalContacts = 0;
        
        foreach ($data['contacts'] as $contact) {
            // Check if contact exists
            $stmt = $pdo->prepare("SELECT id FROM contacts WHERE email = ? AND user_id = ?");
            $stmt->execute([$contact['email'], $userId]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                $contactId = $existing['id'];
            } else {
                // Insert new contact
                $stmt = $pdo->prepare("
                    INSERT INTO contacts 
                    (user_id, email, first_name, last_name, company) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $userId,
                    $contact['email'],
                    $contact['first_name'] ?? '',
                    $contact['last_name'] ?? '',
                    $contact['company'] ?? ''
                ]);
                $contactId = $pdo->lastInsertId();
            }
            
            // Add to campaign queue
            $stmt = $pdo->prepare("
                INSERT INTO campaign_queue 
                (campaign_id, contact_id, email_sequence_id, smtp_config_id, status) 
                VALUES (?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([
                $campaignId,
                $contactId,
                1, // First sequence step
                $data['smtp_config_id']
            ]);
            
            $totalContacts++;
        }
        
        // Update total contacts count
        $stmt = $pdo->prepare("UPDATE campaigns SET total_contacts = ? WHERE id = ?");
        $stmt->execute([$totalContacts, $campaignId]);
    }
    
    echo json_encode([
        'success' => true,
        'campaign_id' => $campaignId,
        'message' => 'Campaign created successfully'
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>