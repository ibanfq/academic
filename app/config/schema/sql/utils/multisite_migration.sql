# INSTITUTIONS
INSERT INTO `institutions` (`id`, `code`, `acronym`, `name`, `created`, `modified`)
	VALUES
    (1, 185, 'FV', 'Facultad de veterinaria', now(), now()),
    (2, 110, 'EITE', 'Escuela de Ingeniería de Telecomunicación y Electrónica', now(), now());

# DEGREES
INSERT INTO `degrees` (`id`, `institution_id`, `code`, `acronym`, `name`, `created`, `modified`)
	VALUES
    (1, 1, 4025, 'GV', 'Grado en veterinaria', now(), now()),
    (2, 2, 4037, 'GITT', 'GITT (Grado en Ingeniería en Tecnologías de la Telecomunicación)', now(), now()),
    (3, 2, 4803, 'DG-GITT-ADE', 'DG-GITT+ADE (Doble Grado en GITT y ADE)', now(), now()),
    (4, 2, 5023, 'MUIT', 'MUIT (Máster en Ingeniería de Telecomunicación)', now(), now());

# ACADEMIC YEARS
INSERT INTO `academic_years` (`initial_date`, `final_date`, `created`, `modified`)
	SELECT `initial_date`, `final_date`, `created`, `modified` FROM `bd_centros_veterinaria`.`courses` ORDER BY `initial_date` ASC;

# USERS
INSERT INTO `users` (`id`, `type`, `super_admin`, `dni`, `first_name`, `last_name`, `username`, `phone`, `password`, `notify_all`, `created`, `modified`)
    SELECT
        `id`, `type`, 0, `dni`, `first_name`, `last_name`, `username`, `phone`, `password`, `notify_all`, `created`, `modified`
    FROM `bd_centros_veterinaria`.`users`
    ORDER BY `users`.`id`;

INSERT INTO `users` (`id`, `type`, `super_admin`, `dni`, `first_name`, `last_name`, `username`, `phone`, `password`, `notify_all`, `created`, `modified`)
    SELECT
        (`users`.`id` + 1 + (select max(`id`) FROM `bd_centros_veterinaria`.`users`) - (select min(`id`) FROM `bd_centros_eite`.`users`)),
        `type`, 0, `dni`, `first_name`, `last_name`, `username`, `phone`, `password`, `notify_all`, `created`, `modified`
    FROM `bd_centros_eite`.`users`
    ORDER BY `users`.`id`;

# USERS INSTITUTIONS
INSERT INTO `users_institutions` (`user_id`, `institution_id`, `active`, `created`, `modified`)
    SELECT
        `id`, 1, 1, `created`, `modified`
    FROM `bd_centros_veterinaria`.`users`
    ORDER BY `users`.`id`;

INSERT INTO `users_institutions` (`user_id`, `institution_id`, `active`, `created`, `modified`)
    SELECT
        (`users`.`id` + 1 + (select max(`id`) FROM `bd_centros_veterinaria`.`users`) - (select min(`id`) FROM `bd_centros_eite`.`users`)),
        2, 1, `created`, `modified`
    FROM `bd_centros_eite`.`users`
    ORDER BY `users`.`id`;

# CLASSROOMS
INSERT INTO `classrooms` (`id`, `institution_id`, `name`, `type`, `capacity`, `show_tv`, `teachers_can_booking`, `created`, `modified`)
    SELECT
        `id`, 1, `name`, `type`, `capacity`, `show_tv`, `teachers_can_booking`, `created`, `modified`
    FROM `bd_centros_veterinaria`.`classrooms`
    ORDER BY `classrooms`.`id`;

INSERT INTO `classrooms` (`id`, `institution_id`, `name`, `type`, `capacity`, `show_tv`, `teachers_can_booking`, `created`, `modified`)
    SELECT
        (`classrooms`.`id` + 1 + (select max(`id`) FROM `bd_centros_veterinaria`.`classrooms`) - (select min(`id`) FROM `bd_centros_eite`.`classrooms`)),
        2, `name`, `type`, `capacity`, `show_tv`, `teachers_can_booking`, `created`, `modified`
    FROM `bd_centros_eite`.`classrooms`
    ORDER BY `classrooms`.`id`;

# CLASSROOMS INSTITUTIONS
INSERT INTO `classrooms_institutions` (`classroom_id`, `institution_id`, `created`, `modified`)
    SELECT
        `id`, 1, `created`, `modified`
    FROM `bd_centros_veterinaria`.`classrooms`
    ORDER BY `classrooms`.`id`;

INSERT INTO `classrooms_institutions` (`classroom_id`, `institution_id`, `created`, `modified`)
    SELECT
        (`classrooms`.`id` + 1 + (select max(`id`) FROM `bd_centros_veterinaria`.`classrooms`) - (select min(`id`) FROM `bd_centros_eite`.`classrooms`)),
        2, `created`, `modified`
    FROM `bd_centros_eite`.`classrooms`
    ORDER BY `classrooms`.`id`;

# MONITORS
INSERT INTO `monitors` (`id`, `institution_id`, `name`, `show_events`, `show_media`, `created`, `modified`)
    SELECT
        `id`, 1, `name`, `show_events`, `show_media`, `created`, `modified`
    FROM `bd_centros_veterinaria`.`monitors`
    ORDER BY `monitors`.`id`;

INSERT INTO `monitors` (`id`, `institution_id`, `name`, `show_events`, `show_media`, `created`, `modified`)
    SELECT
        (`monitors`.`id` + 1 + (select max(`id`) FROM `bd_centros_veterinaria`.`monitors`) - (select min(`id`) FROM `bd_centros_eite`.`monitors`)),
        2, `name`, `show_events`, `show_media`, `created`, `modified`
    FROM `bd_centros_eite`.`monitors`
    ORDER BY `monitors`.`id`;

# MONITORS CLASSROOMS
INSERT INTO `monitors_classrooms` (`monitor_id`, `classroom_id`, `created`, `modified`)
    SELECT
        `monitor_id`, `classroom_id`, `created`, `modified`
    FROM `bd_centros_veterinaria`.`monitors_classrooms`
    ORDER BY `monitors_classrooms`.`monitor_id`, `monitors_classrooms`.`id`;

INSERT INTO `monitors_classrooms` (`monitor_id`, `classroom_id`, `created`, `modified`)
    SELECT
        (`monitor_id` + 1 + (select max(`id`) FROM `bd_centros_veterinaria`.`monitors`) - (select min(`id`) FROM `bd_centros_eite`.`monitors`)),
        (`classroom_id` + 1 + (select max(`id`) FROM `bd_centros_veterinaria`.`classrooms`) - (select min(`id`) FROM `bd_centros_eite`.`classrooms`)),
        `created`, `modified`
    FROM `bd_centros_eite`.`monitors_classrooms`
    ORDER BY `monitors_classrooms`.`monitor_id`, `monitors_classrooms`.`id`;

# MONITORS MEDIA
INSERT INTO `monitors_media` (`monitor_id`, `type`, `src`, `mime_type`, `video_id`, `visible`, `order`, `duration`, `created`, `modified`)
    SELECT
        `monitor_id`, `type`, `src`, `mime_type`, `video_id`, `visible`, `order`, `duration`, `created`, `modified`
    FROM `bd_centros_veterinaria`.`monitors_media`
    ORDER BY `monitors_media`.`monitor_id`, `monitors_media`.`id`;

INSERT INTO `monitors_media` (`monitor_id`, `type`, `src`, `mime_type`, `video_id`, `visible`, `order`, `duration`, `created`, `modified`)
    SELECT
        (`monitor_id` + 1 + (select max(`id`) FROM `bd_centros_veterinaria`.`monitors`) - (select min(`id`) FROM `bd_centros_eite`.`monitors`)),
        `type`, `src`, `mime_type`, `video_id`, `visible`, `order`, `duration`, `created`, `modified`
    FROM `bd_centros_eite`.`monitors_media`
    ORDER BY `monitors_media`.`monitor_id`, `monitors_media`.`id`;

# COURSES
INSERT INTO `courses` (`id`, `academic_year_id`, `institution_id`, `degree_id`, `initial_date`, `final_date`, `created`, `modified`)
	SELECT
        `courses`.`id`, `academic_years`.`id`, 1, 1, `academic_years`.`initial_date`, `academic_years`.`final_date`, `courses`.`created`, `courses`.`modified`
    FROM `bd_centros_veterinaria`.`courses`
    LEFT JOIN `academic`.`academic_years` ON YEAR(`academic_years`.`initial_date`) = YEAR(`courses`.`initial_date`)
    ORDER BY `courses`.`id`;

INSERT INTO `courses` (`id`, `academic_year_id`, `institution_id`, `degree_id`, `initial_date`, `final_date`, `created`, `modified`)
    SELECT
        (`courses`.`id` + `degrees`.`id` - 1 + (select max(`id`) FROM `bd_centros_veterinaria`.`courses`) - (select min(`id`) FROM `bd_centros_eite`.`courses`)),
        `academic_years`.`id`, 2, `degrees`.`id`, `academic_years`.`initial_date`, `academic_years`.`final_date`, `courses`.`created`, `courses`.`modified`
    FROM `bd_centros_eite`.`courses`
    LEFT JOIN `academic`.`academic_years` ON YEAR(`academic_years`.`initial_date`) = YEAR(`courses`.`initial_date`)
    LEFT JOIN `academic`.`degrees` ON `institution_id` = 2
    ORDER BY `courses`.`id`, `degrees`.`id`;

# SUBJECTS
INSERT INTO `subjects` (`id`, `course_id`, `code`, `level`, `type`, `name`, `acronym`, `semester`, `credits_number`, `coordinator_id`, `practice_responsible_id`, `closed_attendance_groups`, `created`, `modified`)
    SELECT
        `id`, `course_id`, `code`, `level`, `type`, `name`, `acronym`, `semester`, `credits_number`, `coordinator_id`, `practice_responsible_id`, `closed_attendance_groups`, `created`, `modified`
    FROM `bd_centros_veterinaria`.`subjects`
    ORDER BY `subjects`.`id`;

INSERT INTO `subjects` (`id`, `course_id`, `code`, `level`, `type`, `name`, `acronym`, `semester`, `credits_number`, `coordinator_id`, `practice_responsible_id`, `closed_attendance_groups`, `created`, `modified`)
    SELECT
        (`subjects`.`id` + 1 + (select max(`id`) FROM `bd_centros_veterinaria`.`subjects`) - (select min(`id`) FROM `bd_centros_eite`.`subjects`)),
        (`subjects`.`course_id` + IFNULL(`degrees`.`id`, 2) - 1 + (select max(`id`) FROM `bd_centros_veterinaria`.`courses`) - (select min(`id`) FROM `bd_centros_eite`.`courses`)),
        `subjects`.`code`, `level`, `type`, `subjects`.`name`, `subjects`.`acronym`, `semester`, `credits_number`,
        (`coordinator_id` + 1 + (select max(`id`) FROM `bd_centros_veterinaria`.`users`) - (select min(`id`) FROM `bd_centros_eite`.`users`)),
        (`practice_responsible_id` + 1 + (select max(`id`) FROM `bd_centros_veterinaria`.`users`) - (select min(`id`) FROM `bd_centros_eite`.`users`)),
        `closed_attendance_groups`, `subjects`.`created`, `subjects`.`modified`
    FROM `bd_centros_eite`.`subjects`
    LEFT JOIN `degrees` ON `institution_id` = 2 AND `degrees`.`acronym` = `subjects`.`degree`
    ORDER BY `subjects`.`id`;

# SUBJECTS_USERS
INSERT INTO `subjects_users` (`subject_id`, `user_id`, `practices_approved`, `created`, `modified`)
    SELECT
        `subject_id`, `user_id`, `practices_approved`, `created`, `modified`
    FROM `bd_centros_veterinaria`.`subjects_users`
    ORDER BY `subjects_users`.`id`;

INSERT INTO `subjects_users` (`subject_id`, `user_id`, `practices_approved`, `created`, `modified`)
    SELECT
        (`subject_id` + 1 + (select max(`id`) FROM `bd_centros_veterinaria`.`subjects`) - (select min(`id`) FROM `bd_centros_eite`.`subjects`)),
        (`user_id` + 1 + (select max(`id`) FROM `bd_centros_veterinaria`.`users`) - (select min(`id`) FROM `bd_centros_eite`.`users`)),
        `practices_approved`, `created`, `modified`
    FROM `bd_centros_eite`.`subjects_users`
    ORDER BY `subjects_users`.`id`;

# ACTIVITIES
INSERT INTO `activities` (`id`, `subject_id`, `type`, `name`, `notes`, `duration`, `inflexible_groups`, `created`, `modified`)
    SELECT
        `id`, `subject_id`, `type`, `name`, `notes`, `duration`, `inflexible_groups`, `created`, `modified`
    FROM `bd_centros_veterinaria`.`activities`
    ORDER BY `activities`.`id`;

INSERT INTO `activities` (`id`, `subject_id`, `type`, `name`, `notes`, `duration`, `inflexible_groups`, `created`, `modified`)
    SELECT
        (`activities`.`id` + 1 + (select max(`id`) FROM `bd_centros_veterinaria`.`activities`) - (select min(`id`) FROM `bd_centros_eite`.`activities`)),
        (`activities`.`subject_id` + 1 + (select max(`id`) FROM `bd_centros_veterinaria`.`subjects`) - (select min(`id`) FROM `bd_centros_eite`.`subjects`)),
        `type`, `name`, `notes`, `duration`, `inflexible_groups`, `created`, `modified`
    FROM `bd_centros_eite`.`activities`
    ORDER BY `activities`.`id`;

# GROUPS
INSERT INTO `groups` (`id`, `subject_id`, `name`, `type`, `capacity`, `notes`, `created`, `modified`)
    SELECT
        `id`, `subject_id`, `name`, `type`, `capacity`, `notes`, `created`, `modified`
    FROM `bd_centros_veterinaria`.`groups`
    ORDER BY `groups`.`id`;

INSERT INTO `groups` (`id`, `subject_id`, `name`, `type`, `capacity`, `notes`, `created`, `modified`)
    SELECT
        (`groups`.`id` + 1 + (select max(`id`) FROM `bd_centros_veterinaria`.`groups`) - (select min(`id`) FROM `bd_centros_eite`.`groups`)),
        (`groups`.`subject_id` + 1 + (select max(`id`) FROM `bd_centros_veterinaria`.`subjects`) - (select min(`id`) FROM `bd_centros_eite`.`subjects`)),
        `name`, `type`, `capacity`, `notes`, `created`, `modified`
    FROM `bd_centros_eite`.`groups`
    ORDER BY `groups`.`id`;

# REGISTRATIONS
INSERT INTO `registrations` (`id`, `group_id`, `activity_id`, `student_id`, `created`, `modified`)
    SELECT
        `id`, `group_id`, `activity_id`, `student_id`, `created`, `modified`
    FROM `bd_centros_veterinaria`.`registrations`
    ORDER BY `registrations`.`id`;

INSERT INTO `registrations` (`id`, `group_id`, `activity_id`, `student_id`, `created`, `modified`)
    SELECT
        (`registrations`.`id` + 1 + (select max(`id`) FROM `bd_centros_veterinaria`.`registrations`) - (select min(`id`) FROM `bd_centros_eite`.`registrations`)),
        (`group_id` + 1 + (select max(`id`) FROM `bd_centros_veterinaria`.`groups`) - (select min(`id`) FROM `bd_centros_eite`.`groups`)),
        (`activity_id` + 1 + (select max(`id`) FROM `bd_centros_veterinaria`.`activities`) - (select min(`id`) FROM `bd_centros_eite`.`activities`)),
        (`student_id` + 1 + (select max(`id`) FROM `bd_centros_veterinaria`.`users`) - (select min(`id`) FROM `bd_centros_eite`.`users`)),
        `created`, `modified`
    FROM `bd_centros_eite`.`registrations`
    ORDER BY `registrations`.`id`;

# GROUP_REQUESTS
INSERT INTO `group_requests` (`activity_id`, `student_id`, `group_id`, `student_2_id`, `group_2_id`, `created`, `modified`)
    SELECT
        `activity_id`, `student_id`, `group_id`, `student_2_id`, `group_2_id`, `created`, `modified`
    FROM `bd_centros_veterinaria`.`group_requests`;

INSERT INTO `group_requests` (`activity_id`, `student_id`, `group_id`, `student_2_id`, `group_2_id`, `created`, `modified`)
    SELECT
        (`activity_id` + 1 + (select max(`id`) FROM `bd_centros_veterinaria`.`activities`) - (select min(`id`) FROM `bd_centros_eite`.`activities`)),
        (`student_id` + 1 + (select max(`id`) FROM `bd_centros_veterinaria`.`users`) - (select min(`id`) FROM `bd_centros_eite`.`users`)),
        (`group_id` + 1 + (select max(`id`) FROM `bd_centros_veterinaria`.`groups`) - (select min(`id`) FROM `bd_centros_eite`.`groups`)),
        (`student_2_id` + 1 + (select max(`id`) FROM `bd_centros_veterinaria`.`users`) - (select min(`id`) FROM `bd_centros_eite`.`users`)),
        (`group_2_id` + 1 + (select max(`id`) FROM `bd_centros_veterinaria`.`groups`) - (select min(`id`) FROM `bd_centros_eite`.`groups`)),
        `created`, `modified`
    FROM `bd_centros_eite`.`group_requests`;

# EVENTS
INSERT INTO `events` (`id`, `parent_id`, `group_id`, `activity_id`, `teacher_id`, `initial_hour`, `final_hour`, `classroom_id`, `duration`, `owner_id`, `teacher_2_id`, `show_tv`, `created`, `modified`)
    SELECT
        `id`, `parent_id`, `group_id`, `activity_id`, `teacher_id`, `initial_hour`, `final_hour`, `classroom_id`, `duration`, `owner_id`, `teacher_2_id`, `show_tv`, `created`, `modified`
    FROM `bd_centros_veterinaria`.`events`
    ORDER BY `events`.`id`;

INSERT INTO `events` (`id`, `parent_id`, `group_id`, `activity_id`, `teacher_id`, `initial_hour`, `final_hour`, `classroom_id`, `duration`, `owner_id`, `teacher_2_id`, `show_tv`, `created`, `modified`)
    SELECT
        (`events`.`id` + 1 + (select max(`id`) FROM `bd_centros_veterinaria`.`events`) - (select min(`id`) FROM `bd_centros_eite`.`events`)),
        (`parent_id` + 1 + (select max(`id`) FROM `bd_centros_veterinaria`.`events`) - (select min(`id`) FROM `bd_centros_eite`.`events`)),
        (`group_id` + 1 + (select max(`id`) FROM `bd_centros_veterinaria`.`groups`) - (select min(`id`) FROM `bd_centros_eite`.`groups`)),
        (`activity_id` + 1 + (select max(`id`) FROM `bd_centros_veterinaria`.`activities`) - (select min(`id`) FROM `bd_centros_eite`.`activities`)),
        (`teacher_id` + 1 + (select max(`id`) FROM `bd_centros_veterinaria`.`users`) - (select min(`id`) FROM `bd_centros_eite`.`users`)),
        `initial_hour`, `final_hour`,
        (`classroom_id` + 1 + (select max(`id`) FROM `bd_centros_veterinaria`.`classrooms`) - (select min(`id`) FROM `bd_centros_eite`.`classrooms`)),
        `duration`,
        (`owner_id` + 1 + (select max(`id`) FROM `bd_centros_veterinaria`.`users`) - (select min(`id`) FROM `bd_centros_eite`.`users`)),
        (`teacher_2_id` + 1 + (select max(`id`) FROM `bd_centros_veterinaria`.`users`) - (select min(`id`) FROM `bd_centros_eite`.`users`)),
        `show_tv`, `created`, `modified`
    FROM `bd_centros_eite`.`events`
    ORDER BY `events`.`id`;

# ATENDANCE REGISTERS
INSERT INTO `attendance_registers` (`id`, `event_id`, `initial_hour`, `final_hour`, `duration`, `teacher_id`, `activity_id`, `group_id`, `num_students`, `teacher_2_id`, `secret_code`, `created`, `modified`)
    SELECT
        `attendance_registers`.`id`, `attendance_registers`.`event_id`,
        IF(`attendance_registers`.`initial_hour` > '0000-01-01 00:00:00', `attendance_registers`.`initial_hour`, `events`.`initial_hour`),
        IF(`attendance_registers`.`final_hour` > '0000-01-01 00:00:00', `attendance_registers`.`final_hour`, `events`.`final_hour`),
        `attendance_registers`.`duration`, `attendance_registers`.`teacher_id`, `attendance_registers`.`activity_id`, `attendance_registers`.`group_id`, `attendance_registers`.`num_students`, `attendance_registers`.`teacher_2_id`, `attendance_registers`.`secret_code`, `attendance_registers`.`created`, `attendance_registers`.`modified`
    FROM `bd_centros_veterinaria`.`attendance_registers`
    INNER JOIN `events` on `events`.`id` = `attendance_registers`.`event_id`
    ORDER BY `attendance_registers`.`id`;

INSERT INTO `attendance_registers` (`id`, `event_id`, `initial_hour`, `final_hour`, `duration`, `teacher_id`, `activity_id`, `group_id`, `num_students`, `teacher_2_id`, `secret_code`, `created`, `modified`)
    SELECT
        (`attendance_registers`.`id` + 1 + (select max(`id`) FROM `bd_centros_veterinaria`.`attendance_registers`) - (select min(`id`) FROM `bd_centros_eite`.`attendance_registers`)),
        (`attendance_registers`.`event_id` + 1 + (select max(`id`) FROM `bd_centros_veterinaria`.`events`) - (select min(`id`) FROM `bd_centros_eite`.`events`)),
        IF(`attendance_registers`.`initial_hour` > '0000-01-01 00:00:00', `attendance_registers`.`initial_hour`, `events`.`initial_hour`),
        IF(`attendance_registers`.`final_hour` > '0000-01-01 00:00:00', `attendance_registers`.`final_hour`, `events`.`final_hour`),
        `attendance_registers`.`duration`,
        (`attendance_registers`.`teacher_id` + 1 + (select max(`id`) FROM `bd_centros_veterinaria`.`users`) - (select min(`id`) FROM `bd_centros_eite`.`users`)),
        (`attendance_registers`.`activity_id` + 1 + (select max(`id`) FROM `bd_centros_veterinaria`.`activities`) - (select min(`id`) FROM `bd_centros_eite`.`activities`)),
        (`attendance_registers`.`group_id` + 1 + (select max(`id`) FROM `bd_centros_veterinaria`.`groups`) - (select min(`id`) FROM `bd_centros_eite`.`groups`)),
        `attendance_registers`.`num_students`,
        (`attendance_registers`.`teacher_2_id` + 1 + (select max(`id`) FROM `bd_centros_veterinaria`.`users`) - (select min(`id`) FROM `bd_centros_eite`.`users`)),
        `attendance_registers`.`secret_code`, `attendance_registers`.`created`, `attendance_registers`.`modified`
    FROM `bd_centros_eite`.`attendance_registers`
    INNER JOIN `events` on `events`.`id` = `attendance_registers`.`event_id`
    ORDER BY `attendance_registers`.`id`;

# MASSIVE ATTENDANCE REGISTERS
INSERT INTO `massive_attendance_registers` (`id`, `subject_id`, `created`, `modified`)
    SELECT
        `id`, `subject_id`, `created`, `modified`
    FROM `bd_centros_veterinaria`.`massive_attendance_registers`;

INSERT INTO `massive_attendance_registers` (`id`, `subject_id`, `created`, `modified`)
    SELECT
        (`massive_attendance_registers`.`id` + 1 + (select max(`id`) FROM `bd_centros_veterinaria`.`massive_attendance_registers`) - (select min(`id`) FROM `bd_centros_eite`.`massive_attendance_registers`)),
        (`subject_id` + 1 + (select max(`id`) FROM `bd_centros_veterinaria`.`subjects`) - (select min(`id`) FROM `bd_centros_eite`.`subjects`)),
        `created`, `modified`
    FROM `bd_centros_eite`.`massive_attendance_registers`;

# USERS ATTENDANCE REGISTER
INSERT INTO `users_attendance_register` (`user_id`, `attendance_register_id`, `user_gone`, `created`, `modified`)
    SELECT
        `user_id`, `attendance_register_id`, `user_gone`, `created`, `modified`
    FROM `bd_centros_veterinaria`.`users_attendance_register`;

INSERT INTO `users_attendance_register` (`user_id`, `attendance_register_id`, `user_gone`, `created`, `modified`)
    SELECT
        (`user_id` + 1 + (select max(`id`) FROM `bd_centros_veterinaria`.`users`) - (select min(`id`) FROM `bd_centros_eite`.`users`)),
        (`attendance_register_id` + 1 + (select max(`id`) FROM `bd_centros_veterinaria`.`attendance_registers`) - (select min(`id`) FROM `bd_centros_eite`.`attendance_registers`)),
        `user_gone`, `created`, `modified`
    FROM `bd_centros_eite`.`users_attendance_register`;

# BOOKINGS
INSERT INTO `bookings` (`id`, `institution_id`, `parent_id`, `user_id`, `user_type`, `initial_hour`, `classroom_id`, `final_hour`, `reason`, `required_equipment`, `show_tv`, `created`, `modified`)
    SELECT
        `id`, 1, `parent_id`, `user_id`, `user_type`, `initial_hour`, `classroom_id`, `final_hour`, `reason`, `required_equipment`, `show_tv`, `created`, `modified`
    FROM `bd_centros_veterinaria`.`bookings`
    ORDER BY `bookings`.`id`;

INSERT INTO `bookings` (`id`, `institution_id`, `parent_id`, `user_id`, `user_type`, `initial_hour`, `classroom_id`, `final_hour`, `reason`, `required_equipment`, `show_tv`, `created`, `modified`)
    SELECT
        (`bookings`.`id` + 1 + (select max(`id`) FROM `bd_centros_veterinaria`.`bookings`) - (select min(`id`) FROM `bd_centros_eite`.`bookings`)),
        2,
        (`parent_id` + 1 + (select max(`id`) FROM `bd_centros_veterinaria`.`bookings`) - (select min(`id`) FROM `bd_centros_eite`.`bookings`)),
        (`user_id` + 1 + (select max(`id`) FROM `bd_centros_veterinaria`.`users`) - (select min(`id`) FROM `bd_centros_eite`.`users`)),
        `user_type`, `initial_hour`,
        (`classroom_id` + 1 + (select max(`id`) FROM `bd_centros_veterinaria`.`classrooms`) - (select min(`id`) FROM `bd_centros_eite`.`classrooms`)),
        `final_hour`, `reason`, `required_equipment`, `show_tv`, `created`, `modified`
    FROM `bd_centros_eite`.`bookings`
    ORDER BY `bookings`.`id`;

# USERS BOOKING
INSERT INTO `users_booking` (`user_id`, `booking_id`, `created`, `modified`)
    SELECT
        `user_id`, `booking_id`, `created`, `modified`
    FROM `bd_centros_veterinaria`.`users_booking`;

INSERT INTO `users_booking` (`user_id`, `booking_id`, `created`, `modified`)
    SELECT
        (`user_id` + 1 + (select max(`id`) FROM `bd_centros_veterinaria`.`users`) - (select min(`id`) FROM `bd_centros_eite`.`users`)),
        (`booking_id` + 1 + (select max(`id`) FROM `bd_centros_veterinaria`.`bookings`) - (select min(`id`) FROM `bd_centros_eite`.`bookings`)),
        `created`, `modified`
    FROM `bd_centros_eite`.`users_booking`;

# COMPETENCE
INSERT INTO `competence`
    SELECT
        *
    FROM `bd_centros_veterinaria`.`competence`
    ORDER BY `competence`.`id`;

# COMPETENCE_GOALS
INSERT INTO `competence_goals`
    SELECT
        *
    FROM `bd_centros_veterinaria`.`competence_goals`
    ORDER BY `competence_goals`.`id`;

# COMPETENCE_GOAL_REQUESTS
INSERT INTO `competence_goal_requests`
    SELECT
        *
    FROM `bd_centros_veterinaria`.`competence_goal_requests`
    ORDER BY `competence_goal_requests`.`id`;

# COMPETENCE_CRITERIA
INSERT INTO `competence_criteria`
    SELECT
        *
    FROM `bd_centros_veterinaria`.`competence_criteria`
    ORDER BY `competence_criteria`.`id`;

# COMPETENCE_CRITERION_GRADES
INSERT INTO `competence_criterion_grades`
    SELECT
        *
    FROM `bd_centros_veterinaria`.`competence_criterion_grades`
    ORDER BY `competence_criterion_grades`.`id`;

# COMPETENCE_CRITERION_RUBRICS
INSERT INTO `competence_criterion_rubrics`
    SELECT
        *
    FROM `bd_centros_veterinaria`.`competence_criterion_rubrics`
    ORDER BY `competence_criterion_rubrics`.`id`;

# COMPETENCE_CRITERION_SUBJECTS
INSERT INTO `competence_criterion_subjects`
    SELECT
        *
    FROM `bd_centros_veterinaria`.`competence_criterion_subjects`
    ORDER BY `competence_criterion_subjects`.`id`;

# COMPETENCE_CRITERION_TEACHERS
INSERT INTO `competence_criterion_teachers`
    SELECT
        *
    FROM `bd_centros_veterinaria`.`competence_criterion_teachers`
    ORDER BY `competence_criterion_teachers`.`id`;

