<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['is_logged_in']) || $_SESSION['role'] !== 'staff') {
    header('Location: ../../login_staff.php'); exit();
}
$conn = require_once __DIR__ . '/../../config/database.php';

$sale_id = intval($_GET['id'] ?? 0);
if ($sale_id <= 0) {
    header('Location: ../sales.php'); exit();
}

// Fetch sale details
$stmt = $conn->prepare('SELECT id, sale_date, total_amount, created_at FROM sales WHERE id = ?');
$stmt->bind_param('i', $sale_id);
$stmt->execute();
$res = $stmt->get_result();
$sale = $res->fetch_assoc();
$stmt->close();

if (!$sale) {
    header('Location: ../sales.php'); exit();
}

// Fetch sale items
$items = $conn->query("SELECT id, product_name, unit, quantity, unit_price, subtotal FROM sale_items WHERE sale_id = $sale_id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>View Sale</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <?php require_once __DIR__ . '/../../includes/sidebar_navigation.php'; ?>
    <div class="absolute w-full -ml-2 top-12">
        <div id="main-content" class="flex-1 px-4 sm:px-6 lg:px-8 py-12">
            <div class="max-w-3xl mx-auto">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-3xl font-bold">Sale Details</h2>
                    <div class="space-x-2">
                        <a href="edit.php?id=<?php echo $sale['id']; ?>" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Edit</a>
                        <a href="../sales.php" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Back</a>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow p-6 mb-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm text-gray-600">Sale ID</label>
                            <p class="text-lg font-semibold"><?php echo $sale['id']; ?></p>
                        </div>
                        <div>
                            <label class="text-sm text-gray-600">Date</label>
                            <p class="text-lg font-semibold"><?php echo htmlspecialchars($sale['sale_date']); ?></p>
                        </div>
                        <div>
                            <label class="text-sm text-gray-600">Total Amount</label>
                            <p class="text-lg font-semibold">₱<?php echo number_format($sale['total_amount'], 2); ?></p>
                        </div>
                        <div>
                            <label class="text-sm text-gray-600">Created</label>
                            <p class="text-lg font-semibold"><?php echo htmlspecialchars($sale['created_at']); ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow p-6">
                    <h3 class="text-xl font-bold mb-4">Sale Items</h3>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-600 border-b">
                                <th class="py-2">Product</th>
                                <th class="py-2">Unit</th>
                                <th class="py-2">Quantity</th>
                                <th class="py-2">Unit Price (₱)</th>
                                <th class="py-2">Subtotal (₱)</th>
                            </tr>
                        </thead>
                        <tbody>
<?php while ($item = $items->fetch_assoc()): ?>
                            <tr class="border-b">
                                <td class="py-2"><?php echo htmlspecialchars($item['product_name']); ?></td>
                                <td class="py-2"><?php echo htmlspecialchars($item['unit']); ?></td>
                                <td class="py-2"><?php echo intval($item['quantity']); ?></td>
                                <td class="py-2"><?php echo number_format($item['unit_price'], 2); ?></td>
                                <td class="py-2"><?php echo number_format($item['subtotal'], 2); ?></td>
                            </tr>
<?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
