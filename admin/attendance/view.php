<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['is_logged_in']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../login_admin.php'); exit();
}
$conn = require_once __DIR__ . '/../../config/database.php';

// Fetch employees for selector
$emps = [];
$res = $conn->query("SELECT id, first_name, last_name, position FROM employees WHERE is_active = 1 ORDER BY first_name, last_name");
while ($r = $res->fetch_assoc()) $emps[] = $r;

$employee_id = $_GET['employee_id'] ?? '';
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$attendance = [];
if ($employee_id) {
    $query = "SELECT id, attendance_date, clock_in, clock_out, hours_worked, overtime_hours, notes
              FROM attendance
              WHERE employee_id = ?";
    $params = [];
    if ($from && $to) {
        $query .= " AND attendance_date BETWEEN ? AND ?";
        $params = [$employee_id, $from, $to];
    } else {
        $params = [$employee_id];
    }
    $types = str_repeat('s', count($params));
    // adjust types: first param is int
    $types = 'i' . substr($types,1);
    $stmt = $conn->prepare($query);
    if ($stmt) {
        if (count($params) === 1) $stmt->bind_param('i', $params[0]);
        elseif (count($params) === 3) $stmt->bind_param('iss', $params[0], $params[1], $params[2]);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) $attendance[] = $row;
        $stmt->close();
    }
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Employee Attendance - Flor de Liz</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
</head>
<body class="bg-gray-50">
<?php require_once __DIR__ . '/../../includes/sidebar_navigation.php'; ?>
<div class="absolute w-full -ml-2 top-12">
    <div id="main-content" class="flex-1 px-4 sm:px-6 lg:px-8 py-12">
        <div class="max-w-5xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold">Employee Attendance</h2>
                <a href="../payroll.php" class="px-3 py-2 bg-gray-200 rounded">Back</a>
            </div>

            <div class="bg-white p-6 rounded shadow mb-6">
                <form method="get" class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-1">Employee</label>
                        <select name="employee_id" class="w-full border px-3 py-2 rounded">
                            <option value="">Select employee</option>
<?php foreach ($emps as $e): ?>
                            <option value="<?php echo $e['id']; ?>" <?php echo ($employee_id == $e['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($e['first_name'].' '.$e['last_name'].' ('.$e['position'].')'); ?></option>
<?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">From</label>
                        <input type="date" name="from" value="<?php echo htmlspecialchars($from); ?>" class="w-full border px-3 py-2 rounded">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">To</label>
                        <input type="date" name="to" value="<?php echo htmlspecialchars($to); ?>" class="w-full border px-3 py-2 rounded">
                    </div>
                    <div class="col-span-3 mt-2">
                        <button type="submit" class="px-4 py-2 bg-amber-600 text-white rounded">Show Attendance</button>
                    </div>
                </form>
            </div>

            <div class="bg-white p-6 rounded shadow">
                <table id="attendance-table" class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-600 border-b">
                            <th class="py-2">Date</th>
                            <th class="py-2">Clock In</th>
                            <th class="py-2">Clock Out</th>
                            <th class="py-2">Hours Worked</th>
                            <th class="py-2">Overtime Hours</th>
                            <th class="py-2">Notes</th>
                        </tr>
                    </thead>
                    <tbody>
<?php foreach ($attendance as $a): ?>
                        <tr class="border-b">
                            <td class="py-2"><?php echo htmlspecialchars($a['attendance_date']); ?></td>
                            <td class="py-2"><?php echo htmlspecialchars($a['clock_in']); ?></td>
                            <td class="py-2"><?php echo htmlspecialchars($a['clock_out']); ?></td>
                            <td class="py-2"><?php echo htmlspecialchars($a['hours_worked']); ?></td>
                            <td class="py-2"><?php echo htmlspecialchars($a['overtime_hours']); ?></td>
                            <td class="py-2"><?php echo htmlspecialchars($a['notes']); ?></td>
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
$(function(){
    $('#attendance-table').DataTable({
        order:[[0,'desc']],
        pageLength:25
    });
});
</script>
</body>
</html>
