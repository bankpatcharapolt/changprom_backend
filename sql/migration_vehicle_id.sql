-- แก้ข้อมูลวันที่ invalid ก่อน
UPDATE `service_jobs` SET `purchase_date` = NULL WHERE `purchase_date` = '0000-00-00';
UPDATE `service_jobs` SET `install_date`  = NULL WHERE `install_date`  = '0000-00-00';

-- เพิ่ม vehicle_id ไม่มี FK
ALTER TABLE `service_jobs`
  ADD COLUMN `vehicle_id` INT NULL COMMENT 'ยานพาหนะที่ใช้' AFTER `map_link`;
