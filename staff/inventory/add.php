<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is staff
if (empty($_SESSION['is_logged_in']) || $_SESSION['role'] !== 'staff') {
    header('Location: ../../login_staff.php');
    exit();
}

$conn = require_once __DIR__ . '/../../config/database.php';

$message = '';
$error = '';
$product_name = '';
$category = '';
$quantity = '';
$unit = '';
$unit_price = '';
$reorder_level = '';
$supplier = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name = $_POST['product_name'] ?? '';
    $category = $_POST['category'] ?? '';
    $quantity = intval($_POST['quantity'] ?? 0);
    $unit = $_POST['unit'] ?? '';
    $unit_price = floatval($_POST['unit_price'] ?? 0);
    $reorder_level = intval($_POST['reorder_level'] ?? 0);
    $supplier = $_POST['supplier'] ?? '';

    if (!$product_name || !$category || !$unit || $unit_price <= 0) {
        $error = 'Please fill in all required fields with valid values.';
    } else {
        $stmt = $conn->prepare('INSERT INTO inventory (product_name, category, quantity, unit, unit_price, reorder_level, supplier) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('ssisdis', $product_name, $category, $quantity, $unit, $unit_price, $reorder_level, $supplier);
        
        if ($stmt->execute()) {
            $message = 'Inventory item added successfully!';
            $product_name = '';
            $category = '';
            $quantity = '';
            $unit = '';
            $unit_price = '';
            $reorder_level = '';
            $supplier = '';
        } else {
            $error = 'Failed to add inventory item. Please try again.';
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Inventory Item - Flor de Liz</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <?php require_once __DIR__ . '/../../includes/sidebar_navigation.php'; ?>

    <div class="absolute w-full -ml-2 top-12">
        <div id="main-content" class="flex-1 px-4 sm:px-6 lg:px-8 py-12">
            <div class="max-w-2xl mx-auto">
                <div class="mb-8">
                    <h2 class="text-4xl font-bold text-gray-900">Add Inventory Item</h2>
                    <p class="text-gray-600 mt-2">Add a new product or supply item to inventory</p>
                </div>

                <?php if ($message): ?>
                    <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-6">
                        <p class="text-green-800 font-semibold"><?php echo htmlspecialchars($message); ?></p>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
                        <p class="text-red-800 font-semibold"><?php echo htmlspecialchars($error); ?></p>
                    </div>
                <?php endif; ?>

                <div class="bg-white rounded-xl shadow-md p-8">
                    <form method="POST">
                        <div class="mb-6">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Product Name *</label>
                            <input type="text" name="product_name" value="<?php echo htmlspecialchars($product_name); ?>" placeholder="e.g., Hardbound Book, Carbonless Paper" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500" required>
                        </div>

                        <div class="grid grid-cols-2 gap-6 mb-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Category *</label>
                                <select name="category" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500" required>
                                    <option value="">-- Select Category --</option>
                                    <option value="Products" <?php echo $category === 'Products' ? 'selected' : ''; ?>>Products</option>
                                    <option value="Materials/Supplies" <?php echo $category === 'Materials/Supplies' ? 'selected' : ''; ?>>Materials/Supplies</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Unit *</label>
                                <select name="unit" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500" required>
                                    <option value="">-- Select Unit --</option>
                                    <option value="pcs" <?php echo $unit === 'pcs' ? 'selected' : ''; ?>>Pieces (pcs)</option>
                                    <option value="rem" <?php echo $unit === 'rem' ? 'selected' : ''; ?>>Ream (rem)</option>
                                    <option value="box" <?php echo $unit === 'box' ? 'selected' : ''; ?>>Box</option>
                                    <option value="dozen" <?php echo $unit === 'dozen' ? 'selected' : ''; ?>>Dozen</option>
                                    <option value="pad" <?php echo $unit === 'pad' ? 'selected' : ''; ?>>Pad</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-6 mb-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Current Quantity *</label>
                                <input type="number" name="quantity" value="<?php echo $quantity; ?>" placeholder="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500" min="0" required>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Unit Price (₱) *</label>
                                <input type="number" name="unit_price" value="<?php echo $unit_price; ?>" placeholder="0.00" step="0.01" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500" min="0" required>
                            </div>
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Reorder Level</label>
                            <input type="number" name="reorder_level" value="<?php echo $reorder_level; ?>" placeholder="Quantity at which to reorder" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500" min="0">
                            <p class="text-xs text-gray-500 mt-1">You'll receive an alert when stock falls below this level</p>
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Supplier</label>
                            <input type="text" name="supplier" value="<?php echo htmlspecialchars($supplier); ?>" placeholder="e.g., Star Paper Corporation" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500">
                        </div>

                        <div class="flex gap-4">
                            <button type="submit" class="flex-1 px-6 py-3 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition font-semibold">Add Item</button>
                            <a href="../inventory.php" class="flex-1 px-6 py-3 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition font-semibold text-center">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
