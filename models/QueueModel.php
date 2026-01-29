<?php
// models/QueueModel.php

class QueueModel
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Get pending emails from campaign_queue
     */
    public function getPending($limit = 10)
    {
        $sql = "
            SELECT
                id,
                campaign_id,
                first_name,
                last_name,
                company,
                email,
                subject,
                body,
                smtp_host,
                smtp_port,
                username,
                password,
                encryption,
                from_email,
                from_name
            FROM campaign_queue
            WHERE status = 'pending'
            ORDER BY id ASC
            LIMIT :limit
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Update email status (FIXED)
     */
    public function updateStatus($queueId, $status, $errorMessage = null)
    {
        // Decide sent_at in PHP (PDO safe)
        $sentAt = ($status === 'sent') ? date('Y-m-d H:i:s') : null;

        $sql = "
            UPDATE campaign_queue
            SET 
                status = :status,
                error_message = :error_message,
                sent_at = :sent_at
            WHERE id = :id
        ";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':status'        => $status,
            ':error_message' => $errorMessage,
            ':sent_at'       => $sentAt,
            ':id'            => $queueId
        ]);
    }

    /**
     * Increment sent count in campaigns table
     */
    public function incrementSentCount($campaignId)
    {
        $stmt = $this->pdo->prepare("
            UPDATE campaigns
            SET sent_count = sent_count + 1
            WHERE id = ?
        ");
        return $stmt->execute([$campaignId]);
    }

    /**
     * Increment failed count in campaigns table
     */
    public function incrementFailedCount($campaignId)
    {
        $stmt = $this->pdo->prepare("
            UPDATE campaigns
            SET failed_count = failed_count + 1
            WHERE id = ?
        ");
        return $stmt->execute([$campaignId]);
    }
}
