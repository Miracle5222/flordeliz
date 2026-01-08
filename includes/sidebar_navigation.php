<?php
/**
 * Sidebar Navigation Component
 * Displays different navigation links based on user role
 */

// Determine the app root based on the includes directory location
// This file is at: document_root/flordeliz/includes/sidebar_navigation.php
// We want the app root: /flordeliz/
$dir = str_replace('\\', '/', dirname(__DIR__));
$doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$app_root = '/' . trim(str_replace($doc_root, '', $dir), '/') . '/';



if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$role = $_SESSION['role'] ?? 'guest';
$full_name = $_SESSION['full_name'] ?? 'User';
$is_staff = ($role === 'staff');
$is_admin = ($role === 'admin');
?>

<style>
    /* Prevent horizontal scrolling caused by positioned sidebar/toggles */
    html, body { overflow-x: hidden; }

    /* Shift main content when sidebar collapses (desktop only) */
    @media (min-width: 768px) {
        #main-content { margin-left: 16rem; transition: margin-left .25s ease; }
        body.sidebar-collapsed #main-content { margin-left: 5rem; }
    }
</style>

<!-- Navigation Bar -->
<nav class="bg-white border-b-4 <?php echo $is_staff ? 'border-teal-800' : 'border-amber-800'; ?> shadow-sm sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <!-- Logo Section with Mobile Menu Toggle -->
            <div class="flex items-center gap-2">
                <!-- Mobile Menu Button -->
                <button id="mobile-menu-toggle" class="md:hidden p-2 rounded-lg hover:bg-gray-100 transition" onclick="toggleSidebar()">
                    <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                <div class="w-10 h-10 bg-gradient-to-br <?php echo $is_staff ? 'from-teal-600 to-teal-800' : 'from-amber-600 to-amber-800'; ?> rounded-lg flex items-center justify-center">
                    <span class="text-white font-bold text-lg"><?php echo $is_staff ? 'FL' : 'AD'; ?></span>
                </div>
                <h1 class="text-xl font-bold text-gray-900">Flor de Liz</h1>
            </div>

            <!-- Right Section: User Info & Profile & Logout -->
            <div class="flex gap-4 items-center">
                <div class="flex items-center gap-2 cursor-pointer hover:opacity-75" onclick="window.location.href='<?php echo $is_staff ? '../staff/profile.php' : '../admin/profile.php'; ?>'">
                    <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <div class="hidden sm:block">
                        <p class="text-gray-700"><strong><?php echo htmlspecialchars($full_name); ?></strong></p>
                        <p class="text-gray-500 text-xs capitalize"><?php echo htmlspecialchars($role); ?></p>
                    </div>
                </div>
                <a href="<?php echo $is_staff ? '../staff/profile.php' : '../admin/profile.php'; ?>" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm">
                    Profile
                </a>
                <a href="<?php echo $app_root; ?>logout.php" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition text-sm">
                    Logout
                </a>
            </div>  
        </div>
    </div>
</nav>

<!-- Sidebar Navigation -->
<div class="flex">
    <!-- Sidebar -->
    <aside id="sidebar" class="w-64 <?php echo $is_staff ? 'text-teal-900' : 'text-amber-900'; ?> min-h-screen p-6 fixed left-0 top-16 bottom-0 transform -translate-x-full transition-all duration-300 ease-in-out md:relative md:translate-x-0 md:top-0 z-40 md:min-h-[calc(100vh-4rem)] overflow-y-auto">
        <!-- Collapse Toggle Button (Desktop Only) -->
        <button id="collapse-toggle" onclick="toggleCollapseSidebar()" class="hidden md:flex absolute top-4 right-6 w-6 h-6 bg-white text-gray-800 rounded-full shadow-lg hover:bg-gray-100 transition items-center justify-center z-50">
            <svg id="collapse-icon" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
        </button>

        <nav class="space-y-2 pt-4">
            <?php if ($is_staff): ?>
                <!-- Staff Navigation -->
                <a href="<?php echo $app_root; ?>staff/dashboard.php" class="sidebar-link flex items-center px-4 py-2 rounded-lg hover:<?php echo $is_staff ? 'text-teal-800' : 'text-amber-800'; ?> transition" title="Dashboard">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-3m0 0l7-4 7 4M5 9v10a1 1 0 001 1h12a1 1 0 001-1V9m-9 11l4-2m0 0l4 2m-4-2v2m-6-4h.01M7 20h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v13a2 2 0 002 2z"></path>
                    </svg>
                    <span class="sidebar-label ml-2 transition-opacity duration-300">Dashboard</span>
                </a>
                
                <div class="my-4 border-t border-teal-700 sidebar-divider"></div>
                
                <p class="sidebar-label px-4 py-2 text-xs font-semibold text-teal-700  uppercase transition-opacity duration-300">Operations</p>
                
                <a href="<?php echo $app_root; ?>staff/inventory.php" class="sidebar-link flex items-center px-4 py-2 rounded-lg hover:text-teal-800 transition" title="Inventory">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                    <span class="sidebar-label ml-2 transition-opacity duration-300">Inventory</span>
                </a>
                
                <!-- Orders Dropdown -->
                <button type="button" onclick="toggleOrdersDropdown()" class="orders-toggle w-full sidebar-link flex items-center px-4 py-2 rounded-lg hover:text-teal-800 transition" title="Orders" style="border:none;background:transparent;text-align:left;">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                    <span class="sidebar-label ml-2 transition-opacity duration-300">Orders</span>
                    <svg id="orders-dropdown-icon" class="w-4 h-4 ml-auto flex-shrink-0 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                    </svg>
                </button>
                <div id="orders-submenu" class="hidden space-y-1 ml-2">
                    <a href="<?php echo $app_root; ?>staff/orders.php" class="sidebar-link flex items-center px-4 py-2 rounded-lg hover:text-teal-700 transition text-sm" title="View Orders">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="sidebar-label ml-2 transition-opacity duration-300">View Orders</span>
                    </a>
                    <a href="<?php echo $app_root; ?>staff/orders/create.php" class="sidebar-link flex items-center px-4 py-2 rounded-lg hover:text-teal-700 transition text-sm" title="Create Order">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span class="sidebar-label ml-2 transition-opacity duration-300">Create Order</span>
                    </a>
                </div>
                <!-- Sales Dropdown -->
                <button type="button" onclick="toggleSalesDropdown()" class="sales-toggle w-full sidebar-link flex items-center px-4 py-2 rounded-lg hover:text-teal-800 transition" title="Sales" style="border:none;background:transparent;text-align:left;">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 11V3m0 8l-4-4m4 4l4-4M3 21h18M3 7h18v14H3V7z"></path>
                    </svg>
                    <span class="sidebar-label ml-2 transition-opacity duration-300">Sales</span>
                    <svg id="sales-dropdown-icon" class="w-4 h-4 ml-auto flex-shrink-0 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                    </svg>
                </button>
                <div id="sales-submenu" class="hidden space-y-1 ml-2">
                    <a href="<?php echo $app_root; ?>staff/sales.php" class="sidebar-link flex items-center px-4 py-2 rounded-lg hover:text-teal-700 transition text-sm" title="View Sales">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17a4 4 0 100-8 4 4 0 000 8zm8-4h-1.5M6.5 13H5"></path>
                        </svg>
                        <span class="sidebar-label ml-2 transition-opacity duration-300">View Sales</span>
                    </a>
                    <a href="<?php echo $app_root; ?>staff/sales/create.php" class="sidebar-link flex items-center px-4 py-2 rounded-lg hover:text-teal-700 transition text-sm" title="Create Sale">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span class="sidebar-label ml-2 transition-opacity duration-300">Create Sale</span>
                    </a>
                </div>
                
                <a href="<?php echo $app_root; ?>staff/payments.php" class="sidebar-link flex items-center px-4 py-2 rounded-lg hover:text-teal-800 transition" title="Payments">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="sidebar-label ml-2 transition-opacity duration-300">Payments</span>
                </a>
                
                <a href="<?php echo $app_root; ?>staff/clock.php" class="sidebar-link flex items-center px-4 py-2 rounded-lg hover:text-teal-800 transition" title="Clock In/Out">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="sidebar-label ml-2 transition-opacity duration-300">Clock In/Out</span>
                </a>

            <?php elseif ($is_admin): ?>
                <!-- Admin Navigation -->
                <a href="<?php echo $app_root; ?>admin/dashboard.php" class="sidebar-link flex items-center px-4 py-2 rounded-lg hover:text-amber-800 transition" title="Dashboard">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-3m0 0l7-4 7 4M5 9v10a1 1 0 001 1h12a1 1 0 001-1V9m-9 11l4-2m0 0l4 2m-4-2v2m-6-4h.01M7 20h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v13a2 2 0 002 2z"></path>
                    </svg>
                    <span class="sidebar-label ml-2 transition-opacity duration-300">Dashboard</span>
                </a>
                
                <div class="my-4 border-t border-amber-700 sidebar-divider"></div>
                
                <p class="sidebar-label px-4 py-2 text-xs font-semibold text-amber-700 uppercase transition-opacity duration-300">Management</p>
                
                <a href="<?php echo $app_root; ?>admin/sales_reports.php" class="sidebar-link flex items-center px-4 py-2 rounded-lg hover:text-amber-800 transition" title="Sales Reports">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3v18h18M7 12l3-3 3 3 5-5"></path>
                    </svg>
                    <span class="sidebar-label ml-2 transition-opacity duration-300">Sales Reports</span>
                </a>
                
                <button type="button" onclick="toggleAttendanceDropdown()" class="attendance-toggle w-full sidebar-link flex items-center px-4 py-2 rounded-lg hover:text-amber-800 transition" title="Attendance" style="border:none;background:transparent;text-align:left;">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="sidebar-label ml-2 transition-opacity duration-300">Attendance</span>
                    <svg id="attendance-dropdown-icon" class="w-4 h-4 ml-auto transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div id="attendance-submenu" class="hidden pl-8 mt-1">
                    <a href="<?php echo $app_root; ?>admin/attendance.php" class="block px-4 py-2 text-sm text-gray-700 hover:text-amber-800">View All Attendance</a>
                    <a href="<?php echo $app_root; ?>admin/attendance/view.php" class="block px-4 py-2 text-sm text-gray-700 hover:text-amber-800">Employee Attendance</a>
                </div>
                
                <button type="button" onclick="togglePayrollDropdown()" class="payroll-toggle w-full sidebar-link flex items-center px-4 py-2 rounded-lg hover:text-amber-800 transition" title="Payroll" style="border:none;background:transparent;text-align:left;">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="sidebar-label ml-2 transition-opacity duration-300">Payroll</span>
                    <svg id="payroll-dropdown-icon" class="w-4 h-4 ml-auto transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div id="payroll-submenu" class="hidden pl-8 mt-1">
                    <a href="<?php echo $app_root; ?>admin/payroll.php" class="block px-4 py-2 text-sm text-gray-700 hover:text-amber-800">View Payroll</a>
                    <a href="<?php echo $app_root; ?>admin/payroll/create.php" class="block px-4 py-2 text-sm text-gray-700 hover:text-amber-800">Create Payroll</a>
                </div>
                
                <a href="<?php echo $app_root; ?>admin/inventory_reports.php" class="sidebar-link flex items-center px-4 py-2 rounded-lg hover:text-amber-800 transition" title="Inventory Reports">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="sidebar-label ml-2 transition-opacity duration-300">Inventory Reports</span>
                </a>

                <div class="my-4 border-t border-amber-700 sidebar-divider"></div>

                <p class="sidebar-label px-4 py-2 text-xs font-semibold text-amber-700 uppercase transition-opacity duration-300">Staff</p>

                <!-- Staff Management Dropdown -->
                <button type="button" onclick="toggleStaffDropdown()" class="staff-toggle w-full sidebar-link flex items-center px-4 py-2 rounded-lg hover:text-amber-800 transition" title="Staff Management" style="border:none;background:transparent;text-align:left;">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 12H9m6 0a6 6 0 11-12 0 6 6 0 0112 0z"></path>
                    </svg>
                    <span class="sidebar-label ml-2 transition-opacity duration-300">Staff</span>
                    <svg id="staff-dropdown-icon" class="w-4 h-4 ml-auto flex-shrink-0 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                    </svg>
                </button>
                <div id="staff-submenu" class="hidden space-y-1 ml-2">
                    <a href="<?php echo $app_root; ?>admin/staff/add.php" class="sidebar-link flex items-center px-4 py-2 rounded-lg hover:text-amber-700 transition text-sm" title="Add Staff">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span class="sidebar-label ml-2 transition-opacity duration-300">Add Staff</span>
                    </a>
                    <a href="<?php echo $app_root; ?>admin/staff/view.php" class="sidebar-link flex items-center px-4 py-2 rounded-lg hover:text-amber-700 transition text-sm" title="View Staff">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="sidebar-label ml-2 transition-opacity duration-300">View Staff</span>
                    </a>
                </div>
            <?php endif; ?>
        </nav>

        <!-- Mobile Close Button -->
        <button onclick="closeSidebar()" class="md:hidden mt-8 w-full p-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition flex items-center justify-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
            Close Menu
        </button>
    </aside>

    <!-- Mobile Overlay (closes sidebar when clicked) -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-black opacity-50 hidden md:hidden z-30 top-16" onclick="closeSidebar()"></div>
</div>

<!-- JavaScript for Sidebar Toggle -->
<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        sidebar.classList.toggle('-translate-x-full');
        overlay.classList.toggle('hidden');
    }

    function closeSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
    }

    function toggleCollapseSidebar() {
        const sidebar = document.getElementById('sidebar');
        const labels = document.querySelectorAll('.sidebar-label');
        const links = document.querySelectorAll('.sidebar-link');
        const dividers = document.querySelectorAll('.sidebar-divider');
        const collapseIcon = document.getElementById('collapse-icon');
        
        // Toggle collapsed state
        sidebar.classList.toggle('w-64');
        sidebar.classList.toggle('w-20');
        sidebar.classList.toggle('px-6');
        sidebar.classList.toggle('px-3');
        sidebar.classList.toggle('collapsed');
        // Toggle body class so pages can shift their content
        document.body.classList.toggle('sidebar-collapsed');
        
        // Toggle label visibility
        labels.forEach(label => {
            label.classList.toggle('opacity-0');
            label.classList.toggle('hidden');
        });
        
        // Rotate collapse icon
        collapseIcon.classList.toggle('rotate-180');
        
        // Center align icons when collapsed
        links.forEach(link => {
            if (sidebar.classList.contains('collapsed')) {
                link.classList.remove('justify-start');
                link.classList.add('justify-center');
            } else {
                link.classList.add('justify-start');
                link.classList.remove('justify-center');
            }
        });
        
        // Hide dividers when collapsed
        dividers.forEach(divider => {
            divider.classList.toggle('hidden');
        });
        
        // Store preference
        localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
    }

    // Restore sidebar state on page load
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        
        if (sidebarCollapsed && window.innerWidth >= 768) {
            // Manually trigger collapse
            const labels = document.querySelectorAll('.sidebar-label');
            const links = document.querySelectorAll('.sidebar-link');
            const dividers = document.querySelectorAll('.sidebar-divider');
            const collapseIcon = document.getElementById('collapse-icon');
            
            sidebar.classList.add('w-20', 'px-3', 'collapsed');
            sidebar.classList.remove('w-64', 'px-6');
            // apply body class so main content shifts on load
            document.body.classList.add('sidebar-collapsed');
            labels.forEach(label => label.classList.add('opacity-0', 'hidden'));
            collapseIcon.classList.add('rotate-180');
            links.forEach(link => {
                link.classList.add('justify-center');
                link.classList.remove('justify-start');
            });
            dividers.forEach(divider => divider.classList.add('hidden'));
        }
        
        const sidebarLinks = document.querySelectorAll('#sidebar a');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth < 768) {
                    closeSidebar();
                }
            });
        });
    });

    function toggleOrdersDropdown() {
        const submenu = document.getElementById('orders-submenu');
        const icon = document.getElementById('orders-dropdown-icon');
        submenu.classList.toggle('hidden');
        icon.classList.toggle('rotate-180');
    }
    function toggleSalesDropdown() {
        const submenu = document.getElementById('sales-submenu');
        const icon = document.getElementById('sales-dropdown-icon');
        submenu.classList.toggle('hidden');
        icon.classList.toggle('rotate-180');
    }

    function toggleStaffDropdown() {
        const submenu = document.getElementById('staff-submenu');
        const icon = document.getElementById('staff-dropdown-icon');
        submenu.classList.toggle('hidden');
        icon.classList.toggle('rotate-180');
    }
    function toggleAttendanceDropdown() {
        const submenu = document.getElementById('attendance-submenu');
        const icon = document.getElementById('attendance-dropdown-icon');
        if (!submenu) return;
        submenu.classList.toggle('hidden');
        if (icon) icon.classList.toggle('rotate-180');
    }
    function togglePayrollDropdown() {
        const submenu = document.getElementById('payroll-submenu');
        const icon = document.getElementById('payroll-dropdown-icon');
        submenu.classList.toggle('hidden');
        icon.classList.toggle('rotate-180');
    }
</script>
