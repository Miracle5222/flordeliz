<?php
/**
 * Update existing customers with sample email and address data
 * Run this once to populate empty customer records
 */

if (php_sapi_name() !== 'cli') {
    echo '<pre>';
}

require_once __DIR__ . '/../config/database.php';

try {
    // Update will smith
    $stmt = $conn->prepare('UPDATE customers SET email = ?, address = ? WHERE id = ?');
    $email1 = 'willsmith@example.com';
    $address1 = '123 Main Street, Manila';
    $id1 = 10;
    $stmt->bind_param('ssi', $email1, $address1, $id1);
    $stmt->execute();
    echo "[✓] Updated 'will smith' with email and address\n";
    $stmt->close();

    // Update max
    $stmt = $conn->prepare('UPDATE customers SET email = ?, address = ? WHERE id = ?');
    $email2 = 'max@example.com';
    $address2 = '456 Oak Avenue, Quezon City';
    $id2 = 11;
    $stmt->bind_param('ssi', $email2, $address2, $id2);
    $stmt->execute();
    echo "[✓] Updated 'max' with email and address\n";
    $stmt->close();

    // Show updated customers
    $result = $conn->query('SELECT id, name, phone, email, address, category FROM customers ORDER BY name');
    echo "\n--- Current Customers in Database ---\n";
    while ($row = $result->fetch_assoc()) {
        echo "ID: {$row['id']}, Name: {$row['name']}, Email: {$row['email']}, Address: {$row['address']}\n";
    }

    $conn->close();
    echo "\n[✓] Customer data updated successfully!\n";
    
} catch (Exception $e) {
    echo "[✗] Error: " . $e->getMessage() . "\n";
    $conn->close();
}

if (php_sapi_name() !== 'cli') {
    echo '</pre>';
}
?>
