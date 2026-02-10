<?php
// Insert sample transaction data
require_once __DIR__ . '/config/database.php';

if (!($conn instanceof mysqli)) {
    die("Database connection error");
}

$errors = [];
$inserted = ['inventory' => 0, 'sales' => 0, 'sales_transactions' => 0];

try {
    // Get users for created_by
    $users_result = $conn->query("SELECT id FROM users LIMIT 1");
    $user = $users_result->fetch_assoc();
    $user_id = $user['id'] ?? 1;

    // ===== INVENTORY TRANSACTIONS SAMPLE DATA =====
    $inventory_transactions = [
        [
            'product_id' => 1,
            'material_id' => NULL,
            'transaction_type' => 'in',
            'quantity' => 50,
            'notes' => 'Stock received from supplier - Star Paper Corporation'
        ],
        [
            'product_id' => 2,
            'material_id' => NULL,
            'transaction_type' => 'out',
            'quantity' => 25,
            'notes' => 'Sold to customer - Ogis Store'
        ],
        [
            'product_id' => NULL,
            'material_id' => 5,
            'transaction_type' => 'in',
            'quantity' => 10,
            'notes' => 'New material stock - Carbonless Paper'
        ],
        [
            'product_id' => 3,
            'material_id' => NULL,
            'transaction_type' => 'adjustment',
            'quantity' => -5,
            'notes' => 'Inventory adjustment - damaged units'
        ],
        [
            'product_id' => 1,
            'material_id' => NULL,
            'transaction_type' => 'out',
            'quantity' => 15,
            'notes' => 'Bulk order fulfillment - Motor Trade customer'
        ],
        [
            'product_id' => NULL,
            'material_id' => 6,
            'transaction_type' => 'out',
            'quantity' => 100,
            'notes' => 'Used for printing job - Colored Bondpaper'
        ],
        [
            'product_id' => 4,
            'material_id' => NULL,
            'transaction_type' => 'in',
            'quantity' => 20,
            'notes' => 'Restock - Receipt pads'
        ],
    ];

    foreach ($inventory_transactions as $trans) {
        $stmt = $conn->prepare("
            INSERT INTO inventory_transactions 
            (product_id, material_id, transaction_type, quantity, notes, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        if ($stmt) {
            $stmt->bind_param(
                'iisisis',
                $trans['product_id'],
                $trans['material_id'],
                $trans['transaction_type'],
                $trans['quantity'],
                $trans['notes'],
                $user_id
            );
            
            if ($stmt->execute()) {
                $inserted['inventory']++;
            } else {
                $errors[] = "Failed to insert inventory transaction: " . $stmt->error;
            }
            $stmt->close();
        }
    }

    // ===== SALES SAMPLE DATA =====
    $sales_data = [
        ['sale_date' => '2026-02-01 09:30:00', 'total_amount' => 5250.00],
        ['sale_date' => '2026-02-02 10:15:00', 'total_amount' => 3500.00],
        ['sale_date' => '2026-02-03 14:45:00', 'total_amount' => 8750.00],
        ['sale_date' => '2026-02-04 11:20:00', 'total_amount' => 4200.00],
    ];

    $sale_ids = [];
    foreach ($sales_data as $sale) {
        $stmt = $conn->prepare("
            INSERT INTO sales (sale_date, total_amount)
            VALUES (?, ?)
        ");
        
        if ($stmt) {
            $stmt->bind_param('sd', $sale['sale_date'], $sale['total_amount']);
            
            if ($stmt->execute()) {
                $sale_ids[] = $stmt->insert_id;
                $inserted['sales']++;
            } else {
                $errors[] = "Failed to insert sale: " . $stmt->error;
            }
            $stmt->close();
        }
    }

    // ===== SALES TRANSACTIONS SAMPLE DATA =====
    $sales_transactions = [
        [
            'order_id' => 30,
            'transaction_date' => '2026-02-01',
            'total_sales' => 5250.00,
            'notes' => 'Hardbound books bulk order'
        ],
        [
            'order_id' => 30,
            'transaction_date' => '2026-02-02',
            'total_sales' => 3500.00,
            'notes' => 'Softbound books and receipt pads'
        ],
        [
            'order_id' => NULL,
            'transaction_date' => '2026-02-03',
            'total_sales' => 8750.00,
            'notes' => 'Multiple customer orders - Mix of products'
        ],
        [
            'order_id' => NULL,
            'transaction_date' => '2026-02-04',
            'total_sales' => 4200.00,
            'notes' => 'Sari-sari store bulk purchase'
        ],
        [
            'order_id' => NULL,
            'transaction_date' => '2026-02-04',
            'total_sales' => 2800.00,
            'notes' => 'Printing materials for local business'
        ],
    ];

    foreach ($sales_transactions as $trans) {
        $stmt = $conn->prepare("
            INSERT INTO sales_transactions 
            (order_id, transaction_date, total_sales, notes, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        if ($stmt) {
            $stmt->bind_param(
                'iisds',
                $trans['order_id'],
                $trans['transaction_date'],
                $trans['total_sales'],
                $trans['notes'],
                $user_id
            );
            
            if ($stmt->execute()) {
                $inserted['sales_transactions']++;
            } else {
                $errors[] = "Failed to insert sales transaction: " . $stmt->error;
            }
            $stmt->close();
        }
    }

    // Query to verify inserted data
    $inv_count = $conn->query("SELECT COUNT(*) as cnt FROM inventory_transactions")->fetch_assoc()['cnt'];
    $sales_count = $conn->query("SELECT COUNT(*) as cnt FROM sales")->fetch_assoc()['cnt'];
    $sales_trans_count = $conn->query("SELECT COUNT(*) as cnt FROM sales_transactions")->fetch_assoc()['cnt'];

} catch (Exception $e) {
    $errors[] = "Error: " . $e->getMessage();
}

$conn->close();

// Display results
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sample Transaction Data & Guide</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .guide-section { page-break-inside: avoid; }
        code { background-color: #f3f4f6; padding: 2px 6px; border-radius: 4px; font-family: monospace; }
    </style>
</head>
<body class="bg-gray-50 p-8">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="bg-gradient-to-r from-teal-600 to-blue-600 text-white rounded-lg shadow-lg p-8 mb-8">
            <h1 class="text-4xl font-bold mb-2">📊 Transaction System Guide</h1>
            <p class="text-lg opacity-90">Complete documentation on how transactions are recorded and tracked</p>
        </div>

        <!-- Status Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-gray-600 text-sm font-semibold">Inventory Transactions</p>
                <p class="text-3xl font-bold text-green-600 mt-2"><?php echo $inv_count ?? 0; ?></p>
                <p class="text-xs text-gray-500 mt-1">Inserted: <?php echo $inserted['inventory']; ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-gray-600 text-sm font-semibold">Sales Records</p>
                <p class="text-3xl font-bold text-blue-600 mt-2"><?php echo $sales_count ?? 0; ?></p>
                <p class="text-xs text-gray-500 mt-1">Inserted: <?php echo $inserted['sales']; ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-gray-600 text-sm font-semibold">Sales Transactions</p>
                <p class="text-3xl font-bold text-purple-600 mt-2"><?php echo $sales_trans_count ?? 0; ?></p>
                <p class="text-xs text-gray-500 mt-1">Inserted: <?php echo $inserted['sales_transactions']; ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-gray-600 text-sm font-semibold">Total Transactions</p>
                <p class="text-3xl font-bold text-amber-600 mt-2"><?php echo ($inv_count ?? 0) + ($sales_count ?? 0) + ($sales_trans_count ?? 0); ?></p>
                <p class="text-xs text-gray-500 mt-1">All systems</p>
            </div>
        </div>

        <!-- Error Messages -->
        <?php if (!empty($errors)): ?>
        <div class="bg-red-50 border border-red-200 rounded-lg p-6 mb-8">
            <h3 class="font-semibold text-red-900 mb-3">⚠️ Errors Encountered:</h3>
            <ul class="space-y-2">
                <?php foreach ($errors as $error): ?>
                <li class="text-red-700">• <?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Main Content -->
        <div class="space-y-6">
            <!-- 1. Overview -->
            <div class="bg-white rounded-lg shadow p-8 guide-section">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">1️⃣ Transaction System Overview</h2>
                <div class="space-y-4 text-gray-700">
                    <p>The Flor de Liz transaction system tracks two main types of transactions:</p>
                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4">
                        <p class="font-semibold text-blue-900">Inventory Transactions</p>
                        <p class="text-blue-800">Track all movement of products and materials (Stock In, Out, Adjustments)</p>
                    </div>
                    <div class="bg-purple-50 border-l-4 border-purple-500 p-4">
                        <p class="font-semibold text-purple-900">Sales Transactions</p>
                        <p class="text-purple-800">Record all sales records and revenue from customer purchases</p>
                    </div>
                </div>
            </div>

            <!-- 2. Database Structure -->
            <div class="bg-white rounded-lg shadow p-8 guide-section">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">2️⃣ Database Tables & Structure</h2>
                
                <div class="space-y-6">
                    <!-- Inventory Transactions Table -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-3">📦 inventory_transactions Table</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm border-collapse">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="border p-2 text-left font-semibold">Column</th>
                                        <th class="border p-2 text-left font-semibold">Type</th>
                                        <th class="border p-2 text-left font-semibold">Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="hover:bg-gray-50">
                                        <td class="border p-2"><code>id</code></td>
                                        <td class="border p-2">INT (Primary Key)</td>
                                        <td class="border p-2">Unique identifier</td>
                                    </tr>
                                    <tr class="hover:bg-gray-50">
                                        <td class="border p-2"><code>product_id</code></td>
                                        <td class="border p-2">INT (Foreign Key)</td>
                                        <td class="border p-2">Reference to products table (NULL for materials)</td>
                                    </tr>
                                    <tr class="hover:bg-gray-50">
                                        <td class="border p-2"><code>material_id</code></td>
                                        <td class="border p-2">INT (Foreign Key)</td>
                                        <td class="border p-2">Reference to inventory_materials table (NULL for products)</td>
                                    </tr>
                                    <tr class="hover:bg-gray-50">
                                        <td class="border p-2"><code>transaction_type</code></td>
                                        <td class="border p-2">ENUM</td>
                                        <td class="border p-2">'in' (Stock In), 'out' (Stock Out), 'adjustment' (Adjustment)</td>
                                    </tr>
                                    <tr class="hover:bg-gray-50">
                                        <td class="border p-2"><code>quantity</code></td>
                                        <td class="border p-2">INT</td>
                                        <td class="border p-2">Number of units (negative for adjustments/reductions)</td>
                                    </tr>
                                    <tr class="hover:bg-gray-50">
                                        <td class="border p-2"><code>notes</code></td>
                                        <td class="border p-2">TEXT</td>
                                        <td class="border p-2">Additional details about the transaction</td>
                                    </tr>
                                    <tr class="hover:bg-gray-50">
                                        <td class="border p-2"><code>created_by</code></td>
                                        <td class="border p-2">INT (Foreign Key)</td>
                                        <td class="border p-2">Reference to users table - who recorded it</td>
                                    </tr>
                                    <tr class="hover:bg-gray-50">
                                        <td class="border p-2"><code>created_at</code></td>
                                        <td class="border p-2">TIMESTAMP</td>
                                        <td class="border p-2">When the transaction was recorded</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Sales Transactions Table -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-3">💰 sales_transactions Table</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm border-collapse">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="border p-2 text-left font-semibold">Column</th>
                                        <th class="border p-2 text-left font-semibold">Type</th>
                                        <th class="border p-2 text-left font-semibold">Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="hover:bg-gray-50">
                                        <td class="border p-2"><code>id</code></td>
                                        <td class="border p-2">INT (Primary Key)</td>
                                        <td class="border p-2">Unique identifier</td>
                                    </tr>
                                    <tr class="hover:bg-gray-50">
                                        <td class="border p-2"><code>order_id</code></td>
                                        <td class="border p-2">INT (Foreign Key)</td>
                                        <td class="border p-2">Reference to orders table (optional)</td>
                                    </tr>
                                    <tr class="hover:bg-gray-50">
                                        <td class="border p-2"><code>transaction_date</code></td>
                                        <td class="border p-2">DATE</td>
                                        <td class="border p-2">Date of the sales transaction</td>
                                    </tr>
                                    <tr class="hover:bg-gray-50">
                                        <td class="border p-2"><code>total_sales</code></td>
                                        <td class="border p-2">DECIMAL(12,2)</td>
                                        <td class="border p-2">Total amount of the sale</td>
                                    </tr>
                                    <tr class="hover:bg-gray-50">
                                        <td class="border p-2"><code>notes</code></td>
                                        <td class="border p-2">TEXT</td>
                                        <td class="border p-2">Additional notes or details</td>
                                    </tr>
                                    <tr class="hover:bg-gray-50">
                                        <td class="border p-2"><code>created_by</code></td>
                                        <td class="border p-2">INT (Foreign Key)</td>
                                        <td class="border p-2">Reference to users table - staff who recorded it</td>
                                    </tr>
                                    <tr class="hover:bg-gray-50">
                                        <td class="border p-2"><code>created_at</code></td>
                                        <td class="border p-2">TIMESTAMP</td>
                                        <td class="border p-2">When the transaction was recorded</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. How Transactions Are Recorded -->
            <div class="bg-white rounded-lg shadow p-8 guide-section">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">3️⃣ How Transactions Are Recorded</h2>
                
                <div class="space-y-6">
                    <!-- Inventory Recording -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-3">📝 Recording Inventory Transactions</h3>
                        <p class="text-gray-700 mb-4">Inventory transactions can be recorded through:</p>
                        
                        <div class="bg-amber-50 border border-amber-200 rounded p-4 mb-4">
                            <p class="font-semibold text-amber-900 mb-2">1. Staff Inventory Management</p>
                            <p class="text-amber-800 text-sm mb-3">Navigate to: <code>Staff > Inventory</code></p>
                            <ul class="text-amber-800 text-sm space-y-1">
                                <li>✓ Add new inventory items (creates 'in' transaction)</li>
                                <li>✓ Record stock received from suppliers</li>
                                <li>✓ Automatic transaction logging</li>
                            </ul>
                        </div>

                        <div class="bg-amber-50 border border-amber-200 rounded p-4">
                            <p class="font-semibold text-amber-900 mb-2">2. Sales Process</p>
                            <p class="text-amber-800 text-sm mb-3">Navigate to: <code>Staff > Sales > Create Sale</code></p>
                            <ul class="text-amber-800 text-sm space-y-1">
                                <li>✓ When items are sold, 'out' transactions are created</li>
                                <li>✓ System automatically tracks inventory deductions</li>
                                <li>✓ Sales transactions recorded simultaneously</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Sales Recording -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-3 mt-6">💼 Recording Sales Transactions</h3>
                        <p class="text-gray-700 mb-4">Sales transactions are recorded automatically during the sales process:</p>
                        
                        <div class="bg-green-50 border border-green-200 rounded p-4">
                            <ol class="text-green-800 text-sm space-y-3">
                                <li><strong>1. Create Sale Order:</strong> Staff member creates a new sale order</li>
                                <li><strong>2. Add Items:</strong> Select products/materials and quantities to sell</li>
                                <li><strong>3. System Records:</strong>
                                    <ul class="ml-4 mt-1 space-y-1">
                                        <li>• Sale entry in <code>sales</code> table</li>
                                        <li>• Sale items in <code>sale_items</code> table</li>
                                        <li>• Inventory 'out' transaction in <code>inventory_transactions</code> table</li>
                                        <li>• Sales transaction in <code>sales_transactions</code> table</li>
                                    </ul>
                                </li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Viewing Transactions -->
            <div class="bg-white rounded-lg shadow p-8 guide-section">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">4️⃣ Viewing Transactions</h2>
                
                <div class="space-y-4">
                    <div class="bg-teal-50 border border-teal-200 rounded p-4">
                        <h4 class="font-semibold text-teal-900 mb-2">👥 For Staff Users</h4>
                        <p class="text-teal-800">Navigate to: <code>Staff > Transactions</code></p>
                        <ul class="text-teal-800 text-sm mt-2 space-y-1">
                            <li>✓ View all inventory transactions</li>
                            <li>✓ View all sales transactions</li>
                            <li>✓ Search and filter by type, date, staff member</li>
                            <li>✓ See transaction details including who recorded it</li>
                            <li>✓ Statistics dashboard with totals</li>
                        </ul>
                    </div>

                    <div class="bg-amber-50 border border-amber-200 rounded p-4">
                        <h4 class="font-semibold text-amber-900 mb-2">🏢 For Admin Users</h4>
                        <p class="text-amber-800">Navigate to: <code>Admin > Transactions</code></p>
                        <ul class="text-amber-800 text-sm mt-2 space-y-1">
                            <li>✓ Full audit trail of all transactions</li>
                            <li>✓ Complete inventory and sales history</li>
                            <li>✓ Filter and search capabilities</li>
                            <li>✓ Revenue statistics and analytics</li>
                            <li>✓ Track which staff member created each transaction</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- 5. Sample Data Inserted -->
            <div class="bg-white rounded-lg shadow p-8 guide-section">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">5️⃣ Sample Data Inserted</h2>
                
                <div class="space-y-6">
                    <!-- Inventory Transactions -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-3">Inventory Transactions (<?php echo $inserted['inventory']; ?> records)</h3>
                        <div class="space-y-2">
                            <div class="bg-green-50 p-3 rounded border border-green-200">
                                <p class="text-green-900 font-semibold">✓ STOCK IN: 50 Hardbound Books</p>
                                <p class="text-green-700 text-sm">From: Star Paper Corporation</p>
                            </div>
                            <div class="bg-red-50 p-3 rounded border border-red-200">
                                <p class="text-red-900 font-semibold">✗ STOCK OUT: 25 Softbound Books</p>
                                <p class="text-red-700 text-sm">To: Ogis Store customer</p>
                            </div>
                            <div class="bg-green-50 p-3 rounded border border-green-200">
                                <p class="text-green-900 font-semibold">✓ STOCK IN: 10 Carbonless Paper</p>
                                <p class="text-green-700 text-sm">New material stock received</p>
                            </div>
                            <div class="bg-yellow-50 p-3 rounded border border-yellow-200">
                                <p class="text-yellow-900 font-semibold">⚠ ADJUSTMENT: -5 Receipt (1 dozen)</p>
                                <p class="text-yellow-700 text-sm">Damaged units removed from inventory</p>
                            </div>
                            <div class="bg-red-50 p-3 rounded border border-red-200">
                                <p class="text-red-900 font-semibold">✗ STOCK OUT: 15 Hardbound Books</p>
                                <p class="text-red-700 text-sm">Bulk order fulfillment - Motor Trade</p>
                            </div>
                            <div class="bg-red-50 p-3 rounded border border-red-200">
                                <p class="text-red-900 font-semibold">✗ STOCK OUT: 100 Colored Bondpaper</p>
                                <p class="text-red-700 text-sm">Used for printing job</p>
                            </div>
                            <div class="bg-green-50 p-3 rounded border border-green-200">
                                <p class="text-green-900 font-semibold">✓ STOCK IN: 20 Receipt (100 books/pad)</p>
                                <p class="text-green-700 text-sm">Restock - Receipt pads</p>
                            </div>
                        </div>
                    </div>

                    <!-- Sales Records -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-3">Sales Records (<?php echo $inserted['sales']; ?> records)</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="border p-2 text-left">Date</th>
                                        <th class="border p-2 text-right">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="hover:bg-gray-50">
                                        <td class="border p-2">Feb 1, 2026 @ 9:30 AM</td>
                                        <td class="border p-2 text-right font-semibold">₱5,250.00</td>
                                    </tr>
                                    <tr class="hover:bg-gray-50">
                                        <td class="border p-2">Feb 2, 2026 @ 10:15 AM</td>
                                        <td class="border p-2 text-right font-semibold">₱3,500.00</td>
                                    </tr>
                                    <tr class="hover:bg-gray-50">
                                        <td class="border p-2">Feb 3, 2026 @ 2:45 PM</td>
                                        <td class="border p-2 text-right font-semibold">₱8,750.00</td>
                                    </tr>
                                    <tr class="hover:bg-gray-50">
                                        <td class="border p-2">Feb 4, 2026 @ 11:20 AM</td>
                                        <td class="border p-2 text-right font-semibold">₱4,200.00</td>
                                    </tr>
                                </tbody>
                                <tfoot class="bg-gray-100">
                                    <tr>
                                        <td class="border p-2 font-semibold">TOTAL:</td>
                                        <td class="border p-2 text-right font-bold">₱21,700.00</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Sales Transactions -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-3">Sales Transactions (<?php echo $inserted['sales_transactions']; ?> records)</h3>
                        <div class="space-y-2">
                            <div class="bg-purple-50 p-3 rounded border border-purple-200">
                                <p class="text-purple-900 font-semibold">Feb 1: ₱5,250.00</p>
                                <p class="text-purple-700 text-sm">Hardbound books bulk order</p>
                            </div>
                            <div class="bg-purple-50 p-3 rounded border border-purple-200">
                                <p class="text-purple-900 font-semibold">Feb 2: ₱3,500.00</p>
                                <p class="text-purple-700 text-sm">Softbound books and receipt pads</p>
                            </div>
                            <div class="bg-purple-50 p-3 rounded border border-purple-200">
                                <p class="text-purple-900 font-semibold">Feb 3: ₱8,750.00</p>
                                <p class="text-purple-700 text-sm">Multiple customer orders - Mix of products</p>
                            </div>
                            <div class="bg-purple-50 p-3 rounded border border-purple-200">
                                <p class="text-purple-900 font-semibold">Feb 4: ₱4,200.00</p>
                                <p class="text-purple-700 text-sm">Sari-sari store bulk purchase</p>
                            </div>
                            <div class="bg-purple-50 p-3 rounded border border-purple-200">
                                <p class="text-purple-900 font-semibold">Feb 4: ₱2,800.00</p>
                                <p class="text-purple-700 text-sm">Printing materials for local business</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 6. Key Features -->
            <div class="bg-white rounded-lg shadow p-8 guide-section">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">6️⃣ Key Features of Transaction System</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-blue-50 border border-blue-200 rounded p-4">
                        <h4 class="font-semibold text-blue-900 mb-2">🔍 Real-time Tracking</h4>
                        <p class="text-blue-800 text-sm">All inventory and sales activities are recorded immediately in the database with timestamps</p>
                    </div>

                    <div class="bg-green-50 border border-green-200 rounded p-4">
                        <h4 class="font-semibold text-green-900 mb-2">📊 Statistics Dashboard</h4>
                        <p class="text-green-800 text-sm">View total counts, revenue summaries, and transaction breakdowns at a glance</p>
                    </div>

                    <div class="bg-purple-50 border border-purple-200 rounded p-4">
                        <h4 class="font-semibold text-purple-900 mb-2">🔐 Audit Trail</h4>
                        <p class="text-purple-800 text-sm">Track who created each transaction and when - complete accountability for all activities</p>
                    </div>

                    <div class="bg-orange-50 border border-orange-200 rounded p-4">
                        <h4 class="font-semibold text-orange-900 mb-2">🔄 Automatic Integration</h4>
                        <p class="text-orange-800 text-sm">Sales automatically create inventory 'out' transactions - no manual entry needed</p>
                    </div>

                    <div class="bg-red-50 border border-red-200 rounded p-4">
                        <h4 class="font-semibold text-red-900 mb-2">📝 Detailed Notes</h4>
                        <p class="text-red-800 text-sm">Each transaction includes notes explaining the reason - supplier info, customer details, etc.</p>
                    </div>

                    <div class="bg-teal-50 border border-teal-200 rounded p-4">
                        <h4 class="font-semibold text-teal-900 mb-2">🔎 Search & Filter</h4>
                        <p class="text-teal-800 text-sm">Powerful filtering by type, date, product, staff member - find transactions instantly</p>
                    </div>
                </div>
            </div>

            <!-- 7. Best Practices -->
            <div class="bg-white rounded-lg shadow p-8 guide-section">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">7️⃣ Best Practices for Using Transactions</h2>
                
                <div class="space-y-3">
                    <div class="flex gap-3">
                        <span class="text-2xl">✅</span>
                        <div>
                            <p class="font-semibold text-gray-900">Always include detailed notes</p>
                            <p class="text-gray-700 text-sm">Explain the reason for each transaction - supplier name, customer info, or reason for adjustment</p>
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <span class="text-2xl">✅</span>
                        <div>
                            <p class="font-semibold text-gray-900">Record transactions immediately</p>
                            <p class="text-gray-700 text-sm">Don't delay - record as items are received or sold for accurate real-time tracking</p>
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <span class="text-2xl">✅</span>
                        <div>
                            <p class="font-semibold text-gray-900">Use correct transaction types</p>
                            <p class="text-gray-700 text-sm">Select 'in' for received items, 'out' for sold items, 'adjustment' for discrepancies</p>
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <span class="text-2xl">✅</span>
                        <div>
                            <p class="font-semibold text-gray-900">Verify inventory regularly</p>
                            <p class="text-gray-700 text-sm">Use the Transactions page to reconcile physical inventory with system records</p>
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <span class="text-2xl">✅</span>
                        <div>
                            <p class="font-semibold text-gray-900">Review statistics weekly</p>
                            <p class="text-gray-700 text-sm">Monitor the dashboard to identify trends and potential inventory issues early</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 8. Navigation -->
            <div class="bg-gradient-to-r from-teal-600 to-blue-600 text-white rounded-lg shadow p-8">
                <h2 class="text-2xl font-bold mb-4">✨ Ready to Explore?</h2>
                <div class="flex flex-wrap gap-4">
                    <a href="staff/transactions.php" class="bg-white text-teal-600 hover:bg-gray-100 font-semibold py-2 px-6 rounded transition">
                        👥 View Staff Transactions
                    </a>
                    <a href="admin/transactions.php" class="bg-white text-blue-600 hover:bg-gray-100 font-semibold py-2 px-6 rounded transition">
                        🏢 View Admin Transactions
                    </a>
                    <a href="staff/sales/create.php" class="bg-white text-green-600 hover:bg-gray-100 font-semibold py-2 px-6 rounded transition">
                        📝 Create New Sale
                    </a>
                    <a href="admin/dashboard.php" class="bg-white text-amber-600 hover:bg-gray-100 font-semibold py-2 px-6 rounded transition">
                        📊 Admin Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
