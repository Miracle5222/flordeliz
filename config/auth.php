<?php
// Authentication handler
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

$conn = require_once __DIR__ . '/database.php';
if (!$conn) {
	http_response_code(500);
	exit('Database connection failed.');
}



// Only handle POST for login
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	// Not a login attempt; nothing to do
	$conn->close();
	exit();
}

$action = $_POST['action'] ?? '';
$username = trim((string)($_POST['username'] ?? ''));
$password = trim((string)($_POST['password'] ?? ''));

$errors = [];

// detect AJAX request
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
		 || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);

if ($username === '') {
	$errors[] = 'Username is required.';
}
if ($password === '') {
	$errors[] = 'Password is required.';
}

if (!empty($errors)) {
	if ($isAjax) {
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(['success' => false, 'errors' => $errors]);
		$conn->close();
		exit();
	}
	$_SESSION['errors'] = $errors;
	if ($action === 'login_staff') {
		header('Location: /login_staff.php');
	} else {
		header('Location: /login_admin.php');
	}
	$conn->close();
	exit();
}

// Determine role
$role = ($action === 'login_staff') ? 'staff' : 'admin';

// Query user
$stmt = $conn->prepare('SELECT id, username, password, role, full_name FROM users WHERE username = ? AND role = ? LIMIT 1');
if (!$stmt) {
	if ($isAjax) {
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(['success' => false, 'errors' => ['Database error']]);
		$conn->close();
		exit();
	}
	$conn->close();
	die('Database error');
}

$stmt->bind_param('ss', $username, $role);
$stmt->execute();

$user = null;
if (method_exists($stmt, 'get_result')) {
	$res = $stmt->get_result();
	if ($res && $res->num_rows === 1) {
		$user = $res->fetch_assoc();
	}
} else {
	$stmt->store_result();
	if ($stmt->num_rows === 1) {
		$stmt->bind_result($uid, $uname, $upwd, $urole, $ufull);
		$stmt->fetch();
		$user = ['id' => $uid, 'username' => $uname, 'password' => $upwd, 'role' => $urole, 'full_name' => $ufull];
	}
}

$response = ['success' => false, 'errors' => ['The username or password you entered is incorrect. Please try again.']];

if ($user && isset($user['password'])) {
	// Compare MD5 hashes (note: MD5 is not secure for production)
	if ($user['password'] === md5($password)) {
		// success
		$_SESSION['user_id'] = $user['id'];
		$_SESSION['username'] = $user['username'];
		$_SESSION['role'] = $user['role'];
		$_SESSION['full_name'] = $user['full_name'];
		$_SESSION['is_logged_in'] = true;
		unset($_SESSION['errors']);

		$response = ['success' => true, 'redirect' => ($role === 'staff') ? './staff/dashboard.php' : './admin/dashboard.php'];
	}
}

$stmt->close();

if ($isAjax) {
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode($response);
	$conn->close();
	exit();
}

// Non-AJAX fallback
if ($response['success']) {
	header('Location: ' . $response['redirect']);
	$conn->close();
	exit();
}

$_SESSION['errors'] = $response['errors'];
if ($action === 'login_staff') {
	header('Location: /login_staff.php');
} else {
	header('Location: /login_admin.php');
}

$conn->close();
exit();
?>
