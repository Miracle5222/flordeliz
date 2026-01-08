<?php 
session_start();
$role = $_SESSION['role'] ?? '';
session_unset();
session_destroy();

// Redirect to appropriate login page based on role
if ($role === 'admin') {
    header('Location: login_admin.php');
} else {
    header('Location: login_staff.php');
}
exit();
?>