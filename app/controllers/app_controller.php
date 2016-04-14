<?php
class AppController extends Controller {
	/**
	 * Application wide controllers
	 */
	var $components = array('Security', 'Session', 'Auth', 'RequestHandler', 'Email');
  
	/**
	 * Application wide helpers
	 *
	 * @since 2012-05-17
	 */
	var $helpers = array('Html', 'Form', 'Session', 'Javascript', 'DateHelper');

  var $isApi = false;

  function __construct() {
    if ($this->isApi) {
      $this->autoLayout = false;
      if ($this->viewPath == null) {
        $this->viewPath = 'api/'.Inflector::underscore($this->name);
      }
  	  $this->helpers = array('Api');
      $this->components []= 'Api';
    }
    
    parent::__construct();
  }

	function beforeFilter() {
		$this->layout = 'default';
    
    $this->Security->validatePost = false;
    
    if ($this->isApi) {
      $this->Security->loginOptions = array(
        'type' => 'basic',
        'realm' => 'academic',
        'login' => '_api_authenticate'
      );
      
      $authorization = env('HTTP_AUTHORIZATION');
      if (empty($authorization)) {
        $authorization = env('REDIRECT_HTTP_AUTHORIZATION');
      }
      if ($authorization || !$this->_authorize()) {
        if (!env('PHP_AUTH_USER') && preg_match('/Basic\s+(.*)$/i', $authorization, $matches)) {
          list($name, $password) = explode(':', base64_decode($matches[1]));
          $_SERVER['PHP_AUTH_USER'] = strip_tags($name);
          $_SERVER['PHP_AUTH_PW'] = strip_tags($password);
        }
        $this->Security->requireLogin();
      }
    } else {
      $this->Auth->loginAction = array('controller' => 'users', 'action' => 'login');
      $this->Auth->logoutRedirect = array('controller' => 'users', 'action' => 'login');
      $this->Auth->loginRedirect = array('controller' => 'users', 'action' => 'home');
      $this->Auth->allow('login');
      $this->Auth->allow('rememberPassword', 'calendar_by_classroom', 'calendar_by_subject', 'calendar_by_level', 'board', 'get', 'get_by_level', 'find_subjects_by_name', 'get_by_subject');

      if ($this->params['controller'] == 'events') {
        $this->Auth->allow('view');
      }

      if (!$this->_authorize()) {
        if ($this->Auth->user('id') == null) {
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
    $data[ $this->Auth->fields['username'] ] = $login['username'];  
    $data[ $this->Auth->fields['password'] ] = $this->Auth->password($login['password']);
    if (!$this->Auth->login($data) || !$this->_authorize()) {
      $this->Security->blackHole($this, 'login');
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
}
?>
