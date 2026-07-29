-- ============================================================
-- Migration: จัดการพนักงาน (employee) + สิทธิ์ดูแผนที่ลูกค้า
-- ============================================================
-- วิธีใช้: import ไฟล์นี้เข้า database เดิมได้เลย (phpMyAdmin หรือ mysql CLI)
-- เป็น additive migration ล้วนๆ ไม่ลบ/ไม่แก้ข้อมูลเดิมที่มีอยู่
-- ทำก่อน deploy โค้ดชุดนี้ ไม่งั้นหน้าเว็บจะ error เพราะหาคอลัมน์/ตารางไม่เจอ
-- ============================================================

-- 1) เพิ่ม role ใหม่ 'employee' และคอลัมน์ที่ใช้กับพนักงานทั่วไป
--    (ไม่แตะ id / role เดิม (admin, staff) ยังอยู่ครบ ข้อมูลเดิมไม่หาย)
ALTER TABLE `users`
  MODIFY `role` ENUM('admin','staff','employee') COLLATE utf8mb4_unicode_ci DEFAULT 'staff',
  ADD COLUMN `first_name` VARCHAR(100) COLLATE utf8mb4_unicode_ci NULL AFTER `full_name`,
  ADD COLUMN `last_name`  VARCHAR(100) COLLATE utf8mb4_unicode_ci NULL AFTER `first_name`,
  ADD COLUMN `phone`      VARCHAR(30)  COLLATE utf8mb4_unicode_ci NULL AFTER `last_name`,
  ADD COLUMN `position`   VARCHAR(150) COLLATE utf8mb4_unicode_ci NULL AFTER `phone`,
  ADD COLUMN `active`     TINYINT(1) NOT NULL DEFAULT 1 AFTER `position`,
  ADD COLUMN `map_access` TINYINT(1) NOT NULL DEFAULT 0 AFTER `active` COMMENT 'สิทธิ์ดูแผนที่ลูกค้า (เฉพาะ role=employee)';

-- 2) ตารางคำขอสิทธิ์ดูแผนที่ลูกค้า (admin ร้องขอ / superadmin อนุมัติ-ปฏิเสธ)
--    หมายเหตุสำคัญ: บัญชี superadmin เป็น hardcode ในโค้ด ไม่มีแถวจริงใน users (id เป็น null)
--    จึงเก็บ requested_by / decided_by เป็น username (string) คู่กับ id แบบ nullable
--    เพื่อไม่ให้พังตอน superadmin เป็นคนอนุมัติ (id จะเป็น NULL เสมอในกรณีนั้น)
CREATE TABLE IF NOT EXISTS `employee_map_requests` (
  `id`              INT(11) NOT NULL AUTO_INCREMENT,
  `employee_id`     INT(11) NOT NULL COMMENT 'อ้างอิง users.id (role=employee) แบบ soft ไม่ใช้ FK constraint',
  `requested_by`    VARCHAR(100) COLLATE utf8mb4_unicode_ci NULL COMMENT 'username ของคนขอ (admin หรือ superadmin)',
  `requested_by_id` INT(11) NULL COMMENT 'users.id ของคนขอ, NULL ถ้าเป็น superadmin',
  `status`          ENUM('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `decided_by`      VARCHAR(100) COLLATE utf8mb4_unicode_ci NULL COMMENT 'username ของคนอนุมัติ/ปฏิเสธ (superadmin เสมอ)',
  `decided_by_id`   INT(11) NULL,
  `note`            VARCHAR(255) COLLATE utf8mb4_unicode_ci NULL,
  `requested_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `decided_at`      DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
