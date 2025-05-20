<?php

// Load environment variables from .env file
$dotenv = file_get_contents('.env');
$lines = explode("\n", $dotenv);
$env = [];
foreach ($lines as $line) {
    if (strpos($line, '=') !== false) {
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        // Remove quotes if present
        if (strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) {
            $value = substr($value, 1, -1);
        }
        $env[$key] = $value;
    }
}

// Set up email configuration
$host = $env['MAIL_HOST'];
$port = $env['MAIL_PORT'];
$username = $env['MAIL_USERNAME'];
$password = $env['MAIL_PASSWORD'];
$encryption = isset($env['MAIL_ENCRYPTION']) ? $env['MAIL_ENCRYPTION'] : 'tls';
$from_address = $env['MAIL_FROM_ADDRESS'];
$from_name = $env['MAIL_FROM_NAME'];

// Create email headers
$headers = "From: $from_name <$from_address>\r\n";
$headers .= "Reply-To: $from_address\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";

// Create email content
$to = "test@example.com";
$subject = "Test Email from PHP Script";
$message = "
<html>
<head>
    <title>Test Email</title>
</head>
<body>
    <h1>Test Email</h1>
    <p>This is a test email sent directly from a PHP script.</p>
    <p>Time: " . date('Y-m-d H:i:s') . "</p>
</body>
</html>
";

// Use PHPMailer to send the email
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Create a new PHPMailer instance
$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      // Enable verbose debug output
    $mail->isSMTP();                                            // Send using SMTP
    $mail->Host       = $host;                                  // Set the SMTP server to send through
    $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
    $mail->Username   = $username;                              // SMTP username
    $mail->Password   = $password;                              // SMTP password
    $mail->SMTPSecure = $encryption;                            // Enable TLS encryption
    $mail->Port       = $port;                                  // TCP port to connect to

    // Recipients
    $mail->setFrom($from_address, $from_name);
    $mail->addAddress('test@example.com');                      // Add a recipient

    // Content
    $mail->isHTML(true);                                        // Set email format to HTML
    $mail->Subject = 'Test Email from PHP Script';
    $mail->Body    = $message;

    $mail->send();
    echo "Message has been sent successfully!\n";
    echo "Configuration used:\n";
    echo "Host: $host\n";
    echo "Port: $port\n";
    echo "Username: $username\n";
    echo "Encryption: $encryption\n";
    echo "From Address: $from_address\n";
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}\n";
}
