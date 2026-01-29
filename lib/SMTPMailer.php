<?php
// lib/SMTPMailer.php

require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class SMTPMailer
{
    private $host;
    private $port;
    private $username;
    private $password;
    private $encryption;

    public function __construct($config)
    {
        $this->host       = $config['host'];
        $this->port       = $config['port'];
        $this->username   = $config['username'];
        $this->password   = $config['password'];
        $this->encryption = $config['encryption'];
    }

    public function sendEmail($fromEmail, $fromName, $toEmail, $subject, $body)
    {
        $mail = new PHPMailer(true);

        try {
            // SMTP config
            $mail->isSMTP();
            $mail->Host       = $this->host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->username;
            $mail->Password   = $this->password;
            $mail->Port       = $this->port;

            if ($this->encryption === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($this->encryption === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            }

            // Sender & receiver
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($toEmail);

            // Email content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;

            $mail->send();

            return [
                'success' => true,
                'message' => 'Email sent successfully'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $mail->ErrorInfo
            ];
        }
    }
}
