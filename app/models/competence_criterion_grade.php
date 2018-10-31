<?php

App::import('model', 'academicModel');

class CompetenceCriterionGrade extends AcademicModel {
    var $name = "CompetenceCriterionGrade";

    var $belongsTo = array(
        'CompetenceCriterion' => array(
            'className' => 'CompetenceCriterion',
            'foreignKey' => 'criterion_id'
        ),
        'Student' => array(
            'className' => 'User',
            'foreignKey' => 'student_id'
        ),
        'CompetenceCriterionRubric' => array(
            'className' => 'CompetenceCriterion',
            'foreignKey' => 'criterion_id'
        )
    );

    var $validate = array(
    );
}
