CREATE TABLE `academic_years` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `initial_date` date default NULL,
  `final_date` date default NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `initial_date_idx` (`initial_date`),
  KEY `end_date_idx` (`final_date`)
) ENGINE = MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `degrees` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `institution_id` INT(11) NOT NULL,
  `code` int(11) NOT NULL,
  `acronym` VARCHAR(16) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `levels` TEXT NOT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `acronym_idx` (`institution_id`ASC, `acronym` ASC)
) ENGINE = MyISAM DEFAULT CHARSET=utf8;

ALTER TABLE `users` 
ADD COLUMN `super_admin` TINYINT(1) NOT NULL DEFAULT 0 AFTER `type`;

ALTER TABLE `courses` 
DROP COLUMN `name`,
DROP INDEX `name_idx`
;

ALTER TABLE `courses` 
ADD COLUMN `academic_year_id` INT(11) NOT NULL AFTER `id`,
ADD COLUMN `degree_id` INT(11) NOT NULL AFTER `institution_id`
;

ALTER TABLE `subjects` 
CHANGE COLUMN `practice_responsible_id` `practice_responsible_id` INT(11) NULL,
DROP COLUMN `degree`;

ALTER TABLE `subjects_users` 
DROP COLUMN `course_id`,
DROP INDEX `course_idx`;