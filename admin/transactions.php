<?php
// admin/transactions.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['is_logged_in']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login_admin.php');
    exit();
}

// Get database connection
$db_return = require_once __DIR__ . '/../config/database.php';
if ($db_return instanceof mysqli) {
    $conn = $db_return;
} elseif (isset($conn) && $conn instanceof mysqli) {
    // existing connection available
} else {
    error_log('Database connection error in admin/transactions.php');
    die('Database connection error');
}

// Fetch all inventory transactions (from inventory module - products and materials)
$inventory_sql = "SELECT it.id, it.transaction_type, it.quantity, it.notes, it.created_at,
                         p.product_name as product_name, im.name as material_name, u.full_name
                  FROM inventory_transactions it
                  LEFT JOIN inventory p ON it.product_id = p.id
                  LEFT JOIN inventory_materials im ON it.material_id = im.id
                  LEFT JOIN users u ON it.created_by = u.id
                  ORDER BY it.created_at DESC";
$inventory_result = $conn->query($inventory_sql);
$inventory_transactions = $inventory_result ? $inventory_result->fetch_all(MYSQLI_ASSOC) : [];

// Fetch all sales transactions (from sales module - direct sales/counter sales)
$sales_sql = "SELECT st.id, st.transaction_date, st.total_sales, st.notes,
                     u.full_name
              FROM sales_transactions st
              LEFT JOIN users u ON st.created_by = u.id
              ORDER BY st.transaction_date DESC";
$sales_result = $conn->query($sales_sql);
$sales_transactions = $sales_result ? $sales_result->fetch_all(MYSQLI_ASSOC) : [];

// Calculate statistics for inventory transactions
$inv_stats = ['total' => 0, 'in' => 0, 'out' => 0, 'adjustment' => 0];
foreach ($inventory_transactions as $t) {
    $inv_stats['total']++;
    $inv_stats[$t['transaction_type']]++;
}

// Calculate statistics for sales transactions
$sales_stats = ['total' => 0, 'total_amount' => 0];
foreach ($sales_transactions as $t) {
    $sales_stats['total']++;
    $sales_stats['total_amount'] += (float)$t['total_sales'];
}

// Fetch recent sales data for overview
$recent_sales_sql = "SELECT s.id, s.sale_date, s.total_amount, COUNT(si.id) AS item_count 
                     FROM sales s 
                     LEFT JOIN sale_items si ON s.id = si.sale_id 
                     GROUP BY s.id 
                     ORDER BY s.sale_date DESC LIMIT 5";
$recent_sales_result = $conn->query($recent_sales_sql);
$recent_sales_data = $recent_sales_result ? $recent_sales_result->fetch_all(MYSQLI_ASSOC) : [];

$conn->close();
require_once __DIR__ . '/../includes/sidebar_navigation.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Transactions - Flor de Liz</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="absolute w-full -ml-2 top-12">
        <div id="main-content" class="flex-1 px-4 sm:px-6 lg:px-8 py-12">
            <div class="max-w-6xl mx-auto">
                <div class="mb-8">
                    <h2 class="text-4xl font-bold text-gray-900">Transactions</h2>
                    <p class="text-gray-600 mt-2">View and track all inventory and sales transactions</p>
                </div>

                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white p-6 rounded-xl shadow-md">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-600 text-sm font-semibold">Total Inventory Transactions</p>
                                <p class="text-3xl font-bold text-amber-600 mt-2"><?php echo $inv_stats['total']; ?></p>
                            </div>
                            <div class="w-12 h-12 bg-amber-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-xl shadow-md">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-600 text-sm font-semibold">Stock In</p>
                                <p class="text-3xl font-bold text-green-600 mt-2"><?php echo $inv_stats['in']; ?></p>
                            </div>
                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-xl shadow-md">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-600 text-sm font-semibold">Stock Out</p>
                                <p class="text-3xl font-bold text-red-600 mt-2"><?php echo $inv_stats['out']; ?></p>
                            </div>
                            <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-xl shadow-md">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-600 text-sm font-semibold">Total Sales</p>
                                <p class="text-3xl font-bold text-amber-600 mt-2">₱<?php echo number_format($sales_stats['total_amount'], 0); ?></p>
                            </div>
                            <div class="w-12 h-12 bg-amber-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab Navigation -->
                <div class="flex gap-4 mb-6 border-b border-gray-200">
                    <button onclick="showTab('inventory')" id="inventory-tab" class="tab-button px-4 py-2 rounded-lg font-semibold text-base border-b-2 border-amber-500 text-amber-600 bg-amber-50">Inventory Transactions</button>
                    <button onclick="showTab('sales')" id="sales-tab" class="tab-button px-4 py-2 rounded-lg font-semibold text-base border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 bg-gray-50">Sales Transactions</button>
                </div>

                <!-- Inventory Transactions Tab -->
                <div id="inventory-tab-content" class="tab-content">
                    <div class="bg-white p-6 rounded-xl shadow-md">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-2xl font-bold text-gray-900">Inventory Transactions</h3>
                            <div class="flex gap-2">
                                <input type="text" id="inv-search" placeholder="Search..." class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                                <select id="inv-type-filter" class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                                    <option value="">All Types</option>
                                    <option value="in">Stock In</option>
                                    <option value="out">Stock Out</option>
                                    <option value="adjustment">Adjustment</option>
                                </select>
                            </div>
                        </div>
                        <?php if (!empty($inventory_transactions)): ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm" id="inventory-table">
                                <thead class="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th class="px-6 py-3 text-left font-semibold text-gray-700">Transaction Date</th>
                                        <th class="px-6 py-3 text-left font-semibold text-gray-700">Transaction Type</th>
                                        <th class="px-6 py-3 text-left font-semibold text-gray-700">Item Name</th>
                                        <th class="px-6 py-3 text-left font-semibold text-gray-700">Quantity</th>
                                        <th class="px-6 py-3 text-left font-semibold text-gray-700">Description</th>
                                        <th class="px-6 py-3 text-left font-semibold text-gray-700">Recorded By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($inventory_transactions as $transaction): ?>
                                    <tr class="border-b border-gray-100 hover:bg-gray-50 inventory-row" data-type="<?php echo $transaction['transaction_type']; ?>">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium"><?php echo date('M d, Y H:i', strtotime($transaction['created_at'])); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full <?php 
                                                $typeClass = 'bg-gray-100 text-gray-800';
                                                $typeLabel = ucfirst($transaction['transaction_type']);
                                                switch ($transaction['transaction_type']) {
                                                    case 'in': 
                                                        $typeClass = 'bg-green-100 text-green-800';
                                                        $typeLabel = 'Stock In';
                                                        break;
                                                    case 'out': 
                                                        $typeClass = 'bg-red-100 text-red-800';
                                                        $typeLabel = 'Stock Out';
                                                        break;
                                                    case 'adjustment': 
                                                        $typeClass = 'bg-yellow-100 text-yellow-800';
                                                        $typeLabel = 'Stock Adjustment';
                                                        break;
                                                }
                                                echo $typeClass;
                                            ?>"><?php echo $typeLabel; ?></span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900 font-medium" data-search="<?php echo htmlspecialchars($transaction['product_name'] ?? $transaction['material_name'] ?? 'N/A'); ?>"><?php echo htmlspecialchars($transaction['product_name'] ?? $transaction['material_name'] ?? 'N/A'); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium"><?php echo $transaction['quantity']; ?></td>
                                        <td class="px-6 py-4 text-sm text-gray-700 max-w-xs"><?php echo htmlspecialchars($transaction['notes'] ?? ''); ?></td>
                                        <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($transaction['full_name'] ?? 'System'); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-8">
                            <p class="text-lg text-gray-500">No inventory transactions found</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Sales Transactions Tab -->
                <div id="sales-tab-content" class="tab-content hidden">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                        <div class="bg-white p-6 rounded-xl shadow-md">
                            <p class="text-gray-600 text-sm font-semibold">Total Transactions</p>
                            <p class="text-3xl font-bold text-amber-600 mt-2"><?php echo $sales_stats['total']; ?></p>
                        </div>
                        <div class="bg-white p-6 rounded-xl shadow-md">
                            <p class="text-gray-600 text-sm font-semibold">Total Revenue</p>
                            <p class="text-3xl font-bold text-green-600 mt-2">₱<?php echo number_format($sales_stats['total_amount'], 0); ?></p>
                        </div>
                        <div class="bg-white p-6 rounded-xl shadow-md">
                            <p class="text-gray-600 text-sm font-semibold">Average Sale</p>
                            <p class="text-3xl font-bold text-amber-600 mt-2">₱<?php echo $sales_stats['total'] > 0 ? number_format($sales_stats['total_amount'] / $sales_stats['total'], 0) : '0'; ?></p>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-xl shadow-md">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-2xl font-bold text-gray-900">Sales Transactions</h3>
                            <input type="text" id="sales-search" placeholder="Search..." class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                        </div>
                        <?php if (!empty($sales_transactions)): ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm" id="sales-table">
                                <thead class="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th class="px-6 py-3 text-left font-semibold text-gray-700">Transaction ID</th>
                                        <th class="px-6 py-3 text-left font-semibold text-gray-700">Sale Date</th>
                                        <th class="px-6 py-3 text-right font-semibold text-gray-700">Amount</th>
                                        <th class="px-6 py-3 text-left font-semibold text-gray-700">Description</th>
                                        <th class="px-6 py-3 text-left font-semibold text-gray-700">Recorded By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sales_transactions as $transaction): ?>
                                    <tr class="border-b border-gray-100 hover:bg-gray-50 sales-row" data-search="<?php echo htmlspecialchars($transaction['notes'] ?? ''); ?>">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">#<?php echo $transaction['id']; ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo date('M d, Y', strtotime($transaction['transaction_date'])); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 font-semibold">₱<?php echo number_format($transaction['total_sales'], 2); ?></td>
                                        <td class="px-6 py-4 text-sm text-gray-700 max-w-xs"><?php echo htmlspecialchars($transaction['notes'] ?? ''); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($transaction['full_name'] ?? 'System'); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-8">
                            <p class="text-lg text-gray-500">No sales transactions found</p>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($recent_sales_data)): ?>
                    <div class="bg-white p-6 rounded-xl shadow-md mt-6">
                        <h3 class="text-2xl font-bold text-gray-900 mb-4">Recent Sales Summary</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th class="px-6 py-3 text-left font-semibold text-gray-700">Sale ID</th>
                                        <th class="px-6 py-3 text-left font-semibold text-gray-700">Sale Date & Time</th>
                                        <th class="px-6 py-3 text-left font-semibold text-gray-700">Items Sold</th>
                                        <th class="px-6 py-3 text-right font-semibold text-gray-700">Total Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_sales_data as $sale): ?>
                                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">#<?php echo $sale['id']; ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo date('M d, Y H:i', strtotime($sale['sale_date'])); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $sale['item_count']; ?> item<?php echo $sale['item_count'] != 1 ? 's' : ''; ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 font-semibold">₱<?php echo number_format($sale['total_amount'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Hide all tabs
            document.getElementById('inventory-tab-content').classList.add('hidden');
            document.getElementById('sales-tab-content').classList.add('hidden');
            
            // Remove active styles from all buttons
            document.getElementById('inventory-tab').classList.remove('border-amber-500', 'text-amber-600', 'bg-amber-50');
            document.getElementById('inventory-tab').classList.add('border-transparent', 'text-gray-500', 'bg-gray-50');
            document.getElementById('sales-tab').classList.remove('border-amber-500', 'text-amber-600', 'bg-amber-50');
            document.getElementById('sales-tab').classList.add('border-transparent', 'text-gray-500', 'bg-gray-50');
            
            // Show selected tab
            if (tabName === 'inventory') {
                document.getElementById('inventory-tab-content').classList.remove('hidden');
                document.getElementById('inventory-tab').classList.remove('border-transparent', 'text-gray-500', 'bg-gray-50');
                document.getElementById('inventory-tab').classList.add('border-amber-500', 'text-amber-600', 'bg-amber-50');
            } else if (tabName === 'sales') {
                document.getElementById('sales-tab-content').classList.remove('hidden');
                document.getElementById('sales-tab').classList.remove('border-transparent', 'text-gray-500', 'bg-gray-50');
                document.getElementById('sales-tab').classList.add('border-amber-500', 'text-amber-600', 'bg-amber-50');
            }
        }

        // Inventory search and filter
        const invSearchInput = document.getElementById('inv-search');
        const invTypeFilter = document.getElementById('inv-type-filter');
        const inventoryTable = document.getElementById('inventory-table');

        if (invSearchInput && inventoryTable) {
            invSearchInput.addEventListener('keyup', filterInventoryTable);
            invTypeFilter.addEventListener('change', filterInventoryTable);

            function filterInventoryTable() {
                const searchTerm = invSearchInput.value.toLowerCase();
                const typeFilter = invTypeFilter.value;
                const rows = inventoryTable.querySelectorAll('tbody tr');

                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    const searchAttr = row.querySelector('[data-search]')?.getAttribute('data-search')?.toLowerCase() || '';
                    const type = row.getAttribute('data-type');
                    
                    const matchesSearch = text.includes(searchTerm) || searchAttr.includes(searchTerm);
                    const matchesType = !typeFilter || type === typeFilter;
                    
                    row.style.display = (matchesSearch && matchesType) ? '' : 'none';
                });
            }
        }

        // Sales search
        const salesSearchInput = document.getElementById('sales-search');
        const salesTable = document.getElementById('sales-table');

        if (salesSearchInput && salesTable) {
            salesSearchInput.addEventListener('keyup', function() {
                const searchTerm = this.value.toLowerCase();
                const rows = salesTable.querySelectorAll('tbody tr');

                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                });
            });
        }
    </script>
    </div>
        </div>
    </div>
</body>
</html>