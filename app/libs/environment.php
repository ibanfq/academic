<?php

/**
 * Environment class
 */
class Environment extends Object {
    var $userModel = 'User';
    var $institutionModel = 'Institution';
    var $userInstitutionModel = 'UserInstitution';

    var $_initialized = false;
    var $_base_url = '';

    var $_user;
    var $_institution;
    var $_user_institution;
    var $_user_institutions;

    /**
     * Gets a reference to the Inflector object instance
     *
     * @return object
     * @access public
     */
    static function &getInstance()
    {
        static $instance = array();

        if (!$instance) {
            $instance[0] =& new Environment();
        }

        return $instance[0];
    }

    static function setInitialized($value)
    {
        $_this =& Environment::getInstance();
        $_this->_initialized = $value;
    }

    static function getInitialized()
    {
        $_this =& Environment::getInstance();
        return $_this->_initialized;
    }

    static function setBaseUrl($base_url)
    {
        $_this =& Environment::getInstance();
        $_this->_base_url = $base_url;
    }

    static function getBaseUrl()
    {
        $_this =& Environment::getInstance();
        return $_this->_base_url;
    }

    static function &getModel($name)
    {
        $model = ClassRegistry::init($name);

        if (empty($model)) {
            trigger_error(__('Environment::getModel() - Model is not set or could not be found', true), E_USER_WARNING);
            return null;
        }

        return $model;
    }

    static function setUser($user)
    {
        $_this =& Environment::getInstance();

        if (is_array($user)) {
            $model =& Environment::getModel($_this->userModel);
            if (isset($user[$model->alias])) {
                $_this->_user = array($model->alias => $user[$model->alias]);
            } else {
                $_this->_user = array($model->alias => $user);
            }
        } else {
            $_this->_user = $user;
        }
    }

    static function setInstitution($institution)
    {
        $_this =& Environment::getInstance();

        if (is_array($institution)) {
            $model =& Environment::getModel($_this->institutionModel);
            if (isset($institution[$model->alias])) {
                $_this->_institution = array($model->alias => $institution[$model->alias]);
            } else {
                $_this->_institution = array($model->alias => $institution);
            }
        } else {
            $_this->_institution = $institution;
        }
    }

    static function user($key = null)
    {
        $_this =& Environment::getInstance();

        if (! is_array($_this->_user)) {
            if (! $_this->_user) {
                return null;
            }
            
            $model =& Environment::getModel($_this->userModel);
            $_this->_user = $model->findById(
                $_this->_user,
                array(), // Fields
                array(), // Order
                -1 // Recursive
            );
            if (! $_this->_user) {
                return null;
            }
        }

        if ($key === null) {
            return $_this->_user;
        } else {
            $model =& Environment::getModel($_this->userModel);
            if (isset($_this->_user[$model->alias][$key])) {
                return $_this->_user[$model->alias][$key];
            }
        }

        return null;
    }
    
    static function institution($key = null)
    {
        $_this =& Environment::getInstance();

        if (! is_array($_this->_institution)) {
            if (! $_this->_institution) {
                return null;
            }
            
            $model =& Environment::getModel($_this->institutionModel);
            $_this->_institution = $model->findById(
                $_this->_institution,
                array(), // Fields
                array(), // Order
                -1 // Recursive
            );
            if (! $_this->_institution) {
                return null;
            }
        }

        if ($key === null) {
            return $_this->_institution;
        } else {
            $model =& Environment::getModel($_this->institutionModel);
            if (isset($_this->_institution[$model->alias][$key])) {
                return $_this->_institution[$model->alias][$key];
            }
        }

        return null;
    }

    static function userInstitution($key =  null)
    {
        $_this =& Environment::getInstance();

        if (! is_array($_this->_user_institution)) {
            if (! Environment::user('id') || ! Environment::institution('id')) {
                return null;
            }

            $model =& Environment::getModel($_this->userInstitutionModel);
            $_this->_user_institution = $model->find('first', array(
                'conditions' => array(
                  "{$model->alias}.user_id" => Environment::user('id'),
                  "{$model->alias}.institution_id" => Environment::institution('id')
                )
            ));
            if (! $_this->_user_institution) {
                return null;
            }
        }

        if ($key === null) {
            return $_this->_user_institution;
        } else {
            $model =& Environment::getModel($_this->userInstitutionModel);
            if (isset($_this->_user_institution[$model->alias][$key])) {
                return $_this->_user_institution[$model->alias][$key];
            }
        }

        return null;
    }

    static function userInstitutions($key =  null)
    {
        $_this =& Environment::getInstance();

        if (! is_array($_this->_user_institutions)) {
            if (! Environment::user('id')) {
                return null;
            }

            $model =& Environment::getModel($_this->userInstitutionModel);
            $_this->_user_institutions = $model->find('all', array(
                'conditions' => array(
                  "{$model->alias}.user_id" => Environment::user('id')
                )
            ));
            if (! $_this->_user_institutions) {
                return null;
            }
        }

        if ($key !== null) {
            $model =& Environment::getModel($_this->userInstitutionModel);
            return Set::extract("/{$model->alias}/{$key}", $_this->_user_institutions);
        }

        return $_this->_user_institutions;
    }
}
