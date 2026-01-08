<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['is_logged_in']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login_admin.php'); exit();
}
$conn = require_once __DIR__ . '/../config/database.php';

$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');

// ========== DAILY SALES SUMMARY ==========
$daily_stmt = $conn->prepare("
    SELECT DATE(s.sale_date) AS sale_day, 
           COUNT(DISTINCT s.id) AS transaction_count,
           SUM(s.total_amount) AS daily_total
    FROM sales s
    WHERE DATE(s.sale_date) BETWEEN ? AND ?
    GROUP BY DATE(s.sale_date)
    ORDER BY sale_day DESC
");
$daily_stmt->bind_param('ss', $from, $to);
$daily_stmt->execute();
$daily_rows = $daily_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$daily_stmt->close();

// ========== WEEKLY SALES SUMMARY ==========
$weekly_stmt = $conn->prepare("
    SELECT YEARWEEK(s.sale_date) AS week_key,
           MIN(DATE(s.sale_date)) AS week_start,
           MAX(DATE(s.sale_date)) AS week_end,
           COUNT(DISTINCT s.id) AS transaction_count,
           SUM(s.total_amount) AS weekly_total
    FROM sales s
    WHERE DATE(s.sale_date) BETWEEN ? AND ?
    GROUP BY YEARWEEK(s.sale_date)
    ORDER BY week_key DESC
");
$weekly_stmt->bind_param('ss', $from, $to);
$weekly_stmt->execute();
$weekly_rows = $weekly_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$weekly_stmt->close();

// ========== YEARLY SALES SUMMARY ==========
$yearly_stmt = $conn->prepare("
    SELECT YEAR(s.sale_date) AS year,
           COUNT(DISTINCT s.id) AS transaction_count,
           SUM(s.total_amount) AS yearly_total
    FROM sales s
    WHERE DATE(s.sale_date) BETWEEN ? AND ?
    GROUP BY YEAR(s.sale_date)
    ORDER BY year DESC
");
$yearly_stmt->bind_param('ss', $from, $to);
$yearly_stmt->execute();
$yearly_rows = $yearly_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$yearly_stmt->close();

// ========== PRODUCT BREAKDOWN (STORE SALES) ==========
$product_stmt = $conn->prepare("
    SELECT si.product_name,
           si.unit,
           SUM(si.quantity) AS total_qty,
           AVG(si.unit_price) AS avg_price,
           SUM(si.subtotal) AS total_revenue,
           COUNT(DISTINCT s.id) AS times_sold
    FROM sale_items si
    JOIN sales s ON si.sale_id = s.id
    WHERE DATE(s.sale_date) BETWEEN ? AND ?
    GROUP BY si.product_name, si.unit
    ORDER BY total_revenue DESC
");
$product_stmt->bind_param('ss', $from, $to);
$product_stmt->execute();
$product_rows = $product_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$product_stmt->close();

// ========== CALCULATE GRAND TOTAL FROM DAILY DATA ==========
$grand_total = array_sum(array_column($daily_rows, 'daily_total'));
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Comprehensive Sales Reports</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.tailwind.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
</head>
<body class="bg-gray-50">
    <?php require_once __DIR__ . '/../includes/sidebar_navigation.php'; ?>
    <div class="absolute w-full -ml-2 top-12">
        <div id="main-content" class="flex-1 px-4 sm:px-6 lg:px-8 py-12">
            <div class="max-w-6xl mx-auto">
                <h2 class="text-3xl font-bold mb-2">Sales Reports</h2>
                <p class="text-gray-600 mb-6">Store Sales Analysis</p>

                <!-- Filter Section -->
                <div class="bg-white rounded-lg shadow-md p-4 mb-6">
                    <form method="get" class="flex gap-3 items-end flex-wrap">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                            <input type="date" name="from" value="<?php echo htmlspecialchars($from); ?>" class="border rounded px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                            <input type="date" name="to" value="<?php echo htmlspecialchars($to); ?>" class="border rounded px-3 py-2 text-sm">
                        </div>
                        <button type="submit" class="px-4 py-2 bg-teal-600 text-white rounded text-sm font-medium hover:bg-teal-700">
                            Filter
                        </button>
                        <button type="button" onclick="window.print()" class="px-4 py-2 bg-blue-600 text-white rounded text-sm font-medium hover:bg-blue-700">
                            Print
                        </button>
                    </form>
                </div>

                <!-- Summary Cards -->
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-md p-4 text-white">
                        <p class="text-sm opacity-90">Total Sales</p>
                        <p class="text-3xl font-bold">₱<?php echo number_format($grand_total, 2); ?></p>
                    </div>
                    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-md p-4 text-white">
                        <p class="text-sm opacity-90">Period</p>
                        <p class="text-lg font-medium"><?php echo htmlspecialchars($from); ?> to <?php echo htmlspecialchars($to); ?></p>
                    </div>
                </div>

                <!-- Tabs for different views -->
                <div class="mb-4 border-b border-gray-200">
                    <div class="flex gap-4">
                        <button onclick="showTab('daily')" id="tab-daily" class="tab-btn px-4 py-2 border-b-2 border-teal-600 text-teal-600 font-medium active">
                            Daily Sales
                        </button>
                        <button onclick="showTab('weekly')" id="tab-weekly" class="tab-btn px-4 py-2 border-b-2 border-transparent text-gray-600 font-medium hover:text-gray-800">
                            Weekly Sales
                        </button>
                        <button onclick="showTab('yearly')" id="tab-yearly" class="tab-btn px-4 py-2 border-b-2 border-transparent text-gray-600 font-medium hover:text-gray-800">
                            Yearly Sales
                        </button>
                        <button onclick="showTab('products')" id="tab-products" class="tab-btn px-4 py-2 border-b-2 border-transparent text-gray-600 font-medium hover:text-gray-800">
                            Product Breakdown
                        </button>
                    </div>
                </div>

                <!-- Daily Sales Table -->
                <div id="tab-daily-content" class="tab-content bg-white rounded-lg shadow-md p-4 mb-6">
                    <h3 class="text-lg font-semibold mb-4">Daily Sales Summary</h3>
                    <table id="daily-table" class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-700 bg-gray-100">
                                <th class="px-4 py-2">Date</th>
                                <th class="px-4 py-2">Transactions</th>
                                <th class="px-4 py-2">Total (₱)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($daily_rows as $r): ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($r['sale_day']); ?></td>
                                    <td class="px-4 py-2"><?php echo intval($r['transaction_count']); ?></td>
                                    <td class="px-4 py-2 font-semibold">₱<?php echo number_format($r['daily_total'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Weekly Sales Table -->
                <div id="tab-weekly-content" class="tab-content bg-white rounded-lg shadow-md p-4 mb-6 hidden">
                    <h3 class="text-lg font-semibold mb-4">Weekly Sales Summary</h3>
                    <table id="weekly-table" class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-700 bg-gray-100">
                                <th class="px-4 py-2">Week</th>
                                <th class="px-4 py-2">Start Date</th>
                                <th class="px-4 py-2">End Date</th>
                                <th class="px-4 py-2">Transactions</th>
                                <th class="px-4 py-2">Total (₱)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($weekly_rows as $r): ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="px-4 py-2 font-medium">Week of <?php echo htmlspecialchars($r['week_start']); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($r['week_start']); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($r['week_end']); ?></td>
                                    <td class="px-4 py-2"><?php echo intval($r['transaction_count']); ?></td>
                                    <td class="px-4 py-2 font-semibold">₱<?php echo number_format($r['weekly_total'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Yearly Sales Table -->
                <div id="tab-yearly-content" class="tab-content bg-white rounded-lg shadow-md p-4 mb-6 hidden">
                    <h3 class="text-lg font-semibold mb-4">Yearly Sales Summary</h3>
                    <table id="yearly-table" class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-700 bg-gray-100">
                                <th class="px-4 py-2">Year</th>
                                <th class="px-4 py-2">Transactions</th>
                                <th class="px-4 py-2">Total (₱)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($yearly_rows as $r): ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="px-4 py-2 font-medium"><?php echo intval($r['year']); ?></td>
                                    <td class="px-4 py-2"><?php echo intval($r['transaction_count']); ?></td>
                                    <td class="px-4 py-2 font-semibold">₱<?php echo number_format($r['yearly_total'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Product Breakdown Table -->
                <div id="tab-products-content" class="tab-content bg-white rounded-lg shadow-md p-4 mb-6 hidden">
                    <h3 class="text-lg font-semibold mb-4">Product Sales Breakdown (Store Sales)</h3>
                    <table id="products-table" class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-700 bg-gray-100">
                                <th class="px-4 py-2">Product</th>
                                <th class="px-4 py-2">Unit</th>
                                <th class="px-4 py-2">Qty Sold</th>
                                <th class="px-4 py-2">Avg Price (₱)</th>
                                <th class="px-4 py-2">Total Revenue (₱)</th>
                                <th class="px-4 py-2">Times Sold</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($product_rows as $r): ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="px-4 py-2 font-medium"><?php echo htmlspecialchars($r['product_name'] ?? 'Unnamed'); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($r['unit'] ?? '-'); ?></td>
                                    <td class="px-4 py-2"><?php echo intval($r['total_qty']); ?></td>
                                    <td class="px-4 py-2">₱<?php echo number_format($r['avg_price'], 2); ?></td>
                                    <td class="px-4 py-2 font-semibold">₱<?php echo number_format($r['total_revenue'], 2); ?></td>
                                    <td class="px-4 py-2"><?php echo intval($r['times_sold']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <style>
        @media print {
            .absolute, .flex.gap-3, button, .border-b {
                display: none !important;
            }
            .tab-content {
                display: block !important;
                page-break-inside: avoid;
            }
        }
    </style>

    <script>
        function showTab(tabName) {
            // Hide all content
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.tab-btn').forEach(el => {
                el.classList.remove('border-teal-600', 'text-teal-600', 'active');
                el.classList.add('border-transparent', 'text-gray-600');
            });

            // Show selected content
            document.getElementById('tab-' + tabName + '-content').classList.remove('hidden');
            document.getElementById('tab-' + tabName).classList.add('border-teal-600', 'text-teal-600', 'active');
            document.getElementById('tab-' + tabName).classList.remove('border-transparent', 'text-gray-600');
        }

        $(document).ready(function() {
            $('#daily-table').DataTable({
                pageLength: 25,
                order: [[0, 'desc']]
            });
            $('#weekly-table').DataTable({
                pageLength: 25,
                order: [[0, 'desc']]
            });
            $('#yearly-table').DataTable({
                pageLength: 25,
                order: [[0, 'desc']]
            });
            $('#products-table').DataTable({
                pageLength: 25,
                order: [[4, 'desc']]
            });
        });
    </script>
</body>
</html>