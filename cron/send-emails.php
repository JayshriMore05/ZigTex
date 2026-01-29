
<?php
// cron/send-emails.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/QueueModel.php';
require_once __DIR__ . '/../lib/SMTPMailer.php';

set_time_limit(0);
date_default_timezone_set('UTC');

$logFile = __DIR__ . '/../logs/send.log';
$lockFile = __DIR__ . '/../logs/send.lock';

/* ================= LOCK ================= */
if (file_exists($lockFile) && time() - filemtime($lockFile) < 290) {
    exit;
}
file_put_contents($lockFile, time());

$pdo = Database::getInstance();
$queueModel = new QueueModel($pdo);

file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Starting email sending\n", FILE_APPEND);

/* ================= FETCH PENDING EMAILS ================= */
$stmt = $pdo->prepare("
    SELECT cq.*
    FROM campaign_queue cq
    JOIN campaign_schedules cs ON cs.campaign_id = cq.campaign_id
    WHERE cq.status = 'pending'
      AND cq.scheduled_at <= NOW()
      AND (
        (DAYOFWEEK(NOW()) = 2 AND cs.monday = 1) OR
        (DAYOFWEEK(NOW()) = 3 AND cs.tuesday = 1) OR
        (DAYOFWEEK(NOW()) = 4 AND cs.wednesday = 1) OR
        (DAYOFWEEK(NOW()) = 5 AND cs.thursday = 1) OR
        (DAYOFWEEK(NOW()) = 6 AND cs.friday = 1) OR
        (DAYOFWEEK(NOW()) = 7 AND cs.saturday = 1) OR
        (DAYOFWEEK(NOW()) = 1 AND cs.sunday = 1)
      )
      AND TIME(CONVERT_TZ(NOW(),'UTC',cs.timezone))
          BETWEEN cs.start_time AND cs.end_time
    ORDER BY cq.id ASC
    LIMIT 10
");
$stmt->execute();
$emails = $stmt->fetchAll(PDO::FETCH_ASSOC);

file_put_contents(
    $logFile,
    "[" . date('Y-m-d H:i:s') . "] Found " . count($emails) . " pending emails\n",
    FILE_APPEND
);

foreach ($emails as $job) {

    try {
        /* ================= SEND EMAIL ================= */
        $mailer = new SMTPMailer([
            'host'       => $job['smtp_host'],
            'port'       => $job['smtp_port'],
            'username'   => $job['username'],
            'password'   => $job['password'],
            'encryption' => $job['encryption']
        ]);

        $subject = str_replace(
            ['{{first_name}}','{{last_name}}','{{company}}','{{email}}'],
            [$job['first_name'],$job['last_name'],$job['company'],$job['email']],
            $job['subject']
        );

        $body = str_replace(
            ['{{first_name}}','{{last_name}}','{{company}}','{{email}}'],
            [$job['first_name'],$job['last_name'],$job['company'],$job['email']],
            $job['body']
        );

        $result = $mailer->sendEmail(
            $job['from_email'],
            $job['from_name'],
            $job['email'],
            $subject,
            $body
        );

        if (!$result['success']) {
            throw new Exception($result['message']);
        }

        /* ================= MARK SENT ================= */
        $queueModel->updateStatus($job['id'], 'sent');

        file_put_contents(
            $logFile,
            "[" . date('Y-m-d H:i:s') . "] Sent to {$job['email']}\n",
            FILE_APPEND
        );

        /* ================= SCHEDULE NEXT FOLLOW-UP ================= */
        $nextStep = $job['step_no'] + 1;

        $seqStmt = $pdo->prepare("
            SELECT * FROM email_sequences
            WHERE campaign_id = ? AND step_no = ?
        ");
        $seqStmt->execute([$job['campaign_id'], $nextStep]);
        $nextSeq = $seqStmt->fetch(PDO::FETCH_ASSOC);

        if ($nextSeq) {

            $scheduleAt = date(
                'Y-m-d H:i:s',
                time() + ($nextSeq['delay_minutes'] * 60)
            );

            $pdo->prepare("
                INSERT INTO campaign_queue (
                    campaign_id, step_no,
                    first_name, last_name, company, email,
                    subject, body,
                    smtp_host, smtp_port, username, password, encryption,
                    from_email, from_name,
                    status, scheduled_at
                ) VALUES (
                    ?,?,
                    ?,?,?,?,
                    ?,?,
                    ?,?,?,?,?,
                    ?,?,
                    'pending',?
                )
            ")->execute([
                $job['campaign_id'],
                $nextStep,
                $job['first_name'],
                $job['last_name'],
                $job['company'],
                $job['email'],
                $nextSeq['subject'],
                $nextSeq['body'],
                $job['smtp_host'],
                $job['smtp_port'],
                $job['username'],
                $job['password'],
                $job['encryption'],
                $job['from_email'],
                $job['from_name'],
                $scheduleAt
            ]);
        }

        sleep(2);

    } catch (Exception $e) {

        $queueModel->updateStatus($job['id'], 'failed', $e->getMessage());

        file_put_contents(
            $logFile,
            "[" . date('Y-m-d H:i:s') . "] Failed {$job['email']} : {$e->getMessage()}\n",
            FILE_APPEND
        );
    }
}

unlink($lockFile);
