<?php
class ApiUsersController extends AppController {
  var $name = 'Users';
  var $isApi = true;

  function _authorize(){
		if (!parent::_authorize()) {
      return false;
    }

		$administrator_actions = array('delete');
		$administrative_actions = array('edit', 'add');
		$neither_student_nor_concierge = array('index', 'view');
		$student_actions = array();
    
		if ((array_search($this->params['action'], $administrator_actions) !== false) && ($this->Auth->user('type') != "Administrador")) {
			return false;
    }
		
		if ((array_search($this->params['action'], $administrative_actions) !== false) && ($this->Auth->user('type') != "Administrador") && ($this->Auth->user('type') != "Administrativo")) {
			return false;
    }
    
		if ((array_search($this->params['action'], $neither_student_nor_concierge) !== false) && (($this->Auth->user('type') == "Estudiante") || ($this->Auth->user('type') == "Conserje") )) {
			return false;
    }
		
		if ((array_search($this->params['action'], $student_actions) !== false) && ($this->Auth->user('type') != "Estudiante")) {
			return false;
    }

		return true;
	}
  
  function index()
  {
    $where = array();

    $limit = $this->Api->getParameter('limit', array('integer', '>0', '<=100'), 100);
    $offset = $this->Api->getParameter('offset', array('integer', '>=0'), 0);
    $q = $this->Api->getParameter('q');
    
    if (!empty($q)) {
      App::import('Sanitize');
      $q = Sanitize::escape($q);
      $where []= "(CONCAT(User.last_name, ' ', User.first_name) LIKE '%$q%' OR User.dni LIKE '%$q%')";
    }
    
    if ($this->Api->getStatus() === 'success') {
      $where = empty($where)? '' : 'WHERE ' . implode(' AND ', $where);
      $users = $this->User->query(
        "SELECT User.* FROM users User $where ORDER BY User.last_name ASC, User.first_name ASC LIMIT $limit OFFSET $offset"
      );
      $this->Api->setData($users);
    }

    $this->Api->setViewVars($this);
  }
  
}
