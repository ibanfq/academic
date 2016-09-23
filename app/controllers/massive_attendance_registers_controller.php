<?php
class MassiveAttendanceRegistersController extends AppController {
	var $name = 'MassiveAttendanceRegisters';

	var $helpers = array('Ajax');

	function add($course_id = null) {
		if ($course_id == null) {
			$course_id = $this->params['form']['course_id'];
		}
		$course = $this->MassiveAttendanceRegister->Subject->Course->findById($course_id);

		$classrooms = $this->MassiveAttendanceRegister->AttendanceRegister->Event->Classroom->find('all', array('order' => 'name', 'recursive' => 0));
		$classrooms_mapped = array();
		foreach($classrooms as $cl) {
			$classrooms_mapped[$cl['Classroom']['id']] = $cl['Classroom']['name'];
		}

		$this->set('course', $course);
		$this->set('classrooms', $classrooms_mapped);

		if ((isset($this->params['form']['registers'])) && ($this->_valid())) {
			foreach($this->params['form']['registers'] as $id => $data) {
				$initial_hour = new DateTime($data['initial_date']." ".$data['initial_hour']);
				$final_hour = $initial_hour;
				$this->_add_days($final_hour, 0, $data['duration'] * 60);

				$initial_hour = $initial_hour->format('Y-m-d H:i:s');
				$final_hour = $final_hour->format('Y-m-d H:i:s');

				if ($data['teacher_2_id'] == '') {
					$teacher_2_id = "NULL";
				} else {
					$teacher_2_id = $data['teacher_2_id'];
				}
				$this->MassiveAttendanceRegister->query("UPDATE attendance_registers SET"
					. " teacher_id = {$data['teacher_id']},"
					. " teacher_2_id = {$teacher_2_id},"
					. " event_id = {$data['event_id']},"
					. " initial_hour = '{$initial_hour}',"
					. " final_hour = '{$final_hour}',"
					. " duration = {$data['duration']},"
					. " activity_id = {$data['activity_id']},"
					. " `group_id` = {$data['group_id']}"
					. " WHERE id = {$id}");
			}
			$this->Session->setFlash('El registro masivo se ha creado con éxito.');
			$this->redirect(array('action' => 'add', $this->params['form']['course_id']));
		} elseif ((isset($this->params['form']['date'])) && (isset($this->params['data']['MassiveAttendanceRegister']['classroom']))) {
			$date = new DateTime($this->params['form']['date']);
			$classroom = $this->params['data']['MassiveAttendanceRegister']['classroom'];
			$this->set('date', $date);
			$this->set('classroom', $classroom);
			$registers = $this->_load_registers($date, $classroom);
			$this->set('registers', $registers);
		}
	}

	function _load_registers($date, $classroom_id){
		$this->_create_attendance_registers($date, $classroom_id);

		$registers = $this->MassiveAttendanceRegister->query("
			SELECT AttendanceRegister.*, `Group`.name, `Group`.id, Activity.id, Activity.name, User.id, User.first_name, User.last_name, User2.id, User2.first_name, User2.last_name, Subject.name, `Event`.`duration`
			FROM attendance_registers AttendanceRegister
			INNER JOIN groups `Group` ON `Group`.id = `AttendanceRegister`.group_id
			INNER JOIN events `Event` ON `Event`.id = `AttendanceRegister`.event_id
			INNER JOIN activities Activity ON Activity.id = AttendanceRegister.activity_id AND `Group`.type = Activity.type AND `Group`.subject_id = Activity.subject_id
			INNER JOIN users User ON User.id = AttendanceRegister.teacher_id
			LEFT JOIN users User2 ON User2.id = AttendanceRegister.teacher_2_id
			INNER JOIN subjects Subject ON Subject.id = Activity.subject_id AND Subject.id = `Group`.subject_id
			WHERE DATE_FORMAT(AttendanceRegister.initial_hour, '%Y-%m-%d') = '{$date->format("Y-m-d")}'
      AND AttendanceRegister.duration = 0
			AND Event.classroom_id = {$classroom_id}
			ORDER BY AttendanceRegister.initial_hour, Subject.name, Activity.name
		");

		return $registers;
	}

	function _create_attendance_registers($date, $classroom_id) {
		$events = $this->MassiveAttendanceRegister->query("
			SELECT Event.*
			FROM events Event
			INNER JOIN groups `Group` ON `Group`.id = Event.group_id
			INNER JOIN activities Activity ON Activity.id = Event.activity_id AND `Group`.type = Activity.type AND `Group`.subject_id = Activity.subject_id
			INNER JOIN users User ON User.id = Event.teacher_id
			WHERE DATE_FORMAT(Event.initial_hour, '%Y-%m-%d') = '{$date->format('Y-m-d')}'
			AND Event.classroom_id = {$classroom_id}
			AND Event.id NOT IN (SELECT IFNULL(event_id, 0) FROM attendance_registers WHERE initial_hour <> '0000-00-00 00:00:00')
		");

		if (count($events) > 0) {
			$query = "INSERT INTO attendance_registers(event_id, initial_hour, final_hour, duration, teacher_id, activity_id, group_id) VALUES ";
			$values = array();
			foreach($events as $event) {
				$delete_query = "DELETE FROM attendance_registers WHERE event_id = {$event['Event']['id']} AND initial_hour = '0000-00-00 00:00:00'";
				$this->MassiveAttendanceRegister->query($delete_query);
				array_push($values, "({$event['Event']['id']}, '{$event['Event']['initial_hour']}', '{$event['Event']['final_hour']}', {$event['Event']['duration']}, {$event['Event']['teacher_id']}, {$event['Event']['activity_id']}, {$event['Event']['group_id']})");
			}
			$query .= implode($values, ",");
			$this->MassiveAttendanceRegister->query($query);
		}
	}

	function _authorize(){
		parent::_authorize();

		$private_actions = array("add");

		if (($this->Auth->user('type') != "Administrador") && ($this->Auth->user('type') != "Administrativo"))
			return false;

		$this->set('section', 'courses');
		return true;
	}

	function _add_days(&$date, $ndays, $nminutes = 0){
		$date_components = split("-", $date->format('Y-m-d-H-i-s'));
		$timestamp = mktime($date_components[3],$date_components[4],$date_components[5], $date_components[1], $date_components[2] + $ndays, $date_components[0]);
		$timestamp += ($nminutes * 60);
		$date_string = date('Y-m-d H:i:s', $timestamp);
		$date = new DateTime($date_string);
	}

	function _valid() {
		if (isset($this->params['form']['registers'])) {
			$error = "";
			foreach($this->params['form']['registers'] as $id => $event) {
				if ((!isset($event['teacher_id'])) || ($event['teacher_id'] == null)) {
					$error = "No se ha especificado alguno de los profesores.";
					break;
				}

				if (preg_match("/^\d+(\.\d{2})?$/", $event['duration']) == 0) {
					$error = "La duración de alguno de los eventos tiene un formato incorrecto.";
					break;
				}

				if (preg_match("/^\d{1,2}-\d{1,2}-\d{4}$/", $event['initial_date']) == 0) {
					$error = "La fecha de alguno de los eventos tiene un formato incorrecto.";
					break;
				}

				if (preg_match("/^\d{2}:\d{2}$/", $event['initial_hour']) == 0) {
					$error = "La hora de inicio de alguno de los eventos tiene un formato incorrecto.";
					break;
				}
			}
			if ($error == "")
				return true;
			else {
				$this->Session->setFlash($error);
				return false;
			}
		}
	}
}
?>
