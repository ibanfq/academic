<?php

App::import('model', 'academicModel');

class GroupRequest extends AcademicModel {
	var $name = "GroupRequest";
	var $belongsTo = array(
			'Activity', 
			'Student' => array(
				'className' => 'User',
				'foreignKey' => 'student_id'),
			'Group',
			'Student_2' => array(
				'className' => 'User',
				'foreignKey' => 'student_2_id'),
			'Group_2' => array(
				'className' => 'Group',
				'foreignKey' => 'group_2_id')
		);
	
	
	function getUserRequests($user_id, $subject_id = null, $activity_id = null, $group_id = null) {
		$inner = '';
		if (empty($group_id)) {
			$where = "(student_id = $user_id OR student_2_id = $user_id)";
		} else {
			$group_id = intval($group_id);
			$where = "(student_id = $user_id AND group_2_id = $group_id OR student_2_id = $user_id AND group_id = $group_id)";
		}
		if (!empty($subject_id)) {
			$subject_id = intval($subject_id);
			$inner = 'INNER JOIN activities ON activity_id = activities.id';
			$where .= " AND subject_id = $subject_id";
		}
		if (!empty($activity_id)) {
			$activity_id = intval($activity_id);
			$where .= " AND activity_id = $activity_id";
		}
		return $this->query("SELECT group_requests.* FROM group_requests $inner WHERE $where");
	}
}
