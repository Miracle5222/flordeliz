<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is staff
if (empty($_SESSION['is_logged_in']) || $_SESSION['role'] !== 'staff') {
    // If AJAX request, return JSON 401 to avoid HTML redirect
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json', true, 401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
    header('Location: ../../login_staff.php');
    exit();
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    header('Content-Type: application/json');
    // enable mysqli exceptions to catch DB errors and return JSON
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn = require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../config/sms.php';
    
    $customer_id = $_POST['customer_id'] ?? null;
    $customer_name = $_POST['customer_name'] ?? '';
    $customer_phone = $_POST['customer_phone'] ?? '';
    $customer_category = $_POST['customer_category'] ?? '';
    $delivery_date = $_POST['delivery_date'] ?? null;
    $delivery_address = $_POST['delivery_address'] ?? '';
    $downpayment = floatval($_POST['downpayment'] ?? 0);
    $notes = $_POST['notes'] ?? '';
    
    $products = json_decode($_POST['products'] ?? '[]', true);
    
    if (empty($products) || !$delivery_date) {
        echo json_encode(['success' => false, 'message' => 'Please add products and select delivery date']);
        exit();
    }
    
    try {
        $conn->begin_transaction();
        
        // Create or get customer
        if (!$customer_id) {
            $stmt = $conn->prepare('INSERT INTO customers (name, phone, category) VALUES (?, ?, ?)');
            $stmt->bind_param('sss', $customer_name, $customer_phone, $customer_category);
            $stmt->execute();
            $customer_id = $conn->insert_id;
            $stmt->close();
        }
        
        // Calculate total amount
        $total_amount = 0;
        foreach ($products as $product) {
            $total_amount += $product['subtotal'];
        }
        
        // Generate order number
        $order_number = 'ORD-' . date('YmdHis');
        
        // Create order (database schema does not include downpayment fields)
        $status = 'pending';
        $stmt = $conn->prepare('INSERT INTO orders (order_number, customer_id, order_date, delivery_date, delivery_address, status, total_amount, notes) VALUES (?, ?, NOW(), ?, ?, ?, ?, ?)');
        // types: s=order_number, i=customer_id, s=delivery_date, s=delivery_address, s=status, d=total_amount, s=notes
        $stmt->bind_param('sisssds', $order_number, $customer_id, $delivery_date, $delivery_address, $status, $total_amount, $notes);
        $stmt->execute();
        $order_id = $conn->insert_id;
        $stmt->close();
        
        // Add order items
        // Insert order items (include total_price per schema)
        $stmt = $conn->prepare('INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)');
        foreach ($products as $product) {
            $product_id = $product['product_id'];
            $quantity = $product['quantity'];
            $unit_price = $product['unit_price'];
            $total_price = $quantity * $unit_price;
            // types: i=order_id, i=product_id, i=quantity, d=unit_price, d=total_price
            $stmt->bind_param('iiidd', $order_id, $product_id, $quantity, $unit_price, $total_price);
            $stmt->execute();
        }
        $stmt->close();
        
        // Record downpayment if provided
        if ($downpayment > 0) {
            $payment_method = 'cash';
            $payment_type = 'downpayment';
            // Insert payment using schema: order_id, amount, payment_date (NOW), payment_method, payment_type
            $stmt = $conn->prepare('INSERT INTO payments (order_id, amount, payment_date, payment_method, payment_type) VALUES (?, ?, NOW(), ?, ?)');
            $stmt->bind_param('idss', $order_id, $downpayment, $payment_method, $payment_type);
            $stmt->execute();
            $stmt->close();
        }
        
        $conn->commit();
        $conn->close();
        
        // Send SMS confirmation to customer (after database commit)
        $sms_result = sendOrderConfirmationSMS($customer_name, $customer_phone, $order_number, $delivery_date, $total_amount);

        echo json_encode([
            'success' => true,
            'message' => 'Order created successfully. ' . ($sms_result['success'] ? 'Confirmation SMS sent to customer.' : 'Note: SMS delivery may have encountered an issue.'),
            'order_number' => $order_number,
            'order_id' => $order_id,
            'sms_sent' => $sms_result['success']
        ]);
    } catch (Exception $e) {
        // If mysqli_report enabled, errors throw exceptions; rollback and return JSON error
        if ($conn) {
            $conn->rollback();
            $conn->close();
        }
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error creating order: ' . $e->getMessage()]);
    }
    exit();
}

// Get customers for autocomplete
$conn = require_once __DIR__ . '/../../config/database.php';
$customers = [];
$result = $conn->query('SELECT id, name, phone, category FROM customers ORDER BY name');
while ($row = $result->fetch_assoc()) {
    $customers[] = $row;
}

// Define products with pricing
$products = [
    ['id' => 1, 'name' => 'Hardbound', 'price' => 350, 'category' => 'Books'],
    ['id' => 2, 'name' => 'Softbound', 'price' => 100, 'category' => 'Books'],
    ['id' => 3, 'name' => 'Receipt (1 dozen)', 'price' => 2000, 'category' => 'Receipts'],
    ['id' => 4, 'name' => 'Receipt (100 books/pad)', 'price' => 4000, 'category' => 'Receipts'],
];

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Order - Flor de Liz</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <?php require_once __DIR__ . '/../../includes/sidebar_navigation.php'; ?>

    <div class="absolute w-full -ml-2 top-12">
        <div id="main-content" class="flex-1 px-4 sm:px-6 lg:px-8 py-12">
            <div class="max-w-4xl mx-auto">
                <div class="mb-8">
                    <h2 class="text-4xl font-bold text-gray-900">Create New Order</h2>
                    <p class="text-gray-600 mt-2">Fill in the details below to create a new order</p>
                </div>

                <div id="alert-container"></div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Form Section -->
                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-xl shadow-md p-8">
                            <form id="createOrderForm">
                                <!-- Customer Section -->
                                <div class="mb-8">
                                    <h3 class="text-lg font-bold text-gray-900 mb-4">Customer Information</h3>
                                    
                                    <div class="mb-4">
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Select Existing Customer</label>
                                        <select id="customerSelect" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500">
                                            <option value="">-- New Customer --</option>
                                            <?php foreach ($customers as $cust): ?>
                                                <option value="<?php echo $cust['id']; ?>" data-name="<?php echo htmlspecialchars($cust['name']); ?>" data-phone="<?php echo htmlspecialchars($cust['phone']); ?>" data-category="<?php echo htmlspecialchars($cust['category']); ?>">
                                                    <?php echo htmlspecialchars($cust['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Customer Name *</label>
                                            <input type="text" id="customerName" name="customer_name" placeholder="Enter customer name" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500" required>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Phone</label>
                                            <input type="tel" id="customerPhone" name="customer_phone" placeholder="09XX-XXX-XXXX" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500">
                                        </div>
                                    </div>

                                    <div class="mt-4">
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Customer Category</label>
                                        <select id="customerCategory" name="customer_category" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500">
                                            <option value="">Select Category</option>
                                            <option value="Ogis">Ogis</option>
                                            <option value="Motor Trade">Motor Trade</option>
                                            <option value="Sari-sari Store">Sari-sari Store</option>
                                            <option value="Private">Private</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Products Section -->
                                <div class="mb-8 border-t pt-8">
                                    <h3 class="text-lg font-bold text-gray-900 mb-4">Products</h3>
                                    
                                    <div class="mb-4">
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Add Product *</label>
                                        <div class="flex gap-2">
                                            <select id="productSelect" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500">
                                                <option value="">-- Select Product --</option>
                                                <?php foreach ($products as $prod): ?>
                                                    <option value="<?php echo $prod['id']; ?>" data-price="<?php echo $prod['price']; ?>" data-name="<?php echo htmlspecialchars($prod['name']); ?>">
                                                        <?php echo htmlspecialchars($prod['name']); ?> - ₱<?php echo number_format($prod['price'], 2); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="number" id="quantity" placeholder="Qty" value="1" class="w-20 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500" min="1">
                                            <button type="button" onclick="addProduct()" class="px-6 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition">Add</button>
                                        </div>
                                    </div>

                                    <div id="productsList" class="space-y-2">
                                        <!-- Products added here -->
                                    </div>
                                </div>

                                <!-- Delivery & Payment -->
                                <div class="mb-8 border-t pt-8">
                                    <h3 class="text-lg font-bold text-gray-900 mb-4">Delivery & Payment</h3>
                                    
                                    <div class="mb-4">
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Delivery Date *</label>
                                        <input type="date" id="deliveryDate" name="delivery_date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500" required>
                                    </div>

                                    <div class="mb-4">
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Delivery Address</label>
                                        <textarea id="deliveryAddress" name="delivery_address" placeholder="Enter delivery address..." rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500"></textarea>
                                    </div>

                                    <div class="mb-4">
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Downpayment (Optional)</label>
                                        <input type="number" id="downpayment" name="downpayment" placeholder="0.00" step="0.01" min="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500">
                                    </div>

                                    <div class="mb-4">
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Notes</label>
                                        <textarea id="notes" name="notes" placeholder="Any special instructions..." rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500"></textarea>
                                    </div>
                                </div>

                                <div class="flex gap-4">
                                    <button type="submit" class="flex-1 px-6 py-3 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition font-semibold">Create Order</button>
                                    <a href="../orders.php" class="flex-1 px-6 py-3 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition font-semibold text-center">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Summary Section -->
                    <div>
                        <div class="bg-white rounded-xl shadow-md p-8 sticky top-24">
                            <h3 class="text-lg font-bold text-gray-900 mb-4">Order Summary</h3>
                            
                            <div id="summaryProducts" class="space-y-2 mb-6 border-b pb-4">
                                <p class="text-gray-500 text-sm">No products added</p>
                            </div>

                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-700">Subtotal:</span>
                                    <span id="subtotal" class="font-semibold text-gray-900">₱0.00</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-700">Discount:</span>
                                    <span id="discount" class="font-semibold text-green-600">₱0.00</span>
                                </div>
                                <div class="flex justify-between border-t pt-3">
                                    <span class="font-bold text-gray-900">Total:</span>
                                    <span id="total" class="text-2xl font-bold text-teal-600">₱0.00</span>
                                </div>
                                <div class="flex justify-between pt-3 border-t">
                                    <span class="text-gray-700">Downpayment:</span>
                                    <span id="downpaymentDisplay" class="font-semibold text-gray-900">₱0.00</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-700">Remaining:</span>
                                    <span id="remaining" class="font-bold text-orange-600">₱0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/js/ajax/orders.js"></script>
</body>
</html>
