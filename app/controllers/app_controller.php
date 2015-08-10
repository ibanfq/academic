<?php
class AppController extends Controller {
	/**
	 * Application wide controllers
	 */
	var $components = array('Session', 'Auth', 'RequestHandler', 'Email');

	/**
	 * Application wide helpers
	 *
	 * @since 2012-05-17
	 */
	var $helpers = array('Html', 'Form', 'Session', 'Javascript', 'DateHelper');

	function beforeFilter() {
		$this->layout = 'default';

		$this->Auth->loginAction = array('controller' => 'users', 'action' => 'login');
		$this->Auth->logoutRedirect = array('controller' => 'users', 'action' => 'login');
		$this->Auth->loginRedirect = array('controller' => 'users', 'action' => 'home');
		$this->Auth->allow('login');
		$this->Auth->allow('rememberPassword', 'calendar_by_classroom', 'calendar_by_subject', 'calendar_by_level', 'events_board', 'get', 'get_by_level', 'find_subjects_by_name', 'get_by_subject');

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

	function _authorize() {
		if ($this->Auth->user('id') != null) {
			$this->set("auth", $this->Auth);
		}
	}

	function _parse_date($date, $separator = "/") {
		$date_components = split($separator, $date);
		return count($date_components) != 3 ? false : date("Y-m-d", mktime(0,0,0, $date_components[1], $date_components[0], $date_components[2]));
	}
}
?>
