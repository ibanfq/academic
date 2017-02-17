<?php

App::import('model', 'academicModel');

class Classroom extends AcademicModel {
	var $name = 'Classroom';

	var $validate = array(
		'name' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Debe especificar un nombre para el aula'
			)
		),
		'type' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Debe especificar el tipo del aula'
			)
		),
		'capacity' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Debe especificar la capacidad del aula'
			),
			'numeric' => array(
				'rule' => 'numeric',
				'message' => 'La capacidad debe ser un número entero (p.ej. 100)'
			),
			'greater_than_0' => array(
				'rule' => array('comparison', '>', 0),
				'message' => 'La capacidad debe ser mayor que 0'
			)
		)
	);

	var $hasMany = array(
		'Event',
		'Booking',
	);
}
?>
