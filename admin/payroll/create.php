<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['is_logged_in']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../login_admin.php'); exit();
}
$conn = require_once __DIR__ . '/../../config/database.php';

// Fetch all active employees
$employees_query = "SELECT e.id, e.first_name, e.last_name, e.position, e.daily_rate, e.overtime_rate 
                   FROM employees e WHERE e.is_active = 1 ORDER BY e.first_name, e.last_name";
$employees_result = $conn->query($employees_query);
$employees = [];
while ($row = $employees_result->fetch_assoc()) {
    $employees[] = $row;
}

$message = '';
$error = '';

if ($_POST) {
    try {
        $employee_id = $_POST['employee_id'] ?? '';
        $pay_period_start = $_POST['pay_period_start'] ?? '';
        $pay_period_end = $_POST['pay_period_end'] ?? '';
        $deductions = $_POST['deductions'] ?? 0;

        if (!$employee_id || !$pay_period_start || !$pay_period_end) {
            throw new Exception('Employee and pay period dates are required.');
        }

        if (strtotime($pay_period_start) >= strtotime($pay_period_end)) {
            throw new Exception('Pay period end date must be after start date.');
        }

        // Get employee details
        $emp_stmt = $conn->prepare("SELECT daily_rate, overtime_rate FROM employees WHERE id = ?");
        $emp_stmt->bind_param('i', $employee_id);
        $emp_stmt->execute();
        $emp_result = $emp_stmt->get_result();
        $employee = $emp_result->fetch_assoc();
        $emp_stmt->close();

        if (!$employee) {
            throw new Exception('Employee not found.');
        }

        // Calculate days worked and overtime from attendance records
        $att_stmt = $conn->prepare("
            SELECT 
                COUNT(DISTINCT attendance_date) as days_worked,
                SUM(hours_worked) as total_hours,
                SUM(overtime_hours) as overtime_hours
            FROM attendance 
            WHERE employee_id = ? 
            AND attendance_date BETWEEN ? AND ?
        ");
        if (!$att_stmt) throw new Exception('Prepare failed: ' . $conn->error);
        
        $att_stmt->bind_param('iss', $employee_id, $pay_period_start, $pay_period_end);
        if (!$att_stmt->execute()) throw new Exception('Attendance query failed: ' . $att_stmt->error);
        
        $att_result = $att_stmt->get_result();
        $attendance = $att_result->fetch_assoc();
        $att_stmt->close();

        $days_worked = $attendance['days_worked'] ?? 0;
        $overtime_hours = $attendance['overtime_hours'] ?? 0;

        // Calculate pay
        $base_pay = $days_worked * $employee['daily_rate'];
        $overtime_pay = $overtime_hours * $employee['overtime_rate'];
        $total_pay = $base_pay + $overtime_pay - $deductions;

        // Insert payroll record
        $stmt = $conn->prepare("
            INSERT INTO payroll (employee_id, pay_period_start, pay_period_end, days_worked, 
                               overtime_hours, base_pay, overtime_pay, deductions, total_pay, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft')
        ");
        if (!$stmt) {
            error_log('Prepare failed: ' . $conn->error);
            throw new Exception('Prepare failed: ' . $conn->error);
        }
        
        $stmt->bind_param('issiddddd', $employee_id, $pay_period_start, $pay_period_end, $days_worked, 
                         $overtime_hours, $base_pay, $overtime_pay, $deductions, $total_pay);
        if (!$stmt->execute()) {
            error_log('Execute failed: ' . $stmt->error);
            throw new Exception('Failed to create payroll: ' . $stmt->error);
        }
        $stmt->close();

        $message = 'Payroll record created successfully from attendance data.';
        // Clear form
        $_POST = [];
    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log('Payroll create error: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Create Payroll - Flor de Liz</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <?php require_once __DIR__ . '/../../includes/sidebar_navigation.php'; ?>
    <div class="absolute w-full -ml-2 top-12">
        <div id="main-content" class="flex-1 px-4 sm:px-6 lg:px-8 py-12">
            <div class="max-w-2xl mx-auto">
                <h2 class="text-3xl font-bold mb-6">Create Payroll Record</h2>

                <?php if ($message): ?><div class="notification bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
                <?php if ($error): ?><div class="notification bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

                <div class="bg-white rounded-xl shadow p-8">
                    <p class="text-gray-600 mb-6 bg-blue-50 p-4 rounded">📋 Payroll will be automatically calculated from attendance records during the selected pay period.</p>
                    
                    <form method="post">
                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Employee *</label>
                            <select name="employee_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                                <option value="">Select Employee</option>
<?php foreach ($employees as $emp): ?>
                                <option value="<?php echo $emp['id']; ?>">
                                    <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) . ' (' . htmlspecialchars($emp['position']) . ')'; ?>
                                </option>
<?php endforeach; ?>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Pay Period Start *</label>
                                <input type="date" name="pay_period_start" required value="<?php echo $_POST['pay_period_start'] ?? ''; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Pay Period End *</label>
                                <input type="date" name="pay_period_end" required value="<?php echo $_POST['pay_period_end'] ?? ''; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Deductions (₱)</label>
                            <input type="number" name="deductions" min="0" step="0.01" value="<?php echo $_POST['deductions'] ?? '0'; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                        </div>

                        <div class="bg-gray-50 p-4 rounded-lg mb-6">
                            <p class="text-sm text-gray-600 mb-2"><strong>ℹ️ How it works:</strong></p>
                            <ul class="text-sm text-gray-600 list-disc list-inside space-y-1">
                                <li>Days worked = number of unique attendance dates in the pay period</li>
                                <li>Base pay = days worked × daily rate from employee profile</li>
                                <li>Overtime pay = overtime hours × overtime rate from attendance records</li>
                                <li>Total pay = base pay + overtime pay - deductions</li>
                            </ul>
                        </div>

                        <div class="flex gap-4">
                            <button type="submit" class="px-6 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 font-semibold">Generate Payroll</button>
                            <a href="../payroll.php" class="px-6 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 font-semibold">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
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
