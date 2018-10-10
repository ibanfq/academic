ALTER TABLE `subjects` 
ADD COLUMN `degree` VARCHAR(255) NULL AFTER `code`;

ALTER TABLE `classrooms` 
ADD COLUMN `teachers_can_booking` TINYINT(1) NOT NULL DEFAULT 0 AFTER `show_tv`;

ALTER TABLE `events` 
ADD COLUMN `show_tv` TINYINT(1) NOT NULL DEFAULT 0 AFTER `teacher_2_id`;

ALTER TABLE `bookings` 
ADD COLUMN `show_tv` TINYINT(1) NOT NULL DEFAULT 0 AFTER `required_equipment`,
ADD INDEX `initial_hour_idx` (`initial_hour` ASC);
