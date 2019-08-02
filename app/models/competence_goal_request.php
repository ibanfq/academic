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
            ),
            'notGraded' => array(
                'rule' => array('goalMustNotBeenGraded'),
                'message' => 'Ya tienes evaluado ese objetivo'
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

        if (! isset($competence_goal_request['teacher_id'])) {
            return false;
        }

        $student_id = $competence_goal_request['student_id'];
        $teacher_id = $competence_goal_request['teacher_id'];

        $query = "SELECT '' FROM competence_goal_requests CompetenceGoalRequest"
            . " WHERE CompetenceGoalRequest.goal_id = {$db->value($goal_id)}"
            . " AND CompetenceGoalRequest.student_id = {$db->value($student_id)}"
            . " AND CompetenceGoalRequest.teacher_id = {$db->value($teacher_id)}"
            . " AND completed IS NULL AND rejected IS NULL AND canceled IS NULL";

        if (!empty($this->id)) {
            $query .= " AND CompetenceGoalRequest.id != {$db->value($this->id)}";
        }

        return ! $this->query($query . ' LIMIT 1');
    }

    /**
     * Validates that the goal is not graded yet (check only with new entry)
     *
     * @param string $goal_id Competence goal id
     *
     * @return boolean
     */
    function goalMustNotBeenGraded($goal_id)
    {
        if (!empty($this->id)) {
            return true; // Ignore if is not a new request
        }

        $db = $this->getDataSource();

        $goal_id = is_array($goal_id)
            ? isset($goal_id['goal_id']) ? $goal_id['goal_id'] : $goal_id[0]
            : $goal_id;

        $competence_goal_request = $this->data[$this->alias];

        if (! isset($competence_goal_request['student_id'])) {
            return false;
        }

        if (! isset($competence_goal_request['teacher_id'])) {
            return false;
        }

        $student_id = $competence_goal_request['student_id'];

        $query = "SELECT '' FROM competence_goals CompetenceGoal"
            . " INNER JOIN competence_criteria CompetenceCriterion ON CompetenceCriterion.goal_id = CompetenceGoal.id"
            . " LEFT JOIN competence_criterion_grades CompetenceCriterionGrade ON CompetenceCriterionGrade.criterion_id = CompetenceCriterion.id AND CompetenceCriterionGrade.student_id = {$db->value($student_id)}"
            . " WHERE CompetenceGoal.id = {$db->value($goal_id)} AND CompetenceCriterionGrade.rubric_id IS NULL";

        return (bool) $this->query($query . ' LIMIT 1');
    }
}
