<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['is_logged_in']) || $_SESSION['role'] !== 'staff') {
    header('Location: ../../login_staff.php'); exit();
}
$conn = require_once __DIR__ . '/../../config/database.php';

// Ensure tables exist
$conn->query("CREATE TABLE IF NOT EXISTS sales (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sale_date DATETIME NOT NULL,
    total_amount DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$conn->query("CREATE TABLE IF NOT EXISTS sale_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sale_id INT NOT NULL,
    inventory_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (sale_id), INDEX (inventory_id)
)");
// Add missing columns if they don't exist
@$conn->query("ALTER TABLE sale_items ADD COLUMN product_name VARCHAR(255) DEFAULT NULL");
@$conn->query("ALTER TABLE sale_items ADD COLUMN unit VARCHAR(50) DEFAULT NULL");

// Load inventory for selection
$invRes = $conn->query('SELECT id, product_name, quantity, unit_price, unit FROM inventory ORDER BY product_name');
$inventory = [];
while ($r = $invRes->fetch_assoc()) $inventory[] = $r;

$message = ''; $error = ''; $warning = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inventory_id_raw = $_POST['inventory_id'] ?? '';
    $quantity = intval($_POST['quantity'] ?? 0);
    $sale_date = $_POST['sale_date'] ?? date('Y-m-d H:i:s');
    // Normalize datetime-local input (e.g. 2026-01-08T14:30) to MySQL DATETIME 'YYYY-MM-DD HH:MM:SS'
    if (strpos($sale_date, 'T') !== false) {
        $sale_date = str_replace('T', ' ', $sale_date);
        // if seconds missing, append :00
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $sale_date)) {
            $sale_date .= ':00';
        }
    }
    $is_other = ($inventory_id_raw === 'others');
    $other_name = trim($_POST['other_name'] ?? '');
    $other_unit = trim($_POST['other_unit'] ?? '');
    $other_price = floatval($_POST['other_unit_price'] ?? 0);

    if ((!$is_other && intval($inventory_id_raw) <= 0) || $quantity <= 0) {
        $error = 'Please select a product and enter a valid quantity.';
    } elseif ($is_other && (!$other_name || $other_price <= 0)) {
        $error = 'Please provide a name and valid price for the "Others" product.';
    } else {
        $inventory_id = $is_other ? 0 : intval($inventory_id_raw);
        // fetch inventory price and available quantity for non-others
        if (!$is_other) {
            $stmt = $conn->prepare('SELECT unit_price, quantity, product_name FROM inventory WHERE id = ?');
            $stmt->bind_param('i', $inventory_id);
            $stmt->execute(); $res = $stmt->get_result();
            $row = $res->fetch_assoc(); $stmt->close();
            if (!$row) {
                $error = 'Product not found.';
            } else {
                $available = intval($row['quantity']);
                if ($quantity > $available) {
                    // don't block the sale; warn and allow, inventory will go to 0
                    $warning = 'Requested quantity exceeds available stock; sale will be recorded and inventory set to 0.';
                }
                $unit_price = floatval($row['unit_price']);
                $product_label = $row['product_name'];
            }
        } else {
            $unit_price = $other_price;
            $product_label = $other_name;
        }

        if (!$error) {
            $subtotal = $unit_price * $quantity;

            // transaction
            $conn->begin_transaction();
            try {
                $ins = $conn->prepare('INSERT INTO sales (sale_date, total_amount, created_at) VALUES (?, ?, NOW())');
                if (!$ins) throw new Exception('Prepare failed (sales): ' . $conn->error);
                if (!$ins->bind_param('sd', $sale_date, $subtotal)) throw new Exception('Bind failed (sales): ' . $ins->error);
                if (!$ins->execute()) throw new Exception('Execute failed (sales): ' . $ins->error);
                $sale_id = $ins->insert_id;
                $ins->close();

                $it = $conn->prepare('INSERT INTO sale_items (sale_id, inventory_id, product_name, unit, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?)');
                if (!$it) {
                    throw new Exception('Prepare failed (sale_items): ' . $conn->error);
                }
                $unit_val = $is_other ? $other_unit : ($row['unit'] ?? '');
                if (!$it->bind_param('iissidd', $sale_id, $inventory_id, $product_label, $unit_val, $quantity, $unit_price, $subtotal)) {
                    throw new Exception('Bind failed (sale_items): ' . $it->error);
                }
                if (!$it->execute()) {
                    throw new Exception('Execute failed (sale_items): ' . $it->error);
                }
                $it->close();

                // update inventory quantity only when not "others"
                if (!$is_other) {
                    // subtract quantity but don't let inventory go below zero
                    $up = $conn->prepare('UPDATE inventory SET quantity = GREATEST(quantity - ?, 0) WHERE id = ?');
                    if (!$up) throw new Exception('Prepare failed (inventory update): ' . $conn->error);
                    if (!$up->bind_param('ii', $quantity, $inventory_id)) throw new Exception('Bind failed (inventory update): ' . $up->error);
                    if (!$up->execute()) throw new Exception('Execute failed (inventory update): ' . $up->error);
                    $up->close();
                }

                $conn->commit();
                $message = 'Sale recorded successfully.';
            } catch (Exception $e) {
                $conn->rollback();
                error_log('[sales/create] ' . $e->getMessage());
                $error = 'Failed to record sale: ' . $e->getMessage();
            }
        }
    }
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Record Sale</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <?php require_once __DIR__ . '/../../includes/sidebar_navigation.php'; ?>
    <div class="absolute w-full -ml-2 top-12">
        <div id="main-content" class="flex-1 px-4 sm:px-6 lg:px-8 py-12">
            <div class="max-w-2xl mx-auto">
                <h2 class="text-3xl font-bold mb-4">Record Sale</h2>
                <?php if ($message): ?><div class="notification bg-green-50 border border-green-200 p-3 mb-4"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
                <?php if ($warning): ?><div class="notification bg-yellow-50 border border-yellow-200 p-3 mb-4 text-yellow-800"><?php echo htmlspecialchars($warning); ?></div><?php endif; ?>
                <?php if ($error): ?><div class="notification bg-red-50 border border-red-200 p-3 mb-4"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
                <div class="bg-white rounded-xl shadow p-6">
                    <form method="post">
                        <div class="mb-4">
                            <label class="block text-sm font-semibold">Product</label>
                            <select id="inventory-select" name="inventory_id" required class="w-full border px-3 py-2 rounded">
                                <option value="">-- Select Product --</option>
                                <?php foreach ($inventory as $p): ?>
                                    <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['product_name']); ?> (<?php echo $p['quantity']; ?> <?php echo $p['unit']; ?>) - ₱<?php echo number_format($p['unit_price'],2); ?></option>
                                <?php endforeach; ?>
                                <option value="others">-- Others (custom product) --</option>
                            </select>
                        </div>

                        <div id="other-fields" class="mb-4 hidden">
                            <label class="block text-sm font-semibold">Other Product Name</label>
                            <input type="text" name="other_name" class="w-full border px-3 py-2 rounded mb-2" placeholder="e.g., Custom Print Job">
                            <label class="block text-sm font-semibold">Unit</label>
                            <select name="other_unit" class="w-full border px-3 py-2 rounded mb-2">
                                <option value="">-- Select Unit --</option>
                                <option value="pcs">Pieces (pcs)</option>
                                <option value="rem">Ream (rem)</option>
                                <option value="box">Box</option>
                                <option value="dozen">Dozen</option>
                                <option value="pad">Pad</option>
                            </select>
                            <label class="block text-sm font-semibold">Unit Price (₱)</label>
                            <input type="number" name="other_unit_price" step="0.01" class="w-full border px-3 py-2 rounded" placeholder="0.00">
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-semibold">Quantity</label>
                            <input type="number" name="quantity" min="1" required class="w-full border px-3 py-2 rounded" />
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-semibold">Sale Date (optional)</label>
                            <input type="datetime-local" name="sale_date" class="w-full border px-3 py-2 rounded" />
                        </div>
                        <div class="flex gap-4">
                            <button type="submit" class="px-4 py-2 bg-teal-600 text-white rounded">Record</button>
                            <a href="../sales.php" class="px-4 py-2 bg-gray-200 rounded">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Toggle visibility of other product fields
        (function(){
            const sel = document.getElementById('inventory-select');
            const other = document.getElementById('other-fields');
            if (!sel) return;
            function update() {
                const otherFields = other.querySelectorAll('input,select');
                if (sel.value === 'others') {
                    other.classList.remove('hidden');
                    otherFields.forEach(function(el){ el.disabled = false; if (el.name === 'other_unit') el.required = true; });
                } else {
                    other.classList.add('hidden');
                    otherFields.forEach(function(el){ el.disabled = true; if (el.name === 'other_unit') el.required = false; });
                }
            }
            sel.addEventListener('change', update);
            // run once in case form retained values after submit
            document.addEventListener('DOMContentLoaded', function(){
                // ensure other fields are disabled by default when not visible
                update();
            });
            update();
        })();

        // Auto-dismiss notifications after 3 seconds
        (function(){
            document.querySelectorAll('.notification').forEach(function(el){
                setTimeout(function(){
                    el.style.opacity = '0';
                    el.style.transition = 'opacity 0.5s';
                    setTimeout(function(){ el.remove(); }, 500);
                }, 3000);
            });
        })();
    </script>
</body>
</html>