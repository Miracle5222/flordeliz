<?php
/**
 * Test email configuration and sending
 */

require_once __DIR__ . '/config/sms.php';

echo '<pre style="font-family: monospace; background: #f5f5f5; padding: 15px;">';
echo "=== EMAIL CONFIGURATION TEST ===\n\n";

// Get email config
$config = get_email_config();

if (!$config) {
    echo "[✗] ERROR: Email config not found!\n";
    exit;
}

echo "[✓] Email config found\n";
echo "    Host: " . $config['host'] . "\n";
echo "    Port: " . $config['port'] . "\n";
echo "    Secure: " . $config['secure'] . "\n";
echo "    From: " . $config['from_email'] . "\n";
echo "    Username: " . $config['username'] . "\n";

echo "\n=== CHECKING PHP MAIL SUPPORT ===\n";
echo "PHP mail() function available: " . (function_exists('mail') ? "✓ Yes" : "✗ No") . "\n";
echo "PHP Version: " . phpversion() . "\n";

echo "\n=== CHECKING PHPMAILER ===\n";
if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    echo "[✓] PHPMailer is installed\n";
    echo "    PHPMailer version: " . (defined('PHPMailer\PHPMailer\PHPMailer::PHPMAILER_VERSION') ? \PHPMailer\PHPMailer\PHPMailer::PHPMAILER_VERSION : 'unknown') . "\n";
} else {
    echo "[✗] PHPMailer is NOT installed\n";
    echo "    This means email will fall back to PHP mail() function\n";
}

echo "\n=== SENDING TEST EMAIL ===\n";
$test_email = 'test@example.com';
$result = sendEmail($test_email, 'Test Email from Flor de Liz', "This is a test email.\n\nIf you received this, email sending is working!");

if ($result['success']) {
    echo "[✓] SUCCESS: " . $result['message'] . "\n";
} else {
    echo "[✗] FAILED: " . $result['message'] . "\n";
}

echo "\n=== CHECK SMS LOG FOR DETAILS ===\n";
if (file_exists(__DIR__ . '/../sms_log.txt')) {
    $log = file(__DIR__ . '/../sms_log.txt');
    $recent = array_slice($log, -5);
    echo "Last 5 log entries:\n";
    foreach ($recent as $line) {
        echo "  " . trim($line) . "\n";
    }
}

echo "\n</pre>";
?>
