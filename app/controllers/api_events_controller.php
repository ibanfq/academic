<?php
	class ApiEventsController extends AppController {
    var $name = 'Events';
    var $isApi = true;
    
    function index()
    {
      if (!$db =& ConnectionManager::getDataSource($this->{$this->modelClass}->useDbConfig)) {
        return false;
      }
    
      $filter = isset($this->params['url']['filter'])? (array) $this->params['url']['filter'] : array();
      $limit = isset($this->params['url']['limit'])? $this->params['url']['limit'] : 100;
      $offset = isset($this->params['url']['offset'])? $this->params['url']['offset'] : 0;
      $where = array();
      
      // Limit
      if (!is_int($limit) && $limit !== (string) intval($limit)) {
        $this->set('status', 'fail');
        $this->set('data', array('limit' => 'Invalid'));
        return;
      } elseif ($limit > 100) {
        $this->set('status', 'fail');
        $this->set('data', array('limit' => 'The maximum value is 100'));
        return;
      } elseif (empty($limit) || $limit < 1) {
        $this->set('status', 'fail');
        $this->set('data', array('limit' => 'The minimum value is 1'));
        return;
      }
      $limit = $db->value($limit, 'integer');
      
      // Offset
      if (!is_int($offset) && $offset !== (string) intval($offset)) {
        $this->set('status', 'fail');
        $this->set('data', array('offset' => 'Invalid'));
        return;
      } elseif (($offset !== 0 && empty($offset)) || $offset < 0) {
        $this->set('status', 'fail');
        $this->set('data', array('offset' => 'The minimum value is 0'));
        return;
      }
      $offset = $db->value($offset, 'integer');
      
      // User filter
      if (isset($filter['user'])) {
        if ($filter['user'] !== (string) intval($filter['user'])) {
          if ($filter['user'] === 'me') {
            $filter['user'] = $this->Auth->user('id');
          } else {
            $this->set('status', 'fail');
            $this->set('data', array('user' => 'Invalid'));
            return;
          }
        }
      }
      if ($this->Auth->user('type') === "Estudiante") {
        if (empty($filter['user'])) {
          $filter['user'] = $this->Auth->user('id');
        } elseif ($filter['user'] != $this->Auth->user('id')) {
          $this->set('status', 'fail');
          $this->set('data', array('user' => 'Not authorized'));
          return;
        }
      }
      if (!empty($filter['user'])) {
        $user = $db->value($user, 'integer');
        $where []= "(Event.teacher_id = {$user} OR Event.teacher_2_id = {$user})";
      }
      
      // Date filter
      if (isset($filter['date'])) {
        if ($filter['date'] === 'today') {
          $where []= 'Event.initial_hour > CURDATE() AND Event.initial_hour < (CURDATE() + INTERVAL 1 DAY)';
        } else {
          $this->set('status', 'fail');
          $this->set('data', array('date' => 'Not supported yet'));
          return;
        }
      }
      
      $where []= 'Event.duration > 0';
      
      $events = $this->Event->query(
        'SELECT Event.*, Activity.*, Subject.*, `Group`.*, Classroom.*' .
        ' FROM events Event INNER JOIN activities Activity ON Activity.id = Event.activity_id' .
        ' INNER JOIN subjects Subject ON Subject.id = Activity.subject_id'.
        ' INNER JOIN groups `Group` ON `Group`.id = Event.group_id' .
        ' INNER JOIN classrooms Classroom ON Classroom.id = Event.classroom_id' .
        ' WHERE ' . implode(' AND ', $where) .
			  " ORDER BY Event.initial_hour DESC LIMIT $limit OFFSET $offset"
      );
      
      $this->set('status', 'success');
      $this->set('data', $events);
    }
  }
