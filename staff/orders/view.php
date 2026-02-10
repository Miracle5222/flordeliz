<?php
// Load PHPMailer at the top
require_once __DIR__ . '/../../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is staff
if (empty($_SESSION['is_logged_in']) || $_SESSION['role'] !== 'staff') {
    header('Location: ../../login_staff.php');
    exit();
}

$order_id = $_GET['id'] ?? null;
$is_edit_mode = isset($_GET['edit']) && $_GET['edit'] == 1;
$message = '';
$error = '';

if (!$order_id) {
    header('Location: ../orders.php');
    exit();
}

$conn = require_once __DIR__ . '/../../config/database.php';

if (!$conn) {
    die('Database connection failed. Please check your database configuration and ensure MySQL is running.');
}

/**
 * Send status update email to customer
 */
function sendStatusUpdateEmail($order, $status, $items) {
    try {
        // Load email config
        $email_config = require __DIR__ . '/../../config/email.php';
        
        $mail = new PHPMailer(true);
        
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
        error_log('Email sending failed: ' . $e->getMessage());
        return false;
    }
}

// Handle form submission for updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'] ?? '';
    $old_status = $_POST['old_status'] ?? '';
    $delivery_date = $_POST['delivery_date'] ?? '';
    $delivery_address = $_POST['delivery_address'] ?? '';
    $notes = $_POST['notes'] ?? '';
    
    // Update order
    $stmt = $conn->prepare('UPDATE orders SET status = ?, delivery_date = ?, delivery_address = ?, notes = ? WHERE id = ?');
    if (!$stmt) {
        $error = 'Database error: Failed to prepare order update statement.';
    } else {
        $stmt->bind_param('ssssi', $status, $delivery_date, $delivery_address, $notes, $order_id);
        
        if ($stmt->execute()) {
            // Handle item updates
            if (isset($_POST['item_id']) && is_array($_POST['item_id'])) {
                $all_items_updated = true;
                foreach ($_POST['item_id'] as $index => $item_id) {
                    $quantity = $_POST['quantity'][$index] ?? 0;
                    $unit_price = $_POST['unit_price'][$index] ?? 0;
                    
                    if ($quantity > 0) {
                        $update_item = $conn->prepare('UPDATE order_items SET quantity = ?, unit_price = ? WHERE id = ?');
                        if (!$update_item) {
                            $all_items_updated = false;
                            $error = 'Database error: Failed to prepare item update statement.';
                        } else {
                            $update_item->bind_param('idi', $quantity, $unit_price, $item_id);
                            if (!$update_item->execute()) {
                                $all_items_updated = false;
                                $error = 'Failed to update item: ' . $update_item->error;
                            }
                            $update_item->close();
                        }
                    }
                }
                
                // Update total amount
                $total = 0;
                foreach ($_POST['item_id'] as $index => $item_id) {
                    $total += ($_POST['quantity'][$index] ?? 0) * ($_POST['unit_price'][$index] ?? 0);
                }
                
                $update_total = $conn->prepare('UPDATE orders SET total_amount = ? WHERE id = ?');
                if (!$update_total) {
                    $error = 'Database error: Failed to prepare total update statement.';
                } else {
                    $update_total->bind_param('di', $total, $order_id);
                    $update_total->execute();
                    $update_total->close();
                }
            }
            
            // Handle payment addition
            if (!empty($_POST['payment_amount']) && $_POST['payment_amount'] > 0) {
                $payment_amount = $_POST['payment_amount'];
                $payment_method = $_POST['payment_method'] ?? 'cash';
                $payment_type = 'additional';
                
                $payment_stmt = $conn->prepare('INSERT INTO payments (order_id, amount, payment_date, payment_method, payment_type) VALUES (?, ?, NOW(), ?, ?)');
                if (!$payment_stmt) {
                    $error = 'Database error: Failed to prepare payment insert statement.';
                } else {
                    $payment_stmt->bind_param('idss', $order_id, $payment_amount, $payment_method, $payment_type);
                    if (!$payment_stmt->execute()) {
                        $error = 'Failed to add payment: ' . $payment_stmt->error;
                    }
                    $payment_stmt->close();
                }
            }
            
            $stmt->close();
            
            // Send email if status changed
            if ($status != $old_status && $status) {
                // Fetch customer email and order items for email
                $email_stmt = $conn->prepare('SELECT o.*, c.name as customer_name, c.email as customer_email 
                                             FROM orders o 
                                             LEFT JOIN customers c ON o.customer_id = c.id 
                                             WHERE o.id = ?');
                if ($email_stmt) {
                    $email_stmt->bind_param('i', $order_id);
                    $email_stmt->execute();
                    $email_order = $email_stmt->get_result()->fetch_assoc();
                    $email_stmt->close();
                    
                    // Fetch items
                    $items_stmt = $conn->prepare('SELECT oi.*, p.name as product_name FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?');
                    if ($items_stmt) {
                        $items_stmt->bind_param('i', $order_id);
                        $items_stmt->execute();
                        $email_items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                        $items_stmt->close();
                        
                        // Send email if customer has email
                        if (!empty($email_order['customer_email'])) {
                            sendStatusUpdateEmail($email_order, $status, $email_items);
                        }
                    }
                }
            }
            
            $conn->close();
            // Redirect to view page without edit mode after successful update
            header('Location: view.php?id=' . $order_id . '&success=1');
            exit();
        } else {
            $error = 'Failed to update order: ' . $stmt->error;
            $stmt->close();
        }
    }
}

// Handle success message from redirect
$success = isset($_GET['success']) && $_GET['success'] == 1;
$message = $success ? 'Order updated successfully!' : '';

// Get order details
$stmt = $conn->prepare('SELECT o.*, c.name as customer_name, c.phone as customer_phone, c.email as customer_email, c.category as customer_category 
                       FROM orders o 
                       LEFT JOIN customers c ON o.customer_id = c.id 
                       WHERE o.id = ?');
$stmt->bind_param('i', $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    header('Location: ../orders.php');
    exit();
}

// Get order items
$stmt = $conn->prepare('SELECT oi.*, p.name as product_name FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?');
$stmt->bind_param('i', $order_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get payments
$stmt = $conn->prepare('SELECT * FROM payments WHERE order_id = ? ORDER BY payment_date DESC');
$stmt->bind_param('i', $order_id);
$stmt->execute();
$payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();

// Calculate totals
$subtotal = 0;
foreach ($items as $item) {
    $subtotal += $item['quantity'] * $item['unit_price'];
}

// Calculate discount
$discount = 0;
if ($subtotal >= 1000) {
    $discount = $subtotal * 0.15;
} elseif ($subtotal >= 500) {
    $discount = $subtotal * 0.10;
} elseif ($subtotal >= 100) {
    $discount = $subtotal * 0.05;
}

$total = $subtotal - $discount;

$total_paid = 0;
foreach ($payments as $payment) {
    $total_paid += $payment['amount'];
}

$remaining = ($order['status'] === 'paid') ? 0 : $total - $total_paid;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order <?php echo htmlspecialchars($order['order_number']); ?> - Flor de Liz</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <?php require_once __DIR__ . '/../../includes/sidebar_navigation.php'; ?>

    <div class="absolute w-full -ml-2 top-12">
        <div id="main-content" class="flex-1 px-4 sm:px-6 lg:px-8 py-12">
            <div class="max-w-4xl mx-auto">
                    <div class="flex justify-between items-center mb-8">
                        <div>
                            <h2 class="text-4xl font-bold text-gray-900">Order <?php echo htmlspecialchars($order['order_number']); ?></h2>
                            <p class="text-gray-600 mt-2">Order Date: <?php echo date('M d, Y', strtotime($order['order_date'])); ?></p>
                        </div>
                        <div class="flex items-center gap-4">
                            <div>
                                <?php 
                                    $status_colors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'in_progress' => 'bg-blue-100 text-blue-800',
                                        'paid' => 'bg-green-100 text-green-800',
                                        'completed' => 'bg-green-100 text-green-800',
                                        'cancelled' => 'bg-red-100 text-red-800'
                                    ];
                                    $color = $status_colors[$order['status']] ?? 'bg-gray-100 text-gray-800';
                                    
                                    // Get display label for status
                                    $status_label = $order['status'];
                                    if ($status_label === 'in_progress') {
                                        $status_label = 'on the way';
                                    } elseif ($status_label === 'completed') {
                                        $status_label = 'delivered';
                                    }
                                ?>
                                <span class="px-4 py-2 rounded-full text-sm font-bold <?php echo $color; ?>">
                                    <?php echo ucwords(str_replace('_', ' ', $status_label)); ?>
                                </span>
                            </div>
                            <?php if (!$is_edit_mode): ?>
                                <a href="?id=<?php echo $order_id; ?>&edit=1" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold text-sm">
                                    Edit Order
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Main Content -->
                    <div class="lg:col-span-2">
                        <?php if ($message): ?>
                            <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-6">
                                <p class="text-green-800 font-semibold"><?php echo htmlspecialchars($message); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
                                <p class="text-red-800 font-semibold"><?php echo htmlspecialchars($error); ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if ($is_edit_mode): ?>
                            <form method="POST" id="edit-form" class="space-y-8">
                        <?php endif; ?>

                        <!-- Customer Information -->
                        <div class="bg-white rounded-xl shadow-md p-8 mb-8">
                            <h3 class="text-lg font-bold text-gray-900 mb-6">Customer Information</h3>
                            <div class="grid grid-cols-2 gap-6">
                                <div>
                                    <p class="text-sm font-semibold text-gray-600">Customer Name</p>
                                    <p class="text-gray-900 mt-1"><?php echo htmlspecialchars($order['customer_name']); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-600">Phone</p>
                                    <p class="text-gray-900 mt-1"><?php echo htmlspecialchars($order['customer_phone']); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-600">Email</p>
                                    <p class="text-gray-900 mt-1"><?php echo htmlspecialchars($order['customer_email'] ?? '--'); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-600">Category</p>
                                    <p class="text-gray-900 mt-1"><?php echo htmlspecialchars($order['customer_category'] ?? '--'); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-600">Delivery Date</p>
                                    <?php if ($is_edit_mode): ?>
                                        <input type="date" name="delivery_date" value="<?php echo $order['delivery_date']; ?>" class="mt-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500 w-full">
                                    <?php else: ?>
                                        <p class="text-gray-900 mt-1"><?php echo date('M d, Y', strtotime($order['delivery_date'])); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="mt-6 pt-6 border-t">
                                <p class="text-sm font-semibold text-gray-600 mb-2">Delivery Address</p>
                                <?php if ($is_edit_mode): ?>
                                    <textarea name="delivery_address" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500" placeholder="Enter delivery address..."><?php echo htmlspecialchars($order['delivery_address'] ?? ''); ?></textarea>
                                <?php else: ?>
                                    <p class="text-gray-900 mt-1"><?php echo htmlspecialchars($order['delivery_address'] ?? '--'); ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($is_edit_mode): ?>
                                <div class="mt-6 pt-6 border-t">
                                    <label class="block text-sm font-semibold text-gray-600 mb-2">Status</label>
                                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500">
                                        <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="in_progress" <?php echo $order['status'] === 'in_progress' ? 'selected' : ''; ?>>On The Way</option>
                                        <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>Delivered</option>
                                        <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                    <input type="hidden" name="old_status" value="<?php echo htmlspecialchars($order['status']); ?>">
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Order Items -->
                        <div class="bg-white rounded-xl shadow-md overflow-hidden mb-8">
                            <div class="p-8 border-b border-gray-200">
                                <h3 class="text-lg font-bold text-gray-900">Order Items</h3>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Product</th>
                                            <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">Quantity</th>
                                            <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">Unit Price</th>
                                            <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">Subtotal</th>
                                            <?php if ($is_edit_mode): ?>
                                                <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Actions</th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($items as $index => $item): ?>
                                            <tr class="border-b border-gray-200">
                                                <td class="px-6 py-4 text-gray-900"><?php echo htmlspecialchars($item['product_name'] ?? 'Product #' . $item['product_id']); ?></td>
                                                <?php if ($is_edit_mode): ?>
                                                    <td class="px-6 py-4 text-right">
                                                        <input type="hidden" name="item_id[]" value="<?php echo $item['id']; ?>">
                                                        <input type="number" name="quantity[]" value="<?php echo $item['quantity']; ?>" min="0" class="w-20 px-2 py-1 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500">
                                                    </td>
                                                    <td class="px-6 py-4 text-right">
                                                        <input type="number" name="unit_price[]" value="<?php echo $item['unit_price']; ?>" step="0.01" min="0" class="w-24 px-2 py-1 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500">
                                                    </td>
                                                    <td class="px-6 py-4 text-right font-semibold text-gray-900">₱<span class="item-subtotal"><?php echo number_format($item['quantity'] * $item['unit_price'], 2); ?></span></td>
                                                    <td class="px-6 py-4 text-center">
                                                        <button type="button" onclick="removeItem(this)" class="text-red-600 hover:text-red-800 font-semibold text-sm">Remove</button>
                                                    </td>
                                                <?php else: ?>
                                                    <td class="px-6 py-4 text-right text-gray-900"><?php echo $item['quantity']; ?></td>
                                                    <td class="px-6 py-4 text-right text-gray-900">₱<?php echo number_format($item['unit_price'], 2); ?></td>
                                                    <td class="px-6 py-4 text-right font-semibold text-gray-900">₱<?php echo number_format($item['quantity'] * $item['unit_price'], 2); ?></td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Payment History -->
                        <div class="bg-white rounded-xl shadow-md p-8 mb-8">
                            <h3 class="text-lg font-bold text-gray-900 mb-6">Payment History</h3>
                            <?php if (empty($payments)): ?>
                                <p class="text-gray-600">No payments recorded yet</p>
                            <?php else: ?>
                                <div class="space-y-4">
                                    <?php foreach ($payments as $payment): ?>
                                        <div class="flex justify-between items-center border-b pb-4 last:border-b-0">
                                            <div>
                                                <p class="font-semibold text-gray-900"><?php echo ucfirst($payment['payment_method']); ?> Payment</p>
                                                <p class="text-sm text-gray-600"><?php echo date('M d, Y H:i', strtotime($payment['payment_date'])); ?></p>
                                            </div>
                                            <div class="text-right">
                                                <p class="font-bold text-green-600">+ ₱<?php echo number_format($payment['amount'], 2); ?></p>
                                                <p class="text-xs text-gray-600"><?php echo ucfirst($payment['payment_type'] ?? ($payment['payment_status'] ?? '')); ?></p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Notes -->
                        <?php if ($is_edit_mode || $order['notes']): ?>
                            <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
                                <h3 class="font-semibold text-blue-900 mb-2">Notes</h3>
                                <?php if ($is_edit_mode): ?>
                                    <textarea name="notes" rows="4" class="w-full px-3 py-2 border border-blue-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Add notes about this order..."><?php echo htmlspecialchars($order['notes'] ?? ''); ?></textarea>
                                <?php else: ?>
                                    <p class="text-blue-800"><?php echo htmlspecialchars($order['notes']); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($is_edit_mode): ?>
                            <!-- Add Payment -->
                            <div class="bg-white rounded-xl shadow-md p-8 mb-8">
                                <h3 class="text-lg font-bold text-gray-900 mb-6">Add Payment</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Payment Amount</label>
                                        <input type="number" name="payment_amount" step="0.01" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500" placeholder="0.00">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
                                        <select name="payment_method" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                                            <option value="cash">Cash</option>
                                            <option value="card">Card</option>
                                            <option value="bank">Bank Transfer</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($is_edit_mode): ?>
                            </form>
                        <?php endif; ?>
                    </div>

                    <!-- Summary Sidebar -->
                    <div>
                        <div class="bg-white rounded-xl shadow-md p-8 sticky top-24">
                            <h3 class="text-lg font-bold text-gray-900 mb-6">Order Summary</h3>
                            
                            <div class="space-y-4 border-b pb-4 mb-6">
                                <div class="flex justify-between">
                                    <span class="text-gray-700">Subtotal:</span>
                                    <span class="font-semibold text-gray-900">₱<?php echo number_format($subtotal, 2); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-700">Discount:</span>
                                    <span class="font-semibold text-green-600">₱<?php echo number_format($discount, 2); ?></span>
                                </div>
                                <div class="flex justify-between border-t pt-4">
                                    <span class="font-bold text-gray-900 text-lg">Total:</span>
                                    <span class="text-2xl font-bold text-teal-600">₱<?php echo number_format($total, 2); ?></span>
                                </div>
                            </div>

                            <div class="space-y-3 border-b pb-4 mb-6">
                                <div class="flex justify-between">
                                    <span class="text-gray-700">Total Paid:</span>
                                    <span class="font-semibold text-green-600">₱<?php echo number_format($total_paid, 2); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-700">Remaining:</span>
                                    <span class="font-bold text-orange-600">₱<?php echo number_format($remaining, 2); ?></span>
                                </div>
                            </div>

                            <?php if ($total_paid > 0 && $total_paid < $total): ?>
                                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                                    <p class="text-sm font-semibold text-yellow-900 mb-1">Payment Status</p>
                                    <p class="text-sm text-yellow-800">Partial payment received. Balance pending.</p>
                                </div>
                            <?php endif; ?>

                            <div class="flex gap-2">
                                <?php if ($is_edit_mode): ?>
                                    <button type="submit" form="edit-form" class="flex-1 px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition text-center font-semibold">Save Changes</button>
                                    <a href="?id=<?php echo $order_id; ?>" class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition text-center font-semibold">Cancel</a>
                                <?php else: ?>
                                    <a href="../orders.php" class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition text-center font-semibold">Back</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

<script>
    function removeItem(button) {
        const row = button.closest('tr');
        const quantityInput = row.querySelector('input[name="quantity[]"]');
        const priceInput = row.querySelector('input[name="unit_price[]"]');
        
        // Set quantity to 0 and disable inputs
        quantityInput.value = 0;
        quantityInput.disabled = true;
        priceInput.disabled = true;
        
        // Hide the row or mark as removed
        row.style.opacity = '0.5';
        button.textContent = 'Removed';
        button.disabled = true;
        button.className = 'text-gray-400 cursor-not-allowed';
    }
    
    // Update subtotal when quantity or price changes
    document.addEventListener('change', function(e) {
        if (e.target.name === 'quantity[]' || e.target.name === 'unit_price[]') {
            const row = e.target.closest('tr');
            const quantityInput = row.querySelector('input[name="quantity[]"]');
            const priceInput = row.querySelector('input[name="unit_price[]"]');
            const subtotal = quantityInput.value * priceInput.value;
            const subtotalSpan = row.querySelector('.item-subtotal');
            if (subtotalSpan) {
                subtotalSpan.textContent = parseFloat(subtotal).toFixed(2);
            }
        }
    });
</script>
