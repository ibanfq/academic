<?php
class AttendanceRegistersController extends AppController {
	var $name = 'AttendanceRegisters';

	var $paginate = array(
		'limit' => 10,
		'order' => array('AttendanceRegister.initial_hour' => 'desc'),
	);

	var $helpers = array('Ajax', 'Barcode');

	/**
	 * Shows a list of attendance registers
	 */
	function index($teacher_id = -1, $activity_id = -1, $date = -1) {
		if (!empty($this->data)) {
			$conditions = $this->_get_search_conditions($this->data['AttendanceRegister']['activity_id'], $this->data['AttendanceRegister']['teacher_id'], $this->data['AttendanceRegister']['date']);
			$registers = $this->paginate('AttendanceRegister', array($conditions));
		} elseif (($teacher_id != -1) || ($activity_id != -1) || ($date != -1)) {
			if ($activity_id == -1)
				$activity_id = null;

			if ($teacher_id == -1)
				$teacher_id = null;

			if ($date == -1)
				$date = null;

			$conditions = $this->_get_search_conditions($activity_id, $teacher_id, $date);
			$registers = $this->paginate('AttendanceRegister', array($conditions));
		} else{
			$registers = $this->paginate('AttendanceRegister', array('AttendanceRegister.duration > 0'));
		}

		$this->set('registers', $registers);
	}

	function _get_search_conditions($activity_id, $teacher_id, $date){
		$conditions = "AttendanceRegister.duration > 0";
		if ($activity_id != null){
			$activity = $this->AttendanceRegister->Activity->findById($activity_id);
			$this->set('activity', $activity);
			$conditions .= " AND AttendanceRegister.activity_id = {$activity_id}";
		}

		if ($teacher_id != null) {
			$teacher = $this->AttendanceRegister->Teacher->findById($teacher_id);
			$this->set('teacher', $teacher);
			$conditions .= " AND (AttendanceRegister.teacher_id = {$teacher_id} OR AttendanceRegister.teacher_2_id = {$teacher_id})";
		}

		if ($date != null) {
			$date = $this->_parse_date($date, "-");
			$this->set('date', date_create($date));
			$conditions .= " AND DATE_FORMAT(AttendanceRegister.initial_hour, '%Y-%m-%d') = '{$date}'";
		}

		return $conditions;
	}

	/**
	 * Adds an attendance register with a possibly modified list of students
	 *
	 * @since 2012-05-18
	 */
	function add(){
		if (!empty($this->data)){
			list($id) = sscanf($this->data['AttendanceRegister']['id'], "%d");
			if ($id != null) {
				$ar = $this->AttendanceRegister->read(null, $id);

				$this->data['AttendanceRegister']['initial_hour'] = $this->data['AttendanceRegister']['initial_hour']['hour'].":".$this->data['AttendanceRegister']['initial_hour']['minute'];
				$this->data['AttendanceRegister']['final_hour'] = $this->data['AttendanceRegister']['final_hour']['hour'].":".$this->data['AttendanceRegister']['final_hour']['minute'];

				if (!(isset($this->data['AttendanceRegister']['teacher_id'])))
					$this->data['AttendanceRegister']['teacher_id'] = $ar["Event"]["teacher_id"];

				if (isset($this->data['AttendanceRegister']['students'])){
					$students = array_unique(array_keys($this->data['AttendanceRegister']['students']));
					$this->data['Student']['Student'] = $students;
					unset($this->data['AttendanceRegister']['students']);
				}

				if ($this->AttendanceRegister->save($this->data)){
					$this->Session->setFlash('El registro de impartición se ha creado correctamente.');
					$this->redirect(array('action' => 'add'));
				}
				else {
					$this->Session->setFlash('No se ha podido crear el registro de impartición. Por favor, revise que ha introducido todos los datos correctamente.');
					$this->redirect(array('action' => 'index'));
				}
			} else {
				$this->Session->setFlash('No se puede crear un registro de impartición sin especificar su código de barras.');
				$this->redirect(array('action' => 'index'));
			}
		}
	}

	/**
	 * Shows an attendance register
	 */
	function view($id){
		$this->AttendanceRegister->id = $id;
		$ar = $this->AttendanceRegister->read();
		$this->set('ar', $ar);
		$this->set('subject', $this->AttendanceRegister->Activity->Subject->findById($ar['Activity']['subject_id']));
	}

	/**
	 * Returns details of an attendance register via AJAX call
	 *
	 * @version 2012-05-30
	 */
	function get_register_info($event_id = null){
		list($id) = sscanf($event_id, "%d");
		$ar = $this->AttendanceRegister->findById($id);
		$ar["AttendanceRegister"]["teacher_id"] = $ar["Event"]["teacher_id"];
		$ar["AttendanceRegister"]["teacher_2_id"] = $ar["Event"]["teacher_2_id"];

		$teacher = $this->AttendanceRegister->Teacher->findById($ar["Event"]["teacher_id"]);
		$teacher2 = $this->AttendanceRegister->Teacher_2->findById($ar["Event"]["teacher_2_id"]);
		$ar["Teacher"] = $teacher["Teacher"];
		$ar["Teacher_2"] = $teacher2["Teacher_2"];

		if ($ar != null) {
			$students = array();
			if (empty($ar['Student'])) {
				// Load student list from original registration
				$students = $this->AttendanceRegister->Student->query("
					SELECT Student.*
					FROM users Student
					INNER JOIN registrations Registration ON Registration.student_id = Student.id
					WHERE Registration.group_id = {$ar['AttendanceRegister']['group_id']}
					AND Registration.activity_id = {$ar['AttendanceRegister']['activity_id']}
					ORDER BY Student.last_name, Student.first_name
				");
			} else {
				// Load student list from preloaded attendance registers
				$students = $this->AttendanceRegister->Student->query("SELECT Student.*
					FROM users Student
					INNER JOIN users_attendance_register UAR ON UAR.user_id = Student.id
					WHERE UAR.attendance_register_id = {$ar['AttendanceRegister']['id']}
					ORDER BY Student.last_name, Student.first_name
				");
			}

			$subject = $this->AttendanceRegister->Activity->Subject->findById($ar['Activity']['subject_id']);
			$this->set('ar', $ar);
			$this->set('students', $students);
			$this->set('subject', $subject);
		}
	}

	/**
	 * Edits attendance register
	 */
	function edit($id = null){
		if (!empty($this->data)){
			$this->data['AttendanceRegister']['id'] = $id;
			$this->data['AttendanceRegister']['initial_hour'] = $this->data['AttendanceRegister']['initial_hour']['hour'].":".$this->data['AttendanceRegister']['initial_hour']['minute'];
			$this->data['AttendanceRegister']['final_hour'] = $this->data['AttendanceRegister']['final_hour']['hour'].":".$this->data['AttendanceRegister']['final_hour']['minute'];

			if (isset($this->data['AttendanceRegister']['students'])){
				$students = array_unique(array_keys($this->data['AttendanceRegister']['students']));
				$this->data['Student']['Student'] = $students;
				unset($this->data['AttendanceRegister']['students']);
			}

			if ($this->AttendanceRegister->save($this->data)){
				$this->Session->setFlash('El registro de impartición se ha creado correctamente.');
				$this->redirect(array('action' => 'view', $id));
			}
			else {
				$this->Session->setFlash('No se ha podido crear el registro de impartición. Por favor, revise que ha introducido todos los datos correctamente.');
				$this->redirect(array('action' => 'view', $id));
			}

		} else {
			$this->data = $this->AttendanceRegister->read(null, $id);
			$this->set('subject', $this->AttendanceRegister->Activity->Subject->findById($this->data['Activity']['subject_id']));
			$this->set('ar', $this->data);
		}
	}

	/**
	 * Prints PDF with a list of students registered in the activity
	 *
	 * @param integer $event_id ID of an event
	 * @return void
	 * @version 2012-05-30
	 */
	function print_attendance_file($event_id = null){
		$this->layout = 'print';
		if ($event_id == null) {
			return;
		}

		$event = $this->AttendanceRegister->Event->findById($event_id);
		if ($event['AttendanceRegister']['id'] == null) {
			// Preload a list of students attending to this activity
			$students = $this->AttendanceRegister->Student->query("
				SELECT Student.id
				FROM users Student
				INNER JOIN registrations Registration ON Student.id = Registration.student_id
				WHERE Registration.activity_id = {$event['Event']['activity_id']}
				AND Registration.group_id = {$event['Event']['group_id']}
				ORDER BY Student.last_name, Student.first_name
			");

			$ar = array(
				'AttendanceRegister' => array(
					'event_id' => $event_id,
					'initial_hour' => $event['Event']['initial_hour'],
					'final_hour' => $event['Event']['final_hour'],
					'activity_id' => $event['Activity']['id'],
					'group_id' => $event['Group']['id'],
					'teacher_id' => $event['Teacher']['id'],
					'teacher_2_id' => $event['Teacher_2']['id'],
				),
				'Student' => array('Student' => array_unique(Set::classicExtract($students, '{n}.Student.id'))),
			);
			$this->AttendanceRegister->create();
			$this->AttendanceRegister->saveAll($ar);

			$event['AttendanceRegister'] = $ar;
			$event['AttendanceRegister']['id'] = $this->AttendanceRegister->id;
		} else {
			if (!isset($event["Teacher_2"]["id"])) {
				$event["Teacher_2"]["id"] = -1;
			}
			$this->AttendanceRegister->query("
				UPDATE attendance_registers
				SET teacher_id = {$event["Teacher"]["id"]}, teacher_2_id = {$event["Teacher_2"]["id"]}
				WHERE id = {$event['AttendanceRegister']['id']}
			");
		}

		$students = $this->AttendanceRegister->query("
			SELECT Student.*
			FROM users Student
			INNER JOIN users_attendance_register UAR ON UAR.user_id = Student.id
			WHERE UAR.attendance_register_id = {$event['AttendanceRegister']['id']}
			ORDER BY Student.last_name, Student.first_name
		");

		/**
		 * Temporary fix to reload students from original registrations.
		 *
		 * After deleting corrupted registers from `users_attendance_register` table,
		 * several `attendance_registers` records remained created without associated
		 * students. Future records will be created correctly, but this is necessary
		 * to restore previous associations with students.
		 *
		 * @author Eliezer Talon <elitalon@gmail.com>
		 * @since 2012-06-14
		 */
		if (empty($students)) {
			$students = $this->AttendanceRegister->Student->query("
				SELECT Student.*
				FROM users Student
				INNER JOIN registrations Registration ON Student.id = Registration.student_id
				WHERE Registration.activity_id = {$event['Event']['activity_id']}
				AND Registration.group_id = {$event['Event']['group_id']}
				ORDER BY Student.last_name, Student.first_name
			");
		}

		$this->set('event', $event);
		$this->set('students', $students);
		$this->set('subject', $this->AttendanceRegister->Event->Activity->Subject->findById($event['Activity']['subject_id']));
	}

	/**
	 * Shows a list of activities given by a teacher
	 */
	function view_my_registers($course_id = null){
		$user_id = $this->Auth->user('id');
		$date = date("Y-m-d");

		$attendance_registers = $this->AttendanceRegister->query("
			SELECT DISTINCT Activity.*, Subject.*, Event.*, IFNULL(uar.num_students, 0) AS num_students
			FROM events Event
			INNER JOIN activities Activity ON Activity.id = Event.activity_id
			INNER JOIN subjects Subject ON Subject.id = Activity.subject_id
			LEFT JOIN attendance_registers AttendanceRegister ON AttendanceRegister.event_id = Event.id
			LEFT JOIN (SELECT attendance_register_id, count(*) AS num_students FROM users_attendance_register
			GROUP BY attendance_register_id) uar ON uar.attendance_register_id = AttendanceRegister.id
			WHERE (Subject.course_id = {$course_id})
			AND (Event.teacher_id = {$user_id} OR Event.teacher_2_id = {$user_id} OR Subject.coordinator_id = {$user_id} OR Subject.practice_responsible_id = {$user_id})
			AND DATE_FORMAT(Event.initial_hour, '%Y-%m-%d') <= '{$date}'
			ORDER BY Event.initial_hour
			");
		$this->set('attendance_registers', $attendance_registers);
	}

	/**
	 * Edits student attendance to a given activity
	 * after the activity took place
	 *
	 * @param integer $event_id ID of an event
	 * @return void
	 * @version 2012-05-30
	 */
	function edit_student_attendance($event_id = null) {
		// Preload student attendance by saving an attendance register
		if (!empty($this->data)) {
			list($id) = sscanf($this->data['AttendanceRegister']['id'], "%d");

			$ar = $this->AttendanceRegister->read(null, $id);
			if (isset($this->data['AttendanceRegister']['students'])) {
				$students = array_unique(array_keys($this->data['AttendanceRegister']['students']));
				$this->data['Student']['Student'] = $students;
				unset($this->data['AttendanceRegister']['students']);
			} else {
				$this->data['Student']['Student'] = null;
			}

			if ($this->AttendanceRegister->save($this->data)) {
				$this->Session->setFlash('El registro de asistencia se ha creado correctamente.');
				$course = $this->AttendanceRegister->Activity->Subject->Course->current();
				$this->redirect(array('action' => 'view_my_registers', $course['id']));
			} else {
				$this->Session->setFlash('No se ha podido crear el registro de asistencia. Por favor, revise que ha introducido todos los datos correctamente.');
				$this->redirect(array('action' => 'index'));
			}
		} else {
			/**
			 * Block edition until attendance sheet has been printed
			 */
			$ar = $this->AttendanceRegister->findByEventId($event_id);
			if (!$ar) {
				$event = $this->AttendanceRegister->Event->findById($event_id);
				$this->set('students', false);
				$this->set('subject', $this->AttendanceRegister->Activity->Subject->findById($event["Activity"]["subject_id"]));
				$this->set('event', $event);
			} else {
				$event = $this->AttendanceRegister->Event->findById($event_id);
				if (!isset($ar['AttendanceRegister']['id'])) {
					$this->data['AttendanceRegister'] = array('id' => null,
						'event_id' => $event['Event']['id'],
						'initial_hour' => $event['Event']['initial_hour'],
						'final_hour' => $event['Event']['final_hour'],
						'activity_id' => $event['Event']['activity_id'],
						'group_id' => $event['Event']['group_id'],
						'teacher_id' => $event['Event']['teacher_id'],
						'teacher_2_id' => $event['Event']['teacher_2_id']);

					$this->AttendanceRegister->save($this->data);
					$ar = $this->AttendanceRegister->findByEventId($event_id);
				}

				if ((isset($ar['Student'])) && (count($ar['Student']))) {
					$students = $this->AttendanceRegister->query("
						SELECT Student.*
						FROM users Student
						INNER JOIN users_attendance_register UAR ON UAR.user_id = Student.id
						WHERE UAR.attendance_register_id = {$ar['AttendanceRegister']['id']}
						ORDER BY Student.last_name, Student.first_name
						");
				} else {
					$students = $this->AttendanceRegister->query("
						SELECT Student.*
						FROM users Student
						INNER JOIN registrations ON registrations.student_id = Student.id
						WHERE registrations.activity_id = {$event['Event']['activity_id']}
						AND registrations.group_id = {$event['Event']['group_id']}
						ORDER BY Student.last_name, Student.first_name
						");
				}

				$students_raw = array();
				foreach ($students as $student) {
					array_push($students_raw, $student['Student']);
				}

				$this->set('students', $students_raw);
				$this->set('subject', $this->AttendanceRegister->Activity->Subject->findById($ar["Activity"]["subject_id"]));
				$this->set('ar', $ar);
			}
		}
	}

	function _authorize(){
		parent::_authorize();
		$private_actions = array("index", "add", "edit", "get_register_info");

		if (($this->Auth->user('type') != "Profesor") && ($this->Auth->user('type') != "Administrador") && ($this->Auth->user('type') != "Administrativo") && ($this->Auth->user('type') != "Becario"))
			return false;

		if (($this->Auth->user('type') != "Administrador") && ($this->Auth->user('type') != "Becario") && ($this->Auth->user('type') != "Administrativo") && (array_search($this->params['action'], $private_actions) !== false))
			return false;

		$this->set('section', 'attendance_registers');
		return true;
	}

	/**
	 * Deletes an attendance register
	 *
	 * @param integer $id ID of an attendance register
	 * @return void
	 * @since 2012-05-17
	 */
	function delete($id) {
		$this->AttendanceRegister->id = $id;
		if (!$this->AttendanceRegister->exists()) {
			$this->Session->setFlash('El registro de asistencia que intentas eliminar no existe.');
			$this->redirect(array('action' => 'index'));
		}

		$this->AttendanceRegister->query("DELETE FROM users_attendance_register WHERE attendance_register_id = $id");
		$updated = $this->AttendanceRegister->updateAll(
			array(
				'AttendanceRegister.duration' => 0.0,
				'AttendanceRegister.num_students' => 0,
			),
			array('AttendanceRegister.id' => $id)
		);
		if ($updated) {
			$this->Session->setFlash('El registro de asistencia se eliminó correctamente.');
			$this->redirect(array('action' => 'index'));
		}

		$this->Session->setFlash('El registro de asistencia no se pudo eliminar. Si el error continúa contacta con el administrador.');
		$this->redirect($this->referer());
	}
}
?>
