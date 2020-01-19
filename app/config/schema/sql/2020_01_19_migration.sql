ALTER TABLE `subjects` 
ADD COLUMN `parent_id` INT(11) NULL AFTER `course_id`,
CHANGE COLUMN `coordinator_id` `coordinator_id` INT(11) NULL;

ALTER TABLE `subjects_users` 
ADD COLUMN `child_subject_id` INT(11) NULL AFTER `subject_id`;
