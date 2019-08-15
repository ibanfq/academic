<?php

/**
 * Environment class
 */
class Environment extends Object {
    var $institutionModel = 'Institution';

    var $_institution;

    /**
     * Gets a reference to the Inflector object instance
     *
     * @return object
     * @access public
     */
    function &getInstance()
    {
        static $instance = array();

        if (!$instance) {
            $instance[0] =& new Environment();
        }
        return $instance[0];
    }

    function &getModel($name)
    {
        $model = null;

        if (PHP5) {
            $model = ClassRegistry::init($name);
        } else {
            $model =& ClassRegistry::init($name);
        }

        if (empty($model)) {
            trigger_error(__('Environment::getModel() - Model is not set or could not be found', true), E_USER_WARNING);
            return null;
        }

        return $model;
    }
    
    function institution($key = null)
    {
        $_this =& Environment::getInstance();

        if (! $_this->_institution) {
            $institution = Configure::read('Environment.institution');

            if (! $institution) {
                return null;
            }
            
            $model =& Environment::getModel($_this->institutionModel);
            $data = $model->findByAcronym(
                $institution,
                array(), // Fields
                array(), // Order
                -1 // Recursive
            );
            if ($data) {
                $_this->_institution = $data[$model->alias];
            }
        }

        if ($key === null) {
            $model =& Environment::getModel($_this->institutionModel);
            return array($model->alias => $_this->_institution);
        } else {
            if (isset($_this->_institution[$key])) {
                return $_this->_institution[$key];
            }
            return null;
        }
    }
}
