<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flor de Liz - Printing Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-slate-50 to-slate-100">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center gap-2">
                    <div class="w-10 h-10 bg-gradient-to-br from-teal-600 to-teal-800 rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold text-lg">FL</span>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900">Flor de Liz</h1>
                </div>
                <div class="flex gap-4">
                    <a href="/pages/login_staff.php" class="px-4 py-2 text-teal-600 hover:bg-teal-50 rounded-lg transition">Staff Login</a>
                    <a href="/pages/login_admin.php" class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition">Admin Login</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
        <div class="text-center mb-16">
            <h2 class="text-5xl sm:text-6xl font-bold text-gray-900 mb-6">Printing Shop Management Made Simple</h2>
            <p class="text-xl text-gray-600 mb-8 max-w-2xl mx-auto">Flor de Liz is your all-in-one solution for managing inventory, orders, payments, and employee payroll with ease.</p>
            <div class="flex gap-4 justify-center">
                <a href="/pages/login_staff.php" class="px-8 py-3 bg-teal-600 text-white rounded-lg hover:bg-teal-700 font-semibold transition shadow-lg">Login as Staff</a>
                <a href="/pages/login_admin.php" class="px-8 py-3 border-2 border-teal-600 text-teal-600 rounded-lg hover:bg-teal-50 font-semibold transition">Admin Access</a>
            </div>
        </div>

        <!-- Features Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mt-20">
            <!-- Staff Features -->
            <div class="bg-white p-8 rounded-xl shadow-md hover:shadow-lg transition">
                <div class="w-12 h-12 bg-teal-100 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Inventory Management</h3>
                <p class="text-gray-600">Add, update, and monitor your printing materials in real-time.</p>
            </div>

            <div class="bg-white p-8 rounded-xl shadow-md hover:shadow-lg transition">
                <div class="w-12 h-12 bg-teal-100 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Order Management</h3>
                <p class="text-gray-600">Track orders, manage client requests, and handle deliveries.</p>
            </div>

            <div class="bg-white p-8 rounded-xl shadow-md hover:shadow-lg transition">
                <div class="w-12 h-12 bg-teal-100 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Payment Processing</h3>
                <p class="text-gray-600">Record payments securely with partial downpayment support.</p>
            </div>

            <div class="bg-white p-8 rounded-xl shadow-md hover:shadow-lg transition">
                <div class="w-12 h-12 bg-teal-100 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Clock In/Out</h3>
                <p class="text-gray-600">Automated attendance tracking and time management.</p>
            </div>

            <div class="bg-white p-8 rounded-xl shadow-md hover:shadow-lg transition">
                <div class="w-12 h-12 bg-teal-100 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Client Notifications</h3>
                <p class="text-gray-600">Send SMS updates and keep clients informed on their orders.</p>
            </div>

            <div class="bg-white p-8 rounded-xl shadow-md hover:shadow-lg transition">
                <div class="w-12 h-12 bg-teal-100 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Admin Reports</h3>
                <p class="text-gray-600">Generate daily, weekly, monthly, and yearly reports.</p>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section class="bg-white py-20 mt-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-4xl font-bold text-gray-900 text-center mb-16">Our Pricing</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <div class="text-center p-6 border-2 border-teal-200 rounded-lg hover:border-teal-600 transition">
                    <p class="text-gray-600 text-sm font-semibold mb-2">Hardbound Books</p>
                    <p class="text-4xl font-bold text-teal-600 mb-2">₱350</p>
                    <p class="text-gray-500">per book</p>
                </div>
                <div class="text-center p-6 border-2 border-teal-200 rounded-lg hover:border-teal-600 transition">
                    <p class="text-gray-600 text-sm font-semibold mb-2">Softbound Books</p>
                    <p class="text-4xl font-bold text-teal-600 mb-2">₱100</p>
                    <p class="text-gray-500">per piece</p>
                </div>
                <div class="text-center p-6 border-2 border-teal-200 rounded-lg hover:border-teal-600 transition">
                    <p class="text-gray-600 text-sm font-semibold mb-2">Receipt (1 dozen)</p>
                    <p class="text-4xl font-bold text-teal-600 mb-2">₱2,000</p>
                    <p class="text-gray-500">per set</p>
                </div>
                <div class="text-center p-6 border-2 border-teal-200 rounded-lg hover:border-teal-600 transition">
                    <p class="text-gray-600 text-sm font-semibold mb-2">Books/Pad (100)</p>
                    <p class="text-4xl font-bold text-teal-600 mb-2">₱4,000</p>
                    <p class="text-gray-500">per package</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="bg-gradient-to-r from-teal-600 to-teal-800 py-16 mt-20">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-4xl font-bold text-white mb-6">Ready to Get Started?</h2>
            <p class="text-xl text-teal-100 mb-8">Choose your portal to access the system.</p>
            <div class="flex gap-4 justify-center">
                <a href="/pages/login_staff.php" class="px-8 py-3 bg-white text-teal-600 rounded-lg hover:bg-gray-50 font-semibold transition">Staff Login</a>
                <a href="/pages/login_admin.php" class="px-8 py-3 border-2 border-white text-white rounded-lg hover:bg-teal-700 font-semibold transition">Admin Login</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-400 py-8 mt-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center">
                <p>&copy; 2024 Flor de Liz Printing Shop. All rights reserved.</p>
                <p class="text-sm">Open every day except Sunday</p>
            </div>
        </div>
    </footer>
</body>
</html>
