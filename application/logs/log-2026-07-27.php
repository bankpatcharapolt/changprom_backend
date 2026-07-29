<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

ERROR - 2026-07-27 10:53:07 --> Query error: Table 'warawat121_service_management.employee_map_requests' doesn't exist - Invalid query: SELECT u.id, u.username, u.email, u.first_name, u.last_name, u.full_name, u.phone, 
                   u.position, u.active, u.map_access, u.created_at, 
                  (SELECT COUNT(*) FROM employee_map_requests r WHERE r.employee_id = u.id AND r.status = 'pending') AS has_pending_request 
            FROM users u 
            WHERE u.role = 'employee' 
            ORDER BY u.first_name ASC
ERROR - 2026-07-27 10:53:07 --> Severity: error --> Exception: Call to a member function result_array() on bool C:\xampp\htdocs\service_management\application\models\Employee_model.php 44
ERROR - 2026-07-27 10:53:07 --> Query error: Table 'warawat121_service_management.employee_map_requests' doesn't exist - Invalid query: SELECT u.id, u.username, u.email, u.first_name, u.last_name, u.full_name, u.phone, u.position, u.active, u.map_access, u.created_at, (SELECT COUNT(*) FROM employee_map_requests r WHERE r.employee_id = u.id AND r.status = 'pending') AS has_pending_request
FROM `users` `u`
WHERE `u`.`role` = 'employee'
ORDER BY `u`.`first_name` ASC
 LIMIT 25
ERROR - 2026-07-27 10:53:07 --> Severity: error --> Exception: Call to a member function result_array() on bool C:\xampp\htdocs\service_management\application\models\Employee_model.php 96
ERROR - 2026-07-27 10:55:15 --> Query error: Column 'email' cannot be null - Invalid query: INSERT INTO `users` (`first_name`, `last_name`, `full_name`, `email`, `phone`, `position`, `active`, `username`, `password`, `created_at`, `map_access`, `role`) VALUES ('ทดสอบพนักงาน', 'ทดสอบพนักงาน', 'ทดสอบพนักงาน ทดสอบพนักงาน', NULL, '0893332424', 'พนักงานคลัง', 1, 'test1', '$2y$10$qrkteeLdCntvZvj4ke51buBgxsMFXgs8VeQKqykN2YyoJ196z9Fg6', '2026-07-27 10:55:15', 0, 'employee')
ERROR - 2026-07-27 10:57:07 --> Query error: Column 'email' cannot be null - Invalid query: INSERT INTO `users` (`first_name`, `last_name`, `full_name`, `email`, `phone`, `position`, `active`, `username`, `password`, `created_at`, `map_access`, `role`) VALUES ('test2', 'test2', 'test2 test2', NULL, '1234', NULL, 1, 'test2', '$2y$10$lYF3vlnUtvCocr6CSNMyRe6962CM6lwcq6Jb19mS/ZAaiIa9P1FFu', '2026-07-27 10:57:07', 0, 'employee')
ERROR - 2026-07-27 10:57:59 --> Query error: Column 'email' cannot be null - Invalid query: INSERT INTO `users` (`first_name`, `last_name`, `full_name`, `email`, `phone`, `position`, `active`, `username`, `password`, `created_at`, `map_access`, `role`) VALUES ('test2', 'test2', 'test2 test2', NULL, '0893332424', NULL, 1, 'test2', '$2y$10$2wMjiQEhzjbx/f3L4dd.AOA0W7Ho/nNzvIQdgkDG29TRFxskAiM2G', '2026-07-27 10:57:59', 0, 'employee')
ERROR - 2026-07-27 10:58:43 --> Query error: Column 'email' cannot be null - Invalid query: INSERT INTO `users` (`first_name`, `last_name`, `full_name`, `email`, `phone`, `position`, `active`, `username`, `password`, `created_at`, `map_access`, `role`) VALUES ('test2', 'test2', 'test2 test2', NULL, '0893332424', NULL, 1, 'test2', '$2y$10$6FwAJdxBGOkRMUebYrcfjelbjVys7cehyua5GWTrYXKlhNC00rK4m', '2026-07-27 10:58:43', 0, 'employee')
