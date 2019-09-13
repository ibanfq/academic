<?php

App::import('model', 'academicModel');

class Degree extends AcademicModel {
    var $name = 'Degree';

    var $belongsTo = array(
        'Institution' => array(
            'className' => 'Institution'
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
