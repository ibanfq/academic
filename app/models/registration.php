<?php
require_once('models/academic_model.php');

class Registration extends AcademicModel {
	var $name = "Registration";

	var $hasOne = array('User', 'Activity', 'Group');

	var $validate = array(
		"registerJustOnce" => array(
				'rule' => array("registerJustOnce")
			)
		);

	function enoughFreeSeats($activity_id, $group_id){
		if ($group_id != -1) {
			$busy_seats = $this->query("SELECT count(*) AS busy_seats FROM registrations WHERE activity_id = {$activity_id} AND group_id = {$group_id}");
			$busy_seats = $busy_seats[0][0]['busy_seats'];

			$group = $this->Group->findById($group_id);

			return $group['Group']['capacity'] > $busy_seats;
		} else
			return true;
	}

	function registerJustOnce() {
		$registrations = $this->query("SELECT count(*) AS registrations FROM registrations WHERE activity_id = {$this->data['Registration']['activity_id']} AND student_id = {$this->data['Registration']['student_id']}");

		return $registrations[0][0]['registrations'] < 2;
	}
    
    function studentGroupRegistered($student_id, $activity_id) {
		$student_id = intval($student_id);
		$activity_id = intval($activity_id);
        $registrations = $this->query("SELECT group_id FROM registrations WHERE activity_id = $activity_id AND student_id = $student_id");
		if ($registrations && $registrations[0]['registrations']['group_id']) {
			return $registrations[0]['registrations']['group_id'];
		}
		return false;
    }
}
?>
