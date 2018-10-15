<?php

App::import('model', 'academicModel');

class Group extends AcademicModel {
    var $name = "Group";

    var $belongsTo = array('Subject');

    var $validate = array(
        'name' => array(
            'rule' => 'notEmpty',
            'required' => true,
            'message' => 'Debe especificar un nombre para el grupo'
        ),
        'type' => array(
            'rule' => 'notEmpty',
            'required' => true,
            'message' => 'Debe especificar un tipo para el grupo' 
        ),
        'capacity' => array(
            'notEmpty' => array(
                'rule' => 'notEmpty',
                'message' => 'Debe especificar una capacidad para el grupo'
            ),
            'isNumeric' => array(
                'rule' => 'numeric',
                'message' => 'La capacidad debe ser un número (p.ej 100)'
            ),
            'greter_than_0' => array(
                'rule' => array('comparison', ">", 0),
                'message' => 'La capacidad debe ser mayor que 0'
            )
        )
    );
    
    function _exists($id){
        $group = $this->findById($id);
        
        return ($group != null);
    }
}
