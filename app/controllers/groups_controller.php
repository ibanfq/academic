<?php
	class GroupsController extends AppController {
		var $name = 'Groups';
		var $paginate = array('limit' => 10, 'order' => array('group.initial_date' => 'asc'));
		var $helpers = array('html', 'javascript', 'dateHelper', 'Ajax');
	
		function add($subject_id = null){
			if (!empty($this->data)){
				if ($this->Group->save($this->data)){
					$this->Session->setFlash('El grupo se ha guardado correctamente');
					$this->redirect(array('controller' => 'subjects', 'action' => 'view', $this->data['Group']['subject_id']));
				}
				else{
					$subject = $this->Group->Subject->find('first', array('conditions' => array('Subject.id' => $this->data['Group']['subject_id'])));
					$this->set('subject', $subject);
					$this->set('subject_id', $this->data['Group']['subject_id']);
				}
			}
			else {
				if (is_null($subject_id)){
					$this->Session->setFlash('Está intentando realizar una acción no permitida.');
					$this->redirect(array('controller' => 'courses', 'action' => 'index'));
				}
				else
				{
					$subject = $this->Group->Subject->find('first', array('conditions' => array('Subject.id' => $subject_id)));
					$this->set('subject', $subject);
					$this->set('subject_id', $subject_id);
				}
			}
		}
	
		function view($id = null) {
			$this->Group->id = $id;
			$group = $this->Group->read();
			$subject = $this->Group->Subject->find('first', array('conditions' => array('Subject.id' => $group['Group']['subject_id'])));
			$this->set('group', $group);
			$this->set('subject', $subject);
		}
	
		function edit($id = null) {
			$this->Group->id = $id;
			if (empty($this->data)) {
				$this->data = $this->Group->read();
				$subject = $this->Group->Subject->find('first', array('conditions' => array('Subject.id' => $this->data['Group']['subject_id'])));
				$this->set('subject', $subject);
				$this->set('group', $this->data);
			} else {
				if ($this->Group->save($this->data)) {
					$this->Session->setFlash('El grupo se ha modificado correctamente.');
					$this->redirect(array('action' => 'view', $id));
				} else {
					$subject = $this->Group->Subject->find('first', array('conditions' => array('Subject.id' => $this->data['Group']['subject_id'])));
					$this->set('subject', $subject);
					$this->set('group', $this->data);
				}
			}
		}
	
		function delete($id = null) {
			$this->Group->id = $id;
			$group = $this->Group->read();
			$subject_id = $group['Subject']['id'];
			$this->Group->query("DELETE FROM events WHERE group_id = {$id}");
			$this->Group->delete($id);
			$this->Session->setFlash('El grupo ha sido eliminado correctamente');
			$this->redirect(array('controller' => 'subjects', 'action' => 'view', $subject_id));
		}
	
		function get($activity_id = null){
			$activity = $this->Group->query("SELECT Activity.id, Activity.type, Activity.subject_id FROM activities Activity WHERE id = {$activity_id}");

			$groups = $this->Group->query("SELECT DISTINCT `Group`.* FROM `groups` `Group` WHERE `Group`.subject_id = {$activity[0]['Activity']['subject_id']} AND `Group`.type = '{$activity[0]['Activity']['type']}' AND `Group`.id NOT IN (SELECT `Group`.id FROM `groups` `Group` INNER JOIN (SELECT activity_id, group_id, sum(duration) as scheduled from events WHERE activity_id = {$activity_id} group by activity_id, group_id) Event ON `Group`.id = Event.group_id INNER JOIN activities Activity ON Activity.id = Event.activity_id WHERE `Group`.type = '{$activity[0]['Activity']['type']}' AND `Group`.subject_id = {$activity[0]['Activity']['subject_id']} AND Activity.duration <= scheduled)");
			
			$this->set('groups', $groups);
		}
	
		function _get_subject(){
			if ($this->params['action'] == 'add'){
				if (!empty($this->data)){
					if (isset($this->data['Group']))
						return $this->Group->Subject->find('first', array('conditions' => array("Subject.id" => $this->data['Group']['subject_id'])));
				 } else {
					if (isset($this->params['pass']['0']))
						return $this->Group->Subject->find('first', array('conditions' => array("Subject.id" => $this->params['pass']['0'])));
				}
			} else {
				if (!empty($this->data)){
					if (isset($this->data['Group']))
						return $this->Group->find('first', array('conditions' => array("Group.id" => $this->data['Group']['id'])));
				} else {
					return $this->Group->find('first', array('conditions' => array("Group.id" => $this->params['pass']['0'])));
				}
			}
			
			return null;
		}
	
		function _authorize() {
			parent::_authorize();
			$administrator_actions = array('add', 'edit', 'delete');
			
			$this->set('section', 'courses');
		
			if ((array_search($this->params['action'], $administrator_actions) !== false) && ($this->Auth->user('type') != "Administrador")) {
				
				if (($this->params['action'] == 'add') || ($this->params['action'] == 'delete'))
					return false;

				$user_id = $this->Auth->user('id');
				$subject = $this->_get_subject();
				
				if (($subject['Subject']['coordinator_id'] != $user_id) && ($subject['Subject']['practice_responsible_id'] != $user_id))
 					return false;
			}
			
			return true;
		}
	}
?>