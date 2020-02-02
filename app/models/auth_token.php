<?php

App::import('model', 'academicModel');

class AuthToken extends AcademicModel {
    var $name = 'AuthToken';

    var $validate = array(
        'token' => array(
            'unique' => array(
                'rule' => array('tokenMustBeUnique'),
                'message' => 'Ya existe un token igual para otro usuario'
            )
        ),
    );

    /**
     * Validates that a combination of token,user is unique
     */
    function tokenMustBeUnique($token) {
        $token = is_array($token)
            ? isset($token['token']) ? $token['token'] : $token[0]
            : $token;

        $data = $this->data[$this->alias];
        
        if (! isset($data['user_id'])) {
            return false;
        }

        $conditions = array(
            "{$this->alias}.token" => $token,
            "{$this->alias}.user_id !=" => $data['user_id'],
        );

        return 0 == $this->find('count', array('recursive' => -1, 'conditions' => $conditions));
    }
}
