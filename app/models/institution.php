<?php

App::import('model', 'academicModel');

class Institution extends AcademicModel {
    var $name = 'Institution';

    var $hasMany = array(
        'Degree' => array(
            'className' => 'Degree',
            'order' => 'Degree.acronym ASC',
            'dependent' => true
        )
    );

    var $validate = array(
        'code' => array(
            'notEmpty' => array(
                'rule' => 'notEmpty',
                'required' => true,
                'message' => 'Debe especificar el código del centro' 
            ),
            'numeric' => array(
                'rule' => 'numeric',
                'message' => 'El código debe ser de tipo numérico.'
            ),
            'unique' => array(
                'rule' => array('codeMustBeUnique'),
                'message' => 'Ya existe un centro con este código'
            )
        ),
        'acronym' => array(
            'notEmpty' => array(
                'rule' => 'notEmpty',
                'required' => true,
                'message' => 'Debe especificar un acrónimo para el centro' 
            )
        ),
        'name' => array(
            'notEmpty' => array(
                'rule' => 'notEmpty',
                'required' => true,
                'message' => 'Debe especificar un nombre para el centro' 
            )
        ),
    );

    /**
     * Validates that a combination of code,course is unique
     */
    function codeMustBeUnique($code) {
        $code = is_array($code)
            ? isset($code['code']) ? $code['code'] : $code[0]
            : $code;

        $conditions = array(
            "{$this->alias}.code" => $code
        );

        if (!empty($this->id)) {
            $conditions[$this->alias . '.' . $this->primaryKey . ' !='] =  $this->id;
        }

        return 0 == $this->find('count', array('recursive' => -1, 'conditions' => $conditions));
    }
}
