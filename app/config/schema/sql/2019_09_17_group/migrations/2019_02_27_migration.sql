ALTER TABLE `subjects` 
ADD COLUMN `closed_attendance_groups` TINYINT(1) NULL DEFAULT 0 AFTER `practice_responsible_id`;
