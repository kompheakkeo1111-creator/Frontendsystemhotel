-- =============================================
-- Hotel Management System Database v1.2
-- Complete schema matching application code
-- =============================================

CREATE DATABASE IF NOT EXISTS `hotel_management_1_2`;
USE `hotel_management_1_2`;

-- =============================================
-- USERS TABLE (Admin, Staff accounts)
-- =============================================
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `role` ENUM('Administrator','Receptionist','Manager','Housekeeping Staff','Accountant') NOT NULL,
  `status` ENUM('Active','Inactive') DEFAULT 'Active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB;

-- =============================================
-- ROOM TYPES TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS `room_types` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `type_name` VARCHAR(50) NOT NULL,
  `description` TEXT,
  `price_per_night` DECIMAL(10,2) NOT NULL,
  `capacity` INT(11) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- =============================================
-- ROOMS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS `rooms` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `room_number` VARCHAR(10) NOT NULL,
  `room_type_id` INT(11) DEFAULT NULL,
  `floor` INT(11) DEFAULT NULL,
  `capacity` INT(11) DEFAULT NULL,
  `price_per_night` DECIMAL(10,2) DEFAULT NULL,
  `description` TEXT,
  `status` ENUM('Available','Reserved','Occupied','Cleaning','Maintenance') DEFAULT 'Available',
  `image` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `room_number` (`room_number`)
) ENGINE=InnoDB;

-- =============================================
-- GUESTS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS `guests` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(100) NOT NULL,
  `gender` ENUM('Male','Female','Other') DEFAULT NULL,
  `date_of_birth` DATE DEFAULT NULL,
  `nationality` VARCHAR(50) DEFAULT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `address` TEXT,
  `identification_number` VARCHAR(50) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- =============================================
-- RESERVATIONS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS `reservations` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `reservation_number` VARCHAR(20) NOT NULL,
  `guest_id` INT(11) DEFAULT NULL,
  `room_id` INT(11) DEFAULT NULL,
  `check_in_date` DATE NOT NULL,
  `check_out_date` DATE NOT NULL,
  `number_of_guests` INT(11) NOT NULL,
  `status` ENUM('Pending','Confirmed','Cancelled','Completed') DEFAULT 'Pending',
  `special_requests` TEXT,
  `total_amount` DECIMAL(10,2) DEFAULT NULL,
  `created_by` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reservation_number` (`reservation_number`)
) ENGINE=InnoDB;

-- =============================================
-- CHECK-INS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS `check_ins` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `reservation_id` INT(11) DEFAULT NULL,
  `guest_id` INT(11) DEFAULT NULL,
  `room_id` INT(11) DEFAULT NULL,
  `check_in_time` DATETIME NOT NULL,
  `expected_check_out` DATE NOT NULL,
  `actual_check_out` DATETIME DEFAULT NULL,
  `status` ENUM('Active','Checked Out') DEFAULT 'Active',
  `created_by` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- =============================================
-- PAYMENTS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `reservation_id` INT(11) DEFAULT NULL,
  `check_in_id` INT(11) DEFAULT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `payment_method` ENUM('Cash','Credit/Debit Card','QR Payment','Bank Transfer') NOT NULL,
  `transaction_id` VARCHAR(100) DEFAULT NULL,
  `payment_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `invoice_number` VARCHAR(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- =============================================
-- EXTRA CHARGES TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS `extra_charges` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `check_in_id` INT(11) DEFAULT NULL,
  `charge_type` ENUM('Room Service','Laundry','Restaurant','Mini Bar','Damage Fee','Other') NOT NULL,
  `description` TEXT,
  `amount` DECIMAL(10,2) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- =============================================
-- MAINTENANCE REQUESTS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS `maintenance_requests` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `room_id` INT(11) DEFAULT NULL,
  `issue_type` VARCHAR(50) NOT NULL,
  `description` TEXT NOT NULL,
  `priority` ENUM('Low','Medium','High','Emergency') DEFAULT 'Medium',
  `assigned_to` INT(11) DEFAULT NULL,
  `status` ENUM('Reported','In Progress','Completed','Cancelled') DEFAULT 'Reported',
  `reported_by` INT(11) DEFAULT NULL,
  `completed_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- =============================================
-- HOUSEKEEPING TASKS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS `housekeeping_tasks` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `room_id` INT(11) DEFAULT NULL,
  `task_type` VARCHAR(50) NOT NULL,
  `assigned_to` INT(11) DEFAULT NULL,
  `status` ENUM('Pending','In Progress','Completed') DEFAULT 'Pending',
  `priority` ENUM('Low','Medium','High') DEFAULT 'Medium',
  `due_date` DATETIME DEFAULT NULL,
  `completed_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- =============================================
-- NOTIFICATIONS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) DEFAULT NULL,
  `title` VARCHAR(100) NOT NULL,
  `message` TEXT NOT NULL,
  `type` VARCHAR(50) DEFAULT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- =============================================
-- SYSTEM SETTINGS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS `system_settings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(50) NOT NULL,
  `setting_value` TEXT,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB;

-- =============================================
-- DEFAULT DATA
-- =============================================

-- Default admin user (password: admin123)
INSERT IGNORE INTO `users` (`username`, `password`, `full_name`, `email`, `role`, `status`) VALUES
('admin', '$2y$10$9/Qp4ILeDWK1gB/tVF1k9OYZUHhWTtDzrfW0BtETJy0d8mxJROeE.', 'System Administrator', 'admin@hotel.local', 'Administrator', 'Active');

-- Default system settings
INSERT IGNORE INTO `system_settings` (`setting_key`, `setting_value`) VALUES
('hotel_name', 'Arizu Arimato'),
('hotel_address', 'Siem Reap, Cambodia'),
('hotel_phone', '+855 12 345 678'),
('hotel_email', 'info@arizuhotel.com'),
('currency', 'USD'),
('tax_rate', '10.00');
