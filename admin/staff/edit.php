<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['is_logged_in']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../login_admin.php'); exit();
}
$conn = require_once __DIR__ . '/../../config/database.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: view.php'); exit();
}

// Fetch staff member details
$stmt = $conn->prepare("
    SELECT e.id, e.first_name, e.last_name, e.email, e.phone, e.position, e.hire_date, 
           e.daily_rate, e.overtime_rate, e.is_active, u.username, u.id as user_id 
    FROM employees e 
    LEFT JOIN users u ON e.user_id = u.id 
    WHERE e.id = ?
");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$employee = $result->fetch_assoc();
$stmt->close();

if (!$employee) {
    header('Location: view.php'); exit();
}

$message = '';
if ($_POST) {
    try {
        $first_name = $_POST['first_name'] ?? '';
        $last_name = $_POST['last_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $position = $_POST['position'] ?? '';
        $hire_date = $_POST['hire_date'] ?? '';
        $daily_rate = $_POST['daily_rate'] ?? 0;
        $overtime_rate = $_POST['overtime_rate'] ?? 0;
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (!$first_name || !$last_name) {
            throw new Exception('First and last name are required.');
        }

        // Update employee record
        $stmt = $conn->prepare("
            UPDATE employees 
            SET first_name = ?, last_name = ?, email = ?, phone = ?, position = ?, 
                hire_date = ?, daily_rate = ?, overtime_rate = ?, is_active = ? 
            WHERE id = ?
        ");
        $stmt->bind_param('ssssssddii', $first_name, $last_name, $email, $phone, $position, 
                         $hire_date, $daily_rate, $overtime_rate, $is_active, $id);
        if (!$stmt->execute()) {
            throw new Exception('Failed to update employee: ' . $stmt->error);
        }
        $stmt->close();

        // Update user's full_name and email
        if ($employee['user_id']) {
            $full_name = $first_name . ' ' . $last_name;
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
            $stmt->bind_param('ssi', $full_name, $email, $employee['user_id']);
            if (!$stmt->execute()) {
                throw new Exception('Failed to update user: ' . $stmt->error);
            }
            $stmt->close();
        }

        $message = '<div class="notification bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">Staff member updated successfully.</div>';
        // Refresh employee data
        $stmt = $conn->prepare("SELECT e.id, e.first_name, e.last_name, e.email, e.phone, e.position, e.hire_date, 
               e.daily_rate, e.overtime_rate, e.is_active, u.username, u.id as user_id FROM employees e 
               LEFT JOIN users u ON e.user_id = u.id WHERE e.id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $employee = $result->fetch_assoc();
        $stmt->close();
    } catch (Exception $e) {
        $message = '<div class="notification bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">' . htmlspecialchars($e->getMessage()) . '</div>';
        error_log('Error updating staff: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Edit Staff - Flor de Liz</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <?php require_once __DIR__ . '/../../includes/sidebar_navigation.php'; ?>
    <div class="absolute w-full -ml-2 top-12">
        <div id="main-content" class="flex-1 px-4 sm:px-6 lg:px-8 py-12">
            <div class="max-w-2xl mx-auto">
                <h2 class="text-3xl font-bold mb-6">Edit Staff Member</h2>

                <?php echo $message; ?>

                <div class="bg-white rounded-xl shadow p-8">
                    <form method="post">
                        <div class="grid grid-cols-2 gap-6 mb-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">First Name *</label>
                                <input type="text" name="first_name" value="<?php echo htmlspecialchars($employee['first_name']); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Last Name *</label>
                                <input type="text" name="last_name" value="<?php echo htmlspecialchars($employee['last_name']); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-6 mb-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($employee['email'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Phone</label>
                                <input type="tel" name="phone" value="<?php echo htmlspecialchars($employee['phone'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-6 mb-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Position</label>
                                <input type="text" name="position" value="<?php echo htmlspecialchars($employee['position'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500" placeholder="e.g., Printer, Operator">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Hire Date</label>
                                <input type="date" name="hire_date" value="<?php echo htmlspecialchars($employee['hire_date'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-6 mb-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Daily Rate (₱)</label>
                                <input type="number" name="daily_rate" value="<?php echo htmlspecialchars($employee['daily_rate']); ?>" step="0.01" min="0" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Overtime Rate (₱/hr)</label>
                                <input type="number" name="overtime_rate" value="<?php echo htmlspecialchars($employee['overtime_rate']); ?>" step="0.01" min="0" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                            </div>
                        </div>

                        <div class="mb-6">
                            <label class="flex items-center">
                                <input type="checkbox" name="is_active" <?php echo $employee['is_active'] ? 'checked' : ''; ?> class="w-4 h-4 text-amber-600">
                                <span class="ml-2 text-sm font-semibold text-gray-700">Active</span>
                            </label>
                        </div>

                        <div class="border-t pt-4">
                            <p class="text-sm text-gray-600 mb-4">
                                <strong>Username:</strong> <?php echo htmlspecialchars($employee['username']); ?><br>
                                <em>To change password, staff member must use the password change form in their profile.</em>
                            </p>
                        </div>

                        <div class="flex gap-4 mt-6">
                            <button type="submit" class="px-6 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 font-semibold">Update Staff</button>
                            <a href="view.php" class="px-6 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 font-semibold">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        const notifications = document.querySelectorAll('.notification');
        notifications.forEach(notif => {
            setTimeout(() => {
                notif.style.opacity = '0';
                notif.style.transition = 'opacity 0.3s ease';
                setTimeout(() => notif.remove(), 300);
            }, 3000);
        });
    </script>
</body>
</html>
