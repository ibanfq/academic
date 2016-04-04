<?php
class CoursesController extends AppController {
	var $name = 'Courses';
	var $paginate = array('limit' => 10, 'order' => array('Course.initial_date' => 'asc'));

	function index() {
		$this->set('courses', $this->Course->find('all', array('order' => array('Course.initial_date'))));
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
		$this->Course->id = $id;
		$this->set('course', $this->Course->read());
		$this->set('friendly_name', $this->Course->friendly_name());
	}

	function edit($id = null) {
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

		// Duplicate every subject
		$savedSubjects = array();
		$error = false;
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
				$savedSubjects[] = $new_subject_id;
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

		if ($error) {
			$subjectIds = implode(',', $savedSubjects);
			$this->Course->query("DELETE FROM activities WHERE activities.subject_id IN ($subjectIds)");
			$this->Course->query("DELETE FROM groups WHERE groups.subject_id IN ($subjectIds)");
			$this->Course->query("DELETE FROM subjects WHERE course_id = {$this->Course->Subject->id}");
			$this->Course->delete($this->Course->id);
			$this->Session->setFlash('El curso no se pudo copiar.');
			$this->redirect($this->referer());
		} else {
			$this->Session->setFlash('El curso se ha copiado correctamente.');
			$this->redirect(array('action' => 'index'));
		}
	}


	function delete($id) {
		$this->Course->delete($id);  // Delete subjects implicitly

		$currentSubjectsQuery = "SELECT DISTINCT `Subject`.id FROM subjects `Subject`";
		$this->Course->query("DELETE FROM `groups` WHERE subject_id NOT IN ($currentSubjectsQuery)");
		$this->Course->query("DELETE FROM `activities` WHERE subject_id NOT IN ($currentSubjectsQuery)");
		$this->Course->query("DELETE FROM `subjects_users` WHERE subject_id NOT IN ($currentSubjectsQuery)");

		$currentActivitiesQuery = "SELECT DISTINCT `Activity`.id FROM activities `Activity`";
		$this->Course->query("DELETE FROM `attendance_registers` WHERE activity_id NOT IN ($currentActivitiesQuery)");
		$this->Course->query("DELETE FROM `events` WHERE activity_id NOT IN ($currentActivitiesQuery)");
		$this->Course->query("DELETE FROM `registrations` WHERE activity_id NOT IN ($currentActivitiesQuery)");

		$this->Course->query("DELETE FROM `users_attendance_register` WHERE attendance_register_id NOT IN (SELECT DISTINCT `AttendanceRegister`.id FROM attendance_registers `AttendanceRegister`)");

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
					SELECT ar1.duration, ar1.teacher_id AS teacher_id, IF(activities.type IN ('Clase magistral', 'Seminario'), 'T', IF(activities.type IN ('Tutoría', 'Evaluación', 'Taller/trabajo en grupo', 'Otra presencial'), 'O', 'P')) AS type, subjects.course_id
					FROM attendance_registers ar1
					INNER JOIN activities ON ar1.activity_id = activities.id
					INNER JOIN subjects ON subjects.id = activities.subject_id
					WHERE DATE_FORMAT(ar1.initial_hour, '%Y-%m-%d') >= '{$initialDate}' AND DATE_FORMAT(ar1.final_hour, '%Y-%m-%d') <= '{$finalDate}'
					UNION ALL
						SELECT ar2.duration, ar2.teacher_2_id AS teacher_id, IF(activities2.type IN ('Clase magistral', 'Seminario'), 'T', IF(activities2.type IN ('Tutoría', 'Evaluación', 'Taller/trabajo en grupo', 'Otra presencial'), 'O', 'P')) AS type, subjects2.course_id
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
					SELECT ar1.duration, ar1.teacher_id AS teacher_id, IF(activities.type IN ('Clase magistral', 'Seminario'), 'T', IF(activities.type IN ('Tutoría', 'Evaluación', 'Taller/trabajo en grupo', 'Otra presencial'), 'O', 'P')) AS type, subjects.course_id
					FROM attendance_registers ar1
					INNER JOIN activities ON ar1.activity_id = activities.id
					INNER JOIN subjects ON subjects.id = activities.subject_id
					WHERE DATE_FORMAT(ar1.initial_hour, '%Y-%m-%d') >= '{$initialDate}' AND DATE_FORMAT(ar1.final_hour, '%Y-%m-%d') <= '{$finalDate}'
					UNION ALL
						SELECT ar2.duration, ar2.teacher_2_id AS teacher_id, IF(activities2.type IN ('Clase magistral', 'Seminario'), 'T', IF(activities2.type IN ('Tutoría', 'Evaluación', 'Taller/trabajo en grupo', 'Otra presencial'), 'O', 'P')) AS type, subjects2.course_id
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
					SELECT ar1.duration, ar1.teacher_id AS teacher_id, IF(activities.type IN ('Clase magistral', 'Seminario'), 'T', IF(activities.type IN ('Tutoría', 'Evaluación', 'Taller/trabajo en grupo', 'Otra presencial'), 'O', 'P')) AS type, subjects.course_id
					FROM attendance_registers ar1
					INNER JOIN activities ON ar1.activity_id = activities.id
					INNER JOIN subjects ON subjects.id = activities.subject_id
					WHERE DATE_FORMAT(ar1.initial_hour, '%Y-%m-%d') >= '{$initialDate}' AND DATE_FORMAT(ar1.final_hour, '%Y-%m-%d') <= '{$finalDate}'
					UNION ALL
						SELECT ar2.duration, ar2.teacher_2_id AS teacher_id, IF(activities2.type IN ('Clase magistral', 'Seminario'), 'T', IF(activities2.type IN ('Tutoría', 'Evaluación', 'Taller/trabajo en grupo', 'Otra presencial'), 'O', 'P')) AS type, subjects2.course_id
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
						)	activities ON activities.subject_id = subjects.id
			WHERE subjects.course_id = {$course_id}
			GROUP BY subjects.id
			ORDER BY subjects.code ASC
		");

	  $this->set('subjects', $subjects);
	}

	function export_stats_by_subject($course_id = null) {
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

		$this->set('section', 'courses');

		if ((array_search($this->params['action'], $administrator_actions) !== false) && ($this->Auth->user('type') != "Administrador") && ($auth->user('type') != "Administrativo"))
			return false;

		return true;
	}
}
?>
