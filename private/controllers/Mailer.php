<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../static/PHPMailer/src/Exception.php';
require '../static/PHPMailer/src/PHPMailer.php';
require '../static/PHPMailer/src/SMTP.php';

class Mailer
{

    public static function sendEmail($to, $subject, $body, $plainText)
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
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = $plainText;

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

        // HTML body
        $htmlBody = '
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
            $htmlBody .= '<li>' . htmlspecialchars($change) . '</li>';
        }
        $htmlBody .= '
                </ul>
            </div>
            <div class="footer">
                <p>Kind regards,<br>The Satisfactory Planner Team</p>
                <p>If you no longer wish to receive these updates, you can <a href="https://satisfactoryplanner.timmygamer.nl/unsubscribe">unsubscribe here</a>.</p>
            </div>
        </div>
    </body>
    </html>';

        // Plain text version
        $plainTextBody = "Hello " . htmlspecialchars($user->username) . ",\n";
        $plainTextBody .= "The website has been updated to version " . htmlspecialchars($changelog['version']) . ".\n";
        $plainTextBody .= "The changes are:\n";
        foreach ($changelog['changes'] as $change) {
            $plainTextBody .= "- " . htmlspecialchars($change) . "\n";
        }
        $plainTextBody .= "\nKind regards,\nThe Satisfactory Planner Team";

        // Send email as multipart/alternative (HTML + Plain Text)
        // Use your mail sending method here
        return self::sendEmail($user->email, $subject, $htmlBody, $plainTextBody);
    }

    public static function sendVerificationEmail($to, $username, $token)
    {
        $subject = 'Verify Your Email Address';
        $encodedUsername = urlencode($username);
        $encodedTo = urlencode($to);
        $encodedToken = urlencode($token);
        $url = "https://satisfactoryplanner.timmygamer.nl/login/verify?usr=$encodedUsername&eml=$encodedTo&tkn=$encodedToken";

        // HTML body
        $htmlBody = '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
            .container {
                width: 100%;
                max-width: 600px;
                padding: 15px;
                margin: 0 auto;
                font-family: Arial, sans-serif;
                color: #333333;
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
                line-height: 1.6;
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
                font-weight: bold;
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
                <p>Hello ' . htmlspecialchars($username) . ',</p>
                <p>Thank you for registering at Satisfactory Planner. Please verify your email address by clicking the link below:</p>
                <p><a href="' . htmlspecialchars($url) . '" aria-label="Verify Email">Verify Email</a></p>
                <p>If the button above does not work, you can also copy and paste the following link into your browser:</p>
                <p><a href="' . htmlspecialchars($url) . '">' . htmlspecialchars($url) . '</a></p>
            </div>
            <div class="footer">
                <p>Kind regards,<br>The Satisfactory Planner Team</p>
            </div>
        </div>
    </body>
    </html>';

        // Plain text version
        $plainTextBody = "Hello,\n\n";
        $plainTextBody .= "Thank you for registering at Satisfactory Planner. Please verify your email address by clicking the link below:\n";
        $plainTextBody .= $url . "\n\n";
        $plainTextBody .= "Kind regards,\nThe Satisfactory Planner Team";

        // Send email as multipart/alternative (HTML + Plain Text)
        return self::sendEmail($to, $subject, $htmlBody, $plainTextBody);
    }
}
