ALTER TABLE `competence` 
ADD INDEX `course_idx` (`course_id` ASC);
;

ALTER TABLE `competence_criteria` 
ADD INDEX `goal_idx` (`goal_id` ASC);
;

ALTER TABLE `competence_criterion_grades` 
ADD COLUMN `teacher_id` INT(11) NULL AFTER `rubric_id`,
ADD INDEX `student_idx` (`student_id` ASC);
;

ALTER TABLE `competence_criterion_rubrics` 
ADD INDEX `criterion_idx` (`criterion_id` ASC);
;

ALTER TABLE `competence_criterion_subjects` 
ADD INDEX `criterion_idx` (`criterion_id` ASC),
ADD INDEX `subject_idx` (`subject_id` ASC);
;

ALTER TABLE `competence_criterion_teachers` 
ADD INDEX `criterion_idx` (`criterion_id` ASC),
ADD INDEX `teacher_idx` (`teacher_id` ASC);
;

ALTER TABLE `competence_goal_requests` 
ADD INDEX `goal_idx` (`goal_id` ASC),
ADD INDEX `student_idx` (`student_id` ASC),
ADD INDEX `teacher_idx` (`teacher_id` ASC),
ADD INDEX `completed_idx` (`completed` ASC),
ADD INDEX `canceled_idx` (`canceled` ASC),
ADD INDEX `rejected_idx` (`rejected` ASC);
;

ALTER TABLE `competence_goals` 
ADD INDEX `competence_idx` (`competence_id` ASC);
;
