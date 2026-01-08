<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['is_logged_in']) || $_SESSION['role'] !== 'staff') {
    header('Location: ../../login_staff.php'); exit();
}
$conn = require_once __DIR__ . '/../config/database.php';

// Ensure sales table exists
$conn->query("CREATE TABLE IF NOT EXISTS sales (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sale_date DATETIME NOT NULL,
    total_amount DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$result = $conn->query("SELECT s.id, s.sale_date, s.total_amount, s.created_at, GROUP_CONCAT(si.product_name SEPARATOR ', ') AS items FROM sales s LEFT JOIN sale_items si ON si.sale_id = s.id GROUP BY s.id ORDER BY s.sale_date DESC LIMIT 200");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Sales - Flor de Liz</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
</head>
<body class="bg-gray-50">
    <?php require_once __DIR__ . '/../includes/sidebar_navigation.php'; ?>
    <div class="absolute w-full -ml-2 top-12">
        <div id="main-content" class="flex-1 px-4 sm:px-6 lg:px-8 py-12">
            <div class="max-w-4xl mx-auto">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-3xl font-bold">Sales</h2>
                    <!-- <a href="create.php" class="px-4 py-2 bg-teal-600 text-white rounded">Record Sale</a> -->
                </div>
                <div class="bg-white rounded-xl shadow p-4">
                    <div class="flex flex-wrap gap-3 mb-4">
                        <div>
                            <label class="text-sm text-gray-600">Date from</label>
                            <input type="date" id="filter-date-from" class="border px-2 py-1 rounded">
                        </div>
                        <div>
                            <label class="text-sm text-gray-600">Date to</label>
                            <input type="date" id="filter-date-to" class="border px-2 py-1 rounded">
                        </div>
                        <div>
                            <label class="text-sm text-gray-600">Min total (₱)</label>
                            <input type="number" step="0.01" id="filter-min-total" class="border px-2 py-1 rounded" placeholder="0.00">
                        </div>
                        <div>
                            <label class="text-sm text-gray-600">Max total (₱)</label>
                            <input type="number" step="0.01" id="filter-max-total" class="border px-2 py-1 rounded" placeholder="0.00">
                        </div>
                        <div class="flex items-end">
                            <button id="filter-clear" class="px-3 py-1 bg-gray-200 rounded">Clear</button>
                        </div>
                    </div>

                    <table id="sales-table" class="w-full text-sm display">
                        <thead>
                            <tr class="text-left text-gray-600 border-b">
                                <th class="py-2">Date</th>
                                <th class="py-2">Items</th>
                                <th class="py-2">Total (₱)</th>
                                <th class="py-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
<?php while ($row = $result->fetch_assoc()): ?>
    <tr class="border-b">
        <td class="py-2"><?php echo htmlspecialchars($row['sale_date']); ?></td>
        <td class="py-2"><?php echo htmlspecialchars($row['items'] ?? ''); ?></td>
        <td class="py-2"><?php echo number_format($row['total_amount'],2); ?></td>
        <td class="py-2 space-x-2">
            <a href="sales/view.php?id=<?php echo $row['id']; ?>" class="text-teal-600 hover:underline">View</a>
            <a href="sales/edit.php?id=<?php echo $row['id']; ?>" class="text-blue-600 hover:underline">Edit</a>
        </td>
    </tr>
<?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

    <!-- Scripts: jQuery + DataTables -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>
        (function(){
            const parseDate = (d) => {
                if (!d) return null;
                const s = d.split(' ')[0];
                const parts = s.split('-');
                if (parts.length !== 3) return null;
                return new Date(parts[0], parts[1]-1, parts[2]);
            };

            const table = $('#sales-table').DataTable({
                order: [[0, 'desc']],
                pageLength: 25,
                columnDefs: [
                    { targets: 3, orderable: false }
                ],
                language: { search: "Quick search:" }
            });

            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                if (settings.nTable.id !== 'sales-table') return true;

                const dateFrom = $('#filter-date-from').val();
                const dateTo = $('#filter-date-to').val();
                const minTotal = parseFloat($('#filter-min-total').val()) || null;
                const maxTotal = parseFloat($('#filter-max-total').val()) || null;

                const dateStr = data[0] || '';
                const totalStr = data[1] || '';

                const rowDate = parseDate(dateStr);
                const fromDate = dateFrom ? parseDate(dateFrom) : null;
                const toDate = dateTo ? parseDate(dateTo) : null;

                let total = parseFloat(totalStr.replace(/[^0-9.-]+/g, ''));
                if (isNaN(total)) total = null;

                if (fromDate && rowDate && rowDate < fromDate) return false;
                if (toDate && rowDate && rowDate > toDate) return false;
                if (minTotal !== null && total !== null && total < minTotal) return false;
                if (maxTotal !== null && total !== null && total > maxTotal) return false;

                return true;
            });

            $('#filter-date-from, #filter-date-to, #filter-min-total, #filter-max-total').on('change keyup', function(){
                table.draw();
            });

            $('#filter-clear').on('click', function(e){
                e.preventDefault();
                $('#filter-date-from').val('');
                $('#filter-date-to').val('');
                $('#filter-min-total').val('');
                $('#filter-max-total').val('');
                table.search('').columns().search('').draw();
            });
        })();
    </script>