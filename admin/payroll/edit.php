<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['is_logged_in']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../login_admin.php'); exit();
}
$conn = require_once __DIR__ . '/../../config/database.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: ../payroll.php'); exit();
}

// Fetch payroll details
$stmt = $conn->prepare("
    SELECT p.*, e.first_name, e.last_name, e.position, e.daily_rate, e.overtime_rate, u.email
    FROM payroll p
    JOIN employees e ON p.employee_id = e.id
    LEFT JOIN users u ON e.user_id = u.id
    WHERE p.id = ?
");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$payroll = $result->fetch_assoc();
$stmt->close();

if (!$payroll) {
    header('Location: ../payroll.php'); exit();
}

$message = '';
$error = '';

if ($_POST) {
    try {
        $days_worked = $_POST['days_worked'] ?? 0;
        $overtime_hours = $_POST['overtime_hours'] ?? 0;
        $deductions = $_POST['deductions'] ?? 0;
        $status = $_POST['status'] ?? 'draft';
        $notes = $_POST['notes'] ?? '';

        // Calculate pay
        $base_pay = $days_worked * $payroll['daily_rate'];
        $overtime_pay = $overtime_hours * $payroll['overtime_rate'];
        $total_pay = $base_pay + $overtime_pay - $deductions;

        // Update payroll record
        $stmt = $conn->prepare("
            UPDATE payroll 
            SET days_worked = ?, overtime_hours = ?, base_pay = ?, overtime_pay = ?, 
                deductions = ?, total_pay = ?, status = ?, notes = ?
            WHERE id = ?
        ");
        if (!$stmt) throw new Exception('Prepare failed: ' . $conn->error);
        
        $stmt->bind_param('idddddssi', $days_worked, $overtime_hours, $base_pay, $overtime_pay, 
                 $deductions, $total_pay, $status, $notes, $id);
        if (!$stmt->execute()) throw new Exception('Failed to update payroll: ' . $stmt->error);
        $stmt->close();

        $message = 'Payroll record updated successfully.';
        // Refresh payroll data
        $stmt = $conn->prepare("
            SELECT p.*, e.first_name, e.last_name, e.position, e.daily_rate, e.overtime_rate, u.email
            FROM payroll p
            JOIN employees e ON p.employee_id = e.id
            LEFT JOIN users u ON e.user_id = u.id
            WHERE p.id = ?
        ");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $payroll = $result->fetch_assoc();
        $stmt->close();
    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log('Payroll update error: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Edit Payroll - Flor de Liz</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <?php require_once __DIR__ . '/../../includes/sidebar_navigation.php'; ?>
    <div class="absolute w-full -ml-2 top-12">
        <div id="main-content" class="flex-1 px-4 sm:px-6 lg:px-8 py-12">
            <div class="max-w-3xl mx-auto">
                <h2 class="text-3xl font-bold mb-6">Edit Payroll Record</h2>

                <?php if ($message): ?><div class="notification bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
                <?php if ($error): ?><div class="notification bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

                <div class="bg-white rounded-xl shadow p-8">
                    <div class="bg-gray-50 p-4 rounded-lg mb-6">
                        <h3 class="text-lg font-bold"><?php echo htmlspecialchars($payroll['first_name'] . ' ' . $payroll['last_name']); ?></h3>
                        <p class="text-gray-600"><?php echo htmlspecialchars($payroll['position']); ?></p>
                        <p class="text-sm text-gray-500">Pay Period: <?php echo date('M d, Y', strtotime($payroll['pay_period_start'])) . ' - ' . date('M d, Y', strtotime($payroll['pay_period_end'])); ?></p>
                    </div>

                    <form method="post">
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Days Worked *</label>
                                <input type="number" name="days_worked" required min="0" max="30" step="0.5" value="<?php echo $payroll['days_worked']; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500" onchange="calculatePay()">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Overtime Hours</label>
                                <input type="number" name="overtime_hours" min="0" max="999" step="0.5" value="<?php echo $payroll['overtime_hours']; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500" onchange="calculatePay()">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Deductions (₱)</label>
                            <input type="number" name="deductions" min="0" step="0.01" value="<?php echo $payroll['deductions']; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500" onchange="calculatePay()">
                        </div>

                        <div class="bg-gray-50 p-4 rounded-lg mb-6">
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-600">Daily Rate:</span>
                                    <span class="font-semibold">₱<?php echo number_format($payroll['daily_rate'], 2); ?></span>
                                </div>
                                <div>
                                    <span class="text-gray-600">OT Rate/Hour:</span>
                                    <span class="font-semibold">₱<?php echo number_format($payroll['overtime_rate'], 2); ?></span>
                                </div>
                                <div class="pt-2">
                                    <span class="text-gray-600">Base Pay:</span>
                                    <span id="base-pay" class="font-semibold">₱<?php echo number_format($payroll['base_pay'], 2); ?></span>
                                </div>
                                <div class="pt-2">
                                    <span class="text-gray-600">OT Pay:</span>
                                    <span id="ot-pay" class="font-semibold">₱<?php echo number_format($payroll['overtime_pay'], 2); ?></span>
                                </div>
                            </div>
                            <div class="border-t pt-4 mt-4">
                                <div class="flex justify-between text-lg font-bold">
                                    <span>Total Pay:</span>
                                    <span id="total-pay">₱<?php echo number_format($payroll['total_pay'], 2); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                                    <option value="draft" <?php echo $payroll['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                    <option value="approved" <?php echo $payroll['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="paid" <?php echo $payroll['status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Notes</label>
                            <textarea name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500"><?php echo htmlspecialchars($payroll['notes'] ?? ''); ?></textarea>
                        </div>

                        <div class="flex gap-4">
                            <button type="submit" class="px-6 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 font-semibold">Update Payroll</button>
                            <a href="view.php?id=<?php echo $payroll['id']; ?>" class="px-6 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 font-semibold">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        const dailyRate = <?php echo $payroll['daily_rate']; ?>;
        const otRate = <?php echo $payroll['overtime_rate']; ?>;
        const daysInput = document.querySelector('input[name="days_worked"]');
        const otInput = document.querySelector('input[name="overtime_hours"]');
        const deductInput = document.querySelector('input[name="deductions"]');

        function calculatePay() {
            const days = parseFloat(daysInput.value) || 0;
            const ot = parseFloat(otInput.value) || 0;
            const deduct = parseFloat(deductInput.value) || 0;

            const basePay = days * dailyRate;
            const otPay = ot * otRate;
            const totalPay = basePay + otPay - deduct;

            document.getElementById('base-pay').textContent = '₱' + basePay.toFixed(2);
            document.getElementById('ot-pay').textContent = '₱' + otPay.toFixed(2);
            document.getElementById('total-pay').textContent = '₱' + totalPay.toFixed(2);
        }

        document.querySelectorAll('.notification').forEach(notif => {
            setTimeout(() => {
                notif.style.opacity = '0';
                notif.style.transition = 'opacity 0.3s ease';
                setTimeout(() => notif.remove(), 300);
            }, 3000);
        });
    </script>
</body>
</html>
