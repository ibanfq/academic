<?php

App::import('model', 'academicModel');

class CompetenceCriterionSubject extends AcademicModel {
    var $name = "CompetenceCriterionSubject";

    var $belongsTo = array(
        'CompetenceCriterion' => array(
            'className' => 'CompetenceCriterion',
            'foreignKey' => 'criterion_id'
        ),
        'Subject' => array(
            'className' => 'Subject',
            'foreignKey' => 'subject_id'
        )
    );

    var $validate = array(
    );
}
