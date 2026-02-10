<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is staff
if (empty($_SESSION['is_logged_in']) || $_SESSION['role'] !== 'staff') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$conn = require_once __DIR__ . '/../../config/database.php';

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$order_id = $data['id'] ?? null;
$status = $data['status'] ?? null;

if (!$order_id || !$status) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit();
}

$valid_statuses = ['pending', 'in_progress', 'paid', 'completed', 'cancelled'];
if (!in_array($status, $valid_statuses)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

// Fetch current order and customer details before updating
$stmt = $conn->prepare('SELECT o.*, c.name as customer_name, c.email as customer_email, c.phone as customer_phone 
                        FROM orders o 
                        LEFT JOIN customers c ON o.customer_id = c.id 
                        WHERE o.id = ?');
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit();
}
$stmt->bind_param('i', $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit();
}

// Get order items for email
$stmt = $conn->prepare('SELECT oi.*, p.name as product_name FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?');
if ($stmt) {
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $items = [];
}

// Update order status
$stmt = $conn->prepare('UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?');
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit();
}
$stmt->bind_param('si', $status, $order_id);
$success = $stmt->execute();
$stmt->close();

if ($success && !empty($order['customer_email'])) {
    // Send email notification to customer
    try {
        sendStatusUpdateEmail($order, $status, $items);
    } catch (Exception $e) {
        error_log('Email notification failed: ' . $e->getMessage());
        // Don't fail the status update if email fails
    }
}

$conn->close();

if ($success) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update status']);
}

/**
 * Send status update email to customer
 */
function sendStatusUpdateEmail($order, $status, $items) {
    // Load email config
    $email_config = require __DIR__ . '/../../config/email.php';
    
    // Load PHPMailer
    require_once __DIR__ . '/../../vendor/autoload.php';
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
    
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = $email_config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $email_config['username'];
        $mail->Password = $email_config['password'];
        $mail->SMTPSecure = $email_config['secure'];
        $mail->Port = $email_config['port'];
        
        // Sender
        $mail->setFrom($email_config['from_email'], $email_config['from_name']);
        
        // Recipient
        $mail->addAddress($order['customer_email'], $order['customer_name']);
        
        // Email content
        $status_messages = [
            'pending' => 'Your order has been received and is pending confirmation.',
            'in_progress' => 'Your order is now being prepared. Thank you for your patience.',
            'paid' => 'Payment for your order has been received. We will proceed with processing.',
            'completed' => 'Your order has been completed and is ready for pickup or delivery.',
            'cancelled' => 'Your order has been cancelled. Please contact us if you have any questions.'
        ];
        
        $status_label = ucfirst(str_replace('_', ' ', $status));
        $status_message = $status_messages[$status] ?? 'Your order status has been updated.';
        
        // Build items list HTML
        $items_html = '';
        $total = 0;
        foreach ($items as $item) {
            $item_total = $item['quantity'] * $item['unit_price'];
            $total += $item_total;
            $items_html .= sprintf(
                '<tr><td style="padding: 8px; border-bottom: 1px solid #ddd;">%s</td><td style="padding: 8px; border-bottom: 1px solid #ddd; text-align: right;">%d</td><td style="padding: 8px; border-bottom: 1px solid #ddd; text-align: right;">₱%.2f</td><td style="padding: 8px; border-bottom: 1px solid #ddd; text-align: right;">₱%.2f</td></tr>',
                htmlspecialchars($item['product_name'] ?? 'Product'),
                $item['quantity'],
                $item['unit_price'],
                $item_total
            );
        }
        
        $delivery_info = '';
        if (!empty($order['delivery_date'])) {
            $delivery_info .= '<p><strong>Delivery Date:</strong> ' . date('F d, Y', strtotime($order['delivery_date'])) . '</p>';
        }
        if (!empty($order['delivery_address'])) {
            $delivery_info .= '<p><strong>Delivery Address:</strong> ' . htmlspecialchars($order['delivery_address']) . '</p>';
        }
        
        // HTML body
        $html_body = sprintf(
            '<!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <style>
                    body { font-family: Arial, sans-serif; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; border: 1px solid #ddd; padding: 20px; }
                    .header { background-color: #2c5f2d; color: white; padding: 15px; text-align: center; border-radius: 5px 5px 0 0; }
                    .content { padding: 20px; }
                    .status-badge { display: inline-block; padding: 8px 16px; border-radius: 5px; font-weight: bold; margin: 10px 0; }
                    .status-pending { background-color: #fff3cd; color: #856404; }
                    .status-in_progress { background-color: #d1ecf1; color: #0c5460; }
                    .status-paid { background-color: #d4edda; color: #155724; }
                    .status-completed { background-color: #d4edda; color: #155724; }
                    .status-cancelled { background-color: #f8d7da; color: #721c24; }
                    table { width: 100%%; border-collapse: collapse; margin: 15px 0; }
                    th { background-color: #f5f5f5; padding: 10px; text-align: left; border-bottom: 2px solid #ddd; }
                    .footer { background-color: #f5f5f5; padding: 15px; text-align: center; font-size: 12px; color: #666; border-radius: 0 0 5px 5px; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <h2>Order Status Update - Flor de Liz</h2>
                    </div>
                    <div class="content">
                        <p>Dear <strong>%s</strong>,</p>
                        
                        <p>This is to inform you that your order has been updated:</p>
                        
                        <div class="status-badge status-%s">%s</div>
                        
                        <p>%s</p>
                        
                        <h3>Order Details</h3>
                        <p><strong>Order ID:</strong> #%d</p>
                        <p><strong>Order Date:</strong> %s</p>
                        
                        <h3>Items Ordered</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                %s
                            </tbody>
                        </table>
                        
                        <p><strong>Order Total:</strong> ₱%.2f</p>
                        
                        %s
                        
                        <p>If you have any questions or concerns, please do not hesitate to contact us:</p>
                        <p>
                            <strong>Flor de Liz</strong><br>
                            Phone: 0915-123-4567<br>
                            Email: flordeliz@gmail.com
                        </p>
                        
                        <p>Thank you for your business!</p>
                    </div>
                    <div class="footer">
                        <p>This is an automated email. Please do not reply to this message.</p>
                        <p>© 2026 Flor de Liz. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>',
            htmlspecialchars($order['customer_name']),
            $status,
            $status_label,
            $status_message,
            $order['id'],
            date('F d, Y g:i A', strtotime($order['created_at'])),
            $items_html,
            $total,
            $delivery_info
        );
        
        $mail->isHTML(true);
        $mail->Subject = "Order #" . $order['id'] . " - Status Update: " . $status_label;
        $mail->Body = $html_body;
        $mail->AltBody = "Your order #" . $order['id'] . " status has been updated to: " . $status_label;
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        throw new Exception("Email sending failed: " . $mail->ErrorInfo);
    }
}
?>
