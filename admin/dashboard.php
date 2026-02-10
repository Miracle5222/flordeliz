<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Flor de Liz</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check if user is logged in and is admin
    if (empty($_SESSION['is_logged_in']) || $_SESSION['role'] !== 'admin') {
        header('Location: ../login_admin.php');
        exit();
    }

    $db_return = require_once __DIR__ . '/../config/database.php';
    if ($db_return instanceof mysqli) {
        $conn = $db_return;
    } elseif (isset($conn) && $conn instanceof mysqli) {
        // existing connection available
    } else {
        error_log('Database connection error in admin/dashboard.php');
        die('Database connection error');
    }

    // Temporary: enable full error reporting for debugging (remove in production)
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);

    // Detect whether mysqli_stmt::get_result() is available (requires mysqlnd)
    $has_get_result = function_exists('mysqli_stmt_get_result') || method_exists('mysqli_stmt', 'get_result');
    if (!$has_get_result) {
        error_log('mysqli_stmt::get_result not available — mysqlnd likely missing');
        echo '<div style="background:#fee;border:1px solid #f99;padding:12px;margin:12px 0;color:#900;font-weight:600;">Debug: server missing mysqlnd / mysqli_stmt::get_result — some queries may fail. See PHP configuration.</div>';
    }
    
    // ========== TOTAL SALES (ALL TIME) ==========
    $sales_stmt = $conn->prepare("SELECT COALESCE(SUM(total_amount), 0) AS total_sales FROM sales");
    if ($sales_stmt === false) {
        error_log('DB prepare failed (total_sales): ' . $conn->error);
        $total_sales = 0;
    } else {
        $sales_stmt->execute();
        $sales_data = $sales_stmt->get_result()->fetch_assoc();
        $sales_stmt->close();
        $total_sales = $sales_data['total_sales'] ?? 0;
    }

    // ========== TODAY'S SALES ==========
    $today = date('Y-m-d');
    $today_sales_stmt = $conn->prepare("SELECT COALESCE(SUM(total_amount), 0) AS today_sales FROM sales WHERE DATE(sale_date) = ?");
    if ($today_sales_stmt === false) {
        error_log('DB prepare failed (today_sales): ' . $conn->error);
        $today_sales = 0;
    } else {
        $today_sales_stmt->bind_param('s', $today);
        $today_sales_stmt->execute();
        $today_sales_data = $today_sales_stmt->get_result()->fetch_assoc();
        $today_sales_stmt->close();
        $today_sales = $today_sales_data['today_sales'] ?? 0;
    }

    // ========== TOTAL EMPLOYEES ==========
    $emp_stmt = $conn->prepare("SELECT COUNT(*) AS total_emp FROM employees");
    if ($emp_stmt === false) {
        error_log('DB prepare failed (total_emp): ' . $conn->error);
        $total_employees = 0;
    } else {
        $emp_stmt->execute();
        $emp_data = $emp_stmt->get_result()->fetch_assoc();
        $emp_stmt->close();
        $total_employees = $emp_data['total_emp'] ?? 0;
    }
    $active_sql = "SELECT COUNT(DISTINCT employee_id) AS active_count FROM attendance WHERE attendance_date = ? AND clock_out IS NULL";
    $active_emp_stmt = $conn->prepare($active_sql);
    if ($active_emp_stmt === false) {
        error_log('DB prepare failed (active_employees): ' . $conn->error);
        $active_employees = 0;
    } else {
        $active_emp_stmt->bind_param('s', $today);
        $active_emp_stmt->execute();
        $active_data = $active_emp_stmt->get_result()->fetch_assoc();
        $active_emp_stmt->close();
        $active_employees = $active_data['active_count'] ?? 0;
    }

    // ========== PENDING ORDERS ==========
    $pending_stmt = $conn->prepare("SELECT COUNT(*) AS pending_count FROM orders WHERE status IN ('pending', 'processing')");
    if ($pending_stmt === false) {
        error_log('DB prepare failed (pending_orders): ' . $conn->error);
        $pending_orders = 0;
    } else {
        $pending_stmt->execute();
        $pending_data = $pending_stmt->get_result()->fetch_assoc();
        $pending_stmt->close();
        $pending_orders = $pending_data['pending_count'] ?? 0;
    }

    // ========== LOW STOCK ITEMS ==========
    $low_stmt = $conn->prepare("SELECT COUNT(*) AS low_count FROM inventory WHERE quantity < reorder_level");
    if ($low_stmt === false) {
        error_log('DB prepare failed (low_stock): ' . $conn->error);
        $low_stock_count = 0;
    } else {
        $low_stmt->execute();
        $low_data = $low_stmt->get_result()->fetch_assoc();
        $low_stmt->close();
        $low_stock_count = $low_data['low_count'] ?? 0;
    }

    // ========== TOTAL INVENTORY VALUE ==========
    $inv_value_stmt = $conn->prepare("SELECT COALESCE(SUM(quantity * unit_price), 0) AS inv_value FROM inventory");
    if ($inv_value_stmt === false) {
        error_log('DB prepare failed (inventory_value): ' . $conn->error);
        $inventory_value = 0;
    } else {
        $inv_value_stmt->execute();
        $inv_value_data = $inv_value_stmt->get_result()->fetch_assoc();
        $inv_value_stmt->close();
        $inventory_value = $inv_value_data['inv_value'] ?? 0;
    }

    // ========== OVERDUE PAYROLL ==========
    $overdue_payroll_stmt = $conn->prepare("SELECT COUNT(*) AS overdue_count FROM payroll WHERE status = 'draft'");
    if ($overdue_payroll_stmt === false) {
        error_log('DB prepare failed (pending_payroll): ' . $conn->error);
        $pending_payroll = 0;
    } else {
        $overdue_payroll_stmt->execute();
        $overdue_data = $overdue_payroll_stmt->get_result()->fetch_assoc();
        $overdue_payroll_stmt->close();
        $pending_payroll = $overdue_data['overdue_count'] ?? 0;
    }

    // ========== RECENT SALES (LAST 5) ==========
    // `sales` table does not have an `order_number` column; use `s.id` as the sale identifier
    $recent_sql = "SELECT s.id, s.sale_date, s.total_amount, COUNT(si.id) AS item_count FROM sales s LEFT JOIN sale_items si ON s.id = si.sale_id GROUP BY s.id ORDER BY s.sale_date DESC LIMIT 5";
    $recent_sales_stmt = $conn->prepare($recent_sql);
    if ($recent_sales_stmt === false) {
        error_log('DB prepare failed (recent_sales): ' . $conn->error);
        $recent_sales = [];
    } else {
        $recent_sales_stmt->execute();
        $recent_sales = $recent_sales_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $recent_sales_stmt->close();
    }

    $conn->close();
    
    // Include sidebar navigation
    require_once __DIR__ . '/../includes/sidebar_navigation.php';
    ?>

    <div class="absolute w-full -ml-2 top-12">
        <div id="main-content" class="flex-1 px-4 sm:px-6 lg:px-8 py-12">
            <div class="max-w-6xl mx-auto">
                <div class="mb-8">
                    <h2 class="text-4xl font-bold text-gray-900">Admin Dashboard</h2>
                    <p class="text-gray-600 mt-2">Manage reports, payroll, and shop operations</p>
                </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-xl shadow-md hover:shadow-lg transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-semibold">Total Sales</p>
                        <p class="text-3xl font-bold text-amber-600 mt-2">₱<?php echo number_format($total_sales, 0); ?></p>
                        <p class="text-xs text-gray-500 mt-1">Today: ₱<?php echo number_format($today_sales, 0); ?></p>
                    </div>
                    <div class="w-12 h-12 bg-amber-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-md hover:shadow-lg transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-semibold">Total Employees</p>
                        <p class="text-3xl font-bold text-amber-600 mt-2"><?php echo intval($total_employees); ?></p>
                        <p class="text-xs text-green-600 mt-1"><?php echo intval($active_employees); ?> clocked in today</p>
                    </div>
                    <div class="w-12 h-12 bg-amber-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.856-1.487M15 10a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-md hover:shadow-lg transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-semibold">Pending Orders</p>
                        <p class="text-3xl font-bold text-amber-600 mt-2"><?php echo intval($pending_orders); ?></p>
                        <p class="text-xs text-gray-500 mt-1">Processing or pending</p>
                    </div>
                    <div class="w-12 h-12 bg-amber-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-md hover:shadow-lg transition <?php echo $low_stock_count > 0 ? 'border-l-4 border-red-500' : ''; ?>">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-semibold">Low Stock Items</p>
                        <p class="text-3xl font-bold <?php echo $low_stock_count > 0 ? 'text-red-600' : 'text-amber-600'; ?> mt-2"><?php echo intval($low_stock_count); ?></p>
                        <p class="text-xs text-gray-500 mt-1">Below reorder level</p>
                    </div>
                    <div class="w-12 h-12 bg-amber-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4v2m0 5v2m0-18v2m0-4v2m0-4v2"></path></svg>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-md hover:shadow-lg transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-semibold">Inventory Value</p>
                        <p class="text-3xl font-bold text-amber-600 mt-2">₱<?php echo number_format($inventory_value, 0); ?></p>
                        <p class="text-xs text-gray-500 mt-1">Total stock value</p>
                    </div>
                    <div class="w-12 h-12 bg-amber-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-md hover:shadow-lg transition <?php echo $pending_payroll > 0 ? 'border-l-4 border-yellow-500' : ''; ?>">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-semibold">Pending Payroll</p>
                        <p class="text-3xl font-bold <?php echo $pending_payroll > 0 ? 'text-yellow-600' : 'text-amber-600'; ?> mt-2"><?php echo intval($pending_payroll); ?></p>
                        <p class="text-xs text-gray-500 mt-1">Draft payrolls</p>
                    </div>
                    <div class="w-12 h-12 bg-amber-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Features Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Recent Sales Activity -->
                    <div class="col-span-full bg-white p-6 rounded-xl shadow-md mb-8">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-bold text-gray-900">Recent Sales Activity</h3>
                            <a href="<?php echo htmlspecialchars($app_root); ?>/admin/sales_reports.php" class="text-amber-600 hover:text-amber-700 font-semibold text-sm">View All →</a>
                        </div>

                        <?php if (!empty($recent_sales)): ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Order ID</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Date</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Items</th>
                                        <th class="px-4 py-3 text-right font-semibold text-gray-700">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_sales as $sale): ?>
                                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                                        <td class="px-4 py-3 text-gray-900 font-medium">#<?php echo htmlspecialchars($sale['id']); ?></td>
                                        <td class="px-4 py-3 text-gray-600"><?php echo date('M d, Y', strtotime($sale['sale_date'])); ?></td>
                                        <td class="px-4 py-3 text-gray-600"><?php echo intval($sale['item_count']); ?> item<?php echo $sale['item_count'] != 1 ? 's' : ''; ?></td>
                                        <td class="px-4 py-3 text-right text-gray-900 font-semibold">₱<?php echo number_format($sale['total_amount'], 0); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <p class="text-gray-500 text-center py-8">No sales recorded yet</p>
                        <?php endif; ?>
                    </div>
        
                    <!-- Features Grid -->
            <a href="/admin/reports.php" class="bg-white p-8 rounded-xl shadow-md hover:shadow-lg transition cursor-pointer">
                <div class="w-12 h-12 bg-amber-100 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Sales Reports</h3>
                <p class="text-gray-600">View daily, weekly, monthly, and yearly sales reports.</p>
            </a>

            <a href="/admin/attendance.php" class="bg-white p-8 rounded-xl shadow-md hover:shadow-lg transition cursor-pointer">
                <div class="w-12 h-12 bg-amber-100 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Attendance</h3>
                <p class="text-gray-600">Track employee attendance and clock-in records.</p>
            </a>

            <a href="/admin/payroll.php" class="bg-white p-8 rounded-xl shadow-md hover:shadow-lg transition cursor-pointer">
                <div class="w-12 h-12 bg-amber-100 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Payroll</h3>
                <p class="text-gray-600">Manage employee salaries and compute payroll.</p>
            </a>

            <div class="bg-white p-8 rounded-xl shadow-md hover:shadow-lg transition">
                <div class="w-12 h-12 bg-amber-100 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Inventory Reports</h3>
                <p class="text-gray-600">Monitor stock levels and inventory analytics.</p>
            </div>

            <div class="bg-white p-8 rounded-xl shadow-md hover:shadow-lg transition">
                <div class="w-12 h-12 bg-amber-100 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Settings</h3>
                <p class="text-gray-600">Configure system settings and preferences.</p>
            </div>

            <div class="bg-white p-8 rounded-xl shadow-md hover:shadow-lg transition">
                <div class="w-12 h-12 bg-amber-100 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Support</h3>
                <p class="text-gray-600">Contact support or view system documentation.</p>
            </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
