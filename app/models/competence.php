<?php

App::import('model', 'academicModel');

class Competence extends AcademicModel {
    var $name = "Competence";

    var $hasMany = array(
        'CompetenceGoal' => array(
            'foreignKey' => 'competence_id',
            'order' => array('CompetenceGoal.code ASC'),
            'dependent' => true,
        )
    );

    var $belongsTo = array(
        'Course' => array(
            'className' => 'Course',
            'foreignKey' => 'course_id'
        )
    );

    var $validate = array(
        'code' => array(
            'notEmpty' => array(
                'rule' => 'notEmpty',
                'required' => true,
                'message' => 'Debe introducir el código de la competencia (p.ej. 2.3)'
            ),
            'unique' => array(
                'rule' => array('codeMustBeUnique'),
                'message' => 'Ya existe una competencia con este código en el curso'
            )
        ),
        'definition' => array(
            'notEmpty' => array(
                'rule' => 'notEmpty',
                'required' => true,
                'message' => 'Debe introducir la definición de la competencia (p.ej. Demonstrate ability to cope with incomplete information, deal with contingencies, and adapt to change.)'
            )
        )
    );
    
    /**
     * Validates that a combination of code,course is unique
     *
     * @param string $code Competence code
     *
     * @return boolean
     */
    function codeMustBeUnique($code)
    {
        $code = is_array($code)
            ? isset($code['code']) ? $code['code'] : $code[0]
            : $code;

        $competence = $this->data[$this->alias];

        if (! isset($competence['course_id'])) {
            return false;
        }

        $conditions = array(
            'Competence.code' => $code,
            'Competence.course_id' => $competence['course_id']
        );

        if (!empty($this->id)) {
            $conditions[$this->alias . '.' . $this->primaryKey . ' !='] =  $this->id;
        }

        return 0 == $this->find('count', array('recursive' => -1, 'conditions' => $conditions));
    }
}
