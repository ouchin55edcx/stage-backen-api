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

// Print configuration
echo "Mail Configuration:\n";
echo "Host: $host\n";
echo "Port: $port\n";
echo "Username: $username\n";
echo "Password: $password\n";
echo "Encryption: $encryption\n";
echo "From Address: $from_address\n";
echo "From Name: $from_name\n\n";

// Test sending an email using mail() function
$to = "test@example.com";
$subject = "Test Email from PHP Script";
$message = "This is a test email sent directly from a PHP script.";
$headers = "From: $from_name <$from_address>\r\n";
$headers .= "Reply-To: $from_address\r\n";

if (mail($to, $subject, $message, $headers)) {
    echo "Email sent successfully using mail() function.\n";
} else {
    echo "Failed to send email using mail() function.\n";
}
