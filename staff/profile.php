<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['is_logged_in']) || $_SESSION['role'] !== 'staff') {
    header('Location: ../login_staff.php'); exit();
}

$conn = require_once __DIR__ . '/../config/database.php';

// Ensure users table exists
$conn->query("CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    role ENUM('staff', 'admin') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header('Location: ../login_staff.php'); exit();
}

// Fetch user info
$stmt = $conn->prepare('SELECT id, username, full_name, email, role FROM users WHERE id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
$stmt->close();

if (!$user) {
    header('Location: ../login_staff.php'); exit();
}

$message = ''; $error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    if (!$full_name) {
        $error = 'Full name is required.';
    } else {
        $stmt = $conn->prepare('UPDATE users SET full_name = ?, email = ? WHERE id = ?');
        $stmt->bind_param('ssi', $full_name, $email, $user_id);
        if ($stmt->execute()) {
            $message = 'Profile updated successfully.';
            $_SESSION['full_name'] = $full_name;
            $user['full_name'] = $full_name;
            $user['email'] = $email;
        } else {
            $error = 'Failed to update profile.';
        }
        $stmt->close();
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (!$current_password || !$new_password || !$confirm_password) {
        $error = 'All password fields are required.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New password and confirm password do not match.';
    } elseif (strlen($new_password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        // Verify current password
        $stmt = $conn->prepare('SELECT password FROM users WHERE id = ?');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $pwd_row = $res->fetch_assoc();
        $stmt->close();
        
        $stored_hash = $pwd_row['password'] ?? '';
        $verified = false;

        if ($stored_hash && password_verify($current_password, $stored_hash)) {
            // modern password hash verified
            $verified = true;
        } elseif ($stored_hash && strlen($stored_hash) === 32 && md5($current_password) === $stored_hash) {
            // legacy MD5 matched — migrate to password_hash()
            $migrated_hash = password_hash($current_password, PASSWORD_DEFAULT);
            $migrate_stmt = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
            if ($migrate_stmt) {
                $migrate_stmt->bind_param('si', $migrated_hash, $user_id);
                $migrate_stmt->execute();
                $migrate_stmt->close();
            }
            $verified = true;
        }

        if (!$verified) {
            $error = 'Current password is incorrect.';
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
            $stmt->bind_param('si', $hashed_password, $user_id);
            if ($stmt->execute()) {
                $message = 'Password changed successfully.';
            } else {
                $error = 'Failed to change password.';
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>My Profile</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <?php require_once __DIR__ . '/../includes/sidebar_navigation.php'; ?>
    <div class="absolute w-full -ml-2 top-12">
        <div id="main-content" class="flex-1 px-4 sm:px-6 lg:px-8 py-12">
            <div class="max-w-2xl mx-auto">
                <h2 class="text-3xl font-bold mb-6">My Profile</h2>

                <?php if ($message): ?><div class="bg-green-50 border border-green-200 p-3 mb-4 rounded text-green-800"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
                <?php if ($error): ?><div class="bg-red-50 border border-red-200 p-3 mb-4 rounded text-red-800"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

                <!-- Profile Information Section -->
                <div class="bg-white rounded-xl shadow p-6 mb-6">
                    <h3 class="text-xl font-bold mb-4">Profile Information</h3>
                    <form method="post">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="mb-4">
                            <label class="block text-sm font-semibold mb-2">Username</label>
                            <p class="text-gray-700 px-3 py-2 bg-gray-100 rounded"><?php echo htmlspecialchars($user['username']); ?></p>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-semibold mb-2">Full Name</label>
                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" class="w-full border px-3 py-2 rounded focus:outline-none focus:ring-2 focus:ring-teal-500" required>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-semibold mb-2">Email</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" class="w-full border px-3 py-2 rounded focus:outline-none focus:ring-2 focus:ring-teal-500">
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-semibold mb-2">Role</label>
                            <p class="text-gray-700 px-3 py-2 bg-gray-100 rounded capitalize"><?php echo htmlspecialchars($user['role']); ?></p>
                        </div>
                        <button type="submit" class="px-4 py-2 bg-teal-600 text-white rounded hover:bg-teal-700">Save Changes</button>
                    </form>
                </div>

                <!-- Change Password Section -->
                <div class="bg-white rounded-xl shadow p-6">
                    <h3 class="text-xl font-bold mb-4">Change Password</h3>
                    <form method="post">
                        <input type="hidden" name="action" value="change_password">
                        <div class="mb-4">
                            <label class="block text-sm font-semibold mb-2">Current Password</label>
                            <input type="password" name="current_password" class="w-full border px-3 py-2 rounded focus:outline-none focus:ring-2 focus:ring-teal-500" required>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-semibold mb-2">New Password</label>
                            <input type="password" name="new_password" class="w-full border px-3 py-2 rounded focus:outline-none focus:ring-2 focus:ring-teal-500" required>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-semibold mb-2">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="w-full border px-3 py-2 rounded focus:outline-none focus:ring-2 focus:ring-teal-500" required>
                        </div>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Change Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
