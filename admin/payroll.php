<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['is_logged_in']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../login_admin.php'); exit();
}
$conn = require_once __DIR__ . '/../config/database.php';

// Create payroll table if it doesn't exist (do not drop existing data)
$create_table = "
    CREATE TABLE IF NOT EXISTS payroll (
        id INT PRIMARY KEY AUTO_INCREMENT,
        employee_id INT NOT NULL,
        pay_period_start DATE NOT NULL,
        pay_period_end DATE NOT NULL,
        days_worked INT DEFAULT 0,
        overtime_hours DECIMAL(5,2) DEFAULT 0,
        base_pay DECIMAL(10,2) DEFAULT 0,
        overtime_pay DECIMAL(10,2) DEFAULT 0,
        deductions DECIMAL(10,2) DEFAULT 0,
        total_pay DECIMAL(10,2) DEFAULT 0,
        status ENUM('draft', 'approved', 'paid') DEFAULT 'draft',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (employee_id) REFERENCES employees(id),
        UNIQUE KEY unique_payroll (employee_id, pay_period_start, pay_period_end)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";
if (!$conn->query($create_table)) {
    error_log('Payroll table creation error: ' . $conn->error);
}

// Fetch all payroll records with employee info
$query = "SELECT p.*, e.first_name, e.last_name, e.position, e.daily_rate, e.overtime_rate, u.email
          FROM payroll p
          JOIN employees e ON p.employee_id = e.id
          LEFT JOIN users u ON e.user_id = u.id
          ORDER BY p.pay_period_start DESC, e.first_name, e.last_name";
$result = $conn->query($query);
$payrolls = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $payrolls[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Payroll - Flor de Liz</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
</head>
<body class="bg-gray-50">
    <?php require_once __DIR__ . '/../includes/sidebar_navigation.php'; ?>
    <div class="absolute w-full -ml-2 top-12">
        <div id="main-content" class="flex-1 px-4 sm:px-6 lg:px-8 py-12">
            <div class="max-w-7xl mx-auto">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-3xl font-bold">Payroll Management</h2>
                    <a href="payroll/create.php" class="px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700">Create Payroll</a>
                </div>

                <div class="bg-white rounded-xl shadow p-6">
                    <table id="payroll-table" class="w-full text-sm display">
                        <thead>
                            <tr class="text-left text-gray-600 border-b">
                                <th class="py-2">Employee Name</th>
                                <th class="py-2">Pay Period</th>
                                <th class="py-2">Days Worked</th>
                                <th class="py-2">Base Pay (₱)</th>
                                <th class="py-2">Overtime (hrs)</th>
                                <th class="py-2">OT Pay (₱)</th>
                                <th class="py-2">Deductions (₱)</th>
                                <th class="py-2">Total Pay (₱)</th>
                                <th class="py-2">Status</th>
                                <th class="py-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
<?php foreach ($payrolls as $p): ?>
                            <tr class="border-b">
                                <td class="py-2"><?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?></td>
                                <td class="py-2"><?php echo date('M d, Y', strtotime($p['pay_period_start'])) . ' - ' . date('M d, Y', strtotime($p['pay_period_end'])); ?></td>
                                <td class="py-2 text-center"><?php echo $p['days_worked']; ?></td>
                                <td class="py-2 text-right">₱<?php echo number_format($p['base_pay'], 2); ?></td>
                                <td class="py-2 text-center"><?php echo number_format($p['overtime_hours'], 2); ?></td>
                                <td class="py-2 text-right">₱<?php echo number_format($p['overtime_pay'], 2); ?></td>
                                <td class="py-2 text-right">₱<?php echo number_format($p['deductions'], 2); ?></td>
                                <td class="py-2 text-right font-bold">₱<?php echo number_format($p['total_pay'], 2); ?></td>
                                <td class="py-2">
                                    <span class="px-2 py-1 rounded text-sm <?php 
                                        if ($p['status'] === 'paid') echo 'bg-green-100 text-green-800';
                                        elseif ($p['status'] === 'approved') echo 'bg-blue-100 text-blue-800';
                                        else echo 'bg-yellow-100 text-yellow-800';
                                    ?>">
                                        <?php echo ucfirst($p['status']); ?>
                                    </span>
                                </td>
                                <td class="py-2 space-x-2">
                                    <a href="payroll/view.php?id=<?php echo $p['id']; ?>" class="text-blue-600 hover:underline text-sm">View</a>
                                    <a href="payroll/edit.php?id=<?php echo $p['id']; ?>" class="text-amber-600 hover:underline text-sm">Edit</a>
                                </td>
                            </tr>
<?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>
        (function(){
            $('#payroll-table').DataTable({
                order: [[1, 'desc']],
                pageLength: 25,
                columnDefs: [
                    { targets: 9, orderable: false }
                ]
            });
        })();
    </script>
</body>
</html>
