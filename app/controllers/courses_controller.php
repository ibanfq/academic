<?php
class CoursesController extends AppController {
    var $name = 'Courses';
    var $paginate = array('limit' => 10, 'order' => array('Course.initial_date' => 'asc'));

    function index() {
        $this->set('courses', $this->Course->find('all', array('order' => array('Course.initial_date desc'))));
    }

    function add(){
        if (!empty($this->data)){
            if ($this->Course->save($this->data)){
                $this->Session->setFlash('El curso se ha guardado correctamente');
                $this->redirect(array('action' => 'index'));
            }
        }
    }

    function view($id = null) {
        $id = $id === null ? null : intval($id);
        $this->Course->id = $id;
        $this->set('course', $this->Course->read());
        $this->set('friendly_name', $this->Course->friendly_name());
    }

    function edit($id = null) {
        $id = $id === null ? null : intval($id);
        $this->Course->id = $id;
        if (empty($this->data)) {
            $this->data = $this->Course->read();
            $this->set('course', $this->data);
        } else {
            if ($this->Course->save($this->data)) {
                $this->Session->setFlash('El curso se ha actualizado correctamente.');
                $this->redirect(array('action' => 'view', $id));
            } else
                $this->set('course', $this->data);
        }
    }

    /**
     * Duplicates a course and all of its subjects
     *
     * @param integer $id ID of a course
     * @return void
     * @since 2012-05-19
     * @version 2012-07-29
     */
    function copy($id) {
        $this->loadModel('Competence');

        $id = $id === null ? null : intval($id);
        $course = $this->Course->findById($id);

        // Creates the new course
        $newCourse = array('Course' => array());
        $newCourse["Course"]["name"] = sprintf('%s (COPIA)', $course["Course"]["name"]);
        $latestFinalDate = $this->Course->latestFinalDate();
        $newCourse["Course"]["initial_date"] = date('Y-m-d', strtotime($latestFinalDate) + 86400);
        $newCourse["Course"]["final_date"] = date('Y-m-d', strtotime($latestFinalDate) + 31536000);

        $this->Course->create();
        if ($this->Course->save($newCourse) === false) {
            $this->Session->setFlash('El curso no se pudo copiar.');
            $this->redirect($this->referer());
        }
        $new_course_id = $this->Course->id;

        $error = false;
        $savedSubjects = array();
        $savedCompetence = array();
        $savedCompetenceGoals = array();
        $savedCompetenceCriteria = array();

        // Duplicate every subject
        foreach ($course['Subject'] as $subject) {
            $subject['course_id'] = $new_course_id;
            $newSubject['Subject'] = $subject;
            unset($newSubject['Subject']['id']);
            unset($newSubject['Subject']['created']);
            unset($newSubject['Subject']['modified']);

            $new_subject_id = null;
            $this->Course->Subject->create();
            if ($this->Course->Subject->save($newSubject)) {
                $new_subject_id = $this->Course->Subject->id;
                $savedSubjects[$subject['id']] = $new_subject_id;
            } else {
                $error = true;
                break;
            }

            // Duplicate all groups of this subject
            foreach ($this->Course->Subject->Group->findAllBySubjectId($subject['id'], array('Group.*')) as $group) {
                $group_id = $group['Group']['id'];
                unset($group['Group']['id']);
                unset($group['Group']['created']);
                unset($group['Group']['modified']);
                $group['Group']['subject_id'] = $new_subject_id;

                $this->Course->Subject->Group->create();
                if ($this->Course->Subject->Group->save($group) === false) {
                    $error = true;
                    break(2);
                }
            }

            // Duplicate all activities of this subject
            foreach ($this->Course->Subject->Activity->findAllBySubjectId($subject['id'], array('Activity.*')) as $activity) {
                $activity_id = $activity['Activity']['id'];
                unset($activity['Activity']['id']);
                unset($activity['Activity']['created']);
                unset($activity['Activity']['modified']);
                $activity['Activity']['subject_id'] = $new_subject_id;

                $this->Course->Subject->Activity->create();
                if ($this->Course->Subject->Activity->save($activity) === false) {
                    $error = true;
                    break(2);
                }
            }
        }

        // Duplicate every competence
        $competenceList = $this->Competence->find(
            'all',
            array(
                'conditions' => array('Competence.course_id' => $id),
                'recursive' => -1
            )
        );
        foreach ($competenceList as $competence) {
            $newCompetence['Competence'] = $competence['Competence'];
            $newCompetence['Competence']['course_id'] = $new_course_id;
            unset($newCompetence['Competence']['id']);
            unset($newCompetence['Competence']['created']);
            unset($newCompetence['Competence']['modified']);

            $new_competence_id = null;
            $this->Competence->create();
            if ($this->Competence->save($newCompetence)) {
                $new_competence_id = $this->Competence->id;
                $savedCompetence[] = $new_competence_id;
            } else {
                $error = true;
                break;
            }

            // Duplicate all goals of this competence
            $competenceGoals = $this->Competence->CompetenceGoal->find(
                'all',
                array(
                    'conditions' => array('CompetenceGoal.competence_id' => $competence['Competence']['id']),
                    'recursive' => -1
                )
            );
            foreach ($competenceGoals as $competenceGoal) {
                $newCompetenceGoal['CompetenceGoal'] = $competenceGoal['CompetenceGoal'];
                $newCompetenceGoal['CompetenceGoal']['competence_id'] = $new_competence_id;
                unset($newCompetenceGoal['CompetenceGoal']['id']);
                unset($newCompetenceGoal['CompetenceGoal']['created']);
                unset($newCompetenceGoal['CompetenceGoal']['modified']);

                $new_competence_goal_id = null;
                $this->Competence->CompetenceGoal->create();
                if ($this->Competence->CompetenceGoal->save($newCompetenceGoal)) {
                    $new_competence_goal_id = $this->Competence->CompetenceGoal->id;
                    $savedCompetenceGoals[] = $new_competence_goal_id;
                } else {
                    $error = true;
                    break(2);
                }

                // Duplicate all criteria of this goal
                $competenceCriteria = $this->Competence->CompetenceGoal->CompetenceCriterion->find(
                    'all',
                    array(
                        'conditions' => array('CompetenceCriterion.goal_id' => $competenceGoal['CompetenceGoal']['id']),
                        'recursive' => -1
                    )
                );
                foreach ($competenceCriteria as $competenceCriterion) {
                    $newCompetenceCriterion['CompetenceCriterion'] = $competenceCriterion['CompetenceCriterion'];
                    $newCompetenceCriterion['CompetenceCriterion']['goal_id'] = $new_competence_goal_id;
                    unset($newCompetenceCriterion['CompetenceCriterion']['id']);
                    unset($newCompetenceCriterion['CompetenceCriterion']['created']);
                    unset($newCompetenceCriterion['CompetenceCriterion']['modified']);

                    $new_competence_criterion_id = null;
                    $this->Competence->CompetenceGoal->CompetenceCriterion->create();
                    if ($this->Competence->CompetenceGoal->CompetenceCriterion->save($newCompetenceCriterion)) {
                        $new_competence_criterion_id = $this->Competence->CompetenceGoal->CompetenceCriterion->id;
                        $savedCompetenceCriteria[] = $new_competence_criterion_id;
                    } else {
                        $error = true;
                        break(2);
                    }

                    // Duplicate all rubrics of this criterion
                    $competenceRubrics = $this->Competence->CompetenceGoal->CompetenceCriterion->CompetenceCriterionRubric->find(
                        'all',
                        array(
                            'conditions' => array('CompetenceCriterionRubric.criterion_id' => $competenceCriterion['CompetenceCriterion']['id']),
                            'recursive' => -1
                        )
                    );
                    foreach ($competenceRubrics as $competenceRubric) {
                        $newCompetenceRubric['CompetenceCriterionRubric'] = $competenceRubric['CompetenceCriterionRubric'];
                        $newCompetenceRubric['CompetenceCriterionRubric']['criterion_id'] = $new_competence_criterion_id;
                        unset($newCompetenceRubric['CompetenceCriterionRubric']['id']);
                        unset($newCompetenceRubric['CompetenceCriterionRubric']['created']);
                        unset($newCompetenceRubric['CompetenceCriterionRubric']['modified']);

                        $this->Competence->CompetenceGoal->CompetenceCriterion->CompetenceCriterionRubric->create();
                        if ($this->Competence->CompetenceGoal->CompetenceCriterion->CompetenceCriterionRubric->save($newCompetenceRubric) === false) {
                            $error = true;
                            break(3);
                        }
                    }

                    // Duplicate all subjects of this criterion
                    $competenceSubjects = $this->Competence->CompetenceGoal->CompetenceCriterion->CompetenceCriterionSubject->find(
                        'all',
                        array(
                            'conditions' => array('CompetenceCriterionSubject.criterion_id' => $competenceCriterion['CompetenceCriterion']['id']),
                            'recursive' => -1
                        )
                    );
                    foreach ($competenceSubjects as $competenceSubject) {
                        $newCompetenceSubject['CompetenceCriterionSubject'] = $competenceSubject['CompetenceCriterionSubject'];
                        $newCompetenceSubject['CompetenceCriterionSubject']['criterion_id'] = $new_competence_criterion_id;
                        $newCompetenceSubject['CompetenceCriterionSubject']['subject_id'] = $savedSubjects[$competenceSubject['CompetenceCriterionSubject']['subject_id']];
                        unset($newCompetenceSubject['CompetenceCriterionSubject']['id']);
                        unset($newCompetenceSubject['CompetenceCriterionSubject']['created']);
                        unset($newCompetenceSubject['CompetenceCriterionSubject']['modified']);

                        $this->Competence->CompetenceGoal->CompetenceCriterion->CompetenceCriterionSubject->create();
                        if ($this->Competence->CompetenceGoal->CompetenceCriterion->CompetenceCriterionSubject->save($newCompetenceSubject) === false) {
                            $error = true;
                            break(3);
                        }
                    }

                    // Duplicate all teachers of this criterion
                    $competenceTeachers = $this->Competence->CompetenceGoal->CompetenceCriterion->CompetenceCriterionTeacher->find(
                        'all',
                        array(
                            'conditions' => array('CompetenceCriterionTeacher.criterion_id' => $competenceCriterion['CompetenceCriterion']['id']),
                            'recursive' => -1
                        )
                    );
                    foreach ($competenceTeachers as $competenceTeacher) {
                        $newCompetenceTeacher['CompetenceCriterionTeacher'] = $competenceTeacher['CompetenceCriterionTeacher'];
                        $newCompetenceTeacher['CompetenceCriterionTeacher']['criterion_id'] = $new_competence_criterion_id;
                        unset($newCompetenceTeacher['CompetenceCriterionTeacher']['id']);
                        unset($newCompetenceTeacher['CompetenceCriterionTeacher']['created']);
                        unset($newCompetenceTeacher['CompetenceCriterionTeacher']['modified']);

                        $this->Competence->CompetenceGoal->CompetenceCriterion->CompetenceCriterionTeacher->create();
                        if ($this->Competence->CompetenceGoal->CompetenceCriterion->CompetenceCriterionTeacher->save($newCompetenceTeacher) === false) {
                            $error = true;
                            break(3);
                        }
                    }
                }
            }
        }

        if ($error) {
            $competenceCriterionIds = implode(',', $savedCompetenceCriteria);
            $competenceGoalIds = implode(',', $savedCompetenceGoals);
            $competenceIds = implode(',', $savedCompetence);
            $subjectIds = implode(',', $savedSubjects);
            $this->Course->query("DELETE FROM competence_criterion_teachers WHERE competence_criterion_teachers.criterion_id IN ($competenceCriterionIds)");
            $this->Course->query("DELETE FROM competence_criterion_subjects WHERE competence_criterion_subjects.criterion_id IN ($competenceCriterionIds)");
            $this->Course->query("DELETE FROM competence_criterion_rubrics WHERE competence_criterion_rubrics.criterion_id IN ($competenceCriterionIds)");
            $this->Course->query("DELETE FROM competence_criteria WHERE competence_criteria.goal_id IN ($competenceGoalIds)");
            $this->Course->query("DELETE FROM competence_goals WHERE competence_goals.competence_id IN ($competenceIds)");
            $this->Course->query("DELETE FROM competence WHERE competence.course_id = {$this->Course->id}");
            $this->Course->query("DELETE FROM activities WHERE activities.subject_id IN ($subjectIds)");
            $this->Course->query("DELETE FROM groups WHERE groups.subject_id IN ($subjectIds)");
            $this->Course->query("DELETE FROM subjects WHERE course_id = {$this->Course->id}");
            $this->Course->delete($this->Course->id);
            $this->Session->setFlash('El curso no se pudo copiar.');
            $this->redirect($this->referer());
        } else {
            $this->Session->setFlash('El curso se ha copiado correctamente.');
            $this->redirect(array('action' => 'index'));
        }
    }


    function delete($id) {
        $id = $id === null ? null : intval($id);
        $this->Course->delete($id);  // Delete subjects implicitly

        $currentSubjectsQuery = "SELECT DISTINCT `Subject`.id FROM subjects `Subject`";
        $this->Course->query("DELETE FROM `groups` WHERE subject_id NOT IN ($currentSubjectsQuery)");
        $this->Course->query("DELETE FROM `activities` WHERE subject_id NOT IN ($currentSubjectsQuery)");
        $this->Course->query("DELETE FROM `subjects_users` WHERE subject_id NOT IN ($currentSubjectsQuery)");

        $currentActivitiesQuery = "SELECT DISTINCT `Activity`.id FROM activities `Activity`";
        $this->Course->query("DELETE FROM `attendance_registers` WHERE activity_id NOT IN ($currentActivitiesQuery)");
        $this->Course->query("DELETE FROM `events` WHERE activity_id NOT IN ($currentActivitiesQuery)");
        $this->Course->query("DELETE FROM `registrations` WHERE activity_id NOT IN ($currentActivitiesQuery)");
        $this->Course->query("DELETE FROM `group_requests` WHERE activity_id NOT IN ($currentActivitiesQuery)");

        $this->Course->query("DELETE FROM `users_attendance_register` WHERE attendance_register_id NOT IN (SELECT DISTINCT `AttendanceRegister`.id FROM attendance_registers `AttendanceRegister`)");

        $this->Course->query("DELETE FROM `competence` WHERE course_id NOT IN (SELECT DISTINCT `Course`.id FROM courses `Course`)");
        $this->Course->query("DELETE FROM `competence_goals` WHERE competence_id NOT IN (SELECT DISTINCT `Competence`.id FROM competence `Competence`)");

        $currentCompetenceGoalsQuery = "SELECT DISTINCT `CompetenceGoal`.id FROM competence_goals `CompetenceGoal`";
        $this->Course->query("DELETE FROM `competence_criteria` WHERE goal_id NOT IN ($currentCompetenceGoalsQuery)");
        $this->Course->query("DELETE FROM `competence_goal_requests` WHERE goal_id NOT IN ($currentCompetenceGoalsQuery)");

        $currentCompetenceCriteriaQuery = "SELECT DISTINCT `CompetenceCriterion`.id FROM competence_criteria `CompetenceCriterion`";
        $this->Course->query("DELETE FROM `competence_criterion_rubrics` WHERE criterion_id NOT IN ($currentCompetenceCriteriaQuery)");
        $this->Course->query("DELETE FROM `competence_criterion_subjects` WHERE criterion_id NOT IN ($currentCompetenceCriteriaQuery)");
        $this->Course->query("DELETE FROM `competence_criterion_teachers` WHERE criterion_id NOT IN ($currentCompetenceCriteriaQuery)");
        $this->Course->query("DELETE FROM `competence_criterion_grades` WHERE criterion_id NOT IN ($currentCompetenceGoalsQuery)");

        $this->Session->setFlash('El curso ha sido eliminado correctamente');
        $this->redirect(array('action' => 'index'));
    }

    /**
     * Shows a summary of lecture hours by teacher
     *
     * @param integer $id ID of a course
     * @return void
     * @since 2012-05-19
     */
    function stats_by_teacher($course_id = null) {
        $course_id = $course_id === null ? null : intval($course_id);
        $this->set('course', $this->Course->read(null, $course_id));
        $this->set('friendly_name', $this->Course->friendly_name());

        $initialDate = date('Y-m-d', strtotime($this->Course->field('initial_date', array('Course.id' => $course_id))));
        $finalDate = date('Y-m-d', strtotime($this->Course->field('final_date', array('Course.id' => $course_id))));

        $teachers = $this->Course->query("
            SELECT Teacher.*, IFNULL(teorical.total, 0) AS teorical, IFNULL(practice.total, 0) AS practice, IFNULL(others.total, 0) AS others
            FROM users Teacher
            LEFT JOIN (
                SELECT teacher_id, SUM(IFNULL(duration,0)) as total
                FROM (
                    SELECT ar1.duration, ar1.teacher_id AS teacher_id, IF(activities.type IN ('Clase magistral', 'Seminario'), 'T', IF(activities.type IN ('Tutoría', 'Evaluación', 'Otra presencial'), 'O', 'P')) AS type, subjects.course_id
                    FROM attendance_registers ar1
                    INNER JOIN activities ON ar1.activity_id = activities.id
                    INNER JOIN subjects ON subjects.id = activities.subject_id
                    WHERE DATE_FORMAT(ar1.initial_hour, '%Y-%m-%d') >= '{$initialDate}' AND DATE_FORMAT(ar1.final_hour, '%Y-%m-%d') <= '{$finalDate}'
                    UNION ALL
                        SELECT ar2.duration, ar2.teacher_2_id AS teacher_id, IF(activities2.type IN ('Clase magistral', 'Seminario'), 'T', IF(activities2.type IN ('Tutoría', 'Evaluación', 'Otra presencial'), 'O', 'P')) AS type, subjects2.course_id
                        FROM attendance_registers ar2
                        INNER JOIN activities activities2 ON ar2.activity_id = activities2.id
                        INNER JOIN subjects subjects2 ON subjects2.id = activities2.subject_id
                        WHERE DATE_FORMAT(ar2.initial_hour, '%Y-%m-%d') >= '{$initialDate}' AND DATE_FORMAT(ar2.final_hour, '%Y-%m-%d') <= '{$finalDate}'
                ) teacher_stats
                WHERE type = 'T'
                AND course_id = {$course_id}
                GROUP BY teacher_id
            ) teorical ON teorical.teacher_id = Teacher.id
            LEFT JOIN (
                SELECT teacher_id, SUM(IFNULL(duration,0)) as total
                FROM (
                    SELECT ar1.duration, ar1.teacher_id AS teacher_id, IF(activities.type IN ('Clase magistral', 'Seminario'), 'T', IF(activities.type IN ('Tutoría', 'Evaluación', 'Otra presencial'), 'O', 'P')) AS type, subjects.course_id
                    FROM attendance_registers ar1
                    INNER JOIN activities ON ar1.activity_id = activities.id
                    INNER JOIN subjects ON subjects.id = activities.subject_id
                    WHERE DATE_FORMAT(ar1.initial_hour, '%Y-%m-%d') >= '{$initialDate}' AND DATE_FORMAT(ar1.final_hour, '%Y-%m-%d') <= '{$finalDate}'
                    UNION ALL
                        SELECT ar2.duration, ar2.teacher_2_id AS teacher_id, IF(activities2.type IN ('Clase magistral', 'Seminario'), 'T', IF(activities2.type IN ('Tutoría', 'Evaluación', 'Otra presencial'), 'O', 'P')) AS type, subjects2.course_id
                        FROM attendance_registers ar2
                        INNER JOIN activities activities2 ON ar2.activity_id = activities2.id
                        INNER JOIN subjects subjects2 ON subjects2.id = activities2.subject_id
                        WHERE DATE_FORMAT(ar2.initial_hour, '%Y-%m-%d') >= '{$initialDate}' AND DATE_FORMAT(ar2.final_hour, '%Y-%m-%d') <= '{$finalDate}'
                ) teacher_stats
                WHERE type = 'P'
                AND course_id = {$course_id}
                GROUP BY teacher_id
            ) practice ON practice.teacher_id = Teacher.id
            LEFT JOIN (
                SELECT teacher_id, SUM(IFNULL(duration,0)) as total
                FROM (
                    SELECT ar1.duration, ar1.teacher_id AS teacher_id, IF(activities.type IN ('Clase magistral', 'Seminario'), 'T', IF(activities.type IN ('Tutoría', 'Evaluación', 'Otra presencial'), 'O', 'P')) AS type, subjects.course_id
                    FROM attendance_registers ar1
                    INNER JOIN activities ON ar1.activity_id = activities.id
                    INNER JOIN subjects ON subjects.id = activities.subject_id
                    WHERE DATE_FORMAT(ar1.initial_hour, '%Y-%m-%d') >= '{$initialDate}' AND DATE_FORMAT(ar1.final_hour, '%Y-%m-%d') <= '{$finalDate}'
                    UNION ALL
                        SELECT ar2.duration, ar2.teacher_2_id AS teacher_id, IF(activities2.type IN ('Clase magistral', 'Seminario'), 'T', IF(activities2.type IN ('Tutoría', 'Evaluación', 'Otra presencial'), 'O', 'P')) AS type, subjects2.course_id
                        FROM attendance_registers ar2
                        INNER JOIN activities activities2 ON ar2.activity_id = activities2.id
                        INNER JOIN subjects subjects2 ON subjects2.id = activities2.subject_id
                        WHERE DATE_FORMAT(ar2.initial_hour, '%Y-%m-%d') >= '{$initialDate}' AND DATE_FORMAT(ar2.final_hour, '%Y-%m-%d') <= '{$finalDate}'
                ) teacher_stats
                WHERE type = 'O'
                AND course_id = {$course_id}
                GROUP BY teacher_id
            ) others ON others.teacher_id = Teacher.id
            WHERE Teacher.type = 'Profesor' OR (Teacher.id IN (SELECT DISTINCT teacher_id FROM events))
            ORDER BY Teacher.last_name, Teacher.first_name
        ");
        $this->set('teachers', $teachers);
    }

    function stats_by_subject($course_id = null){
        $course_id = $course_id === null ? null : intval($course_id);
        $this->Course->id = $course_id;
        $this->set('course', $this->Course->read());
        $this->set('friendly_name', $this->Course->friendly_name());

        $subjects = $this->Course->Subject->query("
            SELECT subjects.id, subjects.code, subjects.name, SUM(activities.expected_duration) AS expected_hours, SUM(activities.programmed_duration) AS programmed_hours, SUM(activities.registered_duration) AS registered_hours, IFNULL(su.total,0) AS students
            FROM subjects
            LEFT JOIN (SELECT subjects_users.subject_id, IFNULL(count(distinct subjects_users.user_id), 0) as total FROM subjects_users INNER JOIN activities ON activities.subject_id = subjects_users.subject_id GROUP BY subjects_users.subject_id) su ON su.subject_id = subjects.id
            INNER JOIN (
                SELECT Activity.id, Activity.subject_id, Activity.duration AS expected_duration, SUM(IFNULL(Event.duration, 0)) / `Group`.total AS programmed_duration, IFNULL(SUM(AttendanceRegister.duration), 0) / `Group`.total AS registered_duration
                FROM activities Activity
                LEFT JOIN events Event ON Event.activity_id = Activity.id
                LEFT JOIN (
                    SELECT `Event`.`activity_id` AS `activity_id`, COUNT(DISTINCT `TemporaryGroup`.`id`) AS `total`
                    FROM `events` `Event`
                    LEFT JOIN `groups` `TemporaryGroup` ON `TemporaryGroup`.`id` = `Event`.`group_id`
                    WHERE `TemporaryGroup`.`name` NOT LIKE '%%no me presento%%'
                    GROUP BY `Event`.`activity_id`
                ) `Group` ON `Group`.`activity_id` = `Activity`.`id`
                LEFT JOIN (
                    SELECT activity_id, event_id, SUM(duration) AS duration
                    FROM attendance_registers
                    GROUP BY activity_id, event_id
                ) AttendanceRegister ON AttendanceRegister.activity_id = Activity.id AND AttendanceRegister.event_id = Event.id
                GROUP BY Activity.id
                        )    activities ON activities.subject_id = subjects.id
            WHERE subjects.course_id = {$course_id}
            GROUP BY subjects.id
            ORDER BY subjects.code ASC
        ");

      $this->set('subjects', $subjects);
    }

    function export_stats_by_subject($course_id = null) {
        $course_id = $course_id === null ? null : intval($course_id);
        $date = Date('Y-m-d');

        $subjects = $this->Course->Subject->query("SELECT subjects.id, subjects.code, subjects.name, SUM(activities.expected_duration) AS expected_hours, SUM(activities.programmed_duration) AS programmed_hours, SUM(activities.registered_duration) AS registered_hours, IFNULL(su.total,0) AS students FROM subjects LEFT JOIN (SELECT subjects_users.subject_id, IFNULL(count(distinct subjects_users.user_id), 0) as total FROM subjects_users INNER JOIN activities ON activities.subject_id = subjects_users.subject_id GROUP BY subjects_users.subject_id) su ON su.subject_id = subjects.id INNER JOIN (SELECT Activity.id, Activity.subject_id, Activity.duration AS expected_duration, SUM(IFNULL(Event.duration, 0)) / `Group`.total AS programmed_duration, IFNULL(SUM(AttendanceRegister.duration), 0) / `Group`.total AS registered_duration FROM activities Activity LEFT JOIN events Event ON Event.activity_id = Activity.id LEFT JOIN (SELECT `groups`.subject_id, `groups`.type, count(id) as total FROM `groups` where `groups`.name NOT LIKE '%no me presento%' GROUP BY `groups`.subject_id, `groups`.type) `Group` ON `Group`.subject_id = Activity.subject_id AND `Group`.type = Activity.type LEFT JOIN (SELECT activity_id, event_id, SUM(duration) AS duration FROM attendance_registers GROUP BY activity_id, event_id) AttendanceRegister ON AttendanceRegister.activity_id = Activity.id AND AttendanceRegister.event_id = Event.id GROUP BY Activity.id) activities ON activities.subject_id = subjects.id WHERE subjects.course_id = {$course_id} GROUP BY subjects.id ORDER BY subjects.code ASC");
        $response = "Código;Nombre;Nº de matriculados;Horas planificadas;Horas programadas;Horas registradas\n";

        foreach($subjects as $subject):
            $expected = str_replace('.', ',', $subject[0]['expected_hours']);
            $programmed = str_replace('.', ',', $subject[0]['programmed_hours']);
            $registered = str_replace('.', ',', $subject[0]['registered_hours']);
            $response .= "{$subject['subjects']['code']}";
            $response .= ";\"{$subject['subjects']['name']}\"";
            $response .= ";{$subject[0]['students']}";
            $response .= ";{$expected}";
            $response .= ";{$programmed}";
            $response .= ";{$registered}";
            $response .= "\n";
        endforeach;

        $this->set('response', $response);
        $this->set('filename', 'Estadisticas_asignatura.csv');

        $this->render('export_stats_by_subject', 'download');
    }

    function _authorize() {
        parent::_authorize();

        $administrator_actions = array('add', 'edit', 'delete');
        $student_actions = array('index', 'view');

        $this->set('section', 'courses');

        if ((array_search($this->params['action'], $administrator_actions) !== false) && ($this->Auth->user('type') != "Administrador") && ($this->Auth->user('type') != "Administrativo")) {
            return false;
        }
    
        if ((array_search($this->params['action'], $student_actions) === false) && ($this->Auth->user('type') == "Estudiante")) {
            return false;
        }

        return true;
    }
}
