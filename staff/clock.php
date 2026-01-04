<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Detect AJAX early for auth handling
$isAjaxRequest = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

// Check if user is logged in and is staff
if (empty($_SESSION['is_logged_in']) || $_SESSION['role'] !== 'staff') {
    if ($isAjaxRequest) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['message' => 'Not authenticated', 'message_type' => 'error']);
        exit();
    }
    header('Location: ../login_staff.php');
    exit();
}

// Get database connection
$conn = require_once __DIR__ . '/../config/database.php';

// Get employee ID from users table
$stmt = $conn->prepare('SELECT id FROM employees WHERE user_id = ? LIMIT 1');
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$res = $stmt->get_result();
$employee = $res->fetch_assoc();
$employee_id = $employee['id'] ?? null;
$stmt->close();

// If no employee record exists for this user, create one automatically
if (!$employee_id) {
    $full_name = $_SESSION['full_name'] ?? null;
    $first = null; $last = null;
    if ($full_name) {
        $parts = preg_split('/\s+/', trim($full_name));
        $first = $parts[0] ?? null;
        $last = isset($parts[1]) ? implode(' ', array_slice($parts,1)) : null;
    }
    $stmt = $conn->prepare('INSERT INTO employees (user_id, first_name, last_name, email, created_at) VALUES (?, ?, ?, ?, NOW())');
    $email = $_SESSION['email'] ?? null;
    $stmt->bind_param('isss', $_SESSION['user_id'], $first, $last, $email);
    if ($stmt->execute()) {
        $employee_id = $stmt->insert_id;
    }
    $stmt->close();
}

$message = '';
$message_type = '';
$today_attendance = null;

// Handle clock in/out
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $employee_id) {
    $action = $_POST['action'] ?? '';

    if ($action === 'clock_in') {
        // Check if already clocked in today
        $stmt = $conn->prepare('SELECT id, clock_in FROM attendance WHERE employee_id = ? AND attendance_date = CURDATE()');
        $stmt->bind_param('i', $employee_id);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($existing && $existing['clock_in']) {
            $message = 'You have already clocked in today.';
            $message_type = 'warning';
        } else {
            // Create or update attendance record
            if ($existing) {
                $stmt = $conn->prepare('UPDATE attendance SET clock_in = NOW() WHERE id = ?');
                $stmt->bind_param('i', $existing['id']);
            } else {
                $stmt = $conn->prepare('INSERT INTO attendance (employee_id, clock_in, attendance_date) VALUES (?, NOW(), CURDATE())');
                $stmt->bind_param('i', $employee_id);
            }
            if ($stmt->execute()) {
                $message = 'Clocked in successfully ';
                $message_type = 'success';
            } else {
                $message = 'Error clocking in. Please try again.';
                $message_type = 'error';
            }
            $stmt->close();
        }
    } elseif ($action === 'clock_out') {
        // Get today's attendance
        $stmt = $conn->prepare('SELECT id, clock_in FROM attendance WHERE employee_id = ? AND attendance_date = CURDATE() AND clock_in IS NOT NULL');
        $stmt->bind_param('i', $employee_id);
        $stmt->execute();
        $record = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$record) {
            $message = 'You have not clocked in today.';
            $message_type = 'warning';
        } else {
            // Update clock out time and calculate hours
            $stmt = $conn->prepare('UPDATE attendance SET clock_out = NOW(), hours_worked = TIMESTAMPDIFF(HOUR, clock_in, NOW()) WHERE id = ?');
            $stmt->bind_param('i', $record['id']);
            if ($stmt->execute()) {
                $message = 'Clocked out successfully';
                $message_type = 'success';
            } else {
                $message = 'Error clocking out. Please try again.';
                $message_type = 'error';
            }
            $stmt->close();
        }
    }

    // If request expects JSON (AJAX), respond with JSON and stop further HTML output
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

    if ($isAjax) {
        // Refresh today's attendance to include latest changes
        $today = null;
        if ($employee_id) {
            $stmt = $conn->prepare('SELECT id, clock_in, clock_out, hours_worked FROM attendance WHERE employee_id = ? AND attendance_date = CURDATE()');
            $stmt->bind_param('i', $employee_id);
            $stmt->execute();
            $today = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            // Add server-formatted strings to avoid client-side parsing timezone issues
            if ($today) {
                $today['clock_in_formatted'] = $today['clock_in'] ? date('h:i A', strtotime($today['clock_in'])) : null;
                $today['clock_out_formatted'] = $today['clock_out'] ? date('h:i A', strtotime($today['clock_out'])) : null;
                $today['hours_worked_formatted'] = $today['hours_worked'] !== null ? number_format($today['hours_worked'], 2) : null;
                // provide availability flags for client
                $today['can_clock_in'] = false;
                $today['can_clock_out'] = false;
                if (empty($today['clock_in'])) {
                    $today['can_clock_in'] = true;
                } elseif (!empty($today['clock_in']) && empty($today['clock_out'])) {
                    $today['can_clock_out'] = true;
                }
            }
        }

        $response = [
            'message' => $message,
            'message_type' => $message_type,
            'today' => $today
        ];
        header('Content-Type: application/json');
        echo json_encode($response);
        $conn->close();
        exit();
    }
}

// Get today's attendance record
if ($employee_id) {
    $stmt = $conn->prepare('SELECT id, clock_in, clock_out, hours_worked FROM attendance WHERE employee_id = ? AND attendance_date = CURDATE()');
    $stmt->bind_param('i', $employee_id);
    $stmt->execute();
    $today_attendance = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Determine button availability (respect 6:00 AM lock if previous day closed)
$can_clock_in = true;
$can_clock_out = false;
$now = new DateTime('now');
$hour = (int)$now->format('H');

// If yesterday had a clock_out and current time is before 06:00, keep buttons disabled
if ($employee_id) {
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $stmt = $conn->prepare('SELECT clock_out FROM attendance WHERE employee_id = ? AND attendance_date = ? LIMIT 1');
    $stmt->bind_param('is', $employee_id, $yesterday);
    $stmt->execute();
    $yrow = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($yrow && !empty($yrow['clock_out']) && $hour < 6) {
        $can_clock_in = false;
        $can_clock_out = false;
    }
}

if ($today_attendance) {
    if (empty($today_attendance['clock_in'])) {
        $can_clock_in = true;
        $can_clock_out = false;
    } elseif (!empty($today_attendance['clock_in']) && empty($today_attendance['clock_out'])) {
        $can_clock_in = false;
        $can_clock_out = true;
    } else {
        // both clock_in and clock_out present
        $can_clock_in = false;
        $can_clock_out = false;
    }
}

// Get recent attendance history
$recent_records = [];
if ($employee_id) {
    $stmt = $conn->prepare('SELECT attendance_date, clock_in, clock_out, hours_worked FROM attendance WHERE employee_id = ? ORDER BY attendance_date DESC LIMIT 30');
    $stmt->bind_param('i', $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $recent_records[] = $row;
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clock In/Out - Flor de Liz</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">

    <!-- Include Navigation and Sidebar -->
    <?php require_once __DIR__ . '/../includes/sidebar_navigation.php'; ?>

    <!-- Main Content Area -->
   <div class="absolute w-full -ml-2  top-12">
            <div id="main-content" class="flex-1 px-4 sm:px-6 lg:px-4 py-12 ">
        <div class="max-w-4xl mx-auto">
            <div class="mb-8">
                <h2 class="text-4xl font-bold text-gray-900">Clock In/Out</h2>
                <p class="text-gray-600 mt-2">Record your attendance and work hours</p>
            </div>

            <!-- Message Alert -->
                <?php if ($message): ?>
                    <?php 
                        $alertClass = 'bg-blue-50 border border-blue-200 text-blue-700';
                        if ($message_type === 'success') {
                            $alertClass = 'bg-green-50 border border-green-200 text-green-700';
                        } elseif ($message_type === 'error') {
                            $alertClass = 'bg-red-50 border border-red-200 text-red-700';
                        } elseif ($message_type === 'warning') {
                            $alertClass = 'bg-yellow-50 border border-yellow-200 text-yellow-700';
                        }
                    ?>
                    <div class="mb-6 p-4 rounded-lg <?php echo $alertClass; ?>">
                        <p class="font-semibold"><?php echo htmlspecialchars($message); ?></p>
                    </div>
                <?php endif; ?>

                <!-- Clock Status Card -->
                <div class="bg-white rounded-xl shadow-lg p-8 mb-8">
                    <h3 class="text-2xl font-bold text-gray-900 mb-6">Today's Status</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <!-- Clock In Time -->
                        <div class="bg-gradient-to-br from-teal-50 to-teal-100 p-6 rounded-lg">
                            <p class="text-gray-600 text-sm font-semibold">Clock In Time</p>
                            <p class="text-3xl font-bold text-teal-600 mt-2">
                                <?php echo $today_attendance && $today_attendance['clock_in'] 
                                    ? date('h:i A', strtotime($today_attendance['clock_in']))
                                    : '--:--'; 
                                ?>
                            </p>
                        </div>

                        <!-- Clock Out Time -->
                        <div class="bg-gradient-to-br from-orange-50 to-orange-100 p-6 rounded-lg">
                            <p class="text-gray-600 text-sm font-semibold">Clock Out Time</p>
                            <p class="text-3xl font-bold text-orange-600 mt-2">
                                <?php echo $today_attendance && $today_attendance['clock_out']
                                    ? date('h:i A', strtotime($today_attendance['clock_out']))
                                    : '--:--';
                                ?>
                            </p>
                        </div>

                        <!-- Hours Worked -->
                        <div class="bg-gradient-to-br from-purple-50 to-purple-100 p-6 rounded-lg">
                            <p class="text-gray-600 text-sm font-semibold">Hours Worked</p>
                            <p class="text-3xl font-bold text-purple-600 mt-2">
                                <?php echo $today_attendance && $today_attendance['hours_worked']
                                    ? number_format($today_attendance['hours_worked'], 2)
                                    : '0.00';
                                ?>
                            </p>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex gap-4">
                        <form id="clock-in-form" method="POST" class="flex-1">
                            <input type="hidden" name="action" value="clock_in">
                            <button id="clock-in-btn" type="submit" <?php echo (!$can_clock_in) ? 'disabled' : ''; ?>
                                class="w-full py-3 bg-gradient-to-r from-teal-600 to-teal-700 text-white font-semibold rounded-lg hover:from-teal-700 hover:to-teal-800 transition shadow-lg disabled:opacity-50 disabled:cursor-not-allowed">
                                Clock In
                            </button>
                        </form>
                        <form id="clock-out-form" method="POST" class="flex-1">
                            <input type="hidden" name="action" value="clock_out">
                            <button id="clock-out-btn" type="submit" <?php echo (!$can_clock_out) ? 'disabled' : ''; ?>
                                class="w-full py-3 bg-gradient-to-r from-orange-600 to-orange-700 text-white font-semibold rounded-lg hover:from-orange-700 hover:to-orange-800 transition shadow-lg disabled:opacity-50 disabled:cursor-not-allowed">
                                Clock Out
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Recent Attendance History -->
                <!-- <div class="bg-white rounded-xl shadow-lg p-8">
                    <h3 class="text-2xl font-bold text-gray-900 mb-6">Attendance History</h3>
                    
                    <?php if (!empty($recent_records)): ?>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b-2 border-gray-200">
                                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Date</th>
                                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Clock In</th>
                                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Clock Out</th>
                                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Hours Worked</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_records as $record): ?>
                                        <tr class="border-b border-gray-200 hover:bg-gray-50">
                                            <td class="py-3 px-4 text-gray-900"><?php echo date('M d, Y', strtotime($record['attendance_date'])); ?></td>
                                            <td class="py-3 px-4 text-gray-600">
                                                <?php echo $record['clock_in'] 
                                                    ? date('h:i A', strtotime($record['clock_in']))
                                                    : '--:--';
                                                ?>
                                            </td>
                                            <td class="py-3 px-4 text-gray-600">
                                                <?php echo $record['clock_out']
                                                    ? date('h:i A', strtotime($record['clock_out']))
                                                    : '--:--';
                                                ?>
                                            </td>
                                            <td class="py-3 px-4 text-gray-600">
                                                <?php echo $record['hours_worked'] 
                                                    ? number_format($record['hours_worked'], 2) . ' hrs'
                                                    : '--';
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-600 text-center py-8">No attendance records found.</p>
                    <?php endif; ?>
                </div> -->
            </div>
        </div>
    </div>
   </div>
    </body>
    </html>
    <script src="../assets/js/ajax/clock.js"></script>
