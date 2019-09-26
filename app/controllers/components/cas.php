<?php

/**
 * CAS component
 */
class CasComponent extends Object {
    var $components = array('Auth');

    var $_initialized = false;
    
    function _initialize()
    {
        if ($this->_initialized) {
            return;
        }

        $this->_initialized = true;

        $debug = Configure::read('debug');

        if ($debug) {
            // Enable debugging
            phpCAS::setDebug(ROOT . DS . APP_DIR . '/tmp/logs/cas.log');
            // Enable verbose error messages. Disable in production!
            phpCAS::setVerbose($debug > 1);
        }

        // Initialize phpCAS
        phpCAS::client(
            constant(Configure::read('CAS.version')),
            Configure::read('CAS.hostname'),
            Configure::read('CAS.port'),
            Configure::read('CAS.context')
        );

        $cas_server_ca_cert_path = Configure::read('CAS.server_ca_cert_path');

        if ($cas_server_ca_cert_path) {
            // For production use set the CA certificate that is the issuer of the cert
            // on the CAS server and uncomment the line below
            phpCAS::setCasServerCACert($cas_server_ca_cert_path);
        } else {
            // For quick testing you can disable SSL validation of the CAS server.
            // THIS SETTING IS NOT RECOMMENDED FOR PRODUCTION.
            // VALIDATING THE CAS SERVER IS CRUCIAL TO THE SECURITY OF THE CAS PROTOCOL!
            phpCAS::setNoCasServerValidation();
        }

        // handle incoming logout requests
        // As an advanced featue handle SAML logout requests that emanate from the
        // CAS host exclusively.
        // Failure to restrict SAML logout requests to authorized hosts could
        // allow denial of service attacks where at the least the server is
        // tied up parsing bogus XML messages.
        phpCAS::handleLogoutRequests(
            Configure::read('CAS.logout_check_client') === false ? false : true,
            Configure::read('CAS.logout_allowed_clients') ?: array()
        );
    }

    function checkAuthentication()
    {
        if ($this->Auth->user('id') && !$this->Auth->user('__LOGGED_WITH_CAS__')) {
            // User logged already with other system
            return;
        }
        
        $this->_initialize();

        // force CAS authentication
        if (phpCAS::checkAuthentication()) {
            $logged = $this->_loginCasUser();

            if (! $logged && $this->Auth->user('id')) {
                $this->Auth->logout();
            }
        } elseif ($this->Auth->user('id')) {
            $this->Auth->logout();
        }
    }

    function forceAuthentication()
    {
        if ($this->Auth->user('id') && !$this->Auth->user('__LOGGED_WITH_CAS__')) {
            $this->Auth->logout();
        }

        $this->_initialize();

        // force CAS authentication
        phpCAS::forceAuthentication();
        
        $logged = $this->_loginCasUser();

        if (! $logged) {
            $this->Session->setFlash('No se ha podido acceder a la cuenta.');
        }
    }

    function logout()
    {
        $this->_initialize();

        $redirect = $this->Auth->logout();

        phpCAS::logoutWithRedirectService($redirect);
    }

    function _loginCasUser()
    {
        $attributes = phpCAS::getAttributes();

        if (empty($attributes['Correo'])) {
            return false;
        }

        $model = $this->Auth->getModel();

        $user = $model->find('first', array(
            'conditions' => array(
                "{$model->alias}.{$this->Auth->fields['username']}" => $attributes['Correo']
            ),
            'recursive' => 0
        ));

        if (! $user) {
            return false;
        }

        $user = $user[$model->alias];
        $hasChanges = false;

        if (!empty($attributes['Nombre']) && $attributes['Nombre'] !== $user['first_name']) {
            $user['first_name'] = $attributes['Nombre'];
            $hasChanges = true;
        }
        if (!empty($attributes['Apellidos']) && $attributes['Apellidos'] !== $user['last_name']) {
            $user['last_name'] = $attributes['Apellidos'];
            $hasChanges = true;
        }

        if ($hasChanges) {
            $model->save($user);
        }

        if ($user['id'] === $this->Auth->user('id')) {
            $user['__LOGGED_WITH_CAS__'] = true;
            $this->Auth->Session->write($this->Auth->sessionKey, $user);
            return true;
        } elseif ($this->Auth->login($user[$model->primaryKey])) {
            $user['__LOGGED_WITH_CAS__'] = true;
            $this->Auth->Session->write($this->Auth->sessionKey, $user);
            return true;
        }

        return false;
    }
}
