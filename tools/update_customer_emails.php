<?php
/**
 * Update existing customers with email addresses
 * This allows the email field to populate when selecting existing customers
 */

require_once __DIR__ . '/../config/database.php';

try {
    // Update customer 10 (will smith)
    $stmt = $conn->prepare('UPDATE customers SET email = ? WHERE id = ?');
    $email1 = 'willsmith@example.com';
    $id1 = 10;
    $stmt->bind_param('si', $email1, $id1);
    $stmt->execute();
    $rows1 = $stmt->affected_rows;
    $stmt->close();

    // Update customer 11 (max)
    $stmt = $conn->prepare('UPDATE customers SET email = ? WHERE id = ?');
    $email2 = 'max@example.com';
    $id2 = 11;
    $stmt->bind_param('si', $email2, $id2);
    $stmt->execute();
    $rows2 = $stmt->affected_rows;
    $stmt->close();

    echo "<pre style='font-family: monospace; background: #f0f0f0; padding: 10px;'>";
    echo "[✓] Updated customer IDs 10 and 11 with email addresses\n\n";
    echo "-- Updated Customers --\n";
    
    // Show updated data
    $result = $conn->query('SELECT id, name, phone, email FROM customers WHERE id IN (10, 11) ORDER BY id');
    while ($row = $result->fetch_assoc()) {
        printf("ID: %d, Name: %-20s, Phone: %s, Email: %s\n", 
            $row['id'], 
            $row['name'], 
            $row['phone'],
            $row['email'] ?? '(not set)'
        );
    }
    
    echo "\n[✓] Done! Refresh the order form and select a customer to see the email populate.\n";
    echo "</pre>";
    
    $conn->close();
} catch (Exception $e) {
    echo "<pre style='color: red;'>[✗] Error: " . $e->getMessage() . "</pre>";
    $conn->close();
    exit(1);
}
?>
