<?php
// track_open.php
require_once 'db_config.php';

if (isset($_GET['queue_id'])) {
    $queueId = (int)$_GET['queue_id'];
    
    try {
        $pdo = getPDO();
        
        // Update email open time
        $stmt = $pdo->prepare("
            UPDATE email_queue 
            SET opened_at = NOW(), 
                status = CASE WHEN status = 'sent' THEN 'opened' ELSE status END
            WHERE id = ?
        ");
        $stmt->execute([$queueId]);
        
        // Get campaign info for stats
        $campaignStmt = $pdo->prepare("
            SELECT campaign_id, prospect_id FROM email_queue WHERE id = ?
        ");
        $campaignStmt->execute([$queueId]);
        $emailData = $campaignStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($emailData) {
            // Update campaign open count
            $updateStmt = $pdo->prepare("
                UPDATE campaigns 
                SET total_opened = COALESCE(total_opened, 0) + 1,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $updateStmt->execute([$emailData['campaign_id']]);
            
            // Log tracking
            $logStmt = $pdo->prepare("
                INSERT INTO email_tracking (
                    queue_id, prospect_id, campaign_id, action, 
                    ip_address, user_agent, created_at
                ) VALUES (?, ?, ?, 'open', ?, ?, NOW())
            ");
            $logStmt->execute([
                $queueId,
                $emailData['prospect_id'],
                $emailData['campaign_id'],
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT']
            ]);
        }
        
    } catch (Exception $e) {
        // Silent fail for tracking
    }
}

// Return 1x1 transparent pixel
header('Content-Type: image/gif');
echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
