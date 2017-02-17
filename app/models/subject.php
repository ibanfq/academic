<?php

App::import('model', 'academicModel');

class Subject extends AcademicModel {
	var $name = "Subject";

	var $hasMany = array(
		'Group' => array(
			'order' => array('Group.type ASC', 'Group.name ASC'),
			'dependent' => true,
		),
		'Activity' => array(
			'order' => array('Activity.type ASC', 'Activity.name ASC'),
			'dependent' => true,
		)
	);

	var $belongsTo = array(
		'Course' => array(
			'className' => 'Course'
		),
		'Coordinator' => array(
			'className' => 'User',
			'conditions' => array("(Coordinator.type = 'Profesor' OR Coordinator.type = 'Administrador')")
		),
		'Responsible' => array(
			'className' => 'User',
			'foreignKey' => 'practice_responsible_id',
			'conditions' => array("(Responsible.type = 'Profesor' OR Responsible.type = 'Administrador')")
		)
	);

	var $validate = array(
		'code' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Debe introducir el código de la asignatura (p.ej. 12587)'
			),
			'unique' => array(
				'rule' => array('codeMustBeUnique'),
				'on' => 'create',
				'message' => 'Ya existe una asignatura con este código en el curso'
			)
		),
		'level' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Debe introducir el nivel de la asignatura'
			)
		),
		'type' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Debe introducir el tipo de la asignatura'
			)
		),
		'name' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Debe introducir el nombre de la asignatura (p.ej. Matemática)'
			)
		),
		'credits_number' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Debe introducir el número de créditos de la asignatura (p.ej. 5.5)'
			)
		),
		'acronym' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Debe introducir un acrónimo para esta asignatura'
			),
			'less_than_5_characters' => array(
				'rule' => array('between', 0, 5),
				'message' => 'El acrónimo debe tener menos de 5 caracteres'
			)
		),
		'coordinator_id' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'El coordinador de la asignatura no puede estar vacío'
			)
		),
		'practice_responsible_id' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'El responsable de prácticas de la asignatura no puede estar vacío'
			)
		)
	);
	
	var $levels = array('Primero', 'Segundo', 'Tercero', 'Cuarto', 'Quinto', 'Postgrado');

	/**
	 * Validates that a combination of code,course is unique
	 */
	function codeMustBeUnique($code){
		$subject = $this->data[$this->alias];
		return $this->find('count', array('conditions' => array(
			'Subject.code' => $code,
			'Subject.course_id' => $subject['course_id'],
		))) == 0;
	}

	/**
	 * Returns an array of activities with teaching hours for
	 * a subject
	 */
	function activityHoursSummary($id = null) {
		$subject = $this->findById($id, array('Subject.id', 'Course.initial_date', 'Course.final_date'));
		$groups = Set::extract('/Group/id', $subject);
		$activities = Set::extract('/Activity/id', $subject);
    
    if (!empty($groups) && !empty($activities)) {
      $this->Group->bindModel(array('hasMany' => array('AttendanceRegister')));
      $registers = $this->Group->AttendanceRegister->query(sprintf(
        "SELECT `Activity`.`id`, `Activity`.`name`, SUM(`AttendanceRegister`.`duration`) / `Group`.`total` as `activity_total`, `Teacher`.`first_name`, `Teacher`.`last_name`, `AttendanceRegister`.*
        FROM `attendance_registers` `AttendanceRegister`
        INNER JOIN `activities` `Activity` ON `Activity`.`id` = `AttendanceRegister`.`activity_id`
        LEFT JOIN (
          SELECT `Event`.`activity_id` AS `activity_id`, COUNT(DISTINCT `TemporaryGroup`.`id`) AS `total`
          FROM `events` `Event`
          LEFT JOIN `groups` `TemporaryGroup` ON `TemporaryGroup`.`id` = `Event`.`group_id`
          WHERE `TemporaryGroup`.`name` NOT LIKE '%%no me presento%%'
          GROUP BY `Event`.`activity_id`
        ) `Group` ON `Group`.`activity_id` = `Activity`.`id`
        INNER JOIN `users` `Teacher` ON `Teacher`.`id` = `AttendanceRegister`.`teacher_id`
        WHERE `AttendanceRegister`.`group_id` IN (%s)
        AND `AttendanceRegister`.`activity_id` IN (%s)
        AND `AttendanceRegister`.`initial_hour` >= '%s'
        AND `AttendanceRegister`.`initial_hour` <= '%s'
        GROUP BY `Activity`.`id`
        ORDER BY `Activity`.`id` ASC",
          implode(',', $groups), implode(',', $activities),
          date('Y-m-d 00:00:00', strtotime($subject['Course']['initial_date'])),
          date('Y-m-d 23:59:59', strtotime($subject['Course']['final_date']))
      ));
    } else {
      $registers = array();
    }
		return $registers;
	}

	/**
	 * Returns an array of attendance registers with teaching hours for
	 * a subject
	 */
	function teachingHoursSummary($id = null) {
		$subject = $this->findById($id, array('Subject.id', 'Course.initial_date', 'Course.final_date'));
		$groups = Set::extract('/Group/id', $subject);
		$activities = Set::extract('/Activity/id', $subject);

		$this->Group->bindModel(array('hasMany' => array('AttendanceRegister')));
		$events = $this->Group->AttendanceRegister->Event->find('all', array(
			'conditions' => array(
				'Event.group_id' => $groups,
				'Event.activity_id' => $activities,
				'Event.initial_hour >= ' => date('Y-m-d 00:00:00', strtotime($subject['Course']['initial_date'])),
				'Event.final_hour <= ' => date('Y-m-d 23:59:59', strtotime($subject['Course']['final_date'])),
			),
			'fields' => array(
				'Event.id', 'Event.initial_hour', 'Event.duration',
				'AttendanceRegister.id', 'AttendanceRegister.initial_hour', 'AttendanceRegister.duration',
				'Activity.id', 'Activity.name',
				'Group.id', 'Group.name',
				'Teacher.first_name', 'Teacher.last_name',
				'Teacher_2.first_name', 'Teacher_2.last_name',
			),
			'order' => array('Event.initial_hour'),
			'recursive' => 0,
		));

		$registers = array();
		foreach ($events as $event) {
			$this->Group->bindModel(array('hasMany' => array('AttendanceRegister')));
			$teachers = $this->Group->AttendanceRegister->find('first', array(
				'conditions' => array('AttendanceRegister.id' => $event['AttendanceRegister']['id']),
				'fields' => array(
					'AttendanceRegister.id',
					'Teacher.first_name', 'Teacher.last_name', 'Teacher_2.first_name', 'Teacher_2.last_name',
				),
				'recursive' => 0,
			));

			$event['OriginalTeacher'] = $event['Teacher'];
			$event['OriginalTeacher_2'] = $event['Teacher_2'];
			$event['Teacher'] = $teachers['Teacher'];
			$event['Teacher_2'] = $teachers['Teacher_2'];
			$registers[] = $event;
		}
		return $registers;
	}
	
	function levelToInt($level) {
		return array_search($level, $this->levels);
	}
}
?>
