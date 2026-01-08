<?php
/**
 * SMS Service Configuration & Functions
 * Handles SMS notifications for orders and customer communications
 */

/**
 * Send SMS notification to customer
 * 
 * @param string $phone Customer phone number (format: 09xxxxxxxxx or +639xxxxxxxxx)
 * @param string $message SMS message to send
 * @return array ['success' => bool, 'message' => string]
 */
function sendSMS($phone, $message) {
    // Normalize phone number: remove spaces, dashes, and convert to standard format
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    
    // Convert 09 format to +639 format
    if (strpos($phone, '09') === 0) {
        $phone = '+63' . substr($phone, 1);
    }
    
    // Validate phone number format
    if (!preg_match('/^\+639\d{9}$/', $phone)) {
        return [
            'success' => false,
            'message' => 'Invalid phone number format. Use 09xxxxxxxxx or +639xxxxxxxxx'
        ];
    }
    
    try {
        // SMS Gateway Integration
        // Currently uses mock/logging approach - can be replaced with:
        // - Twilio API (requires composer: twilio/sdk)
        // - Globe Labs API (Philippine telco)
        // - Semaphore API (Philippine SMS provider)
        // - AWS SNS
        // - Any other SMS gateway service
        
        // For now, log SMS to a file for testing/auditing
        $sms_log = __DIR__ . '/../sms_log.txt';
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[$timestamp] TO: $phone | MESSAGE: " . substr($message, 0, 100) . (strlen($message) > 100 ? '...' : '') . "\n";
        
        file_put_contents($sms_log, $log_entry, FILE_APPEND);
        
        // Return success response
        // In production, replace this with actual SMS API call
        return [
            'success' => true,
            'message' => 'SMS sent successfully to ' . $phone
        ];
        
    } catch (Exception $e) {
        error_log('SMS Error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to send SMS: ' . $e->getMessage()
        ];
    }
}

/**
 * Send order confirmation SMS to customer
 * 
 * @param string $customer_name Customer name
 * @param string $phone Customer phone number
 * @param string $order_number Order reference number
 * @param string $delivery_date Delivery date (format: Y-m-d)
 * @param float $total_amount Total order amount
 * @return array SMS response
 */
function sendOrderConfirmationSMS($customer_name, $phone, $order_number, $delivery_date, $total_amount) {
    // Format delivery date to readable format
    $delivery_date_formatted = date('M d, Y', strtotime($delivery_date));
    
    // Get estimated delivery time (default: 2 PM)
    $delivery_time = '2:00 PM';
    
    // Create SMS message (SMS has character limit, keep it concise)
    $message = "Hi $customer_name,\n\n";
    $message .= "Your order #$order_number has been confirmed!\n\n";
    $message .= "Order Total: ₱" . number_format($total_amount, 2) . "\n";
    $message .= "Delivery: $delivery_date_formatted at $delivery_time\n\n";
    $message .= "Thank you for ordering from Flor de Liz!\n";
    $message .= "For inquiries: (02) 1234-5678";
    
    return sendSMS($phone, $message);
}

/**
 * Send order status update SMS to customer
 * 
 * @param string $customer_name Customer name
 * @param string $phone Customer phone number
 * @param string $order_number Order reference number
 * @param string $status Order status (pending, processing, ready, delivered)
 * @return array SMS response
 */
function sendOrderStatusSMS($customer_name, $phone, $order_number, $status) {
    $status_messages = [
        'pending' => 'Your order is pending and will be processed soon.',
        'processing' => 'Your order is being processed. We\'re preparing your items.',
        'ready' => 'Your order is ready for delivery! We\'ll deliver it as scheduled.',
        'delivered' => 'Your order has been delivered. Thank you for your purchase!',
        'cancelled' => 'Your order has been cancelled. Contact us for details.'
    ];
    
    $status_text = $status_messages[$status] ?? 'Your order status has been updated.';
    
    $message = "Hi $customer_name,\n\n";
    $message .= "Order #$order_number Update:\n";
    $message .= "$status_text\n\n";
    $message .= "Flor de Liz";
    
    return sendSMS($phone, $message);
}

/**
 * Send payment confirmation SMS to customer
 * 
 * @param string $customer_name Customer name
 * @param string $phone Customer phone number
 * @param float $amount Payment amount
 * @param string $payment_type Type of payment (downpayment, partial, full)
 * @param string $order_number Order reference number
 * @return array SMS response
 */
function sendPaymentConfirmationSMS($customer_name, $phone, $amount, $payment_type, $order_number) {
    $payment_label = ucfirst(str_replace('_', ' ', $payment_type));
    
    $message = "Hi $customer_name,\n\n";
    $message .= "We received your $payment_label\n";
    $message .= "Amount: ₱" . number_format($amount, 2) . "\n";
    $message .= "Order: #$order_number\n\n";
    $message .= "Thank you! Flor de Liz";
    
    return sendSMS($phone, $message);
}
?>
