CREATE TABLE IF NOT EXISTS `vehicles` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `vehicle_type`  ENUM('รถยนต์','รถจักรยานยนต์','รถกระบะ','รถตู้','อื่นๆ') NOT NULL COMMENT 'ประเภทยานพาหนะ',
  `license_plate` VARCHAR(20) NOT NULL COMMENT 'ป้ายทะเบียน',
  `province`      VARCHAR(50) NULL COMMENT 'จังหวัด',
  `brand`         VARCHAR(100) NULL COMMENT 'ยี่ห้อ',
  `model`         VARCHAR(100) NULL COMMENT 'รุ่น',
  `color`         VARCHAR(50) NULL COMMENT 'สี',
  `note`          TEXT NULL COMMENT 'หมายเหตุ',
  `active`        TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'สถานะ',
  `created`       DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated`       DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
