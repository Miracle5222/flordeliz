-- Flor de Liz Printing Management System Database Schema
-- Created: 2024

-- Users Table (Staff & Admin)
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    role ENUM('staff', 'admin') NOT NULL,
    full_name VARCHAR(100),
    phone VARCHAR(20),
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Suppliers Table
CREATE TABLE IF NOT EXISTS suppliers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    address VARCHAR(255),
    city VARCHAR(50),
    contact_person VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products/Services Table
CREATE TABLE IF NOT EXISTS products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    category ENUM('hardbound', 'softbound', 'receipt', 'custom', 'other') NOT NULL,
    description TEXT,
    unit_price DECIMAL(10, 2) NOT NULL,
    unit_type VARCHAR(50),
    current_stock INT DEFAULT 0,
    reorder_level INT DEFAULT 10,
    supplier_id INT,
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
);

-- Inventory Materials Table
CREATE TABLE IF NOT EXISTS inventory_materials (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    material_type ENUM('paper', 'ink', 'cardboard', 'binding', 'other') NOT NULL,
    unit VARCHAR(50),
    unit_price DECIMAL(10, 2) NOT NULL,
    current_stock INT DEFAULT 0,
    reorder_level INT DEFAULT 5,
    supplier_id INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
);

-- Inventory Transactions Table
CREATE TABLE IF NOT EXISTS inventory_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT,
    material_id INT,
    transaction_type ENUM('in', 'out', 'adjustment') NOT NULL,
    quantity INT NOT NULL,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (material_id) REFERENCES inventory_materials(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Customers Table
CREATE TABLE IF NOT EXISTS customers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    city VARCHAR(50),
    category ENUM('Ogis', 'Motor Trade', 'Sari-sari Store', 'Private', 'Other') NOT NULL,
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Orders Table
CREATE TABLE IF NOT EXISTS orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    customer_id INT NOT NULL,
    order_date DATE NOT NULL,
    delivery_date DATE,
    delivery_address TEXT,
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    total_amount DECIMAL(12, 2) DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);

-- Order Items Table
CREATE TABLE IF NOT EXISTS order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    total_price DECIMAL(12, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Payments Table
CREATE TABLE IF NOT EXISTS payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    amount DECIMAL(12, 2) NOT NULL,
    payment_date DATE NOT NULL,
    payment_method ENUM('cash', 'check', 'online_transfer', 'credit_card') NOT NULL,
    reference_number VARCHAR(100),
    payment_type ENUM('full', 'partial', 'downpayment') DEFAULT 'partial',
    notes TEXT,
    recorded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (recorded_by) REFERENCES users(id)
);

-- Sales Transactions Table
CREATE TABLE IF NOT EXISTS sales_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT,
    transaction_date DATE NOT NULL,
    total_sales DECIMAL(12, 2) NOT NULL,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Employees Table
CREATE TABLE IF NOT EXISTS employees (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    email VARCHAR(100),
    phone VARCHAR(20),
    position VARCHAR(100),
    hire_date DATE,
    daily_rate DECIMAL(10, 2) DEFAULT 1730.00,
    overtime_rate DECIMAL(10, 2) DEFAULT 80.00,
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Attendance Table
CREATE TABLE IF NOT EXISTS attendance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    clock_in DATETIME,
    clock_out DATETIME,
    attendance_date DATE NOT NULL,
    hours_worked DECIMAL(5, 2),
    overtime_hours DECIMAL(5, 2) DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id)
);

-- Payroll Table
CREATE TABLE IF NOT EXISTS payroll (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    payroll_period_start DATE NOT NULL,
    payroll_period_end DATE NOT NULL,
    days_worked INT DEFAULT 0,
    overtime_hours DECIMAL(5, 2) DEFAULT 0,
    daily_rate DECIMAL(10, 2),
    overtime_rate DECIMAL(10, 2),
    basic_pay DECIMAL(12, 2),
    overtime_pay DECIMAL(12, 2) DEFAULT 0,
    gross_pay DECIMAL(12, 2),
    deductions DECIMAL(12, 2) DEFAULT 0,
    net_pay DECIMAL(12, 2),
    payment_date DATE,
    status ENUM('pending', 'approved', 'paid') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id)
);

-- Equipment/Machines Table
CREATE TABLE IF NOT EXISTS equipment (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    equipment_type ENUM('offset_printer', 'cutter', 'minerva_printer', 'other') NOT NULL,
    model VARCHAR(100),
    purchase_date DATE,
    purchase_cost DECIMAL(12, 2),
    maintenance_date DATE,
    status ENUM('active', 'inactive', 'repair') DEFAULT 'active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Client Notifications/SMS Log Table
CREATE TABLE IF NOT EXISTS client_notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    order_id INT,
    phone_number VARCHAR(20),
    message TEXT NOT NULL,
    notification_type ENUM('sms', 'email', 'manual') DEFAULT 'sms',
    sent_date DATETIME,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    sent_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (sent_by) REFERENCES users(id)
);

-- Reports Log Table
CREATE TABLE IF NOT EXISTS reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    report_type ENUM('sales', 'inventory', 'payroll', 'attendance', 'daily', 'weekly', 'monthly', 'yearly') NOT NULL,
    report_date DATE NOT NULL,
    start_date DATE,
    end_date DATE,
    total_sales DECIMAL(12, 2),
    total_orders INT,
    low_stock_items INT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Initial Data Insertion

-- Insert Suppliers
INSERT INTO suppliers (name, address, city, contact_person, phone, email) VALUES
('Star Paper Corporation', 'Business Address', 'Cagayan de Oro', 'Sales Department', '+63-88-123-4567', 'sales@starpaper.com');

-- Insert Products
INSERT INTO products (name, category, description, unit_price, unit_type, reorder_level) VALUES
('Hardbound Book', 'hardbound', 'Hardbound printed books', 350.00, 'piece', 10),
('Softbound Book', 'softbound', 'Softbound printed books', 100.00, 'piece', 20),
('Receipt (1 dozen)', 'receipt', 'Receipt books - 1 dozen', 2000.00, 'set', 5),
('Receipt (100 books/pad)', 'receipt', 'Receipt books - 100 books per pad', 4000.00, 'pad', 3);

-- Insert Inventory Materials
INSERT INTO inventory_materials (name, material_type, unit, unit_price, reorder_level, supplier_id) VALUES
('Carbonless Paper', 'paper', 'ream', 400.00, 10, 1),
('Colored Bondpaper', 'paper', 'piece', 10.00, 100, 1),
('Kartolina', 'cardboard', 'piece', 8.00, 50, 1),
('Onion Skin', 'paper', 'ream', 1300.00, 5, 1);

-- Insert Default Users
INSERT INTO users (username, password, email, role, full_name) VALUES
('staff', MD5('staff123'), 'staff@flordeliz.com', 'staff', 'Staff User'),
('admin', MD5('admin123'), 'admin@flordeliz.com', 'admin', 'Administrator');

-- Create Indexes for better query performance
CREATE INDEX idx_order_customer ON orders(customer_id);
CREATE INDEX idx_order_status ON orders(status);
CREATE INDEX idx_order_date ON orders(order_date);
CREATE INDEX idx_payment_order ON payments(order_id);
CREATE INDEX idx_attendance_employee ON attendance(employee_id);
CREATE INDEX idx_attendance_date ON attendance(attendance_date);
CREATE INDEX idx_payroll_employee ON payroll(employee_id);
CREATE INDEX idx_inventory_product ON inventory_transactions(product_id);
CREATE INDEX idx_inventory_material ON inventory_transactions(material_id);
CREATE INDEX idx_sales_date ON sales_transactions(transaction_date);
