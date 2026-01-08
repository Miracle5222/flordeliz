-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 08, 2026 at 12:56 PM
-- Server version: 10.4.25-MariaDB
-- PHP Version: 7.4.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `flordeliz`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `clock_in` datetime DEFAULT NULL,
  `clock_out` datetime DEFAULT NULL,
  `attendance_date` date NOT NULL,
  `hours_worked` decimal(5,2) DEFAULT NULL,
  `overtime_hours` decimal(5,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `employee_id`, `clock_in`, `clock_out`, `attendance_date`, `hours_worked`, `overtime_hours`, `notes`, `created_at`) VALUES
(6, 1, '2026-01-04 01:30:50', '2026-01-04 01:30:51', '2026-01-04', '0.00', '0.00', NULL, '2026-01-03 17:30:50'),
(7, 1, '2026-01-08 02:00:09', '2026-01-08 02:00:11', '2026-01-08', '0.00', '0.00', NULL, '2026-01-07 18:00:09');

-- --------------------------------------------------------

--
-- Table structure for table `client_notifications`
--

CREATE TABLE `client_notifications` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `message` text NOT NULL,
  `notification_type` enum('sms','email','manual') DEFAULT 'sms',
  `sent_date` datetime DEFAULT NULL,
  `status` enum('pending','sent','failed') DEFAULT 'pending',
  `sent_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `category` enum('Ogis','Motor Trade','Sari-sari Store','Private','Other') NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `contact_person`, `phone`, `email`, `address`, `city`, `category`, `is_active`, `created_at`, `updated_at`) VALUES
(10, 'will smith', NULL, '09454739358', NULL, NULL, NULL, 'Private', 1, '2026-01-04 08:41:53', '2026-01-04 08:41:53'),
(11, 'max', NULL, '09454739386', NULL, NULL, NULL, 'Ogis', 1, '2026-01-07 18:17:55', '2026-01-07 18:17:55');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `daily_rate` decimal(10,2) DEFAULT 1730.00,
  `overtime_rate` decimal(10,2) DEFAULT 80.00,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `user_id`, `first_name`, `last_name`, `email`, `phone`, `position`, `hire_date`, `daily_rate`, `overtime_rate`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'Staff', 'User', 'staff@gmail.com', '09454739386', 'casher', '0000-00-00', '1730.00', '80.00', 1, '2026-01-03 17:19:54', '2026-01-08 11:51:39'),
(2, 3, 'roneil', 'bansas', 'roneilbansas5222@gmail.com', '09454739384', 'designer', '0000-00-00', '1730.00', '80.00', 1, '2026-01-08 11:39:35', '2026-01-08 11:39:35'),
(3, 4, 'oliver', 'oliver', 'oliver@gmail.com', '09454739384', 'printer operator', '0000-00-00', '1030.00', '80.00', 1, '2026-01-08 11:40:40', '2026-01-08 11:50:41');

-- --------------------------------------------------------

--
-- Table structure for table `equipment`
--

CREATE TABLE `equipment` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `equipment_type` enum('offset_printer','cutter','minerva_printer','other') NOT NULL,
  `model` varchar(100) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `purchase_cost` decimal(12,2) DEFAULT NULL,
  `maintenance_date` date DEFAULT NULL,
  `status` enum('active','inactive','repair') DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `category` enum('Products','Materials/Supplies') NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `unit` varchar(50) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `reorder_level` int(11) NOT NULL DEFAULT 10,
  `supplier` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`id`, `product_name`, `category`, `quantity`, `unit`, `unit_price`, `reorder_level`, `supplier`, `created_at`, `updated_at`) VALUES
(1, 'Hardbound Book', 'Products', 98, 'pcs', '350.00', 20, 'Star Paper Corporation', '2026-01-07 18:42:33', '2026-01-08 11:10:05'),
(2, 'Softbound Book', 'Products', 100, 'pcs', '100.00', 50, 'Star Paper Corporation', '2026-01-07 18:42:33', '2026-01-07 18:42:33'),
(3, 'Receipt (1 dozen)', 'Products', 50, 'dozen', '2000.00', 10, 'Star Paper Corporation', '2026-01-07 18:42:33', '2026-01-07 18:42:33'),
(4, 'Receipt (100 books/pad)', 'Products', 30, 'pad', '4000.00', 5, 'Star Paper Corporation', '2026-01-07 18:42:33', '2026-01-07 18:42:33'),
(5, 'Carbonless Paper', 'Materials/Supplies', 44, 'rem', '400.00', 10, 'Star Paper Corporation', '2026-01-07 18:42:33', '2026-01-08 11:23:19'),
(6, 'Colored Bondpaper', 'Materials/Supplies', 500, 'pcs', '10.00', 100, 'Star Paper Corporation', '2026-01-07 18:42:33', '2026-01-07 18:42:33'),
(7, 'Kartolina', 'Materials/Supplies', 300, 'pcs', '8.00', 50, 'Star Paper Corporation', '2026-01-07 18:42:33', '2026-01-07 18:42:33'),
(8, 'Onion Skin Paper', 'Materials/Supplies', 20, 'rem', '1300.00', 5, 'Star Paper Corporation', '2026-01-07 18:42:33', '2026-01-07 18:42:33'),
(9, 'po', 'Materials/Supplies', 5, 'pcs', '200.00', 1, 'no', '2026-01-07 18:58:49', '2026-01-07 18:58:49');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_materials`
--

CREATE TABLE `inventory_materials` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `material_type` enum('paper','ink','cardboard','binding','other') NOT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `current_stock` int(11) DEFAULT 0,
  `reorder_level` int(11) DEFAULT 5,
  `supplier_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `inventory_materials`
--

INSERT INTO `inventory_materials` (`id`, `name`, `material_type`, `unit`, `unit_price`, `current_stock`, `reorder_level`, `supplier_id`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'Carbonless Paper', 'paper', 'ream', '400.00', 0, 10, 1, NULL, '2026-01-03 14:51:31', '2026-01-03 14:51:31'),
(2, 'Colored Bondpaper', 'paper', 'piece', '10.00', 0, 100, 1, NULL, '2026-01-03 14:51:31', '2026-01-03 14:51:31'),
(3, 'Kartolina', 'cardboard', 'piece', '8.00', 0, 50, 1, NULL, '2026-01-03 14:51:31', '2026-01-03 14:51:31'),
(4, 'Onion Skin', 'paper', 'ream', '1300.00', 0, 5, 1, NULL, '2026-01-03 14:51:31', '2026-01-03 14:51:31');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_transactions`
--

CREATE TABLE `inventory_transactions` (
  `id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `material_id` int(11) DEFAULT NULL,
  `transaction_type` enum('in','out','adjustment') NOT NULL,
  `quantity` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `order_date` date NOT NULL,
  `delivery_date` date DEFAULT NULL,
  `delivery_address` text DEFAULT NULL,
  `status` enum('pending','in_progress','completed','cancelled') DEFAULT 'pending',
  `total_amount` decimal(12,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `order_date`, `delivery_date`, `delivery_address`, `status`, `total_amount`, `notes`, `created_at`, `updated_at`) VALUES
(4, 'ORD-20260104094153', 10, '2026-01-04', '2026-01-04', '', 'completed', '350.00', 'asdf', '2026-01-04 08:41:53', '2026-01-07 18:36:37'),
(5, 'ORD-20260107190943', 10, '2026-01-08', '2026-01-09', '', 'in_progress', '2000.00', 'fd', '2026-01-07 18:09:43', '2026-01-07 18:36:09'),
(6, 'ORD-20260107191434', 10, '2026-01-08', '2026-01-09', '', 'in_progress', '2350.00', '456', '2026-01-07 18:14:34', '2026-01-07 18:36:19'),
(7, 'ORD-20260107191755', 11, '2026-01-08', '2026-01-08', 'molave', 'in_progress', '350.00', '5', '2026-01-07 18:17:55', '2026-01-07 18:36:29');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(12,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `unit_price`, `total_price`, `created_at`) VALUES
(7, 4, 1, 1, '350.00', '350.00', '2026-01-04 08:41:53'),
(8, 5, 3, 1, '2000.00', '2000.00', '2026-01-07 18:09:43'),
(9, 6, 1, 1, '350.00', '350.00', '2026-01-07 18:14:34'),
(10, 6, 3, 1, '2000.00', '2000.00', '2026-01-07 18:14:34'),
(11, 7, 1, 1, '350.00', '350.00', '2026-01-07 18:17:55');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` enum('cash','check','online_transfer','credit_card') NOT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `payment_type` enum('full','partial','downpayment') DEFAULT 'partial',
  `notes` text DEFAULT NULL,
  `recorded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `order_id`, `amount`, `payment_date`, `payment_method`, `reference_number`, `payment_type`, `notes`, `recorded_by`, `created_at`) VALUES
(1, 4, '200.00', '2026-01-04', 'cash', NULL, 'downpayment', NULL, NULL, '2026-01-04 08:41:53'),
(2, 5, '200.00', '2026-01-08', 'cash', NULL, 'downpayment', NULL, NULL, '2026-01-07 18:09:43'),
(3, 6, '500.00', '2026-01-08', 'cash', NULL, 'downpayment', NULL, NULL, '2026-01-07 18:14:34'),
(4, 7, '200.00', '2026-01-08', 'cash', NULL, 'downpayment', NULL, NULL, '2026-01-07 18:17:55');

-- --------------------------------------------------------

--
-- Table structure for table `payroll`
--

CREATE TABLE `payroll` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `payroll_period_start` date NOT NULL,
  `payroll_period_end` date NOT NULL,
  `days_worked` int(11) DEFAULT 0,
  `overtime_hours` decimal(5,2) DEFAULT 0.00,
  `daily_rate` decimal(10,2) DEFAULT NULL,
  `overtime_rate` decimal(10,2) DEFAULT NULL,
  `basic_pay` decimal(12,2) DEFAULT NULL,
  `overtime_pay` decimal(12,2) DEFAULT 0.00,
  `gross_pay` decimal(12,2) DEFAULT NULL,
  `deductions` decimal(12,2) DEFAULT 0.00,
  `net_pay` decimal(12,2) DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `status` enum('pending','approved','paid') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category` enum('hardbound','softbound','receipt','custom','other') NOT NULL,
  `description` text DEFAULT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `unit_type` varchar(50) DEFAULT NULL,
  `current_stock` int(11) DEFAULT 0,
  `reorder_level` int(11) DEFAULT 10,
  `supplier_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `category`, `description`, `unit_price`, `unit_type`, `current_stock`, `reorder_level`, `supplier_id`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Hardbound Book', 'hardbound', 'Hardbound printed books', '350.00', 'piece', 0, 10, NULL, 1, '2026-01-03 14:51:31', '2026-01-03 14:51:31'),
(2, 'Softbound Book', 'softbound', 'Softbound printed books', '100.00', 'piece', 0, 20, NULL, 1, '2026-01-03 14:51:31', '2026-01-03 14:51:31'),
(3, 'Receipt (1 dozen)', 'receipt', 'Receipt books - 1 dozen', '2000.00', 'set', 0, 5, NULL, 1, '2026-01-03 14:51:31', '2026-01-03 14:51:31'),
(4, 'Receipt (100 books/pad)', 'receipt', 'Receipt books - 100 books per pad', '4000.00', 'pad', 0, 3, NULL, 1, '2026-01-03 14:51:31', '2026-01-03 14:51:31');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `report_type` enum('sales','inventory','payroll','attendance','daily','weekly','monthly','yearly') NOT NULL,
  `report_date` date NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `total_sales` decimal(12,2) DEFAULT NULL,
  `total_orders` int(11) DEFAULT NULL,
  `low_stock_items` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `sale_date` datetime NOT NULL,
  `total_amount` decimal(12,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `sale_date`, `total_amount`, `created_at`) VALUES
(1, '2026-01-08 15:43:00', '1600.00', '2026-01-08 07:43:18'),
(9, '2026-01-08 16:11:00', '300.00', '2026-01-08 08:12:59'),
(10, '2026-01-08 19:01:00', '500.00', '2026-01-08 11:01:44'),
(11, '2026-01-08 19:01:00', '500.00', '2026-01-08 11:04:38'),
(12, '2026-01-08 19:05:00', '500.00', '2026-01-08 11:05:33'),
(13, '2026-01-08 19:10:00', '700.00', '2026-01-08 11:10:05'),
(14, '2026-01-08 19:23:00', '800.00', '2026-01-08 11:23:19');

-- --------------------------------------------------------

--
-- Table structure for table `sales_transactions`
--

CREATE TABLE `sales_transactions` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `total_sales` decimal(12,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `sale_items`
--

CREATE TABLE `sale_items` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `product_name` varchar(255) DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `sale_items`
--

INSERT INTO `sale_items` (`id`, `sale_id`, `inventory_id`, `quantity`, `unit_price`, `subtotal`, `created_at`, `product_name`, `unit`) VALUES
(1, 1, 5, 4, '400.00', '1600.00', '2026-01-08 07:43:18', NULL, NULL),
(2, 9, 0, 2, '150.00', '300.00', '2026-01-08 08:12:59', 'bond papaer', 'rem'),
(3, 10, 0, 2, '250.00', '500.00', '2026-01-08 11:01:44', 'bond papaer', 'box'),
(4, 11, 0, 2, '250.00', '500.00', '2026-01-08 11:04:38', 'bond papaer', 'box'),
(5, 12, 0, 2, '250.00', '500.00', '2026-01-08 11:05:33', 'bond papaer', 'rem'),
(6, 13, 1, 2, '350.00', '700.00', '2026-01-08 11:10:05', 'bondpaper', 'rem'),
(7, 14, 5, 2, '400.00', '800.00', '2026-01-08 11:23:19', 'Carbonless Paper', '');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `address`, `city`, `contact_person`, `phone`, `email`, `created_at`) VALUES
(1, 'Star Paper Corporation', 'Business Address', 'Cagayan de Oro', 'Sales Department', '+63-88-123-4567', 'sales@starpaper.com', '2026-01-03 14:51:31');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` enum('staff','admin') NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `role`, `full_name`, `phone`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'staff', '$2y$10$Yb8bwThfxgv.5sGIMQ7ghu3.n7vDCW4qx75RHUaegSRsUnWPtJUgu', 'staff@gmail.com', 'staff', 'Staff User', NULL, 1, '2026-01-03 14:51:31', '2026-01-08 11:51:39'),
(2, 'admin', '$2y$10$P.H.i5JI1EV//II0Rik5pe54Sa/5rYjBX9WuymJdet7IpbL9E6Bba', 'admin@flordeliz.com', 'admin', 'Administrator', NULL, 1, '2026-01-03 14:51:31', '2026-01-08 10:50:58'),
(3, 'roneil', '$2y$10$0rrWI5yt6NN67dBakr1woOSQzkLErNF5BX8TJcFjBzrRT/9zCSlVO', 'roneilbansas5222@gmail.com', 'staff', 'roneil bansas', '09454739384', 1, '2026-01-08 11:39:35', '2026-01-08 11:39:35'),
(4, 'oliver', '$2y$10$pAqvdeOIOjmWzvD3SoEXPOOdK.FvFGKR4eSg60c2rFK3cV1NjpVom', 'oliver@gmail.com', 'staff', 'oliver oliver', '09454739384', 1, '2026-01-08 11:40:40', '2026-01-08 11:40:40');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_attendance_employee` (`employee_id`),
  ADD KEY `idx_attendance_date` (`attendance_date`);

--
-- Indexes for table `client_notifications`
--
ALTER TABLE `client_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `sent_by` (`sent_by`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `equipment`
--
ALTER TABLE `equipment`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_materials`
--
ALTER TABLE `inventory_materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_inventory_product` (`product_id`),
  ADD KEY `idx_inventory_material` (`material_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `idx_order_customer` (`customer_id`),
  ADD KEY `idx_order_status` (`status`),
  ADD KEY `idx_order_date` (`order_date`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `recorded_by` (`recorded_by`),
  ADD KEY `idx_payment_order` (`order_id`);

--
-- Indexes for table `payroll`
--
ALTER TABLE `payroll`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payroll_employee` (`employee_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sales_transactions`
--
ALTER TABLE `sales_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_sales_date` (`transaction_date`);

--
-- Indexes for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `inventory_id` (`inventory_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `client_notifications`
--
ALTER TABLE `client_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `equipment`
--
ALTER TABLE `equipment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `inventory_materials`
--
ALTER TABLE `inventory_materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `payroll`
--
ALTER TABLE `payroll`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `sales_transactions`
--
ALTER TABLE `sales_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sale_items`
--
ALTER TABLE `sale_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`);

--
-- Constraints for table `client_notifications`
--
ALTER TABLE `client_notifications`
  ADD CONSTRAINT `client_notifications_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `client_notifications_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `client_notifications_ibfk_3` FOREIGN KEY (`sent_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `inventory_materials`
--
ALTER TABLE `inventory_materials`
  ADD CONSTRAINT `inventory_materials_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`);

--
-- Constraints for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD CONSTRAINT `inventory_transactions_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `inventory_transactions_ibfk_2` FOREIGN KEY (`material_id`) REFERENCES `inventory_materials` (`id`),
  ADD CONSTRAINT `inventory_transactions_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `payroll`
--
ALTER TABLE `payroll`
  ADD CONSTRAINT `payroll_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`);

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `sales_transactions`
--
ALTER TABLE `sales_transactions`
  ADD CONSTRAINT `sales_transactions_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `sales_transactions_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
