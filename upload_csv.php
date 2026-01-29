<?php
require_once "config/database.php";

if (!isset($_FILES['csv_file'])) {
    die("CSV file not uploaded");
}

$campaignId = (int)$_POST['campaign_id'];
$file = $_FILES['csv_file']['tmp_name'];

$pdo = Database::getInstance();

$handle = fopen($file, "r");
if (!$handle) {
    die("Unable to read CSV");
}

$header = fgetcsv($handle); // skip header

$count = 0;

while (($row = fgetcsv($handle)) !== false) {

    [$firstName, $lastName, $company, $email] = $row;

    $stmt = $pdo->prepare("
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
            'smtp.gmail.com',
            587,
            'jayshri.sphurti@gmail.com',
            'GMAIL_APP_PASSWORD_HERE',
            'tls',
            'jayshri.sphurti@gmail.com',
            'ZigTex',
            'pending'
        )
    ");

    $stmt->execute([
        ':campaign_id' => $campaignId,
        ':first_name'  => $firstName,
        ':last_name'   => $lastName,
        ':company'     => $company,
        ':email'       => $email,
        ':subject'     => 'Hello {{first_name}} from ZigTex',
        ':body'        => 'Hi {{first_name}}, welcome to ZigTex 🚀'
    ]);

    $count++;
}

fclose($handle);

echo "✅ $count emails added to queue successfully";
