<?php
class ClassroomsController extends AppController {
    var $name = 'Classrooms';
    var $paginate = array('limit' => 10, 'order' => array('Classroom.name' => 'asc'), 'recursive' => 0);
    
    function index(){
        App::import('Core', 'Sanitize');
        if (isset($this->params['url']['q']))
            $q = Sanitize::escape($this->params['url']['q']);
        else {
            if (isset($this->passedArgs['q']))
                $q = Sanitize::escape($this->passedArgs['q']);
            else
                $q = '';
        }
        $conditions = array(
            'Classroom.institution_id' => Environment::institution('id'),
            "OR" => array('Classroom.name LIKE' => "%$q%", 'Classroom.type LIKE' => "%$q%")
        );
        $classrooms = $this->paginate('Classroom', $conditions);
        $this->set('classrooms', $classrooms);
        $this->set('q', $q);
    }
    
    function add(){
        if (!empty($this->data)){
            $this->data['Classroom']['institution_id'] = Environment::institution('id');
            if ($this->Classroom->save($this->data)){
                $this->Session->setFlash('El aula se ha guardado correctamente');
                $this->redirect(array('action' => 'index'));
            }
        }
    }
    
    function view($id = null){
        $id = $id === null ? null : intval($id);

        $classroom = $this->Classroom->find('first', array(
            'conditions' => array(
                'Classroom.id' => $id,
                'Classroom.institution_id' => Environment::institution('id'),
            )
        ));

        if (!$classroom) {
            $this->Session->setFlash('No se ha podido acceder al aula.');
            $this->redirect($this->referer());
        }

        $this->set('classroom', $classroom);
    }
    
    function edit($id = null) {
        $id = $id === null ? null : intval($id);

        $classroom = $this->Classroom->find('first', array(
            'conditions' => array(
                'Classroom.id' => $id,
                'Classroom.institution_id' => Environment::institution('id'),
            )
        ));

        if (!$classroom) {
            $this->Session->setFlash('No se ha podido acceder al aula.');
            $this->redirect($this->referer());
        }

        $this->Classroom->id = $id;
        if (empty($this->data)) {
            $this->data = $this->Classroom->read();
            $this->set('classroom', $this->data);
        } else {
            $this->data['Classroom']['id'] = $id;
            $this->data['Classroom']['institution_id'] = $classroom['Classroom']['institution_id'];
            if ($this->Classroom->save($this->data)) {
                $this->Session->setFlash('El aula se ha actualizado correctamente.');
                $this->redirect(array('action' => 'view', $id));
            } else {
                $this->set('classroom', $classroom);
            }
        }
    }

    function find_by_name() {
        App::import('Core', 'Sanitize');

        $db = $this->Classroom->getDataSource();

        $name_conditions = array();
        foreach (explode(' ', $this->params['url']['q']) as $q) {
            $q = '%'.Sanitize::escape($q).'%';
            $name_conditions[] = array('Classroom.name like' => $q);
        }

        $classrooms = $this->Classroom->find('all', array(
            'fields' => array('Classroom.id', 'Classroom.name'),
            'recursive' => 0,
            'conditions' => array(
                "Classroom.id IN (SELECT classroom_id FROM classrooms_institutions ClassroomInstitution WHERE ClassroomInstitution.institution_id = {$db->value(Environment::institution('id'))})",
                'AND' => $name_conditions
            ),
            'order' => array('Classroom.name')
        ));
        $this->set('classrooms', $classrooms);
    }
    
    function get_sign_file() {
        $classrooms = $this->Classroom->find('all', array(
            'conditions' => array(
                'Classroom.institution_id' => Environment::institution('id'),
            ),
            'order' => "Classroom.name ASC",
            'recursive' => 0
        ));

        $classrooms_mapped = array();
        foreach($classrooms as $cl):
            $classrooms_mapped[$cl['Classroom']['id']] = $cl['Classroom']['name'];
        endforeach;

        $this->set('classrooms', $classrooms_mapped);
    }
    
    /**
     * Shows a form to print bookings on a given date
     *
     * @return void
     * @since 2013-03-10
     */
    function get_bookings() { /* see print_bookings() */ }

    /**
     * Prints a bookings sheet for a given date
     *
     * @return void
     * @since 2013-03-10
     */
    function print_bookings() {
        $date = $this->_parse_date(isset($this->params['url']['date']) ? $this->params['url']['date'] : null);
        $this->layout = false;

        if (!$date) {
            $date = date("Y-m-d");
        }

        $this->set('date', date_create($date));
        $this->set('events', $this->Classroom->Event->findAllByDate($date));
        $this->set('bookings', $this->Classroom->Booking->findAllByDate($date));
    }

    function print_sign_file() {
        $this->layout = 'sign_file';
        $date = $this->_parse_date($this->params['url']['date']);
        $classroom_id = intval($this->params['url']['classroom']);

        $classroom = $this->Classroom->find('first', array(
            'conditions' => array(
                'Classroom.id' => $classroom_id,
                'Classroom.institution_id' => Environment::institution('id'),
            )
        ));

        if (!$classroom) {
            $this->Session->setFlash('No se ha podido acceder al aula.');
            $this->redirect($this->referer());
        }

        $activities = $this->Classroom->query("SELECT Event.*, Activity.*, Subject.*, Teacher.*, Teacher2.* FROM events Event INNER JOIN activities Activity ON Activity.id = Event.activity_id INNER JOIN subjects Subject ON Subject.id = Activity.subject_id INNER JOIN users Teacher ON Teacher.id = Event.teacher_id LEFT JOIN users Teacher2 ON Teacher2.id = Event.teacher_2_id WHERE DATE_FORMAT(Event.initial_hour, '%Y-%m-%d') = '{$date}' AND Event.classroom_id = {$classroom_id} ORDER BY Event.initial_hour");

        $this->set('activities', $activities);
        $this->set('classroom', $classroom);
        $this->set('date', date_create($date));
    }
    
    function _parse_date($date, $separator = '-') {
        $date_components = explode($separator, $date);
        
        return count($date_components) != 3 ? false : date("Y-m-d", mktime(0,0,0, $date_components[1], $date_components[0], $date_components[2]));
    }
    
    function stats($course_id=null){
        if ($course_id == null) {
            $course_id = intval($this->params['url']['course_id']);
        } else {
            $course_id = intval($course_id);
        }
        
        if (! $course_id) {
            $this->Session->setFlash('No se ha podido acceder al curso.');
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        $db = $this->Classroom->getDataSource();
        $this->loadModel('Course');

        $course = $this->Course->find('first', array(
            'fields' => array('Course.*', 'Degree.*'),
            'joins' => array(
                array(
                    'table' => 'degrees',
                    'alias' => 'Degree',
                    'type' => 'INNER',
                    'conditions' => 'Degree.id = Course.degree_id'
                )
            ),
            'conditions' => array(
                'Course.id' => $course_id,
                'Course.institution_id' => Environment::institution('id')
            ),
            'recursive' => -1
        ));

        if (! $course) {
            $this->Session->setFlash('No se ha podido acceder al curso.');
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        $this->set('course', $course);
      
        if (isset($this->params['url']['data']['classrooms'])) {
            $id = intval($this->params['url']['data']['classrooms']);

            $classroom = $this->Classroom->find('first', array(
                'conditions' => array(
                    'Classroom.id' => $id,
                    "Classroom.id IN (SELECT classroom_id FROM classrooms_institutions ClassroomInstitution WHERE ClassroomInstitution.institution_id = {$db->value(Environment::institution('id'))})"
                ),
                'recursive' => -1
            ));

            if (!$classroom) {
                $this->Session->setFlash('No se ha podido acceder al aula.');
                $this->redirect($this->referer());
            }

            $date = Date('Y-m-d');
        
            $stats = $this->Classroom->query("SELECT Subject.id, Subject.name, User.id, User.first_name, User.last_name, SUM(AttendanceRegister.duration) as num_hours, SUM(IFNULL(AttendanceRegister.num_students, 0)) as num_students, IFNULL(count(DISTINCT AttendanceRegister.event_id), 1) AS num_events FROM attendance_registers AttendanceRegister INNER JOIN users User ON User.id = AttendanceRegister.teacher_id INNER JOIN activities Activity ON Activity.id = AttendanceRegister.activity_id INNER JOIN events Event ON Event.id = AttendanceRegister.event_id INNER JOIN subjects Subject ON Subject.id = Activity.subject_id WHERE AttendanceRegister.duration IS NOT NULL AND AttendanceRegister.duration > 0 AND DATE_FORMAT(AttendanceRegister.initial_hour, '%Y-%m-%d') <= '{$date}' AND  Event.classroom_id = {$id} AND Subject.course_id = {$course_id} GROUP BY Subject.id, User.id ORDER BY Subject.name, User.first_name, User.last_name");
    
            $this->set('stats', $stats);
            $this->set('classroom', $classroom);
        } else {
            $classrooms = $this->Classroom->find('all', array(
                'conditions' => array(
                    'Classroom.institution_id' => Environment::institution('id'),
                ),
                'order' => "Classroom.name ASC",
                'recursive' => 0
            ));

            $classrooms_mapped = array();
            foreach($classrooms as $cl):
                $classrooms_mapped[$cl['Classroom']['id']] = $cl['Classroom']['name'];
            endforeach;

            $this->set('classrooms', $classrooms_mapped);
        }
    }
    
    function delete($id = null){
        $id = $id === null ? null : intval($id);

        if (!$id) {
            $this->Session->setFlash('No se ha podido acceder al aula.');
            $this->redirect($this->referer());
        }

        $classroom = $this->Classroom->find('first', array(
            'conditions' => array(
                'Classroom.id' => $id,
                'Classroom.institution_id' => Environment::institution('id'),
            )
        ));

        if (!$classroom) {
            $this->Session->setFlash('No se ha podido acceder al aula.');
            $this->redirect($this->referer());
        }

        $this->Classroom->delete($id);
        $this->Session->setFlash('El aula ha sido eliminada correctamente');
        $this->redirect(array('action' => 'index'));
    }
    
    function _authorize() {
        parent::_authorize();

        if (! Environment::institution('id')) {
            return false;
        }
        
        $administrator_actions = array('add', 'edit', 'delete');
        $student_actions = array('index', 'view');
        
        $this->set('section', 'classrooms');
        
        if ((array_search($this->params['action'], $administrator_actions) !== false) && ($this->Auth->user('type') != "Administrador")) {
            return false;
        }
    
        if ((array_search($this->params['action'], $student_actions) === false) && ($this->Auth->user('type') == "Estudiante")) {
            return false;
        }
    
        return true;
    }
}
