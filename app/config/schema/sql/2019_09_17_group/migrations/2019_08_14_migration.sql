CREATE TABLE `institutions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `acronym` VARCHAR(16) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `audit_email` VARCHAR(255) NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `acronym_idx` (`acronym` ASC))
ENGINE = MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `users_institutions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `institution_id` INT(11) NOT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 0,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `user_idx` (`user_id` ASC))
  INDEX `institution_idx` (`institution_id` ASC),
ENGINE = MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `classrooms_institutions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `classroom_id` INT(11) NOT NULL,
  `institution_id` INT(11) NOT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `classroom_idx` (`classroom_id` ASC),
  INDEX `institution_idx` (`institution_id` ASC))
ENGINE = MyISAM DEFAULT CHARSET=utf8;


ALTER TABLE `bookings` 
ADD COLUMN `institution_id` INT(11) NOT NULL AFTER `id`;

ALTER TABLE `classrooms` 
ADD COLUMN `institution_id` INT(11) NOT NULL AFTER `id`;

ALTER TABLE `courses` 
ADD COLUMN `institution_id` INT(11) NOT NULL AFTER `id`;

ALTER TABLE `monitors` 
ADD COLUMN `institution_id` INT(11) NOT NULL AFTER `id`;

ALTER TABLE `log` 
ADD COLUMN `institution_id` INT(11) NULL AFTER `id`;


ALTER TABLE `bookings` 
ADD INDEX `institution_idx` (`institution_id` ASC);

ALTER TABLE `classrooms` 
ADD INDEX `institution_idx` (`institution_id` ASC);

ALTER TABLE `courses` 
ADD INDEX `institution_idx` (`institution_id` ASC);

ALTER TABLE `monitors` 
ADD INDEX `institution_idx` (`institution_id` ASC);

ALTER TABLE `attendance_registers` 
ADD INDEX `secret_code_idx` (`secret_code` ASC);

ALTER TABLE `log` 
ADD INDEX `institution_idx` (`institution_id` ASC);
