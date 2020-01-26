CREATE TABLE `auth_tokens` (
  `token` VARCHAR(120) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `device` VARCHAR(120) NULL,
  `created` DATETIME NULL,
  `last_used` DATETIME NULL,
  PRIMARY KEY (`token`));

ALTER TABLE `users_attendance_register` 
  ADD COLUMN `id` INT(11) NOT NULL AUTO_INCREMENT FIRST,
  ADD PRIMARY KEY (`id`);
