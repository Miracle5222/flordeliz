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

// Get company info if available
$company_name = "Flor de Liz";
$company_address = "Molave, Zamboanga del Sur";
$company_contact = "09454789875";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Payroll Slip - <?php echo htmlspecialchars($payroll['first_name'] . ' ' . $payroll['last_name']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .no-print {
            display: block;
            margin-bottom: 20px;
            text-align: right;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin-right: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        .btn-print {
            background-color: #2563eb;
            color: white;
        }

        .btn-print:hover {
            background-color: #1d4ed8;
        }

        .btn-back {
            background-color: #d1d5db;
            color: #374151;
        }

        .btn-back:hover {
            background-color: #9ca3af;
        }

        .payroll-slip {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            page-break-after: always;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .document-title {
            font-size: 18px;
            font-weight: bold;
            color: #555;
            margin-top: 15px;
        }

        .employee-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
            padding: 15px;
            background-color: #f9fafb;
            border-radius: 4px;
        }

        .info-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 30px;
        }

        .info-item {
            padding: 10px 0;
        }

        .info-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 3px;
        }

        .info-value {
            font-size: 14px;
            color: #333;
            font-weight: 500;
        }

        .salary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .salary-table th {
            background-color: #1f2937;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            border: 1px solid #374151;
        }

        .salary-table td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 13px;
        }

        .salary-table tr:nth-child(even) {
            background-color: #f9fafb;
        }

        .amount {
            text-align: right;
            font-weight: 500;
        }

        .total-row {
            background-color: #f3f4f6;
            font-weight: bold;
            border-top: 2px solid #374151;
            border-bottom: 2px solid #374151;
        }

        .total-row td {
            padding: 14px 12px;
            font-size: 14px;
        }

        .summary {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #e5e7eb;
        }

        .summary-column h3 {
            font-size: 12px;
            text-transform: uppercase;
            color: #666;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 13px;
            border-bottom: 1px solid #f0f0f0;
        }

        .summary-label {
            color: #666;
        }

        .summary-amount {
            font-weight: 600;
            text-align: right;
        }

        .net-pay {
            font-size: 16px;
            font-weight: bold;
            color: #059669;
            padding-top: 15px;
            border-top: 2px solid #059669;
            margin-top: 15px;
        }

        .footer {
            text-align: center;
            padding-top: 40px;
            margin-top: 40px;
            border-top: 1px solid #e5e7eb;
            font-size: 11px;
            color: #999;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .no-print {
                display: none !important;
            }

            .payroll-slip {
                box-shadow: none;
                border: none;
                padding: 0;
            }

            .btn {
                display: none;
            }
        }

        @media (max-width: 600px) {
            .payroll-slip {
                padding: 20px;
            }

            .employee-info,
            .summary {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .salary-table {
                font-size: 12px;
            }

            .salary-table th,
            .salary-table td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="no-print">
            <button class="btn btn-print" onclick="window.print()">Print Slip</button>
            <button class="btn btn-back" onclick="window.location.href='../payroll.php'">Back to Payroll</button>
        </div>

        <div class="payroll-slip">
            <!-- Header -->
            <div class="header">
                <div class="company-name"><?php echo $company_name; ?></div>
                <div style="font-size: 12px; color: #666; margin-top: 5px;">
                    <?php echo $company_address; ?><br>
                    <?php echo $company_contact; ?>
                </div>
                <div class="document-title">PAYROLL SLIP</div>
            </div>

            <!-- Employee Information -->
            <div class="employee-info">
                <div>
                    <div class="info-item">
                        <div class="info-label">Employee Name</div>
                        <div class="info-value"><?php echo htmlspecialchars($payroll['first_name'] . ' ' . $payroll['last_name']); ?></div>
                    </div>
                </div>
                <div>
                    <div class="info-item">
                        <div class="info-label">Position</div>
                        <div class="info-value"><?php echo htmlspecialchars($payroll['position']); ?></div>
                    </div>
                </div>
            </div>

            <!-- Pay Period and Status -->
            <div class="info-section">
                <div class="info-item">
                    <div class="info-label">Pay Period Start</div>
                    <div class="info-value"><?php echo date('M d, Y', strtotime($payroll['pay_period_start'])); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Pay Period End</div>
                    <div class="info-value"><?php echo date('M d, Y', strtotime($payroll['pay_period_end'])); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Days Worked</div>
                    <div class="info-value"><?php echo $payroll['days_worked']; ?> days</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Overtime Hours</div>
                    <div class="info-value"><?php echo number_format($payroll['overtime_hours'], 2); ?> hours</div>
                </div>
            </div>

            <!-- Earnings and Deductions Table -->
            <table class="salary-table">
                <thead>
                    <tr>
                        <th style="width: 60%;">Description</th>
                        <th style="width: 40%;" class="amount">Amount (₱)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Earnings</strong></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td style="padding-left: 30px;">Base Pay</td>
                        <td class="amount">₱<?php echo number_format($payroll['base_pay'], 2); ?></td>
                    </tr>
                    <tr>
                        <td style="padding-left: 30px;">Overtime Pay</td>
                        <td class="amount">₱<?php echo number_format($payroll['overtime_pay'], 2); ?></td>
                    </tr>
                    <tr>
                        <td colspan="2" style="height: 8px; border: none;"></td>
                    </tr>
                    <tr>
                        <td><strong>Deductions</strong></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td style="padding-left: 30px;">Deductions</td>
                        <td class="amount">₱<?php echo number_format($payroll['deductions'], 2); ?></td>
                    </tr>
                    <tr>
                        <td colspan="2" style="height: 8px; border: none;"></td>
                    </tr>
                    <tr class="total-row">
                        <td><strong>Net Pay</strong></td>
                        <td class="amount"><strong>₱<?php echo number_format($payroll['total_pay'], 2); ?></strong></td>
                    </tr>
                </tbody>
            </table>

            <!-- Summary -->
            <div class="summary">
                <div class="summary-column">
                    <h3>Income Summary</h3>
                    <div class="summary-item">
                        <span class="summary-label">Base Pay</span>
                        <span class="summary-amount">₱<?php echo number_format($payroll['base_pay'], 2); ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Overtime Pay</span>
                        <span class="summary-amount">₱<?php echo number_format($payroll['overtime_pay'], 2); ?></span>
                    </div>
                    <div class="summary-item net-pay">
                        <span class="summary-label">Gross Income</span>
                        <span class="summary-amount">₱<?php echo number_format($payroll['base_pay'] + $payroll['overtime_pay'], 2); ?></span>
                    </div>
                </div>
                <div class="summary-column">
                    <h3>Deductions Summary</h3>
                    <div class="summary-item">
                        <span class="summary-label">Total Deductions</span>
                        <span class="summary-amount">₱<?php echo number_format($payroll['deductions'], 2); ?></span>
                    </div>
                    <div class="summary-item net-pay">
                        <span class="summary-label">Net Pay</span>
                        <span class="summary-amount">₱<?php echo number_format($payroll['total_pay'], 2); ?></span>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <?php if ($payroll['notes']): ?>
            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
                <div class="info-label" style="margin-bottom: 8px;">Notes</div>
                <div style="font-size: 13px; color: #555; line-height: 1.5;">
                    <?php echo nl2br(htmlspecialchars($payroll['notes'])); ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Footer -->
            <div class="footer">
                <p>This is a computer-generated document and does not require a signature.</p>
                <p style="margin-top: 10px;">Generated on <?php echo date('M d, Y g:i A'); ?></p>
            </div>
        </div>
    </div>
</body>
</html>
