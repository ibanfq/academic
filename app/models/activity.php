<?php
require_once('models/academic_model.php');

class Activity extends AcademicModel {
    const DAYS_TO_BLOCK_CHANGING_GROUP = 7;
    
	var $name = "Activity";

	var $belongsTo = array('Subject');

	var $hasMany = array('Registration');

	var $validate = array(
		'name' => array(
			'rule' => 'notEmpty',
			'required' => true,
			'message' => 'Debe especificar un nombre para la actividad'
			),
		'type' => array(
			'rule' => 'notEmpty',
			'required' => true,
			'message' => 'Debe especificar un tipo para la actividad'
			),
		'duration' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'message' => 'Debe especificar una duración para la actividad'
				),
			'isNumeric' => array(
				'rule' => 'numeric',
				'message' => 'La duración debe ser un número (p.ej 10)'
				),
			'greter_than_0' => array(
				'rule' => array('comparison', ">", 0),
				'message' => 'La duración debe ser mayor que 0'
				)
			)
		);

	function _exists($id){
		$activity = $this->findById($id);

		return ($activity != null);
	}
    
	function _existsAndGroupOpened($activity_id, $group_id) {
		$activity_id = intval($activity_id);
		$group_id = intval($group_id);
		$activity = $this->query("SELECT Activity.id, Activity.inflexible_groups, DATEDIFF(MIN(Event.initial_hour), CURDATE()) as days_to_start FROM events Event INNER JOIN activities Activity ON Activity.id = Event.activity_id WHERE Event.activity_id = $activity_id AND Event.group_id = $group_id");
		if ($activity && $activity[0]['Activity']['id']) {
			return (!$activity[0]['Activity']['inflexible_groups'] || $activity[0][0]['days_to_start'] > self::DAYS_TO_BLOCK_CHANGING_GROUP);
		} else {
			return false;
		}
	}
	
	function afterSave($created) {
		if ($created && $this->typeIsPractice($this->data['Activity']['type'])) {
			$users = $this->query("Select SubjectStudent.user_id FROM subjects_users SubjectStudent WHERE SubjectStudent.subject_id = {$this->data['Activity']['subject_id']} AND practices_approved");
			$new_registrations = array();
			foreach ($users as $user) {
				$user_id = $user['SubjectStudent']['user_id'];
				$new_registrations[]= array('Registration' => array(
					'activity_id' => $this->id,
					'student_id' => $user_id,
					'group_id' => -1
				));
			}
			if (!empty($new_registrations)) {
				$this->Registration->unbindModel(array('hasOne' => array('User', 'Activity', 'Group')));
				$this->Registration->saveAll($new_registrations);
			}
		}
	}
	
	function typeIsPractice($type) {
		$prefix = 'Práctica';
		return substr($type, 0, strlen($prefix)) === $prefix;
	}
}
