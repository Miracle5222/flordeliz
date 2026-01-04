<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is staff
if (empty($_SESSION['is_logged_in']) || $_SESSION['role'] !== 'staff') {
    header('Location: ../login_staff.php');
    exit();
}

// Get database connection
$conn = require_once __DIR__ . '/../config/database.php';

$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$limit = 20;
$page = max(1, $_GET['page'] ?? 1);
$offset = ($page - 1) * $limit;

// Build query
$query = 'SELECT o.*, c.name as customer_name, c.category as customer_category 
          FROM orders o 
          LEFT JOIN customers c ON o.customer_id = c.id 
          WHERE 1=1';
$count_query = 'SELECT COUNT(*) as total FROM orders o LEFT JOIN customers c ON o.customer_id = c.id WHERE 1=1';
$params = [];
$types = '';

if ($search) {
    $query .= ' AND (o.order_number LIKE ? OR c.name LIKE ?)';
    $count_query .= ' AND (o.order_number LIKE ? OR c.name LIKE ?)';
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

if ($status_filter) {
    $query .= ' AND o.status = ?';
    $count_query .= ' AND o.status = ?';
    $params[] = $status_filter;
    $types .= 's';
}

// Get total count
$stmt = $conn->prepare($count_query);
if ($types && count($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'];
$pages = ceil($total / $limit);
$stmt->close();

// Get orders
$query .= ' ORDER BY o.order_date DESC LIMIT ? OFFSET ?';
$stmt = $conn->prepare($query);
$limit_val = $limit;
$offset_val = $offset;
$types .= 'ii';
$params[] = $limit_val;
$params[] = $offset_val;
$stmt->bind_param($types, ...$params);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get status counts
$status_counts = [];
$status_query = 'SELECT status, COUNT(*) as count FROM orders GROUP BY status';
$status_result = $conn->query($status_query);
while ($row = $status_result->fetch_assoc()) {
    $status_counts[$row['status']] = $row['count'];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - Flor de Liz</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <?php require_once __DIR__ . '/../includes/sidebar_navigation.php'; ?>

    <div class="absolute w-full -ml-2 top-12">
        <div id="main-content" class="flex-1 px-4 sm:px-6 lg:px-8 py-12">
            <div class="max-w-7xl mx-auto">
                <div class="flex justify-between items-center mb-8">
                    <div>
                        <h2 class="text-4xl font-bold text-gray-900">Orders</h2>
                        <p class="text-gray-600 mt-2">Manage customer orders and deliveries</p>
                    </div>
                    <a href="orders/create.php" class="px-6 py-3 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition">
                        + New Order
                    </a>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                    <div class="bg-white p-6 rounded-xl shadow-md">
                        <p class="text-gray-600 text-sm font-semibold">Total Orders</p>
                        <p class="text-3xl font-bold text-teal-600 mt-2"><?php echo $total; ?></p>
                    </div>
                    <div class="bg-white p-6 rounded-xl shadow-md">
                        <p class="text-gray-600 text-sm font-semibold">Pending</p>
                        <p class="text-3xl font-bold text-yellow-600 mt-2"><?php echo $status_counts['pending'] ?? 0; ?></p>
                    </div>
                    <div class="bg-white p-6 rounded-xl shadow-md">
                        <p class="text-gray-600 text-sm font-semibold">In Progress</p>
                        <p class="text-3xl font-bold text-blue-600 mt-2"><?php echo $status_counts['in_progress'] ?? 0; ?></p>
                    </div>
                    <div class="bg-white p-6 rounded-xl shadow-md">
                        <p class="text-gray-600 text-sm font-semibold">Completed</p>
                        <p class="text-3xl font-bold text-green-600 mt-2"><?php echo $status_counts['completed'] ?? 0; ?></p>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white rounded-xl shadow-md p-6 mb-8">
                    <form method="GET" class="flex gap-4 flex-wrap">
                        <input type="text" name="search" placeholder="Search order # or customer..." value="<?php echo htmlspecialchars($search); ?>" class="flex-1 min-w-64 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500">
                        <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                        <button type="submit" class="px-6 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition">Filter</button>
                    </form>
                </div>

                <!-- Orders Table -->
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-100 border-b-2 border-gray-200">
                                <tr>
                                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Order #</th>
                                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Customer</th>
                                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Category</th>
                                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Amount</th>
                                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Delivery Date</th>
                                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Status</th>
                                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr class="border-b border-gray-200 hover:bg-gray-50 transition">
                                        <td class="px-6 py-4 font-semibold text-gray-900"><?php echo htmlspecialchars($order['order_number']); ?></td>
                                        <td class="px-6 py-4 text-gray-700"><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                        <td class="px-6 py-4 text-gray-700"><?php echo htmlspecialchars($order['customer_category'] ?? '--'); ?></td>
                                        <td class="px-6 py-4 font-semibold text-gray-900">₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td class="px-6 py-4 text-gray-700"><?php echo $order['delivery_date'] ? date('M d, Y', strtotime($order['delivery_date'])) : '--'; ?></td>
                                        <td class="px-6 py-4">
                                            <?php 
                                                $status_colors = [
                                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                                    'in_progress' => 'bg-blue-100 text-blue-800',
                                                    'completed' => 'bg-green-100 text-green-800',
                                                    'cancelled' => 'bg-red-100 text-red-800'
                                                ];
                                                $color = $status_colors[$order['status']] ?? 'bg-gray-100 text-gray-800';
                                            ?>
                                            <span class="px-3 py-1 rounded-full text-sm font-semibold <?php echo $color; ?>">
                                                <?php echo ucwords(str_replace('_', ' ', $order['status'])); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <button onclick="viewOrder(<?php echo $order['id']; ?>)" class="text-teal-600 hover:text-teal-800 font-semibold text-sm">View</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php if (empty($orders)): ?>
                            <div class="text-center py-12">
                                <p class="text-gray-600">No orders found.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Pagination -->
                <?php if ($pages > 1): ?>
                    <div class="flex justify-center gap-2 mt-8">
                        <?php for ($i = 1; $i <= $pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?><?php echo $status_filter ? '&status='.urlencode($status_filter) : ''; ?>" 
                               class="px-4 py-2 rounded-lg <?php echo $i === $page ? 'bg-teal-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function viewOrder(orderId) {
            window.location.href = `orders/view.php?id=${orderId}`;
        }
    </script>
</body>
</html>
