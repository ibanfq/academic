<?php
class SubjectsController extends AppController {
	var $name = 'Subjects';

	var $paginate = array(
		'limit' => 10,
		'order' => array('Subject.code' => 'asc'),
	);

	function add($course_id = null) {
		if (!empty($this->data)){
			if ($this->Subject->save($this->data)){
				$this->Session->setFlash('La asignatura se ha guardado correctamente');
				$this->redirect(array('controller' => 'courses', 'action' => 'view', $this->data['Subject']['course_id']));
			} else {
				$this->set('course_id', $this->data['Subject']['course_id']);
				$this->set('course', $this->Subject->Course->find('first', array('conditions' => array('Course.id' => $this->data['Subject']['course_id']))));
			}
		} else {
			if (is_null($course_id)) {
				$this->redirect(array('controller' => 'courses', 'action' => 'index'));
			} else {
				$this->set('course', $this->Subject->Course->find('first', array('conditions' => array('Course.id' => $course_id))));
				$this->set('course_id', $course_id);
			}
		}
	}

	function view($id = null) {
		$this->Subject->id = $id;
		$subject = $this->Subject->read();

		$activities = $this->Subject->Activity->query("
			SELECT Activity.*, SUM(Event.duration) / `Group`.total AS duration, IFNULL(Registration.total / `Group`.total, 0) as students, Registration.total AS activity_total
			FROM activities Activity
			LEFT JOIN events Event ON Event.activity_id = Activity.id
			LEFT JOIN (SELECT `groups`.subject_id, `groups`.type, count(id) as total FROM `groups` where `groups`.name NOT LIKE '%no me presento%' GROUP BY `groups`.subject_id, `groups`.type) `Group` ON `Group`.subject_id = Activity.subject_id AND `Group`.type = Activity.type
			LEFT JOIN (SELECT registrations.activity_id, count(registrations.student_id) as total FROM registrations WHERE registrations.group_id <> -1 GROUP BY registrations.activity_id) Registration ON Registration.activity_id = Activity.id
			WHERE Activity.subject_id = {$id}
			GROUP BY Activity.type ASC, Activity.name ASC
			");

		$this->set('students_registered_on_subject', $this->Subject->query("
			SELECT count(*) AS total
			FROM subjects_users
			WHERE subjects_users.subject_id = {$id}
			"));

		$this->set('subject', $subject);
		$this->set('activities', $activities);
	}

	function edit($id = null){
		$this->Subject->id = $id;
		if (empty($this->data)) {
			$this->data = $this->Subject->read();
			$this->set('subject', $this->data);
		} else {
			if ($this->Subject->save($this->data)) {
				$this->Session->setFlash('La asignatura se ha modificado correctamente.');
				$this->redirect(array('action' => 'view', $id));
			}	else {
				$this->set('subject', $this->data);
			}
		}
	}

	function getScheduledInfo($id = null) {
		$activities = $this->Subject->query("
			SELECT `Group`.name AS group_name, Activity.name AS activity_name, Activity.duration, Activity.type, IFNULL(SUM(Event.duration), 0) AS scheduled
			FROM activities Activity
			INNER JOIN `groups` `Group` ON `Group`.subject_id = Activity.subject_id AND `Group`.type = Activity.type
			LEFT JOIN events Event ON Event.activity_id = Activity.id AND Event.group_id = `Group`.id
			WHERE Activity.subject_id = {$id}
			GROUP BY Activity.id, Group.id
			ORDER BY type, activity_name, group_name
			");
		$this->set('activities', $activities);

		$this->Subject->id = $id;
		$this->set('subject', $this->Subject->read());
	}

	function send_alert_students_without_group($id = null) {
		$this->Subject->id = $id;
		$subject = $this->Subject->read();

		if (($subject != null) && (($this->Auth->user('type') == "Administrador") || ($this->Auth->user('id') == $subject['Subject']['coordinator_id']) || ($this->Auth->user('id') == $subject['Subject']['practice_responsible_id']))) {
			$activities = $this->Subject->Activity->query("
				SELECT DISTINCT User.id, User.username, Activity.name
				FROM users User
				INNER JOIN subjects_users SubjectUser ON SubjectUser.user_id = User.id
				INNER JOIN activities Activity ON Activity.subject_id = SubjectUser.subject_id
				INNER JOIN events Event ON Event.activity_id = Activity.id
				WHERE SubjectUser.subject_id = {$id}
				AND NOT EXISTS (SELECT * FROM registrations Registration WHERE Registration.activity_id = Event.activity_id AND Registration.student_id = SubjectUser.user_id)
			");

			$students = array();
			foreach ($activities as $activity) {
				if (isset($students[$activity['User']['id']]))
					array_push($students[$activity['User']['id']]['activities'], $activity['Activity']['name']);
				else{
					$students[$activity['User']['id']] = array();
					$students[$activity['User']['id']]['email'] = $activity['User']['username'];
					$students[$activity['User']['id']]['activities'] = array($activity['Activity']['name']);
				}
			}

			foreach ($students as $student) {
				$this->Email->from = 'Academic <noreply@ulpgc.es>';
				$this->Email->to = $student['email'];
				$this->Email->subject = "AVISO: Debe seleccionar grupo para algunas actividades";

				$activities_list = implode('</li><li>',$student['activities']);
				$activities_list = "<ul><li>{$activities_list}</li></ul>";

				$this->Email->sendAs = 'html';
				$this->Email->send("Hola:\nDebe seleccionar grupos de prácticas para las siguientes actividades de la asignatura {$subject['Subject']['name']}:\n {$activities_list}\nSi cree que ha recibido este correo por error, por favor, póngase en contacto con los responsables de la asignatura.\n\nUn saludo,\nEl equipo de Academic.");

				$this->Email->reset();
			}

			$students_number = count($students);
			if ($students_number > 0) {
				$this->Session->setFlash("Se han avisado a {$students_number} estudiantes.");
			} else {
				$this->Session->setFlash("No se ha encontrado ningún estudiante sin grupo en esta asignatura.");
			}
			$this->redirect(array('action' => 'view', $id));

		} else {
			$this->Session->setFlash('No tiene permisos para realizar esta acción.');
			$this->redirect(array('controller' => 'courses'));
		}
	}

	function find_subjects_by_name(){
		App::import('Sanitize');
		$q = '%'.Sanitize::escape($this->params['url']['q']).'%';

		if (isset($this->params['url']['course_id'])) {
			$course_id = Sanitize::escape($this->params['url']['course_id']);
		} else{
			$course = $this->Subject->Course->current();
			$course_id = $course["id"];
		}

		if (isset($this->params['url']['user_id'])) {
			$student_id = $this->params['url']['user_id'];
			$subjects = $this->Subject->query("SELECT Subject.* FROM subjects Subject WHERE Subject.id NOT IN (SELECT subject_id AS id FROM subjects_users WHERE user_id = {$student_id}) AND Subject.name LIKE '{$q}' AND Subject.course_id = {$course_id}");
		} else {
			$subjects = $this->Subject->find('all', array('conditions' => array('Subject.name LIKE' => $q, 'Subject.course_id =' => $course_id)));
		}

		$this->set('subjects', $subjects);
	}

	function students_stats($subject_id = null) {
		$teorical_types = "'Clase magistral', 'Seminario'";
		$practice_types = "'Práctica en aula', 'Práctica de problemas', 'Práctica de informática', 'Práctica de microscopía', 'Práctica de laboratorio', 'Práctica clínica', 'Práctica externa', 'Taller/trabajo en grupo'";
		$other_types = "'Tutoría', 'Evaluación', 'Otra presencial'";

		$students = $this->Subject->query("SELECT Student.*, IFNULL(teorical.total, 0) AS teorical, IFNULL(practice.total, 0) AS practice, IFNULL(others.total, 0) AS others FROM users Student INNER JOIN subjects_users SU ON SU.user_id = Student.id LEFT JOIN (SELECT user_id, SUM(Event.duration) AS total FROM users_attendance_register UAR INNER JOIN attendance_registers AttendanceRegister ON AttendanceRegister.id = UAR.attendance_register_id INNER JOIN events Event ON Event.id = AttendanceRegister.event_id INNER JOIN activities Activity ON Activity.id = AttendanceRegister.activity_id WHERE UAR.user_gone AND Activity.subject_id = {$subject_id} AND Activity.type IN ({$teorical_types}) GROUP BY user_id) teorical ON teorical.user_id = Student.id LEFT JOIN (SELECT user_id, SUM(Event.duration) AS total FROM users_attendance_register UAR INNER JOIN attendance_registers AttendanceRegister ON AttendanceRegister.id = UAR.attendance_register_id INNER JOIN events Event ON Event.id = AttendanceRegister.event_id INNER JOIN activities Activity ON Activity.id = AttendanceRegister.activity_id WHERE UAR.user_gone AND Activity.subject_id = {$subject_id} AND Activity.type IN ({$practice_types}) GROUP BY user_id) practice ON practice.user_id = Student.id LEFT JOIN (SELECT user_id, SUM(Event.duration) AS total FROM users_attendance_register UAR INNER JOIN attendance_registers AttendanceRegister ON AttendanceRegister.id = UAR.attendance_register_id INNER JOIN events Event ON Event.id = AttendanceRegister.event_id INNER JOIN activities Activity ON Activity.id = AttendanceRegister.activity_id WHERE UAR.user_gone AND Activity.subject_id = {$subject_id} AND Activity.type IN ({$other_types}) GROUP BY user_id) others ON others.user_id = Student.id WHERE SU.subject_id = {$subject_id} ORDER BY Student.last_name, Student.first_name");

		$this->set('students', $students);
		$this->Subject->id = $subject_id;
		$this->set('subject', $this->Subject->read());
	}
	
	function students_edit($subject_id = null) {
		$this->Subject->id = $subject_id;
		$this->Subject->unbindModel(array('belongsTo' => array('Coordinator', 'Responsible'), 'hasMany' => array('Group')));
		$subject = $this->Subject->read();
		$students = $this->Subject->query("SELECT Student.*, SubjectStudent.* FROM users Student INNER JOIN subjects_users SubjectStudent ON SubjectStudent.user_id = Student.id WHERE SubjectStudent.subject_id = {$subject_id} ORDER BY Student.last_name, Student.first_name");
		$subject['Students'] = &$students;
		if (empty($this->data)) {
			$this->data = $subject;
		} else {
			$users_to_update = array();
			$users_to_approve = array();
			$users_to_disapprove = array();
			foreach ($students as &$student) {
				$student_id = $student['Student']['id'];
				if (isset($this->data['Students'][$student_id]['practices_approved'])) {
					$practices_approved = (bool)$this->data['Students'][$student_id]['practices_approved'];
					if ($student['SubjectStudent']['practices_approved'] != $practices_approved) {
						$users_to_update[$student_id] = $student_id;
						if ($practices_approved) {
							$users_to_approve[$student_id] = $student_id;
						} else {
							$users_to_disapprove[$student_id] = $student_id;
						}
						$student['SubjectStudent']['practices_approved'] = $practices_approved;
					}
				}
			}
			$this->data = $subject;
			$error = false;
			
			if (!empty($users_to_update)) {
				$practices_id = array();
				foreach ($subject['Activity'] as $activity) {
					if ($this->Subject->Activity->typeIsPractice($activity['type'])) {
						$practices_id[] = $activity['id'];
					}
				}

				$this->loadModel('SubjectsUser');
				$subjects_users = $this->SubjectsUser->find('all', array('conditions' => array('subject_id' => $subject_id, 'user_id' => $users_to_update)));
				foreach ($subjects_users as &$subject_user) {
					if (isset($users_to_approve[$subject_user['SubjectsUser']['user_id']])) {
						$subject_user['SubjectsUser']['practices_approved'] = 1;
					} else {
						$subject_user['SubjectsUser']['practices_approved'] = 0;
					}
				}

				$this->loadModel('Registration');
				$this->Registration->unbindModel(array('hasOne' => array('User', 'Activity', 'Group')), false);
				$registrations = $this->Registration->find('all', array('conditions' => array('activity_id' => $practices_id, 'student_id' => $users_to_update)));
				$registrations_to_delete = array();
				$registrations_to_save = array();
				foreach ($registrations as &$registration) {
					$user_id = $registration['Registration']['student_id'];
					if (isset($users_to_disapprove[$user_id])) {
						if ($registration['Registration']['group_id'] == -1) {
							$registrations_to_delete[] = $registration['Registration']['id'];
						}
					} else {
						$registration['Registration']['group_id'] = -1;
						$registrations_to_save[$user_id][$registration['Registration']['activity_id']] = $registration;
					}
				}
				foreach ($users_to_approve as $user_id) {
					if (!isset($registrations_to_save[$user_id])) {
						$registrations_to_save[$user_id] = array();
					}
					foreach ($practices_id as $activity_id) {
						if (!isset($registrations_to_save[$user_id][$activity_id])) {
							$registrations_to_save[$user_id][$activity_id] = array('Registration' => array(
								'activity_id' => $activity_id,
								'student_id' => $user_id,
								'group_id' => -1
							));
						}
					}
				}
				$registrations = array();
				foreach ($registrations_to_save as &$user_registrations) {
					foreach ($user_registrations as &$registration) {
						$registrations[] = $registration;
					}
				}
				$registrations_to_save = &$registrations;
				
				if (!empty($registrations_to_save) && !$this->Registration->saveAll($registrations_to_save)) {
					$error = true;
				} elseif (!empty($registrations_to_delete) && !$this->Registration->deleteAll(array('Registration.id' => $registrations_to_delete))) {
					$error = true;
				} elseif (!empty($subjects_users) && !$this->SubjectsUser->saveAll($subjects_users)) {
					$error = true;
				}
			}
			
			if ($error) {
				$this->Session->setFlash('Error al actualizar los estudiantes.');
			} else {
				$this->Session->setFlash('Los estudiantes se han modificado correctamente.');
				$this->redirect(array('action' => 'view', $subject_id));
			}
		}
		
		$this->set('subject', $this->data);
	}

	function statistics($subject_id = null) {
		$this->Subject->id = $subject_id;
		if (!$this->Subject->exists()) {
			$this->Session->setFlash('La asignatura no existe.');
			$this->redirect($this->referer());
		}

		$this->set(array(
			'subject' => $this->Subject->findById($subject_id),
			'activities' => $this->Subject->activityHoursSummary($subject_id),
			'registers' => $this->Subject->teachingHoursSummary($subject_id),
		));
	}

	function delete($id = null){
		$this->Subject->id = $id;
		$subject = $this->Subject->read();
		$course_id = $subject['Subject']['course_id'];
		$this->Subject->delete($id);
		$this->Session->setFlash('La asignatura ha sido eliminada correctamente');
		$this->redirect(array('controller' => 'courses', 'action' => 'index', $course_id));
	}

	function _authorize() {
		parent::_authorize();
		$administrator_actions = array('add', 'edit', 'delete');

		$this->set('section', 'courses');

		if ((array_search($this->params['action'], $administrator_actions) !== false) && ($this->Auth->user('type') != "Administrador"))
			return false;

		return true;
	}
}
