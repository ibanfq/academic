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
        $joins_for_where = '';
        $where = array();

        $limit = $this->Api->getParameter('limit', array('integer', '>0', '<=100'), 100);
        $offset = $this->Api->getParameter('offset', array('integer', '>=0'), 0);

        if (isset($this->params['url']['filter']['user']) && $this->params['url']['filter']['user'] === 'me') {
            $user = intval($this->Auth->user('id'));
        } else {
            $user_default = $this->Auth->user('type') === "Estudiante"? $this->Auth->user('id') : null;
            $user = $this->Api->getParameter('filter.user', array('integer', '>=0'), $user_default);
        }
        if (!empty($user)) {
            if ($user === $this->Auth->user('id')) {
                $type = $this->Auth->user('type');
            } else {
                $type = $this->Event->Teacher->find(
                    'first',
                    array(
                        'conditions' => array('Teacher.id' => $user),
                        'recursive' => -1,
                        'fields' => array('Teacher.type')
                    )
                );
                if ($type) {
                    $type = $type['Teacher']['type'];
                } else {
                    $this->Api->setError('No se ha podido encontrar al usuario', 404);
                }
            }
            
            if ($type === "Estudiante") {
                $joins_for_where .= " INNER JOIN registrations Registration ON Registration.activity_id = Event.activity_id AND Registration.student_id = {$user}";
            } else {
                $where []= "(Event.teacher_id = {$user} OR Event.teacher_2_id = {$user})";
            }
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
                $where = implode(' AND ', $where);
                $order = ' ORDER BY Event.initial_hour ASC, Event.id';
                $limit = " LIMIT $limit OFFSET $offset";
                $events = $this->Event->query(
                    'SELECT distinct Event.*, Activity.*, Subject.*, `Group`.*, Classroom.*' .
                    " FROM (SELECT Event.* from events Event $joins_for_where WHERE $where $order $limit) Event" .
                    ' INNER JOIN activities Activity ON Activity.id = Event.activity_id' .
                    ' INNER JOIN subjects Subject ON Subject.id = Activity.subject_id' .
                    ' INNER JOIN groups `Group` ON `Group`.id = Event.group_id' .
                    ' INNER JOIN classrooms Classroom ON Classroom.id = Event.classroom_id' .
                    $order
                );
                $this->Api->setData($events);
            }
        }

        $this->Api->respond($this);
    }
    
    function view($id) {
        $id = $id === null ? null : intval($id);
        $exists = true;
        
        if ($this->Auth->user('type') === "Estudiante") {
            $user_id = intval($this->Auth->user('id'));
            $exists = (bool) $this->Event->query(
                'SELECT e.id FROM events e INNER JOIN activities a ON a.id = e.activity_id' .
                ' INNER JOIN registrations r ON a.id = r.activity_id AND r.student_id = ' . $user_id .
                " WHERE e.id = $id LIMIT 1"
            );
        }
        if ($exists) {
            $exists = (bool) $event = $this->Event->read(null, $id);
        }
        
        if ($exists) {
            $this->Api->setData($event);
        } else {
            $this->Api->setError('No se ha podido acceder al evento.', 404);
        }
        $this->Api->respond($this);
    }
}
