<?php
class ClassroomsController extends AppController {
	var $name = 'Classrooms';
	var $paginate = array('limit' => 10, 'order' => array('Classroom.name' => 'asc'), 'recursive' => 0);
	
	function index(){
		App::import('Sanitize');
		if (isset($this->params['url']['q']))
			$q = Sanitize::escape($this->params['url']['q']);
		else {
			if (isset($this->passedArgs['q']))
				$q = Sanitize::escape($this->passedArgs['q']);
			else
				$q = '';
		}
		$classrooms = $this->paginate('Classroom', array("OR" => array('Classroom.name LIKE' => "%$q%", 'Classroom.type LIKE' => "%$q%")));
		$this->set('classrooms', $classrooms);
		$this->set('q', $q);
	}
	
	function add(){
		if (!empty($this->data)){
			if ($this->Classroom->save($this->data)){
				$this->Session->setFlash('El aula se ha guardado correctamente');
				$this->redirect(array('action' => 'index'));
			}
		}
	}
	
	function view($id = null){
		$this->Classroom->id = $id;
		$this->set('classroom', $this->Classroom->read());
	}
	
	function edit($id = null) {
		$this->Classroom->id = $id;
		if (empty($this->data)) {
			$this->data = $this->Classroom->read();
			$this->set('classroom', $this->data);
		} else {
			if ($this->Classroom->save($this->data)) {
				$this->Session->setFlash('El aula se ha actualizado correctamente.');
				$this->redirect(array('action' => 'view', $id));
			} else
				$this->set('classroom', $this->data);
		}
	}
	
	function get_sign_file() {
	  $classrooms = $this->Classroom->find('all', array('order' => "name ASC", 'recursive' => 0));

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
    $date = $this->_parse_date($this->params['url']['date']);
		$this->layout = false;

		$this->set('date', date_create($date));
		$this->set('events', $this->Classroom->Event->findAllByDate($date));
		$this->set('bookings', $this->Classroom->Booking->findAllByDate($date));
	}

	function print_sign_file() {
	  $this->layout = 'sign_file';
    $date = $this->_parse_date($this->params['url']['date']);

    $activities = $this->Classroom->query("SELECT Event.*, Activity.*, Subject.*, Teacher.*, Teacher2.* FROM events Event INNER JOIN activities Activity ON Activity.id = Event.activity_id INNER JOIN subjects Subject ON Subject.id = Activity.subject_id INNER JOIN users Teacher ON Teacher.id = Event.teacher_id LEFT JOIN users Teacher2 ON Teacher2.id = Event.teacher_2_id WHERE DATE_FORMAT(Event.initial_hour, '%Y-%m-%d') = '{$date}' AND Event.classroom_id = {$this->params['url']['classroom']} ORDER BY Event.initial_hour");

    $this->Classroom->id = $this->params['url']['classroom'];
    $this->set('activities', $activities);
    $this->set('classroom', $this->Classroom->read());
    $this->set('date', date_create($date));
	}
	
	function _parse_date($date, $separator = "-"){
		$date_components = split($separator, $date);
		
		return count($date_components) != 3 ? false : date("Y-m-d", mktime(0,0,0, $date_components[1], $date_components[0], $date_components[2]));
	}
	
	function stats($course_id=null){
	  if ($course_id == null)
	    $course_id = $this->params['url']['course_id'];
	  
	  $course = $this->Classroom->query("SELECT Course.* FROM courses Course where id = {$course_id}");
	  $this->set('course', $course);
	  
	  if (isset($this->params['url']['data']['classrooms'])) {
	    $id = $this->params['url']['data']['classrooms'];
	    $this->Classroom->id = $id;
	    $date = Date('Y-m-d');
	    
      $stats = $this->Classroom->query("SELECT Subject.id, Subject.name, User.id, User.first_name, User.last_name, SUM(AttendanceRegister.duration) as num_hours, SUM(IFNULL(AttendanceRegister.num_students, 0)) as num_students, IFNULL(count(DISTINCT AttendanceRegister.event_id), 1) AS num_events FROM attendance_registers AttendanceRegister INNER JOIN users User ON User.id = AttendanceRegister.teacher_id INNER JOIN activities Activity ON Activity.id = AttendanceRegister.activity_id INNER JOIN events Event ON Event.id = AttendanceRegister.event_id INNER JOIN subjects Subject ON Subject.id = Activity.subject_id WHERE AttendanceRegister.duration IS NOT NULL AND AttendanceRegister.duration > 0 AND DATE_FORMAT(AttendanceRegister.initial_hour, '%Y-%m-%d') <= '{$date}' AND  Event.classroom_id = {$id} AND Subject.course_id = {$course_id} GROUP BY Subject.id, User.id ORDER BY Subject.name, User.first_name, User.last_name");
    
      $this->set('stats', $stats);
      $this->set('classroom', $this->Classroom->read());
    }
    
    $classrooms = array();
		foreach($this->Classroom->find('all', array('order' => array('Classroom.name'), 'recursive' => -1)) as $classroom):
			$classrooms["{$classroom['Classroom']['id']}"] = $classroom['Classroom']['name'];
		endforeach;
		$this->set('classrooms', $classrooms);
	}
	
	function delete($id = null){
		$this->Classroom->delete($id);
		$this->Session->setFlash('El aula ha sido eliminada correctamente');
		$this->redirect(array('action' => 'index'));
	}
	
	function _authorize() {
		parent::_authorize();
		
		$administrator_actions = array('add', 'edit', 'delete');
		
		$this->set('section', 'classrooms');
		
		if ((array_search($this->params['action'], $administrator_actions) !== false) && ($this->Auth->user('type') != "Administrador"))
			return false;
	
		return true;
	}
}
?>
