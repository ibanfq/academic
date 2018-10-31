<?php

App::import('model', 'academicModel');

class CompetenceCriterionTeacher extends AcademicModel {
    var $name = "CompetenceCriterionTeacher";

    var $belongsTo = array(
        'CompetenceCriterion' => array(
            'className' => 'CompetenceCriterion',
            'foreignKey' => 'criterion_id'
        ),
        'Teacher' => array(
            'className' => 'User',
            'foreignKey' => 'teacher_id'
        )
    );

    var $validate = array(
    );
}
