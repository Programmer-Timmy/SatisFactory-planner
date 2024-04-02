<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../static/phpmailer/src/Exception.php';
require '../static/phpmailer/src/PHPMailer.php';
require '../static/phpmailer/src/SMTP.php';

class Mailer
{

    public static function sendEmail($to, $subject, $body)
    {
        global $email;
        $encryption = '';
        if ($email['SMTPSecure'] == 'tls') {
            $encryption = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif ($email['SMTPSecure'] == 'ssl') {
            $encryption = PHPMailer::ENCRYPTION_SMTPS;
        }
        $mail = new PHPMailer(true);

        try {

            $mail->isSMTP();
            $mail->Host = $email['host'];
            $mail->SMTPAuth = $email['SMTPAuth'];
            $mail->Username = $email['username'];
            $mail->Password = $email['password'];
            $mail->SMTPSecure = $encryption;
            $mail->Port = $email['port'];

            $mail->setFrom($email['form']['email'], $email['from']['name']);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;

            $mail->send();
            return true;

        } catch (Exception $e) {
            return "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    }
}