-- Update existing customers with email and phone data
UPDATE `customers` SET `email` = 'willsmith@example.com' WHERE `id` = 10;
UPDATE `customers` SET `email` = 'max@example.com' WHERE `id` = 11;

-- Verify updates
SELECT id, name, phone, email FROM `customers` WHERE id IN (10, 11);
