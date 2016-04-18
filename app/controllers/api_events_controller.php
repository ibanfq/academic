<?php
class ApiEventsController extends AppController {
  var $name = 'Events';
  var $isApi = true;
  
  function _authorize(){
		if (!parent::_authorize()) {
      return false;
    }
    
    $private_actions = array("delete", "add", "post");

    if ((array_search($this->params['action'], $private_actions) !== false) && ($this->Auth->user('type') != "Administrador") && ($this->Auth->user('type') != "Profesor")) {
			return false;
    }

		return true;
	}

  function index()
  {
    $extra_joins = '';
    $where = array();

    $limit = $this->Api->getParameter('limit', array('integer', '>0', '<=100'), 100);
    $offset = $this->Api->getParameter('offset', array('integer', '>=0'), 0);

    if (isset($this->params['url']['filter']['user']) && $this->params['url']['filter']['user'] === 'me') {
      $user = $this->Auth->user('id');
    } else {
      $user_default = $this->Auth->user('type') === "Estudiante"? $this->Auth->user('id') : null;
      $user = $this->Api->getParameter('filter.user', array('integer', '>=0'), $user_default);
    }
    if (!empty($user)) {
      $extra_joins .= " LEFT JOIN registrations Registration ON Activity.id = Registration.activity_id AND Registration.student_id = {$user}";
      $where []= "(Event.teacher_id = {$user} OR Event.teacher_2_id = {$user} OR Registration.id)";
    }

    $date = $this->Api->getParameter('filter.date');
    if (!empty($date)) {
      if ($date === 'today') {
        $where []= 'Event.initial_hour > CURDATE() AND Event.initial_hour < (CURDATE() + INTERVAL 1 DAY)';
      } else {
        $this->Api->AddFail('filter.date', 'Not supported yet');
      }
    }

    if ($this->Api->getStatus() === 'success') {
      if ($this->Auth->user('type') === "Estudiante" && $user != $this->Auth->user('id')) {
        $this->Api->AddFail('filter.date', 'Not authorized');
      } else {
        $where []= 'Event.duration > 0';
        $events = $this->Event->query(
          'SELECT distinct Event.*, Activity.*, Subject.*, `Group`.*, Classroom.*' .
          ' FROM events Event INNER JOIN activities Activity ON Activity.id = Event.activity_id' .
          ' INNER JOIN subjects Subject ON Subject.id = Activity.subject_id' .
          ' INNER JOIN groups `Group` ON `Group`.id = Event.group_id' .
          ' INNER JOIN classrooms Classroom ON Classroom.id = Event.classroom_id' .
          " $extra_joins WHERE " . implode(' AND ', $where) .
          " ORDER BY Event.initial_hour DESC LIMIT $limit OFFSET $offset"
        );
        $this->Api->setData($events);
      }
    }

    $this->Api->setViewVars($this);
  }
  
  function view($id) {
    $id = intval($id);
    $exists = true;
    
    if ($this->Auth->user('type') === "Estudiante") {
      $exists = (bool) $this->Event->query(
        'SELECT e.id FROM events e INNER JOIN activities a ON a.id = e.activity_id' .
        ' INNER JOIN registrations r ON a.id = r.activity_id AND r.student_id = ' . $this->Auth->user('id') .
        " WHERE e.id = $id LIMIT 1"
      );
    }
    if ($exists) {
      $exists = (bool) $event = $this->Event->read(null, $id);
    }
    
    if ($exists) {
      $this->Api->setData($event);
    } else {
      $this->Api->setError('No se ha podido acceder al evento');
    }
    $this->Api->setViewVars($this);
  }
}
