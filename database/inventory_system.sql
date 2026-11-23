-- ========================================================
-- Centro Escolar México Junior College - IT Department
-- Unified Inventory Management & Stock Control System
-- Database Schema & Realistic Seed Data
-- ========================================================

CREATE DATABASE IF NOT EXISTS `inventory_system` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `inventory_system`;

-- --------------------------------------------------------
-- 1. Table structure for table `users`
-- Purpose: Stores user account details and manages authentication and roles.
-- --------------------------------------------------------
DROP TABLE IF EXISTS `stock_logs`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(191) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 2. Table structure for table `categories`
-- Purpose: Holds the inventory category definitions (e.g., Electronics).
-- --------------------------------------------------------
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 3. Table structure for table `products`
-- Purpose: Contains details of each inventory product.
-- --------------------------------------------------------
CREATE TABLE `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT NOT NULL,
  `sku` VARCHAR(50) NOT NULL UNIQUE,
  `name` VARCHAR(191) NOT NULL,
  `description` TEXT NULL,
  `unit_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `quantity` INT NOT NULL DEFAULT 0,
  `min_stock_threshold` INT NOT NULL DEFAULT 5,
  `image_path` VARCHAR(255) NOT NULL DEFAULT 'uploads/products/placeholder.png',
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 4. Table structure for table `stock_logs`
-- Purpose: Audits stock changes, tracks who did what, and provides a change history.
-- --------------------------------------------------------
CREATE TABLE `stock_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NULL,
  `user_id` INT NULL,
  `action_type` ENUM('CREATED', 'STOCK_IN', 'STOCK_OUT', 'UPDATED', 'DELETED') NOT NULL,
  `quantity_changed` INT NOT NULL DEFAULT 0,
  `platform` ENUM('WEB', 'MOBILE') NOT NULL DEFAULT 'WEB',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_logs_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Realistic Seed Data
-- --------------------------------------------------------

-- Seed Users:
-- Admin: admin@warehouse.com / password: admin123
-- Staff: staff@warehouse.com / password: staff123
-- Staff: maria@warehouse.com / password: staff123
INSERT INTO `users` (`id`, `full_name`, `email`, `password_hash`, `role`) VALUES
(1, 'System Administrator', 'admin@warehouse.com', '$2y$10$dblSqgSOCa2oAe09/f7bhuHKMxYOS60qshM3x.3nSE43X8M9/E1p6', 'admin'),
(2, 'Carlos Warehouse Manager', 'staff@warehouse.com', '$2y$10$0tiVxnntgmZGkUs/ui7KvOG8E2sEYUhmSAEkM7dP4ofqLzt/MfFPm', 'staff'),
(3, 'Maria Rodriguez (Staff)', 'maria@warehouse.com', '$2y$10$0tiVxnntgmZGkUs/ui7KvOG8E2sEYUhmSAEkM7dP4ofqLzt/MfFPm', 'staff');

-- Seed Categories (at least 3 categories):
INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(1, 'Electronics', 'Barcode scanners, handheld mobile terminals, smart labels, and warehouse digital accessories.'),
(2, 'Office Supplies', 'Packaging labels, commercial invoice papers, markers, tape rolls, and packaging materials.'),
(3, 'Hardware & Tools', 'Safety equipment, pallet jacks, steel strapping, utility knives, and warehouse structural fasteners.'),
(4, 'Storage & Containment', 'Heavy-duty plastic totes, steel wire shelving bins, and stackable modular organizers.');

-- Seed Products (12 realistic products across 4 categories):
INSERT INTO `products` (`id`, `category_id`, `sku`, `name`, `description`, `unit_price`, `quantity`, `min_stock_threshold`, `image_path`) VALUES
(1, 1, 'EL-SCN-001', 'Zebra Wireless Barcode Scanner', 'High-durability 1D/2D Bluetooth handheld scanner with charging cradle.', 249.99, 14, 5, 'uploads/products/scanner.png'),
(2, 1, 'EL-TRM-002', 'Android Industrial Mobile Computer', 'Rugged handheld terminal with integrated laser barcode engine and WiFi 6.', 620.00, 3, 5, 'uploads/products/terminal.png'),
(3, 1, 'EL-PRT-003', 'Thermal Shipping Label Printer', 'High-speed 4x6 direct thermal commercial label printer for warehouse dispatch.', 189.50, 8, 4, 'uploads/products/printer.png'),
(4, 2, 'OF-LBL-101', 'Direct Thermal Labels 4x6 Roll (500ct)', 'Permanent self-adhesive fanfold shipping labels for barcode printers.', 14.50, 42, 10, 'uploads/products/labels.png'),
(5, 2, 'OF-TPE-102', 'Heavy Duty Packing Tape 6-Pack', 'Industrial grade ultra-strong transparent acrylic carton sealing tape.', 18.00, 25, 8, 'uploads/products/tape.png'),
(6, 2, 'OF-MRK-103', 'Industrial Permanent Markers (Black 12pk)', 'Chisel-tip quick-drying waterproof black markers for pallet and crate marking.', 11.25, 4, 10, 'uploads/products/markers.png'),
(7, 3, 'HD-KNF-201', 'Auto-Retracting Safety Utility Knife', 'Ergonomic heavy-duty box cutter with internal blade storage for staff safety.', 8.95, 30, 10, 'uploads/products/knife.png'),
(8, 3, 'HD-GLV-202', 'Nitrile-Coated Work Gloves (Pair)', 'Level 5 cut-resistant breathable safety grip gloves for heavy parcel handling.', 6.50, 2, 15, 'uploads/products/gloves.png'),
(9, 3, 'HD-STP-203', 'Polyester Pallet Strapping Kit', 'Manual strapping tensioner and sealer tool kit with 1000m polyester coil.', 145.00, 6, 3, 'uploads/products/strapping.png'),
(10, 4, 'ST-TOT-301', 'Stackable Industrial Plastic Tote 60L', 'Heavy-duty impact-resistant nesting container with attached hinged lids.', 28.50, 18, 8, 'uploads/products/tote.png'),
(11, 4, 'ST-BIN-302', 'Steel Wire Pallet Shelf Bin', 'Collapsible galvanized steel mesh container with front half-drop gate.', 85.00, 7, 5, 'uploads/products/wire_bin.png'),
(12, 4, 'ST-STR-303', 'Stretch Wrap Film Roll 80 Gauge', 'High-clarity cast stretch plastic wrap film 18 inch x 1500 ft for pallet stability.', 21.90, 50, 12, 'uploads/products/stretch_wrap.png');

-- Seed Initial Stock Logs:
INSERT INTO `stock_logs` (`product_id`, `user_id`, `action_type`, `quantity_changed`, `platform`, `created_at`) VALUES
(1, 1, 'CREATED', 14, 'WEB', NOW() - INTERVAL 10 DAY),
(2, 1, 'CREATED', 5, 'WEB', NOW() - INTERVAL 9 DAY),
(2, 3, 'STOCK_OUT', -2, 'MOBILE', NOW() - INTERVAL 4 DAY),
(3, 1, 'CREATED', 8, 'WEB', NOW() - INTERVAL 8 DAY),
(4, 1, 'CREATED', 42, 'WEB', NOW() - INTERVAL 7 DAY),
(5, 2, 'CREATED', 25, 'WEB', NOW() - INTERVAL 7 DAY),
(6, 2, 'CREATED', 15, 'WEB', NOW() - INTERVAL 6 DAY),
(6, 3, 'STOCK_OUT', -11, 'MOBILE', NOW() - INTERVAL 2 DAY),
(7, 1, 'CREATED', 30, 'WEB', NOW() - INTERVAL 5 DAY),
(8, 1, 'CREATED', 20, 'WEB', NOW() - INTERVAL 5 DAY),
(8, 3, 'STOCK_OUT', -18, 'MOBILE', NOW() - INTERVAL 1 DAY),
(9, 1, 'CREATED', 6, 'WEB', NOW() - INTERVAL 4 DAY),
(10, 2, 'CREATED', 18, 'WEB', NOW() - INTERVAL 3 DAY),
(11, 2, 'CREATED', 7, 'WEB', NOW() - INTERVAL 2 DAY),
(12, 1, 'CREATED', 50, 'WEB', NOW() - INTERVAL 1 DAY);
