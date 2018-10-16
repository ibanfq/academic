<?php

App::import('model', 'academicModel');

class CompetenceCriterion extends AcademicModel {
    var $name = "CompetenceCriterion";

    var $hasMany = array(
        'CompetenceCriterionRubric' => array(
            'foreignKey' => 'criterion_id',
            'order' => array('CompetenceCriterionRubric.value ASC'),
            'dependent' => true,
        ),
        'CompetenceCriterionSubject' => array(
            'foreignKey' => 'criterion_id',
            'order' => array('CompetenceCriterionSubject.subject_id ASC'),
            'dependent' => true,
        ),
        'CompetenceCriterionTeacher' => array(
            'foreignKey' => 'criterion_id',
            'order' => array('CompetenceCriterionTeacher.teacher_id ASC'),
            'dependent' => true,
        )
    );

    var $belongsTo = array(
        'CompetenceGoal' => array(
            'className' => 'CompetenceGoal',
            'foreignKey' => 'goal_id'
        )
    );

    var $validate = array(
        'code' => array(
            'notEmpty' => array(
                'rule' => 'notEmpty',
                'required' => true,
                'message' => 'Debe introducir el código del criterio (p.ej. 2.3.1.4)'
            ),
            'unique' => array(
                'rule' => array('codeMustBeUnique'),
                'message' => 'Ya existe un criterio con este código en el curso'
            )
        ),
        'definition' => array(
            'notEmpty' => array(
                'rule' => 'notEmpty',
                'required' => true,
                'message' => 'Debe introducir la definición del criterio (p.ej. The student show security and confidence.)'
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

        $competence_criterion = $this->data[$this->alias];

        if (! isset($competence_criterion['goal_id'])) {
            return false;
        }

        $query = "SELECT '' FROM competence_goals goal_a"
            . " INNER JOIN competence a ON a.id = goal_a.competence_id"
            . " INNER JOIN competence b ON a.course_id = b.course_id"
            . " INNER JOIN competence_goals goal_b ON goal_b.competence_id = b.id"
            . " INNER JOIN competence_criteria criterion ON criterion.goal_id = goal_b.id"
            . " WHERE goal_a.id = {$db->value($competence_criterion['goal_id'])}"
            . " AND criterion.code = {$db->value($code)}";

        if (!empty($this->id)) {
            $query .= " AND criterion.id != {$db->value($this->id)}";
        }

        return ! $this->query($query . ' LIMIT 1');
    }
}

