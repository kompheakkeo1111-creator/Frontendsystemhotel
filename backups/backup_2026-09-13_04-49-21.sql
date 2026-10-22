-- Hotel Management System Backup
-- Date: 2026-09-13 04:49:21

SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET AUTOCOMMIT = 0;
START TRANSACTION;

-- Table: check_ins
DROP TABLE IF EXISTS `check_ins`;
CREATE TABLE `check_ins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reservation_id` int(11) DEFAULT NULL,
  `guest_id` int(11) DEFAULT NULL,
  `room_id` int(11) DEFAULT NULL,
  `check_in_time` datetime NOT NULL,
  `expected_check_out` date NOT NULL,
  `actual_check_out` datetime DEFAULT NULL,
  `status` enum('Active','Checked Out') DEFAULT 'Active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4;

INSERT INTO `check_ins` (`id`, `reservation_id`, `guest_id`, `room_id`, `check_in_time`, `expected_check_out`, `actual_check_out`, `status`, `created_by`, `created_at`) VALUES
('1', '1', '1', '1', '2026-09-06 17:44:57', '2026-09-10', '2026-09-07 18:00:17', 'Checked Out', '1', '2026-09-06 17:44:57'),
('2', '4', '1', '8', '2026-09-07 18:37:07', '2026-09-09', NULL, 'Checked Out', '1', '2026-09-07 18:37:07'),
('3', '5', '4', '1', '2026-09-08 02:02:50', '2026-09-09', NULL, 'Checked Out', '1', '2026-09-08 02:02:50'),
('4', '3', '3', '8', '2026-09-08 02:06:05', '2026-09-17', '2026-09-08 02:06:20', 'Checked Out', '1', '2026-09-08 02:06:05'),
('5', '2', '2', '8', '2026-09-08 02:06:09', '2026-09-12', '2026-09-08 02:07:05', 'Checked Out', '1', '2026-09-08 02:06:09');

-- Table: contacts
DROP TABLE IF EXISTS `contacts`;
CREATE TABLE `contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

INSERT INTO `contacts` (`id`, `full_name`, `email`, `phone`, `subject`, `message`, `is_read`, `created_at`) VALUES
('1', 'Test User', 'test@example.com', '123', 'Test', 'Hello from API test', '0', '2026-09-06 16:50:15');

-- Table: extra_charges
DROP TABLE IF EXISTS `extra_charges`;
CREATE TABLE `extra_charges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `check_in_id` int(11) DEFAULT NULL,
  `charge_type` enum('Room Service','Laundry','Restaurant','Mini Bar','Damage Fee','Other') NOT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: guests
DROP TABLE IF EXISTS `guests`;
CREATE TABLE `guests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `nationality` varchar(50) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `identification_number` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4;

INSERT INTO `guests` (`id`, `full_name`, `gender`, `date_of_birth`, `nationality`, `phone`, `email`, `address`, `identification_number`, `created_at`) VALUES
('1', 'Kompheak Keo', NULL, NULL, NULL, '085373940', 'kompheakkeo1111@gmail.com', NULL, 'PENDING-6899', '2026-09-06 17:44:12'),
('3', 'CORS Test', NULL, NULL, NULL, '099887766', 'cors@test.com', NULL, 'PENDING-3760', '2026-09-07 18:33:51'),
('4', 'Nita', NULL, NULL, NULL, '0123456', 'nana@gmail.com', NULL, 'PENDING-4763', '2026-09-08 02:01:23'),
('5', 'Dara', NULL, NULL, NULL, '012345678', 'test@test.com', NULL, 'PENDING-2157', '2026-09-08 21:33:26');

-- Table: housekeeping_tasks
DROP TABLE IF EXISTS `housekeeping_tasks`;
CREATE TABLE `housekeeping_tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_id` int(11) DEFAULT NULL,
  `task_type` varchar(50) NOT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `status` enum('Pending','In Progress','Completed') DEFAULT 'Pending',
  `priority` enum('Low','Medium','High') DEFAULT 'Medium',
  `due_date` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: maintenance_requests
DROP TABLE IF EXISTS `maintenance_requests`;
CREATE TABLE `maintenance_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_id` int(11) DEFAULT NULL,
  `issue_type` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `priority` enum('Low','Medium','High','Emergency') DEFAULT 'Medium',
  `assigned_to` int(11) DEFAULT NULL,
  `status` enum('Reported','In Progress','Completed','Cancelled') DEFAULT 'Reported',
  `reported_by` int(11) DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: notifications
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: payments
DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reservation_id` int(11) DEFAULT NULL,
  `check_in_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('Cash','Credit/Debit Card','QR Payment','Bank Transfer') NOT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `payment_date` datetime DEFAULT current_timestamp(),
  `invoice_number` varchar(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4;

INSERT INTO `payments` (`id`, `reservation_id`, `check_in_id`, `amount`, `payment_method`, `transaction_id`, `payment_date`, `invoice_number`) VALUES
('1', '1', '1', '220.00', 'Cash', NULL, '2026-09-07 18:00:17', 'INV-20260907-00001'),
('2', '3', '4', '495.00', 'Cash', NULL, '2026-09-08 02:06:20', 'INV-20260907-00004'),
('3', '2', '5', '220.00', 'QR Payment', NULL, '2026-09-08 02:07:05', 'INV-20260907-00005');

-- Table: reservations
DROP TABLE IF EXISTS `reservations`;
CREATE TABLE `reservations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reservation_number` varchar(20) NOT NULL,
  `guest_id` int(11) DEFAULT NULL,
  `room_id` int(11) DEFAULT NULL,
  `check_in_date` date NOT NULL,
  `check_out_date` date NOT NULL,
  `number_of_guests` int(11) NOT NULL,
  `status` enum('Pending','Confirmed','Cancelled','Completed') DEFAULT 'Pending',
  `special_requests` text DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `reservation_number` (`reservation_number`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4;

INSERT INTO `reservations` (`id`, `reservation_number`, `guest_id`, `room_id`, `check_in_date`, `check_out_date`, `number_of_guests`, `status`, `special_requests`, `total_amount`, `created_by`, `created_at`) VALUES
('2', 'RES-20260907-9628', '2', '8', '2026-09-10', '2026-09-12', '2', 'Completed', 'Test booking', '100.00', NULL, '2026-09-07 18:33:38'),
('4', 'RES-20260907-6356', '1', '8', '2026-09-07', '2026-09-09', '2', 'Completed', '', '110.00', NULL, '2026-09-07 18:35:51'),
('5', 'RES-20260907-3554', '4', '1', '2026-09-07', '2026-09-09', '2', 'Completed', '', '110.00', NULL, '2026-09-08 02:01:23'),
('7', 'RES-20260907-6641', '1', '4', '2026-09-07', '2026-09-10', '2', 'Completed', '', '180.00', NULL, '2026-09-08 02:12:25'),
('13', 'RES-20260908-5259', '5', '1', '2026-09-20', '2026-09-22', '2', 'Confirmed', '', '110.00', NULL, '2026-09-08 21:40:46'),
('16', 'RES-20260908-2452', '4', '2', '2026-09-08', '2026-09-11', '2', 'Confirmed', '', '330.00', NULL, '2026-09-09 00:40:52'),
('17', 'RES-20260908-3864', '1', '1', '2026-09-10', '2026-09-12', '2', 'Confirmed', '', '110.00', NULL, '2026-09-09 02:00:51');

-- Table: room_types
DROP TABLE IF EXISTS `room_types`;
CREATE TABLE `room_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `price_per_night` decimal(10,2) NOT NULL,
  `capacity` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4;

INSERT INTO `room_types` (`id`, `type_name`, `description`, `price_per_night`, `capacity`, `created_at`) VALUES
('1', 'Sweet Room', 'Cozy and romantic room perfect for couples, with queen bed and beautiful interior design.', '50.00', '2', '2026-09-06 16:44:34'),
('2', 'VIP Room', 'Full luxury apartment with kitchen, dining area, living room, and separate bedroom. Perfect for families.', '100.00', '5', '2026-09-06 16:45:02'),
('3', 'Deluxe double check', 'Elegant room featuring a comfortable bed, modern amenities, and colonial-style decor.', '55.00', '2', '2026-09-06 19:10:13'),
('4', 'Family Garden room', 'Spacious family room with multiple beds, garden views, and en-suite bathroom. Ideal for families.', '90.00', '4', '2026-09-06 19:10:34'),
('5', 'Premier deluxe room', 'Premium deluxe room with king bed, private balcony, city views, and luxurious bathroom with rain shower.', '60.00', '3', '2026-09-06 19:10:55');

-- Table: rooms
DROP TABLE IF EXISTS `rooms`;
CREATE TABLE `rooms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_number` varchar(10) NOT NULL,
  `room_type_id` int(11) DEFAULT NULL,
  `floor` int(11) DEFAULT NULL,
  `capacity` int(11) DEFAULT NULL,
  `price_per_night` decimal(10,2) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('Available','Reserved','Occupied','Cleaning','Maintenance') DEFAULT 'Available',
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `room_number` (`room_number`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4;

INSERT INTO `rooms` (`id`, `room_number`, `room_type_id`, `floor`, `capacity`, `price_per_night`, `description`, `status`, `image`, `created_at`) VALUES
('1', '103', '1', '2', '2', '50.00', '', 'Available', NULL, '2026-09-06 16:45:30'),
('2', 'VIP001', '2', '1', '5', '100.00', '', 'Available', NULL, '2026-09-06 16:45:56'),
('3', '101', '3', '1', '2', '55.00', '', 'Available', NULL, '2026-09-06 19:11:37'),
('4', '102', '5', '1', '3', '60.00', '', 'Available', NULL, '2026-09-06 19:12:21'),
('5', '201', '3', '2', '2', '55.00', '', 'Available', NULL, '2026-09-06 19:13:11'),
('6', '202', '5', '2', NULL, '60.00', '', 'Available', NULL, '2026-09-06 19:13:41'),
('7', 'VIP002', '2', '2', NULL, '100.00', '', 'Available', NULL, '2026-09-06 19:14:04'),
('8', '203', '1', '2', '2', '50.00', '', 'Available', NULL, '2026-09-06 19:14:20');

-- Table: system_settings
DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4;

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
('1', 'hotel_name', 'Arizu Arimato', '2026-09-06 15:59:15'),
('2', 'hotel_address', 'siemreap', '2026-09-06 15:59:15'),
('3', 'hotel_phone', '085373940', '2026-09-06 15:59:15'),
('4', 'hotel_email', 'kompheakkeo1111@gmail.com', '2026-09-06 15:59:15'),
('5', 'currency', 'USD', '2026-09-06 15:58:38'),
('6', 'tax_rate', '10.00', '2026-09-06 15:58:38');

-- Table: users
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('Administrator','Receptionist','Manager','Housekeeping Staff','Accountant') NOT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4;

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `phone`, `role`, `status`, `created_at`, `updated_at`) VALUES
('1', 'admin', '$2y$10$o5OAzg1NEOhXKfDcmF1sruCIe1SpmK4DNThjkbOdMg7Idf6pPY81S', 'System Administrator', 'admin@hotel.local', NULL, 'Administrator', 'Active', '2026-09-06 15:58:38', '2026-09-06 15:59:19'),
('3', 'kompheak', '$2y$10$nEWcV/FGV.NNLeIyJ5CLoOdxUVD7xv720i0GAuoIOEJhzuj2P7cHO', 'Kompheak', 'kompheak@hotel.local', NULL, 'Manager', 'Active', '2026-09-06 15:59:19', '2026-09-06 15:59:19'),
('4', 'seay', '$2y$10$BB.um0MotMpL9GG7B77YDekP1e52s6Ch0lOPyJHh/kgGZK0lZTTZ.', 'Seay', 'seay@hotel.local', NULL, 'Housekeeping Staff', 'Active', '2026-09-06 15:59:19', '2026-09-06 15:59:19'),
('5', 'sokvang', '$2y$10$lNmyuhAZ9WWLTkBGcHU6X.qusEbx6VbNW5tiPaJ7RjshwxD80jshq', 'Sokvang', 'sokvang@hotel.local', NULL, 'Accountant', 'Active', '2026-09-06 15:59:19', '2026-09-06 15:59:19'),
('8', 'bopha', '$2y$10$1nBz2nVx2lMMPl0v.tN7NuW17CettauT8WfdF3ap4Gfbx5CjZ8Ojy', 'Bopha', 'bopha@hotel.local', NULL, 'Receptionist', 'Active', '2026-09-06 16:02:05', '2026-09-06 16:02:05'),
('9', 'nich', '$2y$10$uglBFgD4b3JSkbA08Xku9uznDZq.y6lD90Nzf7GEMx3PAKt9mA3r.', 'Nich', 'nich@hotel.local', NULL, 'Receptionist', 'Active', '2026-09-06 16:03:42', '2026-09-06 16:03:42');

COMMIT;
