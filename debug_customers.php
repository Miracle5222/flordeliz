<?php
/**
 * Debug page to verify customer data in database
 */
require_once __DIR__ . '/config/database.php';

echo '<pre style="font-family: monospace; background: #f5f5f5; padding: 15px;">';
echo "=== CUSTOMER DATA FROM DATABASE ===\n\n";

$result = $conn->query('SELECT id, name, phone, email, address, category, is_active FROM customers ORDER BY id');

if (!$result) {
    echo "ERROR: " . $conn->error . "\n";
} else {
    printf("%-3s | %-20s | %-15s | %-30s | %-30s | %-15s | %s\n", 
        'ID', 'NAME', 'PHONE', 'EMAIL', 'ADDRESS', 'CATEGORY', 'ACTIVE');
    echo str_repeat('-', 150) . "\n";
    
    while ($row = $result->fetch_assoc()) {
        printf("%-3d | %-20s | %-15s | %-30s | %-30s | %-15s | %s\n",
            $row['id'],
            substr($row['name'] ?? '', 0, 20),
            substr($row['phone'] ?? '', 0, 15),
            substr($row['email'] ?? '(empty)', 0, 30),
            substr($row['address'] ?? '(empty)', 0, 30),
            substr($row['category'] ?? '', 0, 15),
            $row['is_active'] ? 'Yes' : 'No'
        );
    }
}

echo "\n=== HTML DROPDOWN OPTIONS ===\n\n";
echo "This is what will be rendered in the dropdown:\n\n";

$result = $conn->query('SELECT id, name, phone, email, category FROM customers WHERE is_active = 1 ORDER BY name');
while ($row = $result->fetch_assoc()) {
    $email = htmlspecialchars(trim($row['email'] ?? ''));
    echo "<option value=\"{$row['id']}\" data-name=\"" . htmlspecialchars($row['name']) . "\" data-phone=\"" . htmlspecialchars($row['phone']) . "\" data-email=\"{$email}\" data-category=\"" . htmlspecialchars($row['category']) . "\">\n";
    echo "  {$row['name']}\n";
    echo "</option>\n\n";
}

$conn->close();
echo '</pre>';
?>
