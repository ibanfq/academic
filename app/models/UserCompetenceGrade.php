<?php

App::import('model', 'academicModel');

class UserCompetenceGrade extends AcademicModel {
    var $name = "UserCompetenceGrade";

    var $belongsTo = array(
        'CompetenceCriterion' => array(
            'className' => 'CompetenceCriterion',
            'foreignKey' => 'criterion_id'
        ),
        'CompetenceCriterionRubric' => array(
            'className' => 'CompetenceCriterion',
            'foreignKey' => 'criterion_id'
        ),
        'Student' => array(
            'className' => 'User',
            'foreignKey' => 'student_id'
        )
    );

    var $validate = array(
    );
}

