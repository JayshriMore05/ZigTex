<?php
// campaign_queue.php

session_start();
require_once __DIR__ . '/config/database.php';

header('Content-Type: application/json');

try {

    // =========================
    // 1. Validate input
    // =========================
    if (
        empty($_POST['campaign_id']) ||
        empty($_POST['list_type']) ||
        empty($_POST['limit'])
    ) {
        throw new Exception("Invalid request parameters");
    }

    $campaignId = (int) $_POST['campaign_id'];
    $listType   = $_POST['list_type']; // recent | hot | all
    $limit      = (int) $_POST['limit'];

    if (!in_array($listType, ['recent', 'hot', 'all'])) {
        throw new Exception("Invalid list type");
    }

    if ($limit <= 0) {
        throw new Exception("Invalid limit");
    }

    // =========================
    // 2. DB connection
    // =========================
    $pdo = Database::getInstance();

    // =========================
    // 3. Fetch prospects
    // =========================
    $stmt = $pdo->prepare("
        SELECT 
            first_name,
            last_name,
            company,
            email
        FROM prospects
        WHERE tag = :tag
        ORDER BY id ASC
        LIMIT :limit
    ");
    $stmt->bindValue(':tag', $listType, PDO::PARAM_STR);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $prospects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$prospects) {
        echo json_encode([
            'success' => true,
            'message' => 'No contacts found for selected list',
            'inserted' => 0
        ]);
        exit;
    }

    // =========================
    // 4. Campaign email content
    // (temporary – later UI मधून येईल)
    // =========================
    $subject = "Follow-up from ZigTex";
    $body    = "Hello {{first_name}},<br><br>This is a follow-up email from ZigTex 🙂";

    // SMTP (later per-user config येईल)
    $smtpHost = "smtp.gmail.com";
    $smtpPort = 587;
    $smtpUser = "your@gmail.com";
    $smtpPass = "APP_PASSWORD";
    $encryption = "tls";
    $fromEmail = "your@gmail.com";
    $fromName  = "ZigTex";

    // =========================
    // 5. Insert into queue
    // =========================
    $insert = $pdo->prepare("
        INSERT INTO campaign_queue (
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
            from_name,
            status
        ) VALUES (
            :campaign_id,
            :first_name,
            :last_name,
            :company,
            :email,
            :subject,
            :body,
            :smtp_host,
            :smtp_port,
            :username,
            :password,
            :encryption,
            :from_email,
            :from_name,
            'pending'
        )
    ");

    $count = 0;

    foreach ($prospects as $p) {

        // avoid duplicate queue entries
        $check = $pdo->prepare("
            SELECT id FROM campaign_queue
            WHERE campaign_id = ? AND email = ?
        ");
        $check->execute([$campaignId, $p['email']]);

        if ($check->fetch()) {
            continue;
        }

        $insert->execute([
            ':campaign_id' => $campaignId,
            ':first_name'  => $p['first_name'],
            ':last_name'   => $p['last_name'],
            ':company'     => $p['company'],
            ':email'       => $p['email'],
            ':subject'     => $subject,
            ':body'        => $body,
            ':smtp_host'   => $smtpHost,
            ':smtp_port'   => $smtpPort,
            ':username'    => $smtpUser,
            ':password'    => $smtpPass,
            ':encryption'  => $encryption,
            ':from_email'  => $fromEmail,
            ':from_name'   => $fromName
        ]);

        $count++;
    }

    // =========================
    // 6. Response
    // =========================
    echo json_encode([
        'success'  => true,
        'message'  => 'Contacts added to campaign queue',
        'inserted' => $count
    ]);

} catch (Exception $e) {

    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
