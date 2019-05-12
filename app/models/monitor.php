<?php

App::import('model', 'academicModel');

class Monitor extends AcademicModel {
    var $name = 'Monitor';

    var $validate = array(
        'name' => array(
            'notEmpty' => array(
                'rule' => 'notEmpty',
                'required' => true,
                'message' => 'Debe especificar un nombre para el aula'
            )
        )
    );

    var $hasMany = array(
        'MonitorMedia' => array('dependent' => true),
    );
    var $hasAndBelongsToMany = array(
        'Classroom' => array('joinTable' => 'monitors_classrooms'),
    );
}
