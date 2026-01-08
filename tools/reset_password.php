<?php
// CLI script to reset a user's password using password_hash()
// Usage: php tools/reset_password.php <username|role> <new_password>

if (php_sapi_name() !== 'cli') {
    echo "This script must be run from the command line.\n";
    exit(1);
}

if ($argc < 3) {
    echo "Usage: php tools/reset_password.php <username|role> <new_password>\n";
    echo "Examples:\n";
    echo "  php tools/reset_password.php staff Password123!   # set by username 'staff'\n";
    echo "  php tools/reset_password.php admin Password123!   # set by username 'admin'\n";
    exit(1);
}

$identifier = $argv[1];
$new_password = $argv[2];

require_once __DIR__ . '/../config/database.php';
if (!$conn) {
    echo "Database connection failed.\n";
    exit(1);
}

$hashed = password_hash($new_password, PASSWORD_DEFAULT);

// First try updating by username
$stmt = $conn->prepare('UPDATE users SET password = ? WHERE username = ?');
if ($stmt) {
    $stmt->bind_param('ss', $hashed, $identifier);
    $stmt->execute();
    if ($stmt->affected_rows > 0) {
        echo "Password updated for username '{$identifier}'.\n";
        $stmt->close();
        $conn->close();
        exit(0);
    }
    $stmt->close();
}

// If no username matched, try updating by role (staff/admin)
$stmt2 = $conn->prepare('UPDATE users SET password = ? WHERE role = ?');
if ($stmt2) {
    $stmt2->bind_param('ss', $hashed, $identifier);
    $stmt2->execute();
    if ($stmt2->affected_rows > 0) {
        echo "Password updated for role '{$identifier}' (all users with that role updated).\n";
        $stmt2->close();
        $conn->close();
        exit(0);
    }
    $stmt2->close();
}

echo "No matching username or role found for '{$identifier}'.\n";
$conn->close();
exit(1);
