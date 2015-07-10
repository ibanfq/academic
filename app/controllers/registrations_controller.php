<?php
class RegistrationsController extends AppController {
	var $name = 'Registrations';
	var $helpers = array('Ajax');
	
	function add($activity_id, $group_id){
		
		$this->set('success', false);
		
		$this->Registration->id = null;
		
        if ($this->Registration->studentRegistered($this->Auth->user('id'), $activity_id)) {
            $activity_exists = $this->Registration->Activity->_existsAndGroupsOpened($activity_id);
        } else {
            $activity_exists = $this->Registration->Activity->_exists($activity_id);
        }
		$group_exists = $this->Registration->Group->_exists($group_id);
		
		if ($activity_exists && $group_exists) {
			$this->Registration->create();
			
			$registration = array('Registration' => array('group_id' => $group_id, 'activity_id' => $activity_id, 'student_id' => $this->Auth->user('id'), 'id' => null));

 			if (($this->Registration->enoughFreeSeats($activity_id, $group_id)) && ($this->Registration->save($registration))) {
				$this->Registration->query("DELETE FROM registrations WHERE activity_id = {$activity_id} AND student_id = {$this->Auth->user('id')} AND id <> {$this->Registration->id}");
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
	
	function pass_activity($activity_id = null){
		$this->set('success', false);
		
        if ($this->Registration->studentRegistered($this->Auth->user('id'), $activity_id)) {
            $activity_exists = $this->Registration->Activity->_existsAndGroupsOpened($activity_id);
        } else {
            $activity_exists = $this->Registration->Activity->_exists($activity_id);
        }
        
		if ($activity_exists) {
			$this->Registration->create();
			$registration = array('Registration' => array('group_id' => -1, 'activity_id' => $activity_id, 'student_id' => $this->Auth->user('id')));
			
			if ($this->Registration->save($registration)){
				$this->Registration->query("DELETE FROM registrations WHERE activity_id = {$activity_id} AND student_id = {$this->Auth->user('id')} AND id <> {$this->Registration->id}");
				
				$this->set('success', true);
			}
		}
	}
	
	function fail_activity($activity_id = null) {
		$this->set('success', false);
		
		if ($this->Registration->Activity->_exists($activity_id)){
			$this->Registration->query("DELETE FROM registrations WHERE activity_id = {$activity_id} AND student_id = {$this->Auth->user('id')}");
			$this->set('success', true);
		}
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
