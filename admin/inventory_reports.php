<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['is_logged_in']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login_admin.php'); exit();
}
$conn = require_once __DIR__ . '/../config/database.php';

$app_root = preg_replace('#/admin.*$#', '/', __DIR__);
if ($app_root === __DIR__) $app_root = '/';

// ========== CURRENT INVENTORY STATUS ==========
$status_stmt = $conn->prepare("
    SELECT id, product_name, category, quantity, unit, unit_price, reorder_level, supplier,
           (quantity * unit_price) AS total_value,
           CASE 
               WHEN quantity < reorder_level THEN 'Low'
               WHEN quantity >= (reorder_level * 2) THEN 'High'
               ELSE 'Normal'
           END AS stock_status
    FROM inventory
    ORDER BY quantity ASC, product_name ASC
");
$status_stmt->execute();
$inventory_status = $status_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$status_stmt->close();

// ========== LOW STOCK / REORDER ALERT ==========
$low_stock_stmt = $conn->prepare("
    SELECT id, product_name, category, quantity, unit, reorder_level, unit_price, supplier,
           (reorder_level - quantity) AS units_needed,
           ((reorder_level - quantity) * unit_price) AS reorder_cost
    FROM inventory
    WHERE quantity < reorder_level
    ORDER BY (reorder_level - quantity) DESC
");
$low_stock_stmt->execute();
$low_stock = $low_stock_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$low_stock_stmt->close();

// ========== INVENTORY VALUE ANALYSIS ==========
$value_stmt = $conn->prepare("
    SELECT 
        category,
        COUNT(*) AS item_count,
        SUM(quantity) AS total_units,
        SUM(quantity * unit_price) AS total_value,
        AVG(unit_price) AS avg_unit_price,
        MAX(unit_price) AS highest_price,
        MIN(unit_price) AS lowest_price
    FROM inventory
    GROUP BY category
    ORDER BY total_value DESC
");
$value_stmt->execute();
$category_analysis = $value_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$value_stmt->close();

// ========== INVENTORY TOTALS ==========
$totals_stmt = $conn->prepare("
    SELECT 
        COUNT(*) AS total_items,
        SUM(quantity) AS total_units,
        SUM(quantity * unit_price) AS total_inventory_value,
        AVG(quantity) AS avg_quantity_per_item,
        COUNT(CASE WHEN quantity < reorder_level THEN 1 END) AS low_stock_count
    FROM inventory
");
$totals_stmt->execute();
$totals = $totals_stmt->get_result()->fetch_assoc();
$totals_stmt->close();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Inventory Reports</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.tailwind.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
</head>
<body class="bg-gray-50">
    <?php require_once __DIR__ . '/../includes/sidebar_navigation.php'; ?>
    <div class="absolute w-full -ml-2 top-12">
        <div id="main-content" class="flex-1 px-4 sm:px-6 lg:px-8 py-12">
            <div class="max-w-7xl mx-auto">
                <h2 class="text-3xl font-bold mb-2">Inventory Reports</h2>
                <p class="text-gray-600 mb-6">Stock levels, value analysis, and inventory status</p>

                <!-- Summary Cards -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-md p-4 text-white">
                        <p class="text-sm opacity-90">Total Items</p>
                        <p class="text-3xl font-bold"><?php echo intval($totals['total_items']); ?></p>
                    </div>
                    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-md p-4 text-white">
                        <p class="text-sm opacity-90">Total Units</p>
                        <p class="text-3xl font-bold"><?php echo intval($totals['total_units']); ?></p>
                    </div>
                    <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow-md p-4 text-white">
                        <p class="text-sm opacity-90">Inventory Value</p>
                        <p class="text-2xl font-bold">₱<?php echo number_format($totals['total_inventory_value'], 2); ?></p>
                    </div>
                    <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-lg shadow-md p-4 text-white">
                        <p class="text-sm opacity-90">Low Stock Items</p>
                        <p class="text-3xl font-bold"><?php echo intval($totals['low_stock_count']); ?></p>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="mb-4 border-b border-gray-200">
                    <div class="flex gap-4 flex-wrap">
                        <button onclick="showTab('status')" id="tab-status" class="tab-btn px-4 py-2 border-b-2 border-amber-600 text-amber-600 font-medium active">
                            Current Stock
                        </button>
                        <button onclick="showTab('low')" id="tab-low" class="tab-btn px-4 py-2 border-b-2 border-transparent text-gray-600 font-medium hover:text-gray-800">
                            Low Stock Alert
                        </button>
                        <button onclick="showTab('value')" id="tab-value" class="tab-btn px-4 py-2 border-b-2 border-transparent text-gray-600 font-medium hover:text-gray-800">
                            Value Analysis
                        </button>
                    </div>
                </div>

                <!-- Current Stock Table -->
                <div id="tab-status-content" class="tab-content bg-white rounded-lg shadow-md p-4 mb-6">
                    <h3 class="text-lg font-semibold mb-4">Current Inventory Status</h3>
                    <table id="status-table" class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-700 bg-gray-100">
                                <th class="px-4 py-2">Product Name</th>
                                <th class="px-4 py-2">Category</th>
                                <th class="px-4 py-2">Quantity</th>
                                <th class="px-4 py-2">Unit</th>
                                <th class="px-4 py-2">Unit Price (₱)</th>
                                <th class="px-4 py-2">Total Value (₱)</th>
                                <th class="px-4 py-2">Reorder Level</th>
                                <th class="px-4 py-2">Status</th>
                                <th class="px-4 py-2">Supplier</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inventory_status as $item): ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="px-4 py-2 font-medium"><?php echo htmlspecialchars($item['product_name']); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($item['category']); ?></td>
                                    <td class="px-4 py-2"><?php echo intval($item['quantity']); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($item['unit']); ?></td>
                                    <td class="px-4 py-2">₱<?php echo number_format($item['unit_price'], 2); ?></td>
                                    <td class="px-4 py-2 font-semibold">₱<?php echo number_format($item['total_value'], 2); ?></td>
                                    <td class="px-4 py-2"><?php echo intval($item['reorder_level']); ?></td>
                                    <td class="px-4 py-2">
                                        <span class="px-2 py-1 rounded text-xs font-semibold <?php 
                                            if ($item['stock_status'] === 'Low') echo 'bg-red-100 text-red-800';
                                            elseif ($item['stock_status'] === 'High') echo 'bg-yellow-100 text-yellow-800';
                                            else echo 'bg-green-100 text-green-800';
                                        ?>">
                                            <?php echo htmlspecialchars($item['stock_status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-xs"><?php echo htmlspecialchars($item['supplier'] ?? '-'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Low Stock Alert Table -->
                <div id="tab-low-content" class="tab-content bg-white rounded-lg shadow-md p-4 mb-6 hidden">
                    <h3 class="text-lg font-semibold mb-4">Low Stock Alert - Reorder Required</h3>
                    <?php if (empty($low_stock)): ?>
                        <p class="text-gray-600 text-center py-8">✓ All items are above reorder level</p>
                    <?php else: ?>
                        <table id="low-table" class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-700 bg-gray-100">
                                    <th class="px-4 py-2">Product Name</th>
                                    <th class="px-4 py-2">Category</th>
                                    <th class="px-4 py-2">Current Qty</th>
                                    <th class="px-4 py-2">Reorder Level</th>
                                    <th class="px-4 py-2">Units Needed</th>
                                    <th class="px-4 py-2">Unit Price (₱)</th>
                                    <th class="px-4 py-2">Reorder Cost (₱)</th>
                                    <th class="px-4 py-2">Supplier</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($low_stock as $item): ?>
                                    <tr class="border-b hover:bg-red-50 bg-red-50">
                                        <td class="px-4 py-2 font-bold text-red-700"><?php echo htmlspecialchars($item['product_name']); ?></td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($item['category']); ?></td>
                                        <td class="px-4 py-2 font-semibold text-red-600"><?php echo intval($item['quantity']); ?></td>
                                        <td class="px-4 py-2"><?php echo intval($item['reorder_level']); ?></td>
                                        <td class="px-4 py-2 font-bold text-red-700">⚠ <?php echo intval($item['units_needed']); ?></td>
                                        <td class="px-4 py-2">₱<?php echo number_format($item['unit_price'], 2); ?></td>
                                        <td class="px-4 py-2 font-semibold">₱<?php echo number_format($item['reorder_cost'], 2); ?></td>
                                        <td class="px-4 py-2 text-xs"><?php echo htmlspecialchars($item['supplier'] ?? '-'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <!-- Value Analysis Table -->
                <div id="tab-value-content" class="tab-content bg-white rounded-lg shadow-md p-4 mb-6 hidden">
                    <h3 class="text-lg font-semibold mb-4">Inventory Value Analysis by Category</h3>
                    <table id="value-table" class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-700 bg-gray-100">
                                <th class="px-4 py-2">Category</th>
                                <th class="px-4 py-2">Item Count</th>
                                <th class="px-4 py-2">Total Units</th>
                                <th class="px-4 py-2">Total Value (₱)</th>
                                <th class="px-4 py-2">Avg Unit Price (₱)</th>
                                <th class="px-4 py-2">Highest Price (₱)</th>
                                <th class="px-4 py-2">Lowest Price (₱)</th>
                                <th class="px-4 py-2">% of Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($category_analysis as $cat): 
                                $pct = ($cat['total_value'] / $totals['total_inventory_value']) * 100;
                            ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="px-4 py-2 font-semibold"><?php echo htmlspecialchars($cat['category']); ?></td>
                                    <td class="px-4 py-2"><?php echo intval($cat['item_count']); ?></td>
                                    <td class="px-4 py-2"><?php echo intval($cat['total_units']); ?></td>
                                    <td class="px-4 py-2 font-bold">₱<?php echo number_format($cat['total_value'], 2); ?></td>
                                    <td class="px-4 py-2">₱<?php echo number_format($cat['avg_unit_price'], 2); ?></td>
                                    <td class="px-4 py-2">₱<?php echo number_format($cat['highest_price'], 2); ?></td>
                                    <td class="px-4 py-2">₱<?php echo number_format($cat['lowest_price'], 2); ?></td>
                                    <td class="px-4 py-2">
                                        <div class="flex items-center gap-2">
                                            <div class="w-16 bg-gray-200 rounded-full h-2">
                                                <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo $pct; ?>%"></div>
                                            </div>
                                            <span class="font-semibold text-sm"><?php echo number_format($pct, 1); ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-3 mt-6">
                    <button type="button" onclick="window.print()" class="px-4 py-2 bg-blue-600 text-white rounded text-sm font-medium hover:bg-blue-700">
                        Print Report
                    </button>
                    <a href="<?php echo $app_root; ?>staff/inventory.php" class="px-4 py-2 bg-teal-600 text-white rounded text-sm font-medium hover:bg-teal-700">
                        Manage Inventory
                    </a>
                </div>
            </div>
        </div>
    </div>

    <style>
        @media print {
            body { margin: 0; padding: 0; }
            nav, aside, button, .border-b, .flex.gap-3 {
                display: none !important;
            }
            #main-content {
                margin-left: 0 !important;
            }
            .tab-content {
                display: block !important;
                page-break-inside: avoid;
            }
            .grid {
                display: grid !important;
            }
        }
    </style>

    <script>
        function showTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.tab-btn').forEach(el => {
                el.classList.remove('border-amber-600', 'text-amber-600', 'active');
                el.classList.add('border-transparent', 'text-gray-600');
            });

            document.getElementById('tab-' + tabName + '-content').classList.remove('hidden');
            document.getElementById('tab-' + tabName).classList.add('border-amber-600', 'text-amber-600', 'active');
            document.getElementById('tab-' + tabName).classList.remove('border-transparent', 'text-gray-600');
        }

        $(document).ready(function() {
            $('#status-table').DataTable({
                pageLength: 25,
                order: [[2, 'asc']]
            });
            $('#low-table').DataTable({
                pageLength: 25,
                order: [[4, 'desc']]
            });
            $('#value-table').DataTable({
                pageLength: 25,
                order: [[3, 'desc']]
            });
        });
    </script>
</body>
</html>
