<?php

App::import('model', 'academicModel');

class CompetenceGoalRequest extends AcademicModel {
    var $name = "CompetenceGoalRequest";

    var $belongsTo = array(
        'CompetenceGoal' => array(
            'className' => 'CompetenceGoal',
            'foreignKey' => 'goal_id'
        ),
        'Student' => array(
            'className' => 'User',
            'foreignKey' => 'student_id'
        ),
        'Teacher' => array(
            'className' => 'User',
            'foreignKey' => 'teacher_id'
        )
    );

    var $validate = array(
    );
}
