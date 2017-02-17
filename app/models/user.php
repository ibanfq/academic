<?php
/**
 * User model
 *
 * @version 2012-06-04
 */
class User extends AppModel {
	/**
	 * PHP4 compatibility
	 */
	var $name = 'User';

	/**
	 * HABTM relationships
	 */
	var $hasAndBelongsToMany = array(
		'Subject' => array('unique' => false, 'order' => 'Subject.level ASC, Subject.name ASC')
	);

	/**
	 * hasMany relationships
	 */
	var $hasMany = array(
		'Registration' => array('foreignKey' => 'student_id')
	);

	/**
	 * Validation rules
	 */
	var $validate = array(
		'username' => array(
			'isUnique' => array(
				'rule' => 'isUnique',
				'message' => 'Ya existe un usuario con el mismo correo electrónico.'
			)
		),
		'vat_number' => array(
			'rule' => 'isUnique',
			'message' => 'Ya existe un usuario con el mismo DNI.'
		), 
		'password' => array(
			'rule' => array('minLength', '6'),
			'message' => 'Debe tener entre 5 y 10 caracteres'
		),
		'type' => array(
			'rule' => 'notEmpty',
			'message' => 'Debe especificar el tipo de usuario'
		),
		'first_name' => array(
			'rule' => 'notEmpty',
			'message' => 'Debe especificar el nombre de pila'
		)
	);
	
	function getCalendarToken() {
		$security = Security::getInstance();
		$key = Configure::read('Security.calendarTokenSeed');
		$value = $this->id.' ';
                if (empty($this->data['User']['created'])) {
                    $value .= $this->data['User']['username'];
                } else {
                    $value .= $this->data['User']['created'];
                }
		return rtrim(strtr(base64_encode($security->cipher($value, $key)), '+/', '-_'), '=');
	}
	
	function findByCalendarToken($token) {
		$key = Configure::read('Security.calendarTokenSeed');
		$security = Security::getInstance();
		$value = $security->cipher(base64_decode(strtr($token, '-_', '+/')), $key);
		$value = explode(' ', $value, 2);
		if (count($value) !== 2) {
			return false;
		}
		list($id, $field2) = $value;
		if (!is_numeric($id)) {
			return false;
		}
		return $this->find('first', array(
			'recursive' => 0,
			'conditions' => array(
				'User.id' => $id,
				'OR' => array(
					'User.username' => $field2,
					'User.created' => $field2
				)
			)
		));
	}
	
	function getEvents() {
		switch($this->data['User']['type']){
			case "Estudiante":
				$events = $this->query("SELECT Event.id, Event.initial_hour, Event.final_hour, Subject.acronym, Activity.name, Activity.type FROM registrations Registration INNER JOIN activities Activity ON Activity.id = Registration.activity_id INNER JOIN events Event ON Event.group_id = Registration.group_id AND Event.activity_id = Registration.activity_id INNER JOIN subjects Subject ON Subject.id = Activity.subject_id WHERE Registration.student_id = {$this->id} AND Registration.group_id <> -1");
				break;
			case "Profesor":
			case "Administrador":
				$events = $this->query("SELECT Event.id, Event.initial_hour, Event.final_hour, Subject.acronym, Activity.name, Activity.type FROM events Event INNER JOIN activities Activity ON Activity.id = Event.activity_id INNER JOIN subjects Subject ON Subject.id = Activity.subject_id WHERE Event.teacher_id = {$this->id} OR Event.teacher_2_id = {$this->id}");
				break;
			default:
				$events = array();
		}
		return $events;
	}
  
  function getBookings() {
    $userType = $this->data['User']['type'];
    switch($userType) {
      case "Estudiante":
        $whereUserType = "(Booking.user_type = 'Todos' OR Booking.user_type = '$userType')";
        break;
      default:
        $whereUserType = "(Booking.user_type = 'Todos' OR Booking.user_type = 'No-estudiante' OR Booking.user_type = '$userType')";
    }
		return $this->query("SELECT DISTINCT Booking.id, Booking.initial_hour, Booking.final_hour, Booking.reason FROM bookings Booking LEFT JOIN users_booking UserBooking ON Booking.id = UserBooking.booking_id WHERE $whereUserType OR UserBooking.user_id = {$this->id}");
	}
	
	function can_send_alerts($user_id, $activity_id, $group_id) {
		return $this->query("SELECT events.* FROM events WHERE activity_id = {$activity_id} AND group_id = {$group_id} AND (teacher_id = {$user_id} OR teacher_2_id = {$user_id})") > 0;
	}

	/**
	 * Returns the number of teaching hours
	 *
	 * @param integer $teacher ID of a teacher
	 * @param integer $course ID of a course
	 * @param string $type Type of the teaching activity
	 * @return float Number of hours teached
	 * @version 2012-06-04
	 */
	function teachingHours($teacher, $course, $type = 'all') {
		$activityFilter = null;
		if ($type === 'theory') {
			$activityFilter = "AND activities.type IN ('Clase magistral', 'Seminario')";
		} elseif ($type === 'practice') {
			$activityFilter = "AND activities.type IN ('Práctica en aula', 'Práctica de problemas', 'Práctica de informática', 'Práctica de microscopía', 'Práctica de laboratorio', 'Práctica clínica', 'Práctica externa', 'Taller/trabajo en grupo')";
		} else if ($type === 'other') {
			$activityFilter = "AND activities.type IN ('Tutoría', 'Evaluación', 'Otra presencial')";
		}

		return $this->query("
			SELECT SUM(IFNULL(AttendanceRegister.duration, 0)) as total
			FROM attendance_registers AttendanceRegister
			INNER JOIN activities ON activities.id = AttendanceRegister.activity_id
			INNER JOIN subjects ON subjects.id = activities.subject_id
			WHERE (AttendanceRegister.teacher_id = $teacher OR AttendanceRegister.teacher_2_id = $teacher)
			AND subjects.course_id = $course
			$activityFilter
		");
	}
  
  /**
	 * Returns the number of scheduled hours
	 *
	 * @param integer $teacher ID of a teacher
	 * @param integer $course ID of a course
	 * @param string $type Type of the teaching activity
	 * @return float Number of hours teached
	 */
	function ScheduledHours($teacher, $course, $type = 'all') {
		$activityFilter = null;
		if ($type === 'theory') {
			$activityFilter = "AND activities.type IN ('Clase magistral', 'Seminario')";
		} elseif ($type === 'practice') {
			$activityFilter = "AND activities.type IN ('Práctica en aula', 'Práctica de problemas', 'Práctica de informática', 'Práctica de microscopía', 'Práctica de laboratorio', 'Práctica clínica', 'Práctica externa', 'Taller/trabajo en grupo')";
		} else if ($type === 'other') {
			$activityFilter = "AND activities.type IN ('Tutoría', 'Evaluación', 'Otra presencial')";
		}

		return $this->query("
			SELECT SUM(IFNULL(Event.duration, 0)) as total
			FROM events Event
			INNER JOIN activities ON activities.id = Event.activity_id
			INNER JOIN subjects ON subjects.id = activities.subject_id
			WHERE (Event.teacher_id = $teacher OR Event.teacher_2_id = $teacher)
			AND subjects.course_id = $course
			$activityFilter
		");
	}
}
?>
