-- =========================================================
-- Hotel Management System - Import to InfinityFree phpMyAdmin
-- Database: if0_42865773_hotel_management_system
-- =========================================================

-- ---------------------------------------------------------
-- Users (system logins)
-- ---------------------------------------------------------
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

-- ---------------------------------------------------------
-- Room types
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `room_types` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `type_name` VARCHAR(50) NOT NULL,
  `description` TEXT,
  `price_per_night` DECIMAL(10,2) NOT NULL,
  `capacity` INT(11) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Rooms
-- ---------------------------------------------------------
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

-- ---------------------------------------------------------
-- Guests
-- ---------------------------------------------------------
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

-- ---------------------------------------------------------
-- Reservations
-- ---------------------------------------------------------
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

-- ---------------------------------------------------------
-- Check-ins
-- ---------------------------------------------------------
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

-- ---------------------------------------------------------
-- Payments
-- ---------------------------------------------------------
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

-- ---------------------------------------------------------
-- Extra charges
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `extra_charges` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `check_in_id` INT(11) DEFAULT NULL,
  `charge_type` ENUM('Room Service','Laundry','Restaurant','Mini Bar','Damage Fee','Other') NOT NULL,
  `description` TEXT,
  `amount` DECIMAL(10,2) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Contacts
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `contacts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20),
  `subject` VARCHAR(200) NOT NULL,
  `message` TEXT NOT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Maintenance requests
-- ---------------------------------------------------------
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

-- ---------------------------------------------------------
-- Housekeeping tasks
-- ---------------------------------------------------------
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

-- ---------------------------------------------------------
-- Notifications
-- ---------------------------------------------------------
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

-- ---------------------------------------------------------
-- System settings
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `system_settings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(50) NOT NULL,
  `setting_value` TEXT,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB;

-- =========================================================
-- SEED DATA
-- =========================================================

-- Admin user (password: admin123)
INSERT INTO `users` (`username`, `password`, `full_name`, `email`, `phone`, `role`, `status`) VALUES
('admin', '$2y$10$9/Qp4ILeDWK1gB/tVF1k9OYZUHhWTtDzrfW0BtETJy0d8mxJROeE.', 'System Administrator', 'admin@arizu-arimato.com', '085373940', 'Administrator', 'Active');

-- Room Types
INSERT INTO `room_types` (`type_name`, `description`, `price_per_night`, `capacity`) VALUES
('Sweet Room', 'Cozy and romantic room perfect for couples, with queen bed and beautiful interior design.', 50.00, 2),
('VIP Room', 'Full luxury apartment with kitchen, dining area, living room, and separate bedroom. Perfect for families.', 100.00, 5),
('Deluxe Double Check', 'Elegant room featuring a comfortable bed, modern amenities, and colonial-style decor.', 55.00, 2),
('Family Garden Room', 'Spacious family room with multiple beds, garden views, and en-suite bathroom. Ideal for families.', 90.00, 4),
('Premier Deluxe Room', 'Premium deluxe room with king bed, private balcony, city views, and luxurious bathroom with rain shower.', 60.00, 3),
('Family Private Pool', 'Premium family suite with private pool access, luxury amenities, and spacious living area.', 150.00, 4),
('Prime Deluxe Twin Room', 'Elegant twin room with two separate beds, modern decor, and all premium amenities.', 65.00, 2);

-- Rooms (at least 2 rooms per type)
INSERT INTO `rooms` (`room_number`, `room_type_id`, `floor`, `capacity`, `price_per_night`, `description`, `status`) VALUES
-- Sweet Room (type 1)
('101', 1, 1, 2, 50.00, 'Cozy sweet room floor 1', 'Available'),
('102', 1, 1, 2, 50.00, 'Cozy sweet room floor 1', 'Available'),
('103', 1, 1, 2, 50.00, 'Cozy sweet room floor 1', 'Available'),
-- VIP Room (type 2)
('VIP001', 2, 3, 5, 100.00, 'VIP luxury suite floor 3', 'Available'),
('VIP002', 2, 3, 5, 100.00, 'VIP luxury suite floor 3', 'Available'),
-- Deluxe Double Check (type 3)
('104', 3, 1, 2, 55.00, 'Deluxe double room floor 1', 'Available'),
('105', 3, 1, 2, 55.00, 'Deluxe double room floor 1', 'Available'),
('106', 3, 1, 2, 55.00, 'Deluxe double room floor 1', 'Available'),
-- Family Garden Room (type 4)
('201', 4, 2, 4, 90.00, 'Family garden room floor 2', 'Available'),
('202', 4, 2, 4, 90.00, 'Family garden room floor 2', 'Available'),
-- Premier Deluxe Room (type 5)
('203', 5, 2, 3, 60.00, 'Premier deluxe room floor 2', 'Available'),
('204', 5, 2, 3, 60.00, 'Premier deluxe room floor 2', 'Available'),
-- Family Private Pool (type 6)
('301', 6, 3, 4, 150.00, 'Family suite with private pool floor 3', 'Available'),
('302', 6, 3, 4, 150.00, 'Family suite with private pool floor 3', 'Available'),
-- Prime Deluxe Twin Room (type 7)
('205', 7, 2, 2, 65.00, 'Deluxe twin room floor 2', 'Available'),
('206', 7, 2, 2, 65.00, 'Deluxe twin room floor 2', 'Available');

-- System Settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES
('hotel_name', 'Arizu Arimato'),
('hotel_address', 'Siem Reap'),
('hotel_phone', '085373940'),
('hotel_email', 'kompheakkeo1111@gmail.com'),
('currency', 'USD'),
('tax_rate', '10.00');

-- Sample Guests
INSERT INTO `guests` (`full_name`, `gender`, `date_of_birth`, `nationality`, `phone`, `email`, `address`, `identification_number`) VALUES
('John Smith', 'Male', '1985-05-15', 'American', '1234567890', 'john@email.com', '123 Main St, USA', 'US123456'),
('Maria Garcia', 'Female', '1990-08-22', 'Spanish', '0987654321', 'maria@email.com', '456 Madrid St, Spain', 'ES789012'),
('Chen Wei', 'Male', '1988-12-10', 'Chinese', '1122334455', 'chen@email.com', '789 Beijing St, China', 'CN345678'),
('Sarah Johnson', 'Female', '1992-03-18', 'British', '5566778899', 'sarah@email.com', '321 London St, UK', 'UK901234');
