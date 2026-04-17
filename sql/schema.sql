CREATE DATABASE IF NOT EXISTS service_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE service_management;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(200),
    role ENUM('admin','staff') DEFAULT 'staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS service_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_type ENUM('ติดตั้ง','ซ่อม','ล้างเครื่อง','เปลี่ยนไส้กรอง','ส่งสินค้า','นำสินค้ากลับ') COMMENT 'ประเภทงาน',
    bill_no VARCHAR(50) NOT NULL UNIQUE COMMENT 'เลขที่บิล (จำเป็น)',
    customer_name VARCHAR(200) COMMENT 'ชื่อลูกค้า',
    purchase_date DATE COMMENT 'วันที่ซื้อ',
    address TEXT COMMENT 'ที่อยู่',
    location VARCHAR(500) COMMENT 'location',
    install_date DATE COMMENT 'วันที่นัดติดตั้ง',
    install_time TIME COMMENT 'เวลา',
    phone VARCHAR(50) COMMENT 'เบอร์โทร',
    technician VARCHAR(200) COMMENT 'ช่างรับผิดชอบ',
    tech_note TEXT COMMENT 'หมายเหตุช่าง',
    status ENUM('รอดำเนินการ','ยืนยันแล้ว','กำลังดำเนินงาน','เสร็จแล้ว','เลื่อนนัด','ยกเลิกนัด') DEFAULT 'รอดำเนินการ' COMMENT 'สถานะ',
    product_service VARCHAR(500) COMMENT 'ชื่อสินค้า/บริการ',
    bill_note TEXT COMMENT 'หมายเหตุบิล',
    tags VARCHAR(500) COMMENT 'แท็ก',
    sale_code VARCHAR(100) COMMENT 'รหัสพนักงานขาย',
    team VARCHAR(100) COMMENT 'ทีม',
    branch VARCHAR(200) COMMENT 'สาขา',
    amount DECIMAL(12,2) DEFAULT 0 COMMENT 'ยอดรวม',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS ci_sessions (
    id VARCHAR(128) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    timestamp INT(10) UNSIGNED DEFAULT 0 NOT NULL,
    data BLOB NOT NULL,
    KEY ci_sessions_timestamp (timestamp)
);

INSERT IGNORE INTO users (username, email, password, full_name, role)
VALUES ('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin');
