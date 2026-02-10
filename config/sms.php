<?php
/**
 * SMS & Email Service Configuration & Functions
 * Primary: Semaphore.co SMS API
 * Fallback: Gmail SMTP Email
 * 
 * IMPORTANT: API keys/credentials are read from config files (ignored by git)
 * - `config/semaphore_key.local` - Semaphore API key
 * - `config/email.php` - Email SMTP credentials
 */

// Load Composer autoloader for PHPMailer
require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Retrieve Semaphore API key from environment or local file.
 * @return string|null
 */
function get_semaphore_api_key() {
    $key = getenv('SEMAPHORE_API_KEY');
    if (!empty($key)) return trim($key);

    $local_file = __DIR__ . '/semaphore_key.local';
    if (file_exists($local_file)) {
        $k = trim((string)@file_get_contents($local_file));
        if (!empty($k)) return $k;
    }

    return null;
}

/**
 * Get email configuration
 * @return array|null
 */
function get_email_config() {
    $config_file = __DIR__ . '/email.php';
    if (file_exists($config_file)) {
        return require $config_file;
    }
    return null;
}

/**
 * Send email via SMTP (Gmail fallback)
 * @param string $to_email Recipient email
 * @param string $subject Email subject
 * @param string $body Email body (plain text)
 * @return array ['success' => bool, 'message' => string]
 */
function sendEmail($to_email, $subject, $body) {
    $config = get_email_config();
    if (empty($config)) {
        return ['success' => false, 'message' => 'Email config not found'];
    }

    $sms_log = __DIR__ . '/../sms_log.txt';
    $timestamp = date('Y-m-d H:i:s');

    // Try PHPMailer first if available
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $config['host'];
            $mail->Port = $config['port'];
            $mail->SMTPSecure = $config['secure'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['username'];
            $mail->Password = $config['password'];
            $mail->setFrom($config['from_email'], $config['from_name']);
            $mail->addAddress($to_email);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->isHTML(false);
            
            if ($mail->send()) {
                $log_entry = "[$timestamp] [EMAIL SENT] TO: $to_email | SUBJECT: $subject\n";
                file_put_contents($sms_log, $log_entry, FILE_APPEND);
                return ['success' => true, 'message' => 'Email sent successfully'];
            }
        } catch (Exception $e) {
            $log_entry = "[$timestamp] [EMAIL ERROR - PHPMailer] TO: $to_email | ERROR: " . $e->getMessage() . "\n";
            file_put_contents($sms_log, $log_entry, FILE_APPEND);
            // Continue to mail() fallback below
        }
    }

    // Fallback: use basic PHP mail() function
    $headers = "From: " . $config['from_email'] . "\r\n";
    $headers .= "Reply-To: " . $config['from_email'] . "\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    if (mail($to_email, $subject, $body, $headers)) {
        $log_entry = "[$timestamp] [EMAIL SENT - mail()] TO: $to_email | SUBJECT: $subject\n";
        file_put_contents($sms_log, $log_entry, FILE_APPEND);
        return ['success' => true, 'message' => 'Email sent via mail() function'];
    }

    // Both PHPMailer and mail() failed
    $log_entry = "[$timestamp] [EMAIL FAILED] TO: $to_email | Both PHPMailer and mail() failed\n";
    file_put_contents($sms_log, $log_entry, FILE_APPEND);
    return ['success' => false, 'message' => 'Failed to send email via both PHPMailer and mail()'];
}

/**
 * Send SMS via Semaphore (https://semaphore.co)
 * Falls back to email if SMS fails.
 * @param string $phone 09xxxxxxxxx or +639xxxxxxxxx
 * @param string $message SMS body
 * @param string $to_email Email fallback recipient (optional)
 * @return array ['success'=>bool, 'message'=>string, 'method'=>'sms'|'email'|'both']
 */
function sendSMS($phone, $message, $to_email = null) {
    // Normalize phone number
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    if (strpos($phone, '09') === 0) $phone = '+63' . substr($phone, 1);

    if (!preg_match('/^\+639\d{9}$/', $phone)) {
        return ['success' => false, 'message' => 'Invalid phone number format. Use 09xxxxxxxxx or +639xxxxxxxxx', 'method' => 'none'];
    }

    $api_key = get_semaphore_api_key();
    $sms_log = __DIR__ . '/../sms_log.txt';
    $sms_sent = false;

    // Try sending SMS first
    if (empty($api_key)) {
        // Demo / logging mode
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[$timestamp] [DEMO MODE] TO: $phone | MESSAGE: " . substr($message, 0, 160) . (strlen($message) > 160 ? '...' : '') . "\n";
        file_put_contents($sms_log, $log_entry, FILE_APPEND);
        $sms_sent = true;
        $sms_method = 'demo';
    } else {
        // Try real Semaphore API
        $url = 'https://api.semaphore.co/api/v4/messages';
        $post_fields = http_build_query([
            'apikey' => $api_key,
            'number' => $phone,
            'message' => $message,
            'sendername' => 'FLORDELIZ'
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $response = curl_exec($ch);
        $curl_err = curl_error($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $timestamp = date('Y-m-d H:i:s');
        if ($response === false || $curl_err) {
            $log_entry = "[$timestamp] [SMS CURL ERROR] TO: $phone | ERROR: $curl_err | HTTP: $http_code\n";
            file_put_contents($sms_log, $log_entry, FILE_APPEND);
            $sms_sent = false;
            $sms_method = 'error';
        } else {
            $decoded = json_decode($response, true);
            $ok = ($http_code >= 200 && $http_code < 300 && (!isset($decoded['success']) || $decoded['success'] === true));
            $log_entry = "[$timestamp] [SMS " . ($ok ? 'SENT' : 'FAILED') . "] TO: $phone | HTTP: $http_code\n";
            file_put_contents($sms_log, $log_entry, FILE_APPEND);
            $sms_sent = $ok;
            $sms_method = $ok ? 'sms' : 'failed';
        }
    }

    // If SMS failed and email provided, fallback to email
    if (!$sms_sent && !empty($to_email)) {
        $email_result = sendEmail($to_email, 'Message from Flor de Liz', $message);
        if ($email_result['success']) {
            $timestamp = date('Y-m-d H:i:s');
            $log_entry = "[$timestamp] [FALLBACK TO EMAIL] PHONE: $phone | EMAIL: $to_email\n";
            file_put_contents($sms_log, $log_entry, FILE_APPEND);
            return ['success' => true, 'message' => 'SMS failed, sent via email instead', 'method' => 'email'];
        }
        return ['success' => false, 'message' => 'Both SMS and email failed', 'method' => 'both_failed'];
    }

    return ['success' => $sms_sent, 'message' => $sms_sent ? 'Sent via SMS' : 'SMS failed (no email fallback provided)', 'method' => $sms_method];
}

/* Helper wrappers with SMS → Email fallback */

/**
 * Send order confirmation (SMS with email fallback)
 * Note: You should pass customer email for email fallback
 */
function sendOrderConfirmationSMS($customer_name, $phone, $order_number, $delivery_date, $total_amount, $email = null) {
    $delivery_date_formatted = date('M d, Y', strtotime($delivery_date));
    $delivery_time = '2:00 PM';
    $message = "Hi $customer_name\n";
    $message .= "Your order #$order_number has been confirmed!\n";
    $message .= "Order Total: ₱" . number_format($total_amount, 2) . "\n";
    $message .= "Delivery: $delivery_date_formatted at $delivery_time\n";
    $message .= "Thank you for ordering from Flor de Liz!";
    return sendSMS($phone, $message, $email);
}

/**
 * Send order status update (SMS with email fallback)
 */
function sendOrderStatusSMS($customer_name, $phone, $order_number, $status, $email = null) {
    $status_messages = [
        'pending' => 'Your order is pending and will be processed soon.',
        'processing' => "Your order is being processed. We're preparing your items.",
        'ready' => "Your order is ready for delivery! We'll deliver it as scheduled.",
        'delivered' => 'Your order has been delivered. Thank you for your purchase!',
        'cancelled' => 'Your order has been cancelled. Contact us for details.'
    ];
    $status_text = $status_messages[$status] ?? 'Your order status has been updated.';
    $message = "Hi $customer_name\nOrder #$order_number Update:\n$status_text\nFlor de Liz";
    return sendSMS($phone, $message, $email);
}

/**
 * Send payment confirmation (SMS with email fallback)
 */
function sendPaymentConfirmationSMS($customer_name, $phone, $amount, $payment_type, $order_number, $email = null) {
    $payment_label = ucfirst(str_replace('_', ' ', $payment_type));
    $message = "Hi $customer_name\nWe received your $payment_label\nAmount: ₱" . number_format($amount, 2) . "\nOrder: #$order_number\nThank you! Flor de Liz";
    return sendSMS($phone, $message, $email);
}

?>
