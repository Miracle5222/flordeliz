<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['is_logged_in']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../login_admin.php'); exit();
}
$conn = require_once __DIR__ . '/../../config/database.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: ../payroll.php'); exit();
}

// Fetch payroll details
$stmt = $conn->prepare("
    SELECT p.*, e.first_name, e.last_name, e.position, e.daily_rate, e.overtime_rate, u.email
    FROM payroll p
    JOIN employees e ON p.employee_id = e.id
    LEFT JOIN users u ON e.user_id = u.id
    WHERE p.id = ?
");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$payroll = $result->fetch_assoc();
$stmt->close();

if (!$payroll) {
    header('Location: ../payroll.php'); exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>View Payroll - Flor de Liz</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Hide navigation and controls when printing */
        @media print {
            .no-print { display: none !important; }
            body { background: #fff; }
            .print-container { box-shadow: none !important; border: none !important; }
                /* Hide everything by default, then show the payroll details container */
                body * { visibility: hidden !important; }
                #payroll-details, #payroll-details * { visibility: visible !important; }
                #payroll-details { position: static !important; top: 0 !important; left: 0 !important; width: 100% !important; margin: 0 !important; }
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php require_once __DIR__ . '/../../includes/sidebar_navigation.php'; ?>
    <div class="absolute w-full -ml-2 top-12">
        <div id="main-content" class="flex-1 px-4 sm:px-6 lg:px-8 py-12">
            <div class="max-w-3xl mx-auto">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-3xl font-bold">Payroll Details</h2>
                    <div class="flex gap-2">
                            <button onclick="printPayroll()" class="no-print px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Print</button>
                        <a href="../payroll.php" class="no-print px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">Back</a>
                    </div>
                </div>

                 <div id="payroll-details" class="bg-white rounded-xl shadow p-8 print-container">
                    <div class="border-b pb-6 mb-6">
                        <h3 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($payroll['first_name'] . ' ' . $payroll['last_name']); ?></h3>
                        <p class="text-gray-600"><?php echo htmlspecialchars($payroll['position']); ?></p>
                        <p class="text-sm text-gray-500">Email: <?php echo htmlspecialchars($payroll['email'] ?? 'N/A'); ?></p>
                    </div>

                    <div class="grid grid-cols-2 gap-6 mb-6">
                        <div>
                            <p class="text-sm text-gray-600">Pay Period</p>
                            <p class="text-lg font-semibold"><?php echo date('M d, Y', strtotime($payroll['pay_period_start'])) . ' - ' . date('M d, Y', strtotime($payroll['pay_period_end'])); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Status</p>
                            <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold <?php 
                                if ($payroll['status'] === 'paid') echo 'bg-green-100 text-green-800';
                                elseif ($payroll['status'] === 'approved') echo 'bg-blue-100 text-blue-800';
                                else echo 'bg-yellow-100 text-yellow-800';
                            ?>">
                                <?php echo ucfirst($payroll['status']); ?>
                            </span>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-6 rounded-lg mb-6">
                        <h4 class="font-bold text-lg mb-4">Payroll Breakdown</h4>
                        
                        <div class="space-y-3 mb-6 border-b pb-6">
                            <div class="flex justify-between">
                                <span class="text-gray-700">Daily Rate:</span>
                                <span class="font-semibold">₱<?php echo number_format($payroll['daily_rate'], 2); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-700">Days Worked:</span>
                                <span class="font-semibold"><?php echo number_format($payroll['days_worked'], 1); ?></span>
                            </div>
                            <div class="flex justify-between text-lg font-bold bg-blue-50 p-3 rounded">
                                <span>Base Pay:</span>
                                <span>₱<?php echo number_format($payroll['base_pay'], 2); ?></span>
                            </div>
                        </div>

                        <div class="space-y-3 mb-6 border-b pb-6">
                            <div class="flex justify-between">
                                <span class="text-gray-700">Overtime Rate/Hour:</span>
                                <span class="font-semibold">₱<?php echo number_format($payroll['overtime_rate'], 2); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-700">Overtime Hours:</span>
                                <span class="font-semibold"><?php echo number_format($payroll['overtime_hours'], 2); ?></span>
                            </div>
                            <div class="flex justify-between text-lg font-bold bg-amber-50 p-3 rounded">
                                <span>Overtime Pay:</span>
                                <span>₱<?php echo number_format($payroll['overtime_pay'], 2); ?></span>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-700">Deductions:</span>
                                <span class="font-semibold text-red-600">- ₱<?php echo number_format($payroll['deductions'], 2); ?></span>
                            </div>
                            <div class="flex justify-between text-2xl font-bold bg-green-50 p-4 rounded border-2 border-green-200">
                                <span>Total Pay:</span>
                                <span class="text-green-700">₱<?php echo number_format($payroll['total_pay'], 2); ?></span>
                            </div>
                        </div>
                    </div>

                    <?php if ($payroll['notes']): ?>
                    <div class="bg-gray-50 p-4 rounded-lg mb-6">
                        <p class="text-sm text-gray-600">Notes</p>
                        <p class="text-gray-800"><?php echo htmlspecialchars($payroll['notes']); ?></p>
                    </div>
                    <?php endif; ?>

                     <div class="flex gap-4 no-print">
                         <a href="edit.php?id=<?php echo $payroll['id']; ?>" class="px-6 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 font-semibold">Edit</a>
                         <a href="../payroll.php" class="px-6 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 font-semibold">Back to Payroll</a>
                     </div>
                </div>
            </div>
        </div>
    </div>
     <script>
        // Open a new window containing only the payroll details and trigger print
        function printPayroll() {
            const content = document.getElementById('payroll-details');
            if (!content) return;
            const w = window.open('', '_blank');
            const doc = w.document;
            doc.open();
            doc.write('<!doctype html><html><head><meta charset="utf-8"><title>Payroll</title>');
            doc.write('<meta name="viewport" content="width=device-width,initial-scale=1">');
            doc.write('<script src="https://cdn.tailwindcss.com"></' + 'script>');
            doc.write('<style>body{padding:20px;font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial;} .print-container{box-shadow:none;border:none;}</style>');
            doc.write('</head><body>');
            doc.write('<div class="print-container">' + content.innerHTML + '</div>');
            doc.write('</body></html>');
            doc.close();
            w.focus();
            // Wait a short moment for styles to load then print
            setTimeout(() => {
                w.print();
                // Optionally close window after printing
                // w.close();
            }, 500);
        }
     </script>
</body>
</html>
