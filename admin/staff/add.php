<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['is_logged_in']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../login_admin.php'); exit();
}
$conn = require_once __DIR__ . '/../../config/database.php';

// Ensure users table exists (staff have login credentials)
$conn->query("CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    role ENUM('staff', 'admin') NOT NULL,
    full_name VARCHAR(255),
    phone VARCHAR(20),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Ensure employees table exists
$conn->query("CREATE TABLE IF NOT EXISTS employees (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    position VARCHAR(100),
    hire_date DATE,
    daily_rate DECIMAL(10,2) DEFAULT 1730.00,
    overtime_rate DECIMAL(10,2) DEFAULT 80.00,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)");

$message = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $full_name = $first_name . ' ' . $last_name;
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $hire_date = $_POST['hire_date'] ?? '';
    $daily_rate = floatval($_POST['daily_rate'] ?? 1730.00);
    $overtime_rate = floatval($_POST['overtime_rate'] ?? 80.00);

    if (!$first_name || !$last_name || !$username || !$password) {
        $error = 'First name, last name, username, and password are required.';
    } elseif ($password !== $confirm_password) {
        $error = 'Password and confirm password do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        $conn->begin_transaction();
        try {
            // Create user account
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('INSERT INTO users (username, password, email, role, full_name, phone, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)');
            if (!$stmt) throw new Exception('Prepare failed: ' . $conn->error);
            $role = 'staff';
            $stmt->bind_param('ssssss', $username, $hashed_password, $email, $role, $full_name, $phone);
            if (!$stmt->execute()) throw new Exception('Create user failed: ' . $stmt->error);
            $user_id = $stmt->insert_id;
            $stmt->close();

            // Create employee record
            $stmt = $conn->prepare('INSERT INTO employees (user_id, first_name, last_name, email, phone, position, hire_date, daily_rate, overtime_rate, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)');
            if (!$stmt) throw new Exception('Prepare failed: ' . $conn->error);
            $stmt->bind_param('isssssddd', $user_id, $first_name, $last_name, $email, $phone, $position, $hire_date, $daily_rate, $overtime_rate);
            if (!$stmt->execute()) throw new Exception('Create employee failed: ' . $stmt->error);
            $stmt->close();

            $conn->commit();
            $message = 'Staff member added successfully.';
        } catch (Exception $e) {
            $conn->rollback();
            error_log('[admin/staff/add] ' . $e->getMessage());
            $error = 'Failed to add staff: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Add Staff - Flor de Liz</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <?php require_once __DIR__ . '/../../includes/sidebar_navigation.php'; ?>
    <div class="absolute w-full -ml-2 top-12">
        <div id="main-content" class="flex-1 px-4 sm:px-6 lg:px-8 py-12">
            <div class="max-w-2xl mx-auto">
                <h2 class="text-3xl font-bold mb-6">Add Staff Member</h2>
                
                <?php if ($message): ?><div class="notification bg-green-50 border border-green-200 p-3 mb-4 rounded text-green-800"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
                <?php if ($error): ?><div class="notification bg-red-50 border border-red-200 p-3 mb-4 rounded text-red-800"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

                <div class="bg-white rounded-xl shadow p-6">
                    <form method="post">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-semibold mb-2">First Name</label>
                                <input type="text" name="first_name" required class="w-full border px-3 py-2 rounded focus:outline-none focus:ring-2 focus:ring-amber-500">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold mb-2">Last Name</label>
                                <input type="text" name="last_name" required class="w-full border px-3 py-2 rounded focus:outline-none focus:ring-2 focus:ring-amber-500">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-semibold mb-2">Email</label>
                                <input type="email" name="email" class="w-full border px-3 py-2 rounded focus:outline-none focus:ring-2 focus:ring-amber-500">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold mb-2">Phone</label>
                                <input type="tel" name="phone" class="w-full border px-3 py-2 rounded focus:outline-none focus:ring-2 focus:ring-amber-500">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-semibold mb-2">Position</label>
                            <input type="text" name="position" placeholder="e.g., Printer, Operator" class="w-full border px-3 py-2 rounded focus:outline-none focus:ring-2 focus:ring-amber-500">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-semibold mb-2">Hire Date</label>
                                <input type="date" name="hire_date" class="w-full border px-3 py-2 rounded focus:outline-none focus:ring-2 focus:ring-amber-500">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold mb-2">Username (for login)</label>
                                <input type="text" name="username" required class="w-full border px-3 py-2 rounded focus:outline-none focus:ring-2 focus:ring-amber-500">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-semibold mb-2">Password</label>
                                <input type="password" name="password" value="flordeliz123" class="w-full border px-3 py-2 rounded focus:outline-none focus:ring-2 focus:ring-amber-500">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold mb-2">Confirm Password</label>
                                <input type="password" name="confirm_password" value="flordeliz123" class="w-full border px-3 py-2 rounded focus:outline-none focus:ring-2 focus:ring-amber-500">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div>
                                <label class="block text-sm font-semibold mb-2">Daily Rate (₱)</label>
                                <input type="number" name="daily_rate" value="1730.00" step="0.01" min="0" class="w-full border px-3 py-2 rounded focus:outline-none focus:ring-2 focus:ring-amber-500">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold mb-2">Overtime Rate per Hour (₱)</label>
                                <input type="number" name="overtime_rate" value="80.00" step="0.01" min="0" class="w-full border px-3 py-2 rounded focus:outline-none focus:ring-2 focus:ring-amber-500">
                            </div>
                        </div>

                        <div class="flex gap-4">
                            <button type="submit" class="px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700">Add Staff</button>
                            <a href="view.php" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">View Staff</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Auto-dismiss notifications after 3 seconds
        document.querySelectorAll('.notification').forEach(function(el){
            setTimeout(function(){
                el.style.opacity = '0';
                el.style.transition = 'opacity 0.5s';
                setTimeout(function(){ el.remove(); }, 500);
            }, 3000);
        });
    </script>
</body>
</html>
