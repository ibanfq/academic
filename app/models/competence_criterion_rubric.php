<?php

App::import('model', 'academicModel');

class CompetenceCriterionRubric extends AcademicModel {
    var $name = "CompetenceCriterionRubric";

    var $hasMany = array(
        'CompetenceCriterionGrade' => array(
            'foreignKey' => 'rubric_id',
            'order' => array('CompetenceCriterionGrade.criterion_id ASC'),
            'dependent' => true,
        )
    );

    var $belongsTo = array(
        'CompetenceCriterion' => array(
            'className' => 'CompetenceCriterion',
            'foreignKey' => 'criterion_id'
        )
    );
}

