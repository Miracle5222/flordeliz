<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['is_logged_in']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login_admin.php'); exit();
}
$conn = require_once __DIR__ . '/../config/database.php';

// Fetch all attendance records with employee info
$query = "SELECT a.id, a.attendance_date, a.clock_in, a.clock_out, a.hours_worked, a.overtime_hours, a.notes,
                 e.first_name, e.last_name, e.position
          FROM attendance a
          JOIN employees e ON a.employee_id = e.id
          ORDER BY a.attendance_date DESC, e.first_name, e.last_name";
$result = $conn->query($query);
$records = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Attendance - Flor de Liz</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
</head>
<body class="bg-gray-50">
    <?php require_once __DIR__ . '/../includes/sidebar_navigation.php'; ?>
    <div class="absolute w-full -ml-2 top-12">
        <div id="main-content" class="flex-1 px-4 sm:px-6 lg:px-8 py-12">
            <div class="max-w-6xl mx-auto">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-3xl font-bold">All Attendance Records</h2>
                    <a href="attendance/view.php" class="px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700">Employee Attendance</a>
                </div>

                <div class="bg-white rounded-xl shadow p-6">
                    <table id="attendance-table" class="w-full text-sm display">
                        <thead>
                            <tr class="text-left text-gray-600 border-b">
                                <th class="py-2">Employee Name</th>
                                <th class="py-2">Position</th>
                                <th class="py-2">Date</th>
                                <th class="py-2">Clock In</th>
                                <th class="py-2">Clock Out</th>
                                <th class="py-2">Hours Worked</th>
                                <th class="py-2">Overtime Hrs</th>
                                <th class="py-2">Notes</th>
                            </tr>
                        </thead>
                        <tbody>
<?php foreach ($records as $r): ?>
                            <tr class="border-b">
                                <td class="py-2"><?php echo htmlspecialchars($r['first_name'] . ' ' . $r['last_name']); ?></td>
                                <td class="py-2"><?php echo htmlspecialchars($r['position']); ?></td>
                                <td class="py-2"><?php echo htmlspecialchars($r['attendance_date']); ?></td>
                                <td class="py-2"><?php echo htmlspecialchars($r['clock_in'] ?? ''); ?></td>
                                <td class="py-2"><?php echo htmlspecialchars($r['clock_out'] ?? ''); ?></td>
                                <td class="py-2 text-center"><?php echo htmlspecialchars($r['hours_worked'] ?? ''); ?></td>
                                <td class="py-2 text-center"><?php echo htmlspecialchars($r['overtime_hours'] ?? ''); ?></td>
                                <td class="py-2 text-sm"><?php echo htmlspecialchars(substr($r['notes'] ?? '', 0, 50)); ?></td>
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
            $('#attendance-table').DataTable({
                order: [[2, 'desc']],
                pageLength: 25,
                columnDefs: []
            });
        })();
    </script>
</body>
</html>
