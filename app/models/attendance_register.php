<?php
require_once('models/academic_model.php');

class AttendanceRegister extends AcademicModel {
	var $name = "AttendanceRegister";
	var $belongsTo = array(
			'Event',
			'Activity', 
			'Group',
			'Teacher' => array(
				'className' => 'User',
				'foreignKey' => 'teacher_id',
				'conditions' => array("(Teacher.type = 'Profesor' OR Teacher.type = 'Administrador')")),
			'Teacher_2' => array(
				'className' => 'User',
				'foreignKey' => 'teacher_2_id',
				'conditions' => array("(Teacher_2.type = 'Profesor' OR Teacher_2.type = 'Administrador')"))
		);
	var $hasAndBelongsToMany = array(
		'Student' =>
			array(
					'className'				=> 'User',
					'joinTable'				=> 'users_attendance_register',
					'foreignKey'			=> 'attendance_register_id',
					'associationForeignKey'	=> 'user_id',
					'unique'				=> true
				)
		);
	
	var $validate = array(
			'initial_hour' => array(
				'notEmpty' => array(
						'message' => 'La hora de inicio no puede estar vacía',
						'rule' => array('initialHourNotEmpty')
					)
				),
			'final_hour' => array(
				'notEmpty' => array(
						'message' => 'La hora de fin no puede estar vacía',
						'rule' => array('finalHourNotEmpty')
					)
				)
		);
	
	function beforeValidate(){
		if (!empty($this->data['AttendanceRegister']['date'])) {
			$internal_format = $this->dateFormatInternal($this->data['AttendanceRegister']['date']);
			$initial_hour = date_create("{$internal_format} {$this->data['AttendanceRegister']['initial_hour']}");
			$final_hour = date_create("{$internal_format} {$this->data['AttendanceRegister']['final_hour']}");
			
			$this->data['AttendanceRegister']['initial_hour'] = $initial_hour->format('Y-m-d H:i:s');
			
			$this->data['AttendanceRegister']['final_hour'] = $final_hour->format('Y-m-d H:i:s');
			$this->data['AttendanceRegister']['duration'] = $this->_get_register_duration($initial_hour, $final_hour);
		}

		return true;
	}
  
  function createFromEvent($event, $is_printing_attendance_file = true, $secret_code = null) {
    $is_new = false;
    if ($event['AttendanceRegister']['id'] == null) {
      $is_new = true;
			// Preload a list of students attending to this activity
			$students = $this->Student->query("
				SELECT Student.*
				FROM users Student
				INNER JOIN registrations Registration ON Student.id = Registration.student_id
				WHERE Registration.activity_id = {$event['Event']['activity_id']}
				AND Registration.group_id = {$event['Event']['group_id']}
				ORDER BY Student.last_name, Student.first_name
			");

			$ar = array(
				'AttendanceRegister' => array(
					'event_id' => $event['Event']['id'],
					'initial_hour' => $event['Event']['initial_hour'],
					'final_hour' => $event['Event']['final_hour'],
					'activity_id' => $event['Activity']['id'],
					'group_id' => $event['Group']['id'],
					'teacher_id' => $event['Teacher']['id'],
					'teacher_2_id' => $event['Teacher_2']['id'],
				),
				'Student' => array('Student' => array_unique(Set::classicExtract($students, '{n}.Student.id'))),
			);
      if (!empty($secret_code)) {
        $ar['AttendanceRegister']['secret_code'] = $secret_code;
      }
			$this->create();
			$this->saveAll($ar);

			$event['AttendanceRegister'] = $ar['AttendanceRegister'];
			$event['AttendanceRegister']['id'] = $this->id;
			
			$attendanceCreatedTimestamp = time();
		} else {
			if (!isset($event["Teacher_2"]["id"])) {
				$event["Teacher_2"]["id"] = -1;
			}
      
      $set_secret_code = '';
      if (empty($event['AttendanceRegister']['secret_code']) && !empty($secret_code)) {
        App::import('Sanitize');
        $set_secret_code = ', secret_code = "' . Sanitize::escape($secret_code) . '"';
        $event['AttendanceRegister']['secret_code'] = $secret_code;
      }
      $event['AttendanceRegister']['teacher_id'] = $event["Teacher"]["id"];
      $event['AttendanceRegister']['teacher_2_id'] = $event["Teacher_2"]["id"];
      
			$this->query("
				UPDATE attendance_registers
				SET teacher_id = {$event["Teacher"]["id"]}, teacher_2_id = {$event["Teacher_2"]["id"]} $set_secret_code
				WHERE id = {$event['AttendanceRegister']['id']}
			");
			$this->data = array('AttendanceRegister' => $event['AttendanceRegister']);
			$this->id = $event['AttendanceRegister']['id'];
			
			$attendanceCreatedTimestamp = strtotime($event['AttendanceRegister']['created']);

      /**
       * Update users preloaded in attendance register if activity hasn't take place.
       *
       * This has been added because students can register and unregister in activities
       * at any time.
       *
       * @author Eliezer Talon <elitalon@gmail.com>
       * @since 2013-09-20
       */
      if ($is_printing_attendance_file) {
        $currentTimestamp = time();
        $activityTimestamp = strtotime($event['Event']['initial_hour']);
        if ($currentTimestamp < $activityTimestamp) {
          $students = $this->updateStudents();
        }
      } else if (empty($event['AttendanceRegister']['duration']) || !floatval($event['AttendanceRegister']['duration'])) {
        $students = $this->updateStudents();
      }
		}

    if ($is_printing_attendance_file) {
      if (!isset($students)) {
        $students = $this->query("
          SELECT Student.*
          FROM users Student
          INNER JOIN users_attendance_register UAR ON UAR.user_id = Student.id
          WHERE UAR.attendance_register_id = {$event['AttendanceRegister']['id']}
          ORDER BY Student.last_name, Student.first_name
        ");
      }

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
        if ($attendanceCreatedTimestamp < strtotime('2012-08-01 00:00:00')) {
          $students = $this->Student->query("
            SELECT Student.*
            FROM users Student
            INNER JOIN registrations Registration ON Student.id = Registration.student_id
            WHERE Registration.activity_id = {$event['Event']['activity_id']}
            AND Registration.group_id = {$event['Event']['group_id']}
            ORDER BY Student.last_name, Student.first_name
          ");
        }
      }

      $event['AttendanceRegister']['Student'] = $students;
    } else {
      if ($is_new) {
        $students = array();
      } else {
        $students = $this->query("
          SELECT Student.*, UserAttendanceRegister.user_id, UserAttendanceRegister.attendance_register_id, UserAttendanceRegister.user_gone
          FROM users Student
          INNER JOIN users_attendance_register UserAttendanceRegister ON UserAttendanceRegister.user_id = Student.id
          WHERE UserAttendanceRegister.attendance_register_id = {$event['AttendanceRegister']['id']}
          ORDER BY Student.last_name, Student.first_name
        ");
      }
      $event['AttendanceRegister']['Student'] = $students;
    }
    
    return $event;
  }
	
	function updateStudents(){
		$activity_id = $this->data['AttendanceRegister']['activity_id'];
		$group_id = $this->data['AttendanceRegister']['group_id'];
		
		$students = $this->query("
			SELECT Student.*, UsersAttendanceRegister.user_gone
			FROM users Student
			INNER JOIN users_attendance_register UsersAttendanceRegister ON UsersAttendanceRegister.user_id = Student.id
			WHERE UsersAttendanceRegister.attendance_register_id = {$this->id}
			ORDER BY Student.last_name, Student.first_name
		");

		$studentsRegistered = $this->Student->query("
			SELECT Student.*
			FROM users Student
			INNER JOIN registrations Registration ON Student.id = Registration.student_id
			WHERE Registration.activity_id = {$activity_id}
			AND Registration.group_id = {$group_id}
			ORDER BY Student.last_name, Student.first_name
		");
    
		// Delete from AR those who aren't registered now and user_gone == false
		$studentsPreloadedToKeep = array();
		foreach ($students as $student) {
      if (!empty($student['UsersAttendanceRegister']['user_gone'])) {
        $studentsPreloadedToKeep[] = $student;
      } else {
        $results = Set::extract(sprintf('/Student[id=%d]', $student['Student']['id']), $studentsRegistered);
        $isStudentRegistered = count($results);
        if ($isStudentRegistered) {
          $studentsPreloadedToKeep[] = $student;
        }
      }
		}
		$students = $studentsPreloadedToKeep;

		// Add to preloaded attendance registers the newly registered students
		foreach ($studentsRegistered as $studentRegistered) {
			$results = Set::extract(sprintf('/Student[id=%d]', $studentRegistered['Student']['id']), $students);
			$isStudentPreloaded = count($results);

			if ($isStudentPreloaded) {
				continue;
			}
			if (!is_array($students)) {
				$students = array();
			}
			array_push($students, $studentRegistered);
		}

		// Update database
		$this->data['Student'] = array('Student' => array_unique(Set::classicExtract($students, '{n}.Student.id')));
		$this->saveAll();
		return $students;
	}
	
	function initialHourNotEmpty(){
		return ($this->data['AttendanceRegister']['initial_hour'] != null);
	}
	
	function finalHourNotEmpty(){
		return ($this->data['AttendanceRegister']['final_hour'] != null);
	}

	function _get_register_duration($initial_hour, $final_hour) {
		// Hour, minute, second, month, day, year
		$initial_timestamp = $this->_get_timestamp($initial_hour);
		$final_timestamp = $this->_get_timestamp($final_hour);
		return ($final_timestamp - $initial_timestamp) / 3600.0;
	}

	function _get_timestamp($date){
		$date_components = split("-", $date->format('Y-m-d-H-i-s'));
		return mktime($date_components[3],$date_components[4],$date_components[5], $date_components[1], $date_components[2], $date_components[0]);
	}
}
