<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - Flor de Liz</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check if user is logged in and is staff
    if (empty($_SESSION['is_logged_in']) || $_SESSION['role'] !== 'staff') {
        header('Location: ../login_staff.php');
        exit();
    }

    $conn = require_once __DIR__ . '/../config/database.php';
    $user_id = $_SESSION['user_id'] ?? null;
    $full_name = $_SESSION['full_name'] ?? 'Staff Member';

    // Get current user's employee ID
    $emp_stmt = $conn->prepare("SELECT id FROM employees WHERE user_id = ?");
    $emp_stmt->bind_param('i', $user_id);
    $emp_stmt->execute();
    $emp_result = $emp_stmt->get_result()->fetch_assoc();
    $employee_id = $emp_result['id'] ?? null;
    $emp_stmt->close();

    // ========== TOTAL INVENTORY ITEMS ==========
    $inv_stmt = $conn->prepare("SELECT COUNT(*) AS total_items, SUM(quantity) AS total_units FROM inventory");
    $inv_stmt->execute();
    $inv_data = $inv_stmt->get_result()->fetch_assoc();
    $inv_stmt->close();
    $total_items = $inv_data['total_items'] ?? 0;
    $total_units = $inv_data['total_units'] ?? 0;

    // ========== PENDING ORDERS (Status not completed) ==========
    $order_stmt = $conn->prepare("SELECT COUNT(*) AS pending_count FROM orders WHERE status != 'completed'");
    $order_stmt->execute();
    $order_data = $order_stmt->get_result()->fetch_assoc();
    $order_stmt->close();
    $pending_orders = $order_data['pending_count'] ?? 0;

    // ========== TODAY'S SALES ==========
    $today = date('Y-m-d');
    $sales_stmt = $conn->prepare("SELECT SUM(total_amount) AS today_sales FROM sales WHERE DATE(sale_date) = ?");
    $sales_stmt->bind_param('s', $today);
    $sales_stmt->execute();
    $sales_data = $sales_stmt->get_result()->fetch_assoc();
    $sales_stmt->close();
    $today_sales = $sales_data['today_sales'] ?? 0;

    // ========== CURRENT CLOCK STATUS ==========
    $clock_stmt = $conn->prepare("
        SELECT clock_in, clock_out, hours_worked
        FROM attendance
        WHERE employee_id = ? AND attendance_date = ?
        ORDER BY id DESC LIMIT 1
    ");
    $clock_stmt->bind_param('is', $employee_id, $today);
    $clock_stmt->execute();
    $clock_data = $clock_stmt->get_result()->fetch_assoc();
    $clock_stmt->close();
    
    $clock_status = 'Not Clocked In';
    $clock_time = '--:--';
    if ($clock_data) {
        if (!$clock_data['clock_out']) {
            $clock_status = 'Clocked In';
            $clock_time = date('H:i', strtotime($clock_data['clock_in']));
        } else {
            $clock_status = 'Clocked Out';
            $clock_time = 'Hours: ' . $clock_data['hours_worked'];
        }
    }

    // ========== LOW STOCK ITEMS ==========
    $low_stmt = $conn->prepare("SELECT COUNT(*) AS low_count FROM inventory WHERE quantity < reorder_level");
    $low_stmt->execute();
    $low_data = $low_stmt->get_result()->fetch_assoc();
    $low_stmt->close();
    $low_stock_count = $low_data['low_count'] ?? 0;

    $conn->close();
    
    // Include sidebar navigation
    require_once __DIR__ . '/../includes/sidebar_navigation.php';
    ?>

    <!-- Main Content Area -->
   <div class="absolute w-full -ml-2  top-12">
            <div id="main-content" class="flex-1 px-4 sm:px-6 lg:px-8 py-12 ">
        <div class="max-w-6xl mx-auto">
            <div class="mb-8">
                <h2 class="text-4xl font-bold text-gray-900">Staff Dashboard</h2>
                <p class="text-gray-600 mt-2">Manage inventory, orders, and daily operations</p>
            </div>

    <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-xl shadow-md hover:shadow-lg transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-semibold">Total Inventory</p>
                        <p class="text-3xl font-bold text-teal-600 mt-2"><?php echo intval($total_items); ?></p>
                        <p class="text-xs text-gray-500 mt-1"><?php echo intval($total_units); ?> units</p>
                    </div>
                    <div class="w-12 h-12 bg-teal-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-md hover:shadow-lg transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-semibold">Pending Orders</p>
                        <p class="text-3xl font-bold text-teal-600 mt-2"><?php echo intval($pending_orders); ?></p>
                        <p class="text-xs text-gray-500 mt-1">Awaiting processing</p>
                    </div>
                    <div class="w-12 h-12 bg-teal-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-md hover:shadow-lg transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-semibold">Today's Sales</p>
                        <p class="text-3xl font-bold text-teal-600 mt-2">₱<?php echo number_format($today_sales, 2); ?></p>
                        <p class="text-xs text-gray-500 mt-1"><?php echo date('M d, Y'); ?></p>
                    </div>
                    <div class="w-12 h-12 bg-teal-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-md hover:shadow-lg transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-semibold">Clock Status</p>
                        <p class="text-3xl font-bold text-teal-600 mt-2"><?php echo htmlspecialchars($clock_time); ?></p>
                        <p class="text-xs <?php echo strpos($clock_status, 'In') !== false ? 'text-green-600' : 'text-gray-500'; ?> mt-1"><?php echo htmlspecialchars($clock_status); ?></p>
                    </div>
                    <div class="w-12 h-12 bg-teal-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Features Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <a href="/staff/inventory.php" class="bg-white p-8 rounded-xl shadow-md hover:shadow-lg transition cursor-pointer">
                <div class="w-12 h-12 bg-teal-100 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Inventory</h3>
                <p class="text-gray-600">Manage products and track stock levels.</p>
            </a>

            <a href="/staff/orders.php" class="bg-white p-8 rounded-xl shadow-md hover:shadow-lg transition cursor-pointer">
                <div class="w-12 h-12 bg-teal-100 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Orders</h3>
                <p class="text-gray-600">View and manage customer orders.</p>
            </a>

            <a href="/staff/payments.php" class="bg-white p-8 rounded-xl shadow-md hover:shadow-lg transition cursor-pointer">
                <div class="w-12 h-12 bg-teal-100 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Payments</h3>
                <p class="text-gray-600">Record and track customer payments.</p>
            </a>

            <a href="/staff/clock.php" class="bg-white p-8 rounded-xl shadow-md hover:shadow-lg transition cursor-pointer">
                <div class="w-12 h-12 bg-teal-100 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Clock In/Out</h3>
                <p class="text-gray-600">Record your attendance and work hours.</p>
            </a>

            <div class="bg-white p-8 rounded-xl shadow-md hover:shadow-lg transition">
                <div class="w-12 h-12 bg-teal-100 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Notifications</h3>
                <p class="text-gray-600">View system notifications and alerts.</p>
            </div>

            <div class="bg-white p-8 rounded-xl shadow-md hover:shadow-lg transition">
                <div class="w-12 h-12 bg-teal-100 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Reports</h3>
                <p class="text-gray-600">View sales and activity reports.</p>
            </div>
        </div>
        </div>
    </div>
   </div>
</body>
</html>
