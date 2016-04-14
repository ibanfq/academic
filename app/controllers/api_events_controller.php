<?php
	class ApiEventsController extends AppController {
    var $name = 'Events';
    var $isApi = true;
    
    function index()
    {
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
        $where []= "(Event.teacher_id = {$user} OR Event.teacher_2_id = {$user})";
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
            'SELECT Event.*, Activity.*, Subject.*, `Group`.*, Classroom.*' .
            ' FROM events Event INNER JOIN activities Activity ON Activity.id = Event.activity_id' .
            ' INNER JOIN subjects Subject ON Subject.id = Activity.subject_id'.
            ' INNER JOIN groups `Group` ON `Group`.id = Event.group_id' .
            ' INNER JOIN classrooms Classroom ON Classroom.id = Event.classroom_id' .
            ' WHERE ' . implode(' AND ', $where) .
            " ORDER BY Event.initial_hour DESC LIMIT $limit OFFSET $offset"
          );
          $this->Api->setData($events);
        }
      }
      
      $this->Api->setTemplateVars($this);
  }
}
  