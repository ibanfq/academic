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
        'goal_id' => array(
            'notEmpty' => array(
                'rule' => 'notEmpty',
                'required' => true,
                'message' => 'Debe elegir un objetivo'
            ),
            'unique' => array(
                'rule' => array('requestMustBeUnique'),
                'message' => 'Ya has realizado una solicitud de evaluación para ese objetivo'
            )
        ),
        'teacher_id' => array(
            'notEmpty' => array(
                'rule' => 'notEmpty',
                'required' => true,
                'message' => 'Debe elegir un profesor'
            )
        ),
        'student_id' => array(
            'notEmpty' => array(
                'rule' => 'notEmpty',
                'required' => true,
                'message' => 'Debe elegir un alumno'
            )
        )
    );

    /**
     * Validates that a combination of goal,student is unique
     *
     * @param string $goal_id Competence goal id
     *
     * @return boolean
     */
    function requestMustBeUnique($goal_id)
    {
        $db = $this->getDataSource();

        $goal_id = is_array($goal_id)
            ? isset($goal_id['goal_id']) ? $goal_id['goal_id'] : $goal_id[0]
            : $goal_id;

        $competence_goal_request = $this->data[$this->alias];

        if (! isset($competence_goal_request['student_id'])) {
            return false;
        }

        $student_id = $competence_goal_request['student_id'];

        $query = "SELECT '' FROM competence_goal_requests CompetenceGoalRequest"
            . " WHERE CompetenceGoalRequest.goal_id = {$db->value($goal_id)}"
            . " AND CompetenceGoalRequest.student_id = {$db->value($student_id)}"
            . " AND rejected is null AND canceled is null";

        if (!empty($this->id)) {
            $query .= " AND CompetenceGoalRequest.id != {$db->value($this->id)}";
        }

        return ! $this->query($query . ' LIMIT 1');
    }
}
