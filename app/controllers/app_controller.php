<?php
class AppController extends Controller {
	/**
	 * Application wide controllers
	 */
	var $components = array('Security', 'Session', 'Auth', 'RequestHandler', 'Email', 'Api');
  
	/**
	 * Application wide helpers
	 *
	 * @since 2012-05-17
	 */
	var $helpers = array('Html', 'Form', 'Session', 'Javascript', 'DateHelper');

  var $isApi = false;

  function __construct() {
    if ($this->isApi) {
      $this->autoRender = false;
    }
    
    parent::__construct();
  }

	function beforeFilter() {
		$this->layout = 'default';
    
    if (Configure::read('debug') > 0) {
      $this->Email->delivery = 'debug';
    }
    
    $this->Security->validatePost = false;
    
    if ($this->isApi) {
      if (session_id() === '' && empty($_COOKIE[Configure::read('Session.cookie')])) {
        // Disable sessions (Faking php session write)
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
      
      // Read Authorization header
      $authorization = env('HTTP_AUTHORIZATION');
      if (empty($authorization)) {
        $authorization = env('REDIRECT_HTTP_AUTHORIZATION');
      }
      
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
      $this->Auth->loginAction = array('controller' => 'users', 'action' => 'login');
      $this->Auth->logoutRedirect = array('controller' => 'users', 'action' => 'login');
      $this->Auth->loginRedirect = array('controller' => 'users', 'action' => 'home');
      $this->Auth->allow('login');
      $this->Auth->allow('rememberPassword', 'calendar_by_classroom', 'calendar_by_subject', 'calendar_by_level', 'board', 'get', 'get_by_level', 'get_by_degree_and_level', 'find_subjects_by_name', 'get_by_subject', 'add_by_secret_code', 'clean_up_day');

      if ($this->params['controller'] == 'events' || $this->params['controller'] == 'bookings') {
        $this->Auth->allow('view');
      }

      if (!$this->_authorize()) {
        if ($this->RequestHandler->isAjax()) {
          $this->redirect(null, 401, true);
        } elseif ($this->Auth->user('id') == null) {
          $this->redirect(array('controller' => 'users', 'action' => 'login'));
        } else {
          $this->Session->setFlash('Usted no tiene permisos para realizar esta acción.');

          if ($this->Auth->user('type') == "Estudiante") {
            $this->redirect(array('controller' => 'users', 'action' => 'home'));
          } else {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
          }
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
        $token = JWT::decode($login['username'], $secretKey, array('HS512'));
        $this->Auth->login($token->data->id);
      } catch (Exception $e) {
        /*
         * the token was not able to be decoded.
         * this is likely because the signature was not able to be verified (tampered token)
         */
        $this->redirect(null, 401, true);
      }
    }
  }

	function _authorize() {
		if ($this->Auth->user('id') != null) {
			$this->set("auth", $this->Auth);
      return true;
		}
    return false;
	}

	function _parse_date($date, $separator = "/") {
		$date_components = split($separator, $date);
		return count($date_components) != 3 ? false : date("Y-m-d", mktime(0,0,0, $date_components[1], $date_components[0], $date_components[2]));
  }
  
  function __fakeSessionWrite() {
    return true;
  }
}
