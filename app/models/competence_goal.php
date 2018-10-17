<?php

App::import('model', 'academicModel');

class CompetenceGoal extends AcademicModel {
    var $name = "CompetenceGoal";

    var $hasMany = array(
        'CompetenceCriterion' => array(
            'foreignKey' => 'goal_id',
            'order' => array('CompetenceCriterion.code ASC'),
            'dependent' => true,
        ),
        'UserCompetenceGradeRequest' => array(
            'foreignKey' => 'goal_id',
            'order' => array('UserCompetenceGradeRequest.goal_id ASC'),
            'dependent' => true,
        )
    );

    var $belongsTo = array(
        'Competence' => array(
            'className' => 'Competence',
            'foreignKey' => 'competence_id'
        )
    );

    var $validate = array(
        'code' => array(
            'notEmpty' => array(
                'rule' => 'notEmpty',
                'required' => true,
                'message' => 'Debe introducir el código del objetivo (p.ej. 2.3.1)'
            ),
            'unique' => array(
                'rule' => array('codeMustBeUnique'),
                'message' => 'Ya existe un objetivo con este código en el curso'
            )
        ),
        'definition' => array(
            'notEmpty' => array(
                'rule' => 'notEmpty',
                'required' => true,
                'message' => 'Debe introducir la definición del objetivo (p.ej. The student should be able to handle and restrain a dog.)'
            )
        )
    );
    
    /**
     * Validates that a combination of code,competence is unique
     *
     * @param string $code Competence code
     *
     * @return boolean
     */
    function codeMustBeUnique($code)
    {
        $db = $this->getDataSource();

        $code = is_array($code)
            ? isset($code['code']) ? $code['code'] : $code[0]
            : $code;

        $competence_goal = $this->data[$this->alias];

        if (! isset($competence_goal['competence_id'])) {
            return false;
        }

        $query = "SELECT '' FROM competence a INNER JOIN competence b ON a.course_id = b.course_id"
            . " INNER JOIN competence_goals goal ON goal.competence_id = b.id"
            . " WHERE a.id = {$db->value($competence_goal['competence_id'])}"
            . " AND goal.code = {$db->value($code)}";

        if (!empty($this->id)) {
            $query .= " AND goal.id != {$db->value($this->id)}";
        }

        return ! $this->query($query . ' LIMIT 1');
    }
}

