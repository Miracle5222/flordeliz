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
$stmt = $conn->prepare('SELECT id, sale_date, total_amount FROM sales WHERE id = ?');
$stmt->bind_param('i', $sale_id);
$stmt->execute();
$res = $stmt->get_result();
$sale = $res->fetch_assoc();
$stmt->close();

if (!$sale) {
    header('Location: ../sales.php'); exit();
}

$message = ''; $error = '';

// Handle POST for updating sale
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sale_date = $_POST['sale_date'] ?? '';
    $item_ids = $_POST['item_id'] ?? [];
    $product_names = $_POST['product_name'] ?? [];
    $units = $_POST['unit'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $unit_prices = $_POST['unit_price'] ?? [];
    
    if (!$sale_date) {
        $error = 'Please provide a sale date.';
    } else {
        // Convert datetime-local format (YYYY-MM-DDTHH:mm) to MySQL format (YYYY-MM-DD HH:mm:ss)
        $sale_date_formatted = str_replace('T', ' ', $sale_date) . ':00';
        
        $conn->begin_transaction();
        try {
            // Update sale date
            $stmt = $conn->prepare('UPDATE sales SET sale_date = ? WHERE id = ?');
            if (!$stmt) throw new Exception('Prepare failed: ' . $conn->error);
            $stmt->bind_param('si', $sale_date_formatted, $sale_id);
            if (!$stmt->execute()) throw new Exception('Update sale failed: ' . $stmt->error);
            $stmt->close();
            
            // Update each item and recalculate total
            $new_total = 0;
            foreach ($item_ids as $idx => $item_id) {
                $item_id = intval($item_id);
                $qty = intval($quantities[$idx] ?? 0);
                $price = floatval($unit_prices[$idx] ?? 0);
                $pname = trim($product_names[$idx] ?? '');
                $unitVal = trim($units[$idx] ?? '');
                
                if ($qty <= 0 || $price < 0) {
                    throw new Exception('Invalid quantity or price for item.');
                }
                
                $subtotal = $qty * $price;
                $new_total += $subtotal;
                
                $upd = $conn->prepare('UPDATE sale_items SET product_name = ?, unit = ?, quantity = ?, unit_price = ?, subtotal = ? WHERE id = ? AND sale_id = ?');
                if (!$upd) throw new Exception('Prepare failed: ' . $conn->error);
                if (!$upd->bind_param('ssiddii', $pname, $unitVal, $qty, $price, $subtotal, $item_id, $sale_id)) throw new Exception('Bind failed: ' . $upd->error);
                if (!$upd->execute()) throw new Exception('Update item failed: ' . $upd->error);
                $upd->close();
            }
            
            // Update sales total
            $tot = $conn->prepare('UPDATE sales SET total_amount = ? WHERE id = ?');
            if (!$tot) throw new Exception('Prepare failed: ' . $conn->error);
            $tot->bind_param('di', $new_total, $sale_id);
            if (!$tot->execute()) throw new Exception('Update total failed: ' . $tot->error);
            $tot->close();
            
            $conn->commit();
            $message = 'Sale and items updated successfully.';
            $sale['sale_date'] = $sale_date_formatted;
            $sale['total_amount'] = $new_total;
        } catch (Exception $e) {
            $conn->rollback();
            $error = 'Failed to update sale: ' . $e->getMessage();
        }
    }
}

// Load inventory for selects
$invRes = $conn->query('SELECT id, product_name, unit FROM inventory ORDER BY product_name');
$inventory = [];
$units_map = [];
while ($r = $invRes->fetch_assoc()) {
    $inventory[] = $r;
    $units_map[$r['unit']] = true;
}
$units = array_keys($units_map);

// Fetch sale items (include inventory_id)
$items = $conn->query("SELECT id, inventory_id, product_name, unit, quantity, unit_price, subtotal FROM sale_items WHERE sale_id = $sale_id");
$items_array = [];
while ($row = $items->fetch_assoc()) {
    $items_array[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Edit Sale</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <?php require_once __DIR__ . '/../../includes/sidebar_navigation.php'; ?>
    <div class="absolute w-full -ml-2 top-12">
        <div id="main-content" class="flex-1 px-4 sm:px-6 lg:px-8 py-12">
            <div class="max-w-4xl mx-auto">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-3xl font-bold">Edit Sale</h2>
                    <a href="view.php?id=<?php echo $sale['id']; ?>" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancel</a>
                </div>

                <?php if ($message): ?><div class="bg-green-50 border border-green-200 p-3 mb-4 rounded"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
                <?php if ($error): ?><div class="bg-red-50 border border-red-200 p-3 mb-4 rounded"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

                <form method="post">
                    <div class="bg-white rounded-xl shadow p-6 mb-6">
                        <div class="mb-4">
                            <label class="block text-sm font-semibold mb-2">Sale Date</label>
                            <input type="datetime-local" name="sale_date" value="<?php echo htmlspecialchars(str_replace(' ', 'T', substr($sale['sale_date'], 0, 16))); ?>" class="w-full border px-3 py-2 rounded" required>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-semibold mb-2">Total Amount (₱)</label>
                            <p id="total-display" class="text-2xl font-bold text-teal-600"><?php echo number_format($sale['total_amount'], 2); ?></p>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow p-6 mb-6">
                        <h3 class="text-xl font-bold mb-4">Sale Items (Editable)</h3>
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
                            <tbody id="items-tbody">
<?php foreach ($items_array as $idx => $item): ?>
                                <tr class="border-b item-row">
                                    <td class="py-2">
                                        <select class="product-select w-full border px-2 py-1 rounded" data-row-index="<?php echo $idx; ?>">
                                            <option value="">-- Select Product --</option>
                                            <?php foreach ($inventory as $inv): ?>
                                                <option value="<?php echo $inv['id']; ?>" data-unit="<?php echo htmlspecialchars($inv['unit']); ?>" <?php echo (isset($item['inventory_id']) && $item['inventory_id'] == $inv['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($inv['product_name']); ?></option>
                                            <?php endforeach; ?>
                                            <option value="0" <?php echo (empty($item['inventory_id']) ? 'selected' : ''); ?>>-- Others (custom) --</option>
                                        </select>

                                        <input type="hidden" name="product_name[]" value="<?php echo htmlspecialchars($item['product_name']); ?>" class="product-name-hidden">

                                        <input type="text" name="product_custom[]" value="<?php echo htmlspecialchars($item['inventory_id'] ? '' : $item['product_name']); ?>" class="product-custom mt-2 w-full border px-2 py-1 rounded" placeholder="Custom product name" style="display: <?php echo empty($item['inventory_id']) ? 'block' : 'none'; ?>;">
                                    </td>
                                    <td class="py-2">
                                        <select name="unit[]" class="unit-select w-24 border px-2 py-1 rounded">
                                            <option value="">--</option>
                                            <?php foreach ($units as $u): ?>
                                                <option value="<?php echo htmlspecialchars($u); ?>" <?php echo ($item['unit'] == $u) ? 'selected' : ''; ?>><?php echo htmlspecialchars($u); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td class="py-2">
                                        <input type="hidden" name="item_id[]" value="<?php echo $item['id']; ?>">
                                        <input type="number" name="quantity[]" value="<?php echo intval($item['quantity']); ?>" class="w-20 border px-2 py-1 rounded quantity-input" min="1" required>
                                    </td>
                                    <td class="py-2">
                                        <input type="number" name="unit_price[]" value="<?php echo number_format($item['unit_price'], 2, '.', ''); ?>" step="0.01" class="w-24 border px-2 py-1 rounded price-input" min="0" required>
                                    </td>
                                    <td class="py-2 subtotal-display"><?php echo number_format($item['subtotal'], 2); ?></td>
                                </tr>
<?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Save Changes</button>
                        <a href="view.php?id=<?php echo $sale['id']; ?>" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        // Real-time subtotal and total calculation
        function updateTotals() {
            let grandTotal = 0;
            document.querySelectorAll('.item-row').forEach((row) => {
                const qty = parseFloat(row.querySelector('.quantity-input').value) || 0;
                const price = parseFloat(row.querySelector('.price-input').value) || 0;
                const subtotal = qty * price;
                row.querySelector('.subtotal-display').textContent = subtotal.toFixed(2);
                grandTotal += subtotal;
            });
            document.getElementById('total-display').textContent = grandTotal.toFixed(2);
        }

        document.querySelectorAll('.quantity-input, .price-input').forEach(input => {
            input.addEventListener('change', updateTotals);
            input.addEventListener('input', updateTotals);
        });

        // Product select / unit sync
        (function(){
            function setRowFromSelect(row) {
                const sel = row.querySelector('.product-select');
                const hiddenName = row.querySelector('.product-name-hidden');
                const custom = row.querySelector('.product-custom');
                const unitSelect = row.querySelector('.unit-select');
                if (!sel) return;
                const val = sel.value;
                if (val === '0' || val === '') {
                    // others or none
                    if (hiddenName) hiddenName.value = custom.value || '';
                    if (custom) custom.style.display = (val === '0') ? 'block' : 'none';
                } else {
                    const opt = sel.querySelector('option[value="'+val+'"]');
                    const pname = opt ? opt.textContent : '';
                    const punit = opt ? opt.getAttribute('data-unit') : '';
                    if (hiddenName) hiddenName.value = pname.trim();
                    if (custom) custom.style.display = 'none';
                    if (unitSelect && punit) {
                        // try set unit select to punit, add option if missing
                        let found = false;
                        for (let i=0;i<unitSelect.options.length;i++) {
                            if (unitSelect.options[i].value === punit) { found = true; break; }
                        }
                        if (!found) {
                            const o = document.createElement('option'); o.value = punit; o.text = punit; unitSelect.add(o);
                        }
                        unitSelect.value = punit;
                    }
                }
            }

            document.querySelectorAll('.item-row').forEach((row) => {
                const sel = row.querySelector('.product-select');
                if (!sel) return;
                sel.addEventListener('change', function(){ setRowFromSelect(row); updateTotals(); });
                const custom = row.querySelector('.product-custom');
                if (custom) custom.addEventListener('input', function(){ const h = row.querySelector('.product-name-hidden'); if (h) h.value = custom.value; });
                // initialize
                setRowFromSelect(row);
            });
        })();
    </script>
</body>
</html>
