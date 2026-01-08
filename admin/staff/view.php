<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['is_logged_in']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../login_admin.php'); exit();
}
$conn = require_once __DIR__ . '/../../config/database.php';

// Fetch all staff members
$query = "SELECT e.id, u.id as user_id, e.first_name, e.last_name, u.email, e.phone, e.position, e.hire_date, e.daily_rate, e.overtime_rate, e.is_active, u.username 
          FROM employees e 
          LEFT JOIN users u ON e.user_id = u.id 
          ORDER BY e.first_name, e.last_name";
$result = $conn->query($query);
$staff = [];
while ($row = $result->fetch_assoc()) {
    $staff[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Staff - Flor de Liz</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
</head>
<body class="bg-gray-50">
    <?php require_once __DIR__ . '/../../includes/sidebar_navigation.php'; ?>
    <div class="absolute w-full -ml-2 top-12">
        <div id="main-content" class="flex-1 px-4 sm:px-6 lg:px-8 py-12">
            <div class="max-w-6xl mx-auto">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-3xl font-bold">Staff Members</h2>
                    <a href="add.php" class="px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700">Add Staff</a>
                </div>

                <div class="bg-white rounded-xl shadow p-6">
                    <table id="staff-table" class="w-full text-sm display">
                        <thead>
                            <tr class="text-left text-gray-600 border-b">
                                <th class="py-2">Name</th>
                                <th class="py-2">Username</th>
                                <th class="py-2">Position</th>
                                <th class="py-2">Email</th>
                                <th class="py-2">Phone</th>
                                <th class="py-2">Daily Rate (₱)</th>
                                <th class="py-2">OT Rate (₱/hr)</th>
                                <th class="py-2">Status</th>
                                <th class="py-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
<?php foreach ($staff as $member): ?>
                            <tr class="border-b">
                                <td class="py-2"><?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?></td>
                                <td class="py-2"><?php echo htmlspecialchars($member['username'] ?? ''); ?></td>
                                <td class="py-2"><?php echo htmlspecialchars($member['position'] ?? ''); ?></td>
                                <td class="py-2"><?php echo htmlspecialchars($member['email'] ?? ''); ?></td>
                                <td class="py-2"><?php echo htmlspecialchars($member['phone'] ?? ''); ?></td>
                                <td class="py-2">₱<?php echo number_format($member['daily_rate'] ?? 0, 2); ?></td>
                                <td class="py-2">₱<?php echo number_format($member['overtime_rate'] ?? 0, 2); ?></td>
                                <td class="py-2">
                                    <span class="px-2 py-1 rounded text-sm <?php echo $member['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo $member['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td class="py-2 space-x-2">
                                    <a href="edit.php?id=<?php echo $member['id']; ?>" class="text-blue-600 hover:underline">Edit</a>
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
            $('#staff-table').DataTable({
                order: [[0, 'asc']],
                pageLength: 25,
                columnDefs: [
                    { targets: 8, orderable: false }
                ],
                language: { search: "Quick search:" }
            });
        })();
    </script>
</body>
</html>
