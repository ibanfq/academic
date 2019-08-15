<?php

App::import('model', 'academicModel');

class Institution extends AcademicModel {
    var $name = 'Institution';

    var $hasMany = array(
        'Courses' => array('dependent' => true),
        'Classrooms' => array('dependent' => true),
        'Monitors' => array('dependent' => true),
    );
}
