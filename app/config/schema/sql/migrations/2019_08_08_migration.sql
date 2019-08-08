ALTER TABLE `competence` 
ADD INDEX `course_idx` (`course_id` ASC);
;

ALTER TABLE `competence_criteria` 
ADD INDEX `goal_idx` (`goal_id` ASC);
;
