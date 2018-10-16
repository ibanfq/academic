<?php

App::import('model', 'academicModel');

class CompetenceCriterionRubric extends AcademicModel {
    var $name = "CompetenceCriterionRubric";

    var $belongsTo = array(
        'CompetenceCriterion' => array(
            'className' => 'CompetenceCriterion',
            'foreignKey' => 'criterion_id'
        )
    );
}

