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

$category_filter = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$query = 'SELECT * FROM inventory WHERE 1=1';
$count_query = 'SELECT COUNT(*) as total FROM inventory WHERE 1=1';
$params = [];
$types = '';

if ($search) {
    $query .= ' AND product_name LIKE ?';
    $count_query .= ' AND product_name LIKE ?';
    $search_param = "%$search%";
    $params[] = $search_param;
    $types .= 's';
}

if ($category_filter) {
    $query .= ' AND category = ?';
    $count_query .= ' AND category = ?';
    $params[] = $category_filter;
    $types .= 's';
}

// Get total count
$stmt = $conn->prepare($count_query);
if ($types && count($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Get inventory items
$query .= ' ORDER BY product_name ASC';
$stmt = $conn->prepare($query);
if ($types && count($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$inventory = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get low stock items (where quantity <= reorder_level)
$low_stock_query = 'SELECT COUNT(*) as count FROM inventory WHERE quantity <= reorder_level';
$low_stock_result = $conn->query($low_stock_query);
$low_stock_count = $low_stock_result->fetch_assoc()['count'];

// Get category statistics
$category_stats = [];
$category_result = $conn->query('SELECT category, COUNT(*) as count, SUM(quantity) as total_qty FROM inventory GROUP BY category');
while ($row = $category_result->fetch_assoc()) {
    $category_stats[$row['category']] = $row;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory - Flor de Liz</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <?php require_once __DIR__ . '/../includes/sidebar_navigation.php'; ?>

    <div class="absolute w-full -ml-2 top-12">
        <div id="main-content" class="flex-1 px-4 sm:px-6 lg:px-8 py-12">
            <div class="max-w-7xl mx-auto">
                <div class="flex justify-between items-center mb-8">
                    <div>
                        <h2 class="text-4xl font-bold text-gray-900">Inventory</h2>
                        <p class="text-gray-600 mt-2">Manage shop inventory and supplies</p>
                    </div>
                    <a href="inventory/add.php" class="px-6 py-3 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition">
                        + Add Item
                    </a>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                    <div class="bg-white p-6 rounded-xl shadow-md">
                        <p class="text-gray-600 text-sm font-semibold">Total Items</p>
                        <p class="text-3xl font-bold text-teal-600 mt-2"><?php echo $total; ?></p>
                    </div>
                    <div class="bg-white p-6 rounded-xl shadow-md">
                        <p class="text-gray-600 text-sm font-semibold">Low Stock Alert</p>
                        <p class="text-3xl font-bold <?php echo $low_stock_count > 0 ? 'text-red-600' : 'text-green-600'; ?> mt-2"><?php echo $low_stock_count; ?></p>
                    </div>
                    <div class="bg-white p-6 rounded-xl shadow-md">
                        <p class="text-gray-600 text-sm font-semibold">Products</p>
                        <p class="text-3xl font-bold text-blue-600 mt-2"><?php echo $category_stats['Products']['count'] ?? 0; ?></p>
                    </div>
                    <div class="bg-white p-6 rounded-xl shadow-md">
                        <p class="text-gray-600 text-sm font-semibold">Materials/Supplies</p>
                        <p class="text-3xl font-bold text-purple-600 mt-2"><?php echo $category_stats['Materials/Supplies']['count'] ?? 0; ?></p>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white rounded-xl shadow-md p-6 mb-8">
                    <form method="GET" class="flex gap-4 flex-wrap">
                        <input type="text" name="search" placeholder="Search product..." value="<?php echo htmlspecialchars($search); ?>" class="flex-1 min-w-64 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500">
                        <select name="category" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500">
                            <option value="">All Categories</option>
                            <option value="Products" <?php echo $category_filter === 'Products' ? 'selected' : ''; ?>>Products</option>
                            <option value="Materials/Supplies" <?php echo $category_filter === 'Materials/Supplies' ? 'selected' : ''; ?>>Materials/Supplies</option>
                        </select>
                        <button type="submit" class="px-6 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition">Filter</button>
                    </form>
                </div>

                <!-- Inventory Table -->
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-100 border-b-2 border-gray-200">
                                <tr>
                                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Product</th>
                                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Category</th>
                                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">Quantity</th>
                                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">Unit Price</th>
                                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Status</th>
                                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inventory as $item): ?>
                                    <tr class="border-b border-gray-200 hover:bg-gray-50 transition">
                                        <td class="px-6 py-4 font-semibold text-gray-900"><?php echo htmlspecialchars($item['product_name']); ?></td>
                                        <td class="px-6 py-4 text-gray-700">
                                            <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $item['category'] === 'Products' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'; ?>">
                                                <?php echo htmlspecialchars($item['category']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right font-semibold text-gray-900"><?php echo number_format($item['quantity']); ?> <?php echo htmlspecialchars($item['unit']); ?></td>
                                        <td class="px-6 py-4 text-right text-gray-900">₱<?php echo number_format($item['unit_price'], 2); ?></td>
                                        <td class="px-6 py-4">
                                            <?php if ($item['quantity'] <= $item['reorder_level']): ?>
                                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">Low Stock</span>
                                            <?php else: ?>
                                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">In Stock</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <a href="inventory/view.php?id=<?php echo $item['id']; ?>" class="text-teal-600 hover:text-teal-800 font-semibold text-sm">View</a>
                                            <a href="inventory/edit.php?id=<?php echo $item['id']; ?>" class="text-blue-600 hover:text-blue-800 font-semibold text-sm ml-3">Edit</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php if (empty($inventory)): ?>
                            <div class="text-center py-12">
                                <p class="text-gray-600">No inventory items found.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
