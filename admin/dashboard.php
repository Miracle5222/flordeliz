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
    
    // Include sidebar navigation
    require_once __DIR__ . '/../includes/sidebar_navigation.php';
    ?>

    <!-- Main Content Area -->
    <div class="flex-1 px-4 sm:px-6 lg:px-8 py-12">
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
                        <p class="text-3xl font-bold text-amber-600 mt-2">₱--</p>
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
                        <p class="text-3xl font-bold text-amber-600 mt-2">--</p>
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
                        <p class="text-3xl font-bold text-amber-600 mt-2">--</p>
                    </div>
                    <div class="w-12 h-12 bg-amber-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-md hover:shadow-lg transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-semibold">Low Stock Items</p>
                        <p class="text-3xl font-bold text-amber-600 mt-2">--</p>
                    </div>
                    <div class="w-12 h-12 bg-amber-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4v2m0 5v2m0-18v2m0-4v2m0-4v2"></path></svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Features Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
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
</div>
</body>
</html>
