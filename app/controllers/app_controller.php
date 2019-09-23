<?php

App::import('Lib', 'Environment');
App::import('Lib', 'TextUtils');
App::import('Lib', 'ObjectProxy');

class AppController extends Controller {
	/**
	 * Application wide controllers
	 */
	var $components = array('Security', 'Session', 'Auth', 'Acl', 'RequestHandler', 'Email', 'Form', 'Api');
  
	/**
	 * Application wide helpers
	 *
	 * @since 2012-05-17
	 */
	var $helpers = array('Html', 'Form', 'Session', 'Javascript', 'DateHelper', 'ModelHelper');

  var $isApi = false;

  function __construct() {
    if ($this->isApi) {
      $this->autoRender = false;
    }
    
    parent::__construct();
  }

  function beforeRender() {
    $this->webroot = '/';
  }

	function beforeFilter() {
    $this->layout = 'default';

    if (Configure::read('debug_email')) {
      $this->Email->delivery = 'debug';
      $this->Email = new ObjectProxy($this->Email);
      $this->Email->setProxyOverload('reset', function ($object, $arguments) {
        call_user_func_array(array($object, 'reset'), $arguments);
        $object->delivery = 'debug';
      });
      $this->Email->setProxyOverload('send', function ($object, $arguments) {
        $object->delivery = 'debug';
        return call_user_func_array(array($object, 'send'), $arguments);
      });
    }

    $this->Session->path = '/';

    $this->_initEnvironment();

    if (Environment::institution('id')) {
      $authModel = ClassRegistry::init($this->Auth->userModel);
      $db = $authModel->getDataSource();
      $this->Auth->userScope = array(
        "(super_admin OR EXISTS (SELECT '' FROM users_institutions UserInstitution WHERE UserInstitution.user_id = {$authModel->escapeField()} AND UserInstitution.institution_id = {$db->value(Environment::institution('id'))} AND UserInstitution.active))"
      );
    }

    $this->_updateAppBetaOptions();

    $this->Security->validatePost = false;
    
    if ($this->isApi) {
      // Read Authorization header
      $authorization = env('HTTP_AUTHORIZATION');
      if (empty($authorization)) {
        $authorization = env('REDIRECT_HTTP_AUTHORIZATION');
      }

      if (($authorization || env('PHP_AUTH_USER')) && !$this->Session->started()) {
        // Fake php sessions
        session_set_save_handler(
          array(__CLASS__, '__fakeSessionWrite'),
          array(__CLASS__, '__fakeSessionWrite'),
          array(__CLASS__, '__fakeSessionWrite'),
          array(__CLASS__, '__fakeSessionWrite'),
          array(__CLASS__, '__fakeSessionWrite'),
          array(__CLASS__, '__fakeSessionWrite')
        );
        ini_set('session.use_cookies', 0);
        session_start();
      }
      
      // Set login options
      $this->Security->loginOptions = array(
        'type' => 'basic',
        'realm' => 'academic',
        'login' => '_api_authenticate'
      );
      
      // Authorize
      if ($authorization || !$this->_authorize()) {
        if (!env('PHP_AUTH_USER') && preg_match('/(Basic|Bearer)\s+(.*)$/i', $authorization, $matches)) {
          if (strtolower($matches[1]) === 'basic') {
            $name_and_password = explode(':', base64_decode($matches[2]));
            $name = $name_and_password[0];
            $password = isset($name_and_password[1])? $name_and_password[1] : '';
            $_SERVER['PHP_AUTH_USER'] = strip_tags($name);
            $_SERVER['PHP_AUTH_PW'] = strip_tags($password);
          } else {
            $_SERVER['PHP_AUTH_USER'] = $matches[2];
            $_SERVER['PHP_AUTH_PWD'] = null;
          }
        }
        $this->Security->requireLogin();
      } else {
        $this->Auth->allow($this->params['action']);
      }
    } else {
      $this->Auth->loginAction = array('controller' => 'users', 'action' => 'login', 'base' => false);
      $this->Auth->logoutRedirect = Router::url(array('controller' => 'users', 'action' => 'login', 'base' => false, 'full_base' => true));
      $this->Auth->loginRedirect = Router::url(array('controller' => 'users', 'action' => 'home', 'base' => false, 'full_base' => true));
      $this->Auth->allow('login');
      $this->Auth->allow('rememberPassword', 'find_subjects_by_name', 'add_by_secret_code', 'clean_up_day');

      if ($this->params['controller'] == 'bookings') {
        $this->Auth->allow('view', 'get');
      }

      $auth_type = $this->Auth->user('type');
      $acl = Configure::read('app.acl');
      if ($auth_type && !empty($acl[$auth_type]["{$this->params['controller']}.{$this->params['action']}"])) {
        $this->Auth->allow($this->params['action']);
      } elseif (!empty($acl['all']["{$this->params['controller']}.{$this->params['action']}"])) {
        $this->Auth->allow($this->params['action']);
      } elseif (!$this->_authorize()) {
        if ($this->RequestHandler->isAjax()) {
          $this->redirect(null, 401, true);
        } elseif ($this->Auth->user('id') == null) {
          $this->redirect(array('controller' => 'users', 'action' => 'login', 'base' => false));
        } else {
          $this->Session->setFlash('Usted no tiene permisos para realizar esta acción.');

          if ($this->Auth->user('type') == "Estudiante") {
            $this->redirect(array('controller' => 'users', 'action' => 'home', 'base' => false));
          } else {
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
          }
        }
      }
    }

    if (! Environment::user('id')) {
      Environment::setUser($this->Auth->user());
    }

    if (! $this->Auth->user('super_admin') && Environment::institution('id') && ! Environment::userInstitution('active')) {
      if ($this->RequestHandler->isAjax()) {
        $this->redirect(null, 401, true);
      } else {
        $this->Session->setFlash('Usted no tiene permisos para realizar esta acción.');
        
        if ($this->Auth->user('type') == "Estudiante") {
          $this->redirect(array('controller' => 'users', 'action' => 'home', 'base' => false));
        } else {
          $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }
      }
    }
	}
  
  function _api_authenticate($login) {
    $this->Auth->sessionKey = 'Api.Auth.User';
    
    if ($login['password']) {
      $data[ $this->Auth->fields['username'] ] = $login['username'];
      $data[ $this->Auth->fields['password'] ] = $this->Auth->password($login['password']);
      
      if (!$this->Auth->login($data) || !$this->_authorize()) {
        $this->redirect(null, 401, true);
      }
    } else {
      try {
        $secretKey = base64_decode(Configure::read('Security.secret'));
        $token = \Firebase\JWT\JWT::decode($login['username'], $secretKey, array('HS512'));
        $this->Auth->login($token->data->id);
      } catch (Exception $e) {
        /*
         * the token was not able to be decoded.
         * this is likely because the signature was not able to be verified (tampered token)
         */
        $this->redirect(null, 401, true);
      }
    }

    $this->_updateAppBetaOptions();
  }

  function _initEnvironment()
  {
    if (Environment::getInitialized()) {
      // Initialize only the first time ignoring the next disptach calls
      return;
    }

    Environment::setInitialized(true);

    if (!empty($this->params['institution'])) {
      $institution_id = intval($this->params['institution']);

      // Set institution in environment
      Environment::setInstitution($institution_id);

      if (! Environment::institution('id')) {
        $this->Session->setFlash('No se ha podido acceder al centro');
        $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
      }

      if (! is_dir(CONFIGS . "institutions/{$institution_id}")) {
        if (! mkdir(CONFIGS . "institutions/{$institution_id}")) {
          $this->Session->setFlash('No se ha podido acceder al centro');
          $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }
      }

      if (! file_exists(CONFIGS . "institutions/{$institution_id}/app.php")) {
        $fp = fopen(CONFIGS . "institutions/{$institution_id}/app.php", 'w');
        if ($fp === false || !fwrite($fp, '<?php require CONFIGS . \'institutions/default/app.php\';')) {
          $this->Session->setFlash('No se ha podido acceder al centro');
          $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }
        fclose($fp);
      }

      if (! file_exists(CONFIGS . "institutions/{$institution_id}/app.options.php")) {
        $fp = fopen(CONFIGS . "institutions/{$institution_id}/app.options.php", 'w');
        if ($fp === false || !fwrite($fp, '<?php return ' . var_export(array(), true) . ';')) {
          $this->Session->setFlash('No se ha podido acceder al centro');
          $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }
        fclose($fp);
      }

      // Set environment base url
      $base_url = "/institutions/{$institution_id}";
      Environment::setBaseUrl($base_url);

      // Change base url in all route paths to prefix automatically the environment base url to all generated urls
      $router = Router::getInstance();
      foreach (array_keys($router->__paths) as $i) {
        $router->__paths[$i]['base'] = $base_url;
      }

      // Load specific institution app config
      Configure::load("institutions/$institution_id/app");
    
      // Load configurable options values of current institution
      $appOptions = include CONFIGS . "institutions/$institution_id/app.options.php";
      foreach ($appOptions as $key => $value) {
          Configure::write(
              array(
                  "app.$key" => Set::merge($value, Configure::read("app.$key"))
              )
          );
      }
    } else {
        Configure::load('app');
    }
  }

  function _updateAppBetaOptions()
  {
    $username = $this->Auth->user('username');
    $beta_testers = (array) Configure::read('app.beta.testers');
    if ($username && array_search($username, $beta_testers) !== false) {
      $config_writes = (array) Configure::read('app.beta.config_writes');
      foreach ($config_writes as $config => $value) {
        Configure::write($config, $value);
      }
    }
  }

	function _authorize() {
    $this->set("acl", $this->Acl);
    
		if ($this->Auth->user('id') != null) {
      $this->set("auth", $this->Auth);
      
      return true;
		}
    return false;
	}

	function _parse_date($date, $separator = '/') {
		$date_components = explode($separator, $date);
		return count($date_components) != 3 ? false : date("Y-m-d", mktime(0,0,0, $date_components[1], $date_components[0], $date_components[2]));
  }
  
  function __fakeSessionWrite() {
    return true;
  }
}
