<?php
	class ActivitiesController extends AppController {
		var $name = 'Activities';
		var $paginate = array('limit' => 10, 'order' => array('activity.initial_date' => 'asc'));
		var $helpers = array('html', 'javascript', 'dateHelper', 'Ajax');
	
		function add($subject_id = null){
			if (!empty($this->data)){
				if ($this->Activity->save($this->data)){
					$this->Session->setFlash('La actividad se ha guardado correctamente');
					$this->redirect(array('controller' => 'subjects', 'action' => 'view', $this->data['Activity']['subject_id']));
				}
				else{
					$subject = $this->Activity->Subject->find('first', array('conditions' => array('Subject.id' => $this->data['Activity']['subject_id'])));
					$this->set('subject', $subject);
					$this->set('subject_id', $this->data['Activity']['subject_id']);
				}
			}
			else {
				if (is_null($subject_id)){
					$this->Session->setFlash('Está intentando realizar una acción no permitida.');
					$this->redirect(array('controller' => 'courses', 'action' => 'index'));
				}
				else{
					$subject = $this->Activity->Subject->find('first', array('conditions' => array('Subject.id' => $subject_id)));
					$this->set('subject', $subject);
					$this->set('subject_id', $subject_id);
				}
			}
		}
	
		function view($id = null) {
			$this->Activity->id = $id;
			$activity = $this->Activity->read();
			$this->set('activity', $activity);
			$this->set('subject', $this->Activity->Subject->find('first', array('conditions' => array('Subject.id' => $activity['Subject']['id']))));
			$this->set('groups', $this->Activity->query("SELECT `Group`.*, count(DISTINCT Registration.id) AS students FROM groups `Group` INNER JOIN registrations Registration ON Registration.group_id = `Group`.id WHERE Registration.activity_id = {$id} GROUP BY `Group`.id"));
		}
	
		function edit($id = null) {
			$this->Activity->id = $id;
			if (empty($this->data)) {
				$this->data = $this->Activity->read();
				$subject = $this->Activity->Subject->find('first', array('conditions' => array('Subject.id' => $this->data['Activity']['subject_id'])));
				$this->set('activity', $this->data);
				$this->set('subject', $subject);
			} else {
				if ($this->Activity->save($this->data)) {
					$this->Session->setFlash('La actividad se ha modificado correctamente.');
					$this->redirect(array('action' => 'view', $id));
				}
			}
		}
	
		function get($subject_id = null){
			$query = "SELECT DISTINCT Activity.* FROM activities Activity INNER JOIN subjects Subject ON Subject.id = Activity.subject_id WHERE Activity.subject_id = {$subject_id}";
			
			if ($this->Auth->user('type') != "Administrador")
				$query .= " AND (Subject.coordinator_id = {$this->Auth->user('id')} OR Subject.practice_responsible_id = {$this->Auth->user('id')})";
			$activities = $this->Activity->query($query);	
			$this->set('activities', $activities);
		}
	
		function delete($id = null) {
			$this->Activity->id = $id;
			$activity = $this->Activity->read();
			$subject_id = $activity['Subject']['id'];
			$this->Activity->query("DELETE FROM events WHERE activity_id = {$id}");
			$this->Activity->delete($id);
			$this->Session->setFlash('La actividad ha sido eliminada correctamente');
			$this->redirect(array('controller' => 'subjects', 'action' => 'view', $subject_id));
		}
		
		function find_activities_by_name(){
			App::import('Sanitize');
			$q = '%'.Sanitize::escape($this->params['url']['q']).'%';
			$activities = $this->Activity->find('all', array('conditions' => array( 'Activity.name LIKE' => $q)));
			$this->set('activities', $activities);
		}
		
		function delete_student($activity_id = null, $group_id = null, $student_id = null){
			if (($activity_id != null) && ($group_id != null) && ($student_id != null)){
				$this->Activity->query("DELETE FROM registrations WHERE group_id = {$group_id} AND activity_id = {$activity_id} AND student_id = {$student_id}");
				$this->set('activity_id', $activity_id);
				$this->set('group_id', $group_id);
				$this->set('student_id', $student_id);
				$this->set('success', true);
			}
			
		}
		
		function send_alert($activity_id=null, $group_id=null, $message=null){
      $activity = $this->Activity->findById($activity_id);
      $coordinator_id = $activity['Subject']['coordinator_id'];
      $responsible_id = $activity['Subject']['practice_responsible_id'];
      $user_can_send_alerts = $this->Activity->Subject->Coordinator->can_send_alerts($this->Auth->user('id'), $activity_id, $group_id);
      
		  if (($coordinator_id == $this->Auth->user('id')) || ($responsible_id == $this->Auth->user('id')) || ($this->Auth->user('type') == "Administrador") || ($user_can_send_alerts == true)) {
  			if (($activity_id != null) && ($group_id != null)){
  				App::import('Sanitize');
  				$message = Sanitize::escape($message);
  				$this->Email->from = 'Academic <noreply@ulpgc.es>';
				
  				$students = $this->Activity->query("SELECT Student.* FROM users Student INNER JOIN registrations Registration ON Registration.student_id = Student.id WHERE Registration.activity_id = {$activity_id} AND Registration.group_id = {$group_id}");
				
  				$emails = array();
  				foreach($students as $student):
  					if ($student['Student']['username'] != null)
  						array_push($emails, $student['Student']['username']);
  				endforeach;
				
  				$this->Email->to = implode(",", array_unique($emails));
  				$this->Email->subject = "Alta en Academic";
  				$this->Email->send($message);
  				$this->set('success', true);
  			}
			}
		}
		
		function _get_subject(){
			if ($this->params['action'] == 'add'){
				if (!empty($this->data)){
					if (isset($this->data['Activity']))
						return $this->Activity->Subject->find('first', array('conditions' => array("Subject.id" => $this->data['Activity']['subject_id'])));
				 } else {
					if (isset($this->params['pass']['0']))
						return $this->Activity->Subject->find('first', array('conditions' => array("Subject.id" => $this->params['pass']['0'])));
				}
			} else {
				if (!empty($this->data)){
					if (isset($this->data['Activity']))
						return $this->Activity->find('first', array('conditions' => array("Activity.id" => $this->data['Activity']['id'])));
				} else {
					return $this->Activity->find('first', array('conditions' => array("Activity.id" => $this->params['pass']['0'])));
				}
			}
			
			return null;
		}
		
		function view_students($activity_id = null, $group_id = null){
			$activity = $this->Activity->findById($activity_id);
			$this->set('activity', $activity);
			$this->set('subject', $this->Activity->Subject->findById($activity['Subject']['id']));
			$this->set('group', $this->Activity->Subject->Group->findById($group_id));
			$this->set('students', $this->Activity->query("SELECT Student.* FROM users Student INNER JOIN registrations Registration ON Registration.student_id = Student.id WHERE Registration.activity_id = {$activity_id} AND Registration.group_id = {$group_id}"));
			$this->set('user_can_send_alerts', $this->Activity->Subject->Coordinator->can_send_alerts($this->Auth->user('id'), $activity_id, $group_id));
		}
	
		function _authorize() {
			parent::_authorize();
			
			$administrator_actions = array('add', 'edit', 'delete', 'delete_student');
			
			$this->set('section', 'courses');
			if ((array_search($this->params['action'], $administrator_actions) !== false) && ($this->Auth->user('type') != "Administrador")) {
				$user_id = $this->Auth->user('id');
				$subject = $this->_get_subject();
				
				if (($subject['Subject']['coordinator_id'] != $user_id) && ($subject['Subject']['practice_responsible_id'] != $user_id))
 					return false;
			}
				
			return true;
		}
	}
?>