<?php
class RegistrationsController extends AppController {
	var $name = 'Registrations';
	var $helpers = array('Ajax');
	
	function add($activity_id, $group_id){
		
		$this->set('success', false);
		
		$this->Registration->id = null;
		
		$actual_group_id = $this->Registration->studentGroupRegistered($this->Auth->user('id'), $activity_id);
		
        if ($actual_group_id) {
			if ($this->Registration->Activity->_existsAndGroupOpened($activity_id, $actual_group_id)) {
            	$group_exists = $this->Registration->Activity->_existsAndGroupOpened($activity_id, $group_id);
			} else {
				$group_exists = false;
			}
        } else {
			$group_exists = $this->Registration->Activity->_existsAndGroupNotEnded($activity_id, $group_id);
        }
		
		if ($group_exists) {
			$this->Registration->create();
			
			$registration = array('Registration' => array('group_id' => $group_id, 'activity_id' => $activity_id, 'student_id' => $this->Auth->user('id'), 'id' => null));

 			if (($this->Registration->enoughFreeSeats($activity_id, $group_id)) && ($this->Registration->save($registration))) {
				$this->loadModel('AttendanceRegister');
				$attendanceRegisters = $this->AttendanceRegister->find("all", array(
					'fields' => array('AttendanceRegister.*'),
					'conditions' => "AttendanceRegister.activity_id = {$activity_id} and AttendanceRegister.initial_hour > now()",
					'recursive' => 0
				));
				foreach ($attendanceRegisters as $attendanceRegister) {
					$this->AttendanceRegister->data = $attendanceRegister;
					$this->AttendanceRegister->id = $attendanceRegister['AttendanceRegister']['id'];
					$this->AttendanceRegister->updateStudents();
				}

				$this->set('success', true);
		  	} else {
				$this->set('error', "notEnoughSeatsError");
			}
		} 
	}
	
	function get_subject_free_seats($subject_id){
		$free_seats = $this->Registration->query("SELECT `Group`.id, Activity.id, `Group`.capacity - IFNULL(count(Registration.id), 0) AS free_seats FROM groups `Group` INNER JOIN activities Activity ON Activity.subject_id = `Group`.subject_id AND Activity.type = `Group`.type LEFT JOIN registrations Registration ON `Group`.id = Registration.group_id AND Activity.id = Registration.activity_id WHERE `Group`.subject_id = {$subject_id} GROUP BY Activity.id, `Group`.id");
		
		$this->set('free_seats', $free_seats);
	}
	
	function view_students_registered($activity_id = null, $group_id = null) {
		$registrations = $this->Registration->query("SELECT `Registration`.`group_id`, `Registration`.`activity_id`, `Registration`.`student_id`, `Registration`.`id`, `User`.`id`, `User`.`type`, `User`.`dni`, `User`.`first_name`, `User`.`last_name`, `User`.`username`, `User`.`phone`, `User`.`password` FROM `registrations` AS `Registration` LEFT JOIN `users` AS `User` ON (`User`.`id` = `Registration`.`student_id`) WHERE `activity_id` = {$activity_id} AND `group_id` = {$group_id} ORDER BY `User`.`last_name`, `User`.`first_name`");
		
		$this->set('section', 'groups');
		$this->set('registrations', $registrations);
		$this->set('activity', $this->Registration->Activity->find("first", array('conditions' => array('Activity.id' => $activity_id))));
		$this->set('group', $this->Registration->Group->find("first", array('conditions' => array('`Group`.id' => $group_id))));
		
	}
	
	function _authorize(){
		parent::_authorize();
		
		if (($this->Auth->user('type') != "Estudiante") && ($this->Auth->user('type') == "Becario"))
			return false;
	
		return true;
	}
}
?>
