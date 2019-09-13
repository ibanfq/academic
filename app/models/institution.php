<?php

App::import('model', 'academicModel');

class Institution extends AcademicModel {
    var $name = 'Institution';

    var $hasMany = array(
        'Degree' => array(
            'className' => 'Degree',
            'order' => 'Degree.acronym ASC',
            'dependent' => true
        )
    );

    var $validate = array(
        'acronym' => array(
            'notEmpty' => array(
                'rule' => 'notEmpty',
                'required' => true,
                'message' => 'Debe especificar un acrónimo para el centro' 
            )
        ),
        'name' => array(
            'notEmpty' => array(
                'rule' => 'notEmpty',
                'required' => true,
                'message' => 'Debe especificar un nombre para el centro' 
            )
        ),
    );
}
