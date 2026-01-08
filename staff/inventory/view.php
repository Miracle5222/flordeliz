<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is staff
if (empty($_SESSION['is_logged_in']) || $_SESSION['role'] !== 'staff') {
    header('Location: ../../login_staff.php');
    exit();
}

$item_id = $_GET['id'] ?? null;

if (!$item_id) {
    header('Location: ../inventory.php');
    exit();
}

$conn = require_once __DIR__ . '/../../config/database.php';

// Get item details
$stmt = $conn->prepare('SELECT * FROM inventory WHERE id = ?');
$stmt->bind_param('i', $item_id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$item) {
    header('Location: ../inventory.php');
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($item['product_name']); ?> - Flor de Liz</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <?php require_once __DIR__ . '/../../includes/sidebar_navigation.php'; ?>

    <div class="absolute w-full -ml-2 top-12">
        <div id="main-content" class="flex-1 px-4 sm:px-6 lg:px-8 py-12">
            <div class="max-w-4xl mx-auto">
                <div class="flex justify-between items-center mb-8">
                    <div>
                        <h2 class="text-4xl font-bold text-gray-900"><?php echo htmlspecialchars($item['product_name']); ?></h2>
                        <p class="text-gray-600 mt-2">Inventory Details</p>
                    </div>
                    <div>
                        <?php if ($item['quantity'] <= $item['reorder_level']): ?>
                            <span class="px-4 py-2 rounded-full text-sm font-bold bg-red-100 text-red-800">
                                Low Stock
                            </span>
                        <?php else: ?>
                            <span class="px-4 py-2 rounded-full text-sm font-bold bg-green-100 text-green-800">
                                In Stock
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Main Content -->
                    <div class="lg:col-span-2">
                        <!-- Item Details -->
                        <div class="bg-white rounded-xl shadow-md p-8 mb-8">
                            <h3 class="text-lg font-bold text-gray-900 mb-6">Item Information</h3>
                            <div class="grid grid-cols-2 gap-6">
                                <div>
                                    <p class="text-sm font-semibold text-gray-600">Category</p>
                                    <p class="text-gray-900 mt-1">
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $item['category'] === 'Products' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'; ?>">
                                            <?php echo htmlspecialchars($item['category']); ?>
                                        </span>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-600">Unit</p>
                                    <p class="text-gray-900 mt-1"><?php echo htmlspecialchars($item['unit']); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-600">Unit Price</p>
                                    <p class="text-gray-900 mt-1 text-lg font-bold">₱<?php echo number_format($item['unit_price'], 2); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-600">Supplier</p>
                                    <p class="text-gray-900 mt-1"><?php echo htmlspecialchars($item['supplier'] ?? 'Not specified'); ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Stock Information -->
                        <div class="bg-white rounded-xl shadow-md p-8">
                            <h3 class="text-lg font-bold text-gray-900 mb-6">Stock Information</h3>
                            <div class="grid grid-cols-2 gap-6">
                                <div>
                                    <p class="text-sm font-semibold text-gray-600">Current Quantity</p>
                                    <p class="text-3xl font-bold text-teal-600 mt-2"><?php echo number_format($item['quantity']); ?></p>
                                    <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($item['unit']); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-600">Reorder Level</p>
                                    <p class="text-3xl font-bold text-gray-900 mt-2"><?php echo number_format($item['reorder_level']); ?></p>
                                    <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($item['unit']); ?></p>
                                </div>
                            </div>

                            <?php if ($item['quantity'] <= $item['reorder_level']): ?>
                                <div class="mt-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                                    <p class="text-red-800 font-semibold">⚠ Low Stock Alert</p>
                                    <p class="text-red-700 text-sm mt-1">Current stock is at or below reorder level. Consider ordering more soon.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Summary Sidebar -->
                    <div>
                        <div class="bg-white rounded-xl shadow-md p-8 sticky top-24">
                            <h3 class="text-lg font-bold text-gray-900 mb-6">Quick Summary</h3>
                            
                            <div class="space-y-4 mb-6">
                                <div class="border-b pb-4">
                                    <p class="text-sm text-gray-600">Total Value</p>
                                    <p class="text-2xl font-bold text-teal-600 mt-2">₱<?php echo number_format($item['quantity'] * $item['unit_price'], 2); ?></p>
                                </div>
                                <div class="border-b pb-4">
                                    <p class="text-sm text-gray-600">Stock Status</p>
                                    <div class="mt-2">
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            <div class="bg-teal-600 h-2 rounded-full" style="width: <?php echo min(100, ($item['quantity'] / max($item['reorder_level'], 1)) * 100); ?>%"></div>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">
                                            <?php echo number_format($item['quantity']); ?> / <?php echo number_format($item['reorder_level']); ?> <?php echo htmlspecialchars($item['unit']); ?>
                                        </p>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Last Updated</p>
                                    <p class="text-gray-900 mt-1"><?php echo date('M d, Y H:i', strtotime($item['updated_at'] ?? $item['created_at'])); ?></p>
                                </div>
                            </div>

                            <div class="flex gap-2">
                                <a href="edit.php?id=<?php echo $item['id']; ?>" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-center font-semibold">Edit</a>
                                <a href="../inventory.php" class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition text-center font-semibold">Back</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
