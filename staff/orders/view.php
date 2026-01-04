<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is staff
if (empty($_SESSION['is_logged_in']) || $_SESSION['role'] !== 'staff') {
    header('Location: ../../login_staff.php');
    exit();
}

$order_id = $_GET['id'] ?? null;

if (!$order_id) {
    header('Location: ../orders.php');
    exit();
}

$conn = require_once __DIR__ . '/../../config/database.php';

// Get order details
$stmt = $conn->prepare('SELECT o.*, c.name as customer_name, c.phone as customer_phone, c.category as customer_category 
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

$total_paid = 0;
foreach ($payments as $payment) {
    $total_paid += $payment['amount'];
}

$remaining = $order['total_amount'] - $total_paid;
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
                    <div>
                        <?php 
                            $status_colors = [
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'in_progress' => 'bg-blue-100 text-blue-800',
                                'completed' => 'bg-green-100 text-green-800',
                                'cancelled' => 'bg-red-100 text-red-800'
                            ];
                            $color = $status_colors[$order['status']] ?? 'bg-gray-100 text-gray-800';
                        ?>
                        <span class="px-4 py-2 rounded-full text-sm font-bold <?php echo $color; ?>">
                            <?php echo ucwords(str_replace('_', ' ', $order['status'])); ?>
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Main Content -->
                    <div class="lg:col-span-2">
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
                                    <p class="text-sm font-semibold text-gray-600">Category</p>
                                    <p class="text-gray-900 mt-1"><?php echo htmlspecialchars($order['customer_category'] ?? '--'); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-600">Delivery Date</p>
                                    <p class="text-gray-900 mt-1"><?php echo date('M d, Y', strtotime($order['delivery_date'])); ?></p>
                                </div>
                            </div>
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
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($items as $item): ?>
                                            <tr class="border-b border-gray-200">
                                                <td class="px-6 py-4 text-gray-900"><?php echo htmlspecialchars($item['product_name'] ?? 'Product #' . $item['product_id']); ?></td>
                                                <td class="px-6 py-4 text-right text-gray-900"><?php echo $item['quantity']; ?></td>
                                                <td class="px-6 py-4 text-right text-gray-900">₱<?php echo number_format($item['unit_price'], 2); ?></td>
                                                <td class="px-6 py-4 text-right font-semibold text-gray-900">₱<?php echo number_format($item['quantity'] * $item['unit_price'], 2); ?></td>
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
                        <?php if ($order['notes']): ?>
                            <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
                                <h3 class="font-semibold text-blue-900 mb-2">Notes</h3>
                                <p class="text-blue-800"><?php echo htmlspecialchars($order['notes']); ?></p>
                            </div>
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
                                <div class="flex justify-between border-t pt-4">
                                    <span class="font-bold text-gray-900 text-lg">Total:</span>
                                    <span class="text-2xl font-bold text-teal-600">₱<?php echo number_format($order['total_amount'], 2); ?></span>
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

                            <?php if ($total_paid > 0 && $total_paid < $order['total_amount']): ?>
                                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                                    <p class="text-sm font-semibold text-yellow-900 mb-1">Payment Status</p>
                                    <p class="text-sm text-yellow-800">Partial payment received. Balance pending.</p>
                                </div>
                            <?php endif; ?>

                            <div class="flex gap-2">
                                <a href="../orders.php" class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition text-center font-semibold">Back</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
