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

$message = '';
$error = '';

// Handle damage reporting
if (isset($_POST['report_damage'])) {
    $damaged_quantity = intval($_POST['damaged_quantity'] ?? 0);
    
    if ($damaged_quantity <= 0) {
        $error = 'Please enter a valid number of damaged items.';
    } elseif ($damaged_quantity > $item['quantity']) {
        $error = 'Damaged quantity cannot exceed current stock (' . $item['quantity'] . ').';
    } else {
        // Deduct damaged items from quantity
        $new_quantity = $item['quantity'] - $damaged_quantity;
        
        // Update inventory
        $stmt = $conn->prepare('UPDATE inventory SET quantity = ? WHERE id = ?');
        $stmt->bind_param('ii', $new_quantity, $item_id);
        
        if ($stmt->execute()) {
            // Record inventory transaction for damage
            $trans_stmt = $conn->prepare('INSERT INTO inventory_transactions (product_id, transaction_type, quantity, notes, created_by) VALUES (?, ?, ?, ?, ?)');
            $trans_type = 'out';
            $notes = "Damage reported: " . $damaged_quantity . " unit(s) removed. Stock reduced from " . $item['quantity'] . " to " . $new_quantity . ".";
            $created_by = $_SESSION['user_id'] ?? null;
            $trans_stmt->bind_param('isisi', $item_id, $trans_type, $damaged_quantity, $notes, $created_by);
            $trans_stmt->execute();
            $trans_stmt->close();
            
            $message = 'Damage recorded successfully! Stock updated from ' . $item['quantity'] . ' to ' . $new_quantity . '.';
            $item['quantity'] = $new_quantity;
        } else {
            $error = 'Failed to record damage. Please try again.';
        }
        $stmt->close();
    }
}

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
        $stmt = $conn->prepare('UPDATE inventory SET product_name = ?, category = ?, quantity = ?, unit = ?, unit_price = ?, reorder_level = ?, supplier = ? WHERE id = ?');
        $stmt->bind_param('ssisdisi', $product_name, $category, $quantity, $unit, $unit_price, $reorder_level, $supplier, $item_id);
        
        if ($stmt->execute()) {
            // Record inventory transaction if quantity changed
            if ($quantity != $item['quantity']) {
                $quantity_diff = $quantity - $item['quantity'];
                $trans_stmt = $conn->prepare('INSERT INTO inventory_transactions (product_id, transaction_type, quantity, notes, created_by) VALUES (?, ?, ?, ?, ?)');
                $trans_type = 'adjustment';
                $notes = "Quantity adjusted from " . $item['quantity'] . " to " . $quantity . " (" . ($quantity_diff > 0 ? '+' : '') . $quantity_diff . ")";
                $created_by = $_SESSION['user_id'] ?? null;
                $trans_stmt->bind_param('isisi', $item_id, $trans_type, $quantity_diff, $notes, $created_by);
                $trans_stmt->execute();
                $trans_stmt->close();
            }
            
            $message = 'Inventory item updated successfully!';
            $item['product_name'] = $product_name;
            $item['category'] = $category;
            $item['quantity'] = $quantity;
            $item['unit'] = $unit;
            $item['unit_price'] = $unit_price;
            $item['reorder_level'] = $reorder_level;
            $item['supplier'] = $supplier;
        } else {
            $error = 'Failed to update inventory item. Please try again.';
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
    <title>Edit Inventory Item - Flor de Liz</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <?php require_once __DIR__ . '/../../includes/sidebar_navigation.php'; ?>

    <div class="absolute w-full -ml-2 top-12">
        <div id="main-content" class="flex-1 px-4 sm:px-6 lg:px-8 py-12">
            <div class="max-w-2xl mx-auto">
                <div class="mb-8">
                    <h2 class="text-4xl font-bold text-gray-900">Edit Inventory Item</h2>
                    <p class="text-gray-600 mt-2">Update product or supply information</p>
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
                            <input type="text" name="product_name" value="<?php echo htmlspecialchars($item['product_name']); ?>" placeholder="e.g., Hardbound Book, Carbonless Paper" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500" required>
                        </div>

                        <div class="grid grid-cols-2 gap-6 mb-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Category *</label>
                                <select name="category" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500" required>
                                    <option value="Products" <?php echo $item['category'] === 'Products' ? 'selected' : ''; ?>>Products</option>
                                    <option value="Materials/Supplies" <?php echo $item['category'] === 'Materials/Supplies' ? 'selected' : ''; ?>>Materials/Supplies</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Unit *</label>
                                <select name="unit" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500" required>
                                    <option value="pcs" <?php echo $item['unit'] === 'pcs' ? 'selected' : ''; ?>>Pieces (pcs)</option>
                                    <option value="rem" <?php echo $item['unit'] === 'rem' ? 'selected' : ''; ?>>Ream (rem)</option>
                                    <option value="box" <?php echo $item['unit'] === 'box' ? 'selected' : ''; ?>>Box</option>
                                    <option value="dozen" <?php echo $item['unit'] === 'dozen' ? 'selected' : ''; ?>>Dozen</option>
                                    <option value="pad" <?php echo $item['unit'] === 'pad' ? 'selected' : ''; ?>>Pad</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-6 mb-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Current Quantity *</label>
                                <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" placeholder="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500" min="0" required>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Unit Price (₱) *</label>
                                <input type="number" name="unit_price" value="<?php echo $item['unit_price']; ?>" placeholder="0.00" step="0.01" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500" min="0" required>
                            </div>
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Reorder Level</label>
                            <input type="number" name="reorder_level" value="<?php echo $item['reorder_level']; ?>" placeholder="Quantity at which to reorder" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500" min="0">
                            <p class="text-xs text-gray-500 mt-1">You'll receive an alert when stock falls below this level</p>
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Supplier</label>
                            <input type="text" name="supplier" value="<?php echo htmlspecialchars($item['supplier'] ?? ''); ?>" placeholder="e.g., Star Paper Corporation" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500">
                        </div>

                        <div class="flex gap-4">
                            <button type="submit" class="flex-1 px-6 py-3 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition font-semibold">Save Changes</button>
                            <a href="../inventory.php" class="flex-1 px-6 py-3 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition font-semibold text-center">Cancel</a>
                        </div>
                    </form>
                </div>
                <!-- Damage Report Section -->
                <div class="bg-white rounded-xl shadow-md p-8 mt-8">
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">Report Damaged Items</h3>
                    <p class="text-gray-600 mb-6">Record and deduct damaged items from inventory</p>
                    
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
                        <p class="text-gray-700 text-sm">
                            <strong>Current Stock:</strong> <span class="font-bold text-lg text-gray-900"><?php echo $item['quantity']; ?></span> <?php echo htmlspecialchars($item['unit']); ?>
                        </p>
                    </div>

                    <form method="POST">
                        <div class="grid grid-cols-2 gap-6 mb-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Number of Damaged Items *</label>
                                <input type="number" name="damaged_quantity" id="damaged_quantity" placeholder="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500" min="1" max="<?php echo $item['quantity']; ?>" required>
                                <p class="text-xs text-gray-500 mt-1">Maximum: <?php echo $item['quantity']; ?> <?php echo htmlspecialchars($item['unit']); ?></p>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">New Quantity (Preview)</label>
                                <input type="text" id="new_quantity_preview" value="<?php echo $item['quantity']; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-700" readonly>
                                <p class="text-xs text-gray-500 mt-1">Auto-calculated from damage count</p>
                            </div>
                        </div>

                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
                            <p class="text-gray-700 text-sm">
                                <strong>Note:</strong> This action will permanently reduce the inventory quantity and create a transaction record. This action cannot be undone directly.
                            </p>
                        </div>

                        <button type="submit" name="report_damage" class="w-full px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-semibold">Report Damage & Update Stock</button>
                    </form>
                </div>

                        <button type=\"submit\" name=\"report_damage\" class=\"w-full px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-semibold\">Report Damage & Update Stock</button>
                    </form>
                </div>            </div>
        </div>
    </div>

    <script>
        // Auto-update new quantity preview
        const damagedInput = document.getElementById('damaged_quantity');
        const currentStock = <?php echo $item['quantity']; ?>;
        const previewInput = document.getElementById('new_quantity_preview');

        if (damagedInput && previewInput) {
            damagedInput.addEventListener('input', function() {
                const damagedQty = parseInt(this.value) || 0;
                const newQty = currentStock - damagedQty;
                previewInput.value = newQty >= 0 ? newQty : currentStock;
            });
        }
    </script>
</body>
</html>
