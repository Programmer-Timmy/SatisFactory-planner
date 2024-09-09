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
        global $emailSettings;
        $encryption = '';
        if ($emailSettings['encryption'] == 'tls') {
            $encryption = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif ($emailSettings['encryption'] == 'ssl') {
            $encryption = PHPMailer::ENCRYPTION_SMTPS;
        }
        $mail = new PHPMailer(true);

        try {

            $mail->isSMTP();
            $mail->Host = $emailSettings['host'];
            $mail->SMTPAuth = $emailSettings['SMTPAuth'];
            $mail->Username = $emailSettings['username'];
            $mail->Password = $emailSettings['password'];
            $mail->SMTPSecure = $encryption;
            $mail->Port = $emailSettings['port'];

            $mail->setFrom($emailSettings['from']['email'], $emailSettings['from']['name']);
            $mail->addCustomHeader("List-Unsubscribe", "<https://satisfactoryplanner.timmygamer.nl/unsubscribe.php?email=$to>");
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

    public static function sendWebsiteUpdateEmail(object $user)
    {
        $changelog = json_decode(file_get_contents('changelog.json'), true)[0];
        $subject = 'Website Update ' . $changelog['version'];
        $body = '
<!DOCTYPE html>
<html>
<head>
    <style>
        /* Include Bootstrap inline styles */
        .container {
            width: 100%;
            padding: 15px;
            margin: 0 auto;
        }
        .header {
            background-color: #f8f9fa;
            padding: 15px;
            text-align: center;
        }
        .content {
            background-color: #ffffff;
            padding: 20px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            padding: 10px;
            background-color: #f8f9fa;
            border-top: 1px solid #dee2e6;
        }
        a {
            color: #007bff;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Welcome to Satisfactory Planner</h2>
        </div>
        <div class="content">
            <p>Hello ' . htmlspecialchars($user->username) . ',</p>
            <p>The website has been updated to version ' . htmlspecialchars($changelog['version']) . '.</p>
            <p>The changes are:</p>
            <ul>';
        foreach ($changelog['changes'] as $change) {
            $body .= '<li>' . htmlspecialchars($change) . '</li>';
        }
        $body .= '
            </ul>
            <p>If you have any issues or suggestions, let us know at <a href="https://docs.google.com/forms/d/e/1FAIpQLSdFw0S39IJaHRyNnLRictd9nmD5VHzwDGtK8_yC-eaTdFsBeA/viewform">this link</a>.</p>
            <p><a href="https://satisfactoryplanner.timmygamer.nl/changelog">satisfactoryplanner.timmygamer.nl/changelog</a>.</p>
        </div>
        <div class="footer">
            <p>Kind regards,<br>The Satisfactory Planner Team</p>
        </div>
    </div>
</body>
</html> ';

        return self::sendEmail($user->email, $subject, $body);
    }
}