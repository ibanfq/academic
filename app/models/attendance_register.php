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
		if (empty($this->data['AttendanceRegister']['date'])) {
			$initial_hour = date_create("{$this->data['AttendanceRegister']['initial_hour']}");
			$final_hour = date_create("{$this->data['AttendanceRegister']['final_hour']}");
		} else {
			$internal_format = $this->dateFormatInternal($this->data['AttendanceRegister']['date']);
			$initial_hour = date_create("{$internal_format} {$this->data['AttendanceRegister']['initial_hour']}");
			$final_hour = date_create("{$internal_format} {$this->data['AttendanceRegister']['final_hour']}");
			
			$this->data['AttendanceRegister']['initial_hour'] = $initial_hour->format('Y-m-d H:i:s');
			
			$this->data['AttendanceRegister']['final_hour'] = $final_hour->format('Y-m-d H:i:s');
		}
		$this->data['AttendanceRegister']['duration'] = $this->_get_register_duration($initial_hour, $final_hour);

		return true;
	}
	
	function updateStudents(){
		$activity_id = $this->data['AttendanceRegister']['activity_id'];
		$group_id = $this->data['AttendanceRegister']['group_id'];
		
		$students = $this->query("
			SELECT Student.*
			FROM users Student
			INNER JOIN users_attendance_register UAR ON UAR.user_id = Student.id
			WHERE UAR.attendance_register_id = {$this->id}
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

		// Delete from AR those who aren't registered yet
		$studentsPreloadedToKeep = array();
		foreach ($students as $student) {
			$results = Set::extract(sprintf('/Student[id=%d]', $student['Student']['id']), $studentsRegistered);
			$isStudentRegistered = count($results);

			if ($isStudentRegistered) {
				$studentsPreloadedToKeep[] = $student;
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
