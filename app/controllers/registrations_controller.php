<?php
class RegistrationsController extends AppController {
	var $name = 'Registrations';
	var $helpers = array('Ajax');
	
	function add($activity_id, $group_id){
		if ($this->_add($this->Auth->user('id'), $activity_id, $group_id)) {
			$this->set('success', true);
		} else {
			$this->set('error', "notEnoughSeatsError");
		}
	}
	
	function _add($user_id, $activity_id, $group_id, $force = false){
		$this->set('success', false);
		
		$this->Registration->id = null;
		
		$actual_group_id = $this->Registration->studentGroupRegistered($user_id, $activity_id);
		
        if ($actual_group_id) {
			if ($this->Registration->Activity->_existsAndGroupOpened($activity_id, $actual_group_id)) {
            	$group_opened = $this->Registration->Activity->_existsAndGroupOpened($activity_id, $group_id);
			} else {
				$group_opened = false;
			}
        } else {
			$group_opened = $this->Registration->Activity->_existsAndGroupNotEnded($activity_id, $group_id);
        }
		
		if ($group_opened && !$force) {
			$this->loadModel('GroupRequest');
			$group_opened = empty($this->GroupRequest->getUserRequests($user_id, null, $activity_id));
		}
		
		if ($group_opened) {
			$this->Registration->create();
			
			$registration = array('Registration' => array('group_id' => $group_id, 'activity_id' => $activity_id, 'student_id' => $user_id, 'id' => null));

 			if (($force || $this->Registration->enoughFreeSeats($activity_id, $group_id)) && ($this->Registration->save($registration))) {
				$this->loadModel('AttendanceRegister');
				$attendanceRegisters = $this->AttendanceRegister->find("all", array(
					'fields' => array('AttendanceRegister.*'),
					'conditions' => "AttendanceRegister.activity_id = {$activity_id} and AttendanceRegister.initial_hour > now()",
					'recursive' => 0
				));
				foreach ($attendanceRegisters as $attendanceRegister) {
					$this->AttendanceRegister->data = $attendanceRegister;
					$this->AttendanceRegister->id = $attendanceRegister['AttendanceRegister']['id'];
					$this->AttendanceRegister->updateStudents();
				}

				return true;
		  	}
		}
		return false;
	}
	
	function get_subject_free_seats($subject_id){
		$free_seats = $this->Registration->query("SELECT `Group`.id, Activity.id, `Group`.capacity - IFNULL(count(Registration.id), 0) AS free_seats FROM groups `Group` INNER JOIN activities Activity ON Activity.subject_id = `Group`.subject_id AND Activity.type = `Group`.type LEFT JOIN registrations Registration ON `Group`.id = Registration.group_id AND Activity.id = Registration.activity_id WHERE `Group`.subject_id = {$subject_id} GROUP BY Activity.id, `Group`.id");
		
		$this->set('free_seats', $free_seats);
	}
	
	function view_students_registered($activity_id = null, $group_id = null) {
		$registrations = $this->Registration->query("SELECT `Registration`.`group_id`, `Registration`.`activity_id`, `Registration`.`student_id`, `Registration`.`id`, `User`.`id`, `User`.`type`, `User`.`dni`, `User`.`first_name`, `User`.`last_name`, `User`.`username`, `User`.`phone`, `User`.`password` FROM `registrations` AS `Registration` LEFT JOIN `users` AS `User` ON (`User`.`id` = `Registration`.`student_id`) WHERE `activity_id` = {$activity_id} AND `group_id` = {$group_id} ORDER BY `User`.`last_name`, `User`.`first_name`");
		
		$auth_user_id = $this->Auth->user('id');
		
		$actual_group_id = $this->Registration->studentGroupRegistered($auth_user_id, $activity_id);
		$changes_closed = !$actual_group_id || $actual_group_id == $group_id;
		$changes_closed = $changes_closed || !$this->Registration->Activity->_existsAndGroupOpened($activity_id, $actual_group_id);
		$changes_closed = $changes_closed || !$this->Registration->Activity->_existsAndGroupOpened($activity_id, $group_id);

		$this->loadModel('GroupRequest');
		$group_requests = $this->GroupRequest->getUserRequests($this->Auth->user('id'), null, $activity_id, $group_id);
		$changes_requests = array();
		foreach ($group_requests as $request) {
			$request = $request['group_requests'];
			if ($request['student_id'] == $auth_user_id) {
				$changes_requests[$request['student_2_id']] = $request;
			} else {
				$changes_requests[$request['student_id']] = $request;
			}
		}
		
		$this->set('section', 'groups');
		$this->set('registrations', $registrations);
		$this->set('activity', $this->Registration->Activity->find("first", array('conditions' => array('Activity.id' => $activity_id))));
		$this->set('group', $this->Registration->Group->find("first", array('conditions' => array('`Group`.id' => $group_id))));
		$this->set('changes_closed', $changes_closed);
		$this->set('changes_requests', $changes_requests);
	}
	
	function request_add($activity_id, $user_id){
		$this->set('success', false);
		
		$auth_user_id = $this->Auth->user('id');
		
		$actual_group_id = $this->Registration->studentGroupRegistered($auth_user_id, $activity_id);
		$group_id = $this->Registration->studentGroupRegistered($user_id, $activity_id);
		
		if (!$actual_group_id || !$group_id) {
			$this->set('error', "notRegistered");
		} else if ($actual_group_id === $group_id) {
			$this->set('error', "sameGroup");
		} else {
			$group_opened = $this->Registration->Activity->_existsAndGroupOpened($activity_id, $actual_group_id);
			$group_opened = $group_opened && $this->Registration->Activity->_existsAndGroupOpened($activity_id, $group_id);
			
			$this->loadModel('GroupRequest');
			$group_requests = $group_opened? $this->GroupRequest->getUserRequests($auth_user_id, null, $activity_id, $group_id) : array();

			if (!$group_opened) {
				$this->set('error', "groupClosed");
			} else if (count($group_requests)) {
				$this->set('error', "groupWithRequests");
			} else {
				$this->loadModel('GroupRequest');
				$data = array(
					'activity_id' => $activity_id,
					'student_id' => $auth_user_id,
					'group_id' => $actual_group_id,
					'student_2_id' => $user_id,
					'group_2_id' => $group_id
				);
				
				$email_models = $this->Registration->query(
					"SELECT users.id, users.username, users.first_name, users.last_name, activities.id, activities.name, subjects.id, subjects.name, groups.id, groups.name, groups_2.id, groups_2.name FROM users"
					. " LEFT JOIN activities on activities.id = $activity_id"
					. " LEFT JOIN subjects on subjects.id = activities.subject_id"
					. " LEFT JOIN groups on groups.id = $group_id"
					. " LEFT JOIN groups groups_2 on groups_2.id = $actual_group_id"
					. " WHERE users.id = $user_id"
				);
				
				if (!empty($email_models) && $this->GroupRequest->save($data)){
					$user_2 = $this->Auth->user();
					$this->_notifyRequestAdded($email_models[0]['users'], $email_models[0]['activities'], $email_models[0]['subjects'], $user_2['User'], $email_models[0]['groups'], $email_models[0]['groups_2']);
					$this->set('success', true);
				}
			}
		}
	}
	
	function request_accept($activity_id, $user_id){
		$this->set('success', false);
		
		$auth_user_id = $this->Auth->user('id');
		
		$actual_group_id = $this->Registration->studentGroupRegistered($auth_user_id, $activity_id);
		$group_id = $this->Registration->studentGroupRegistered($user_id, $activity_id);
		
		if (!$actual_group_id || !$group_id) {
			$this->set('error', "notRegistered");
		} else if ($actual_group_id === $group_id) {
			$this->set('error', "sameGroup");
		} else {
			$group_opened = $this->Registration->Activity->_existsAndGroupOpened($activity_id, $actual_group_id);
			$group_opened = $group_opened && $this->Registration->Activity->_existsAndGroupOpened($activity_id, $group_id);
			
			$this->loadModel('GroupRequest');
			$request = null;
			
			if ($group_opened) {
				$group_requests = $this->GroupRequest->getUserRequests($auth_user_id, null, $activity_id);
				foreach ($group_requests as $user_request) {
					if ($user_request['group_requests']['student_id'] == $user_id) {
						$request = $user_request;
						break;
					}
				}
			}
			
			if (!$request) {
				$this->set('error', "requestNotExists");
			} else {
				$request_id = $request['group_requests']['id'];
				$requests_ids = array();
				$requests_canceled = array();
				$users_to_load = array();
				$group_requests += $this->GroupRequest->getUserRequests($user_id, null, $activity_id);
				foreach ($group_requests as $user_request) {
					$id = $user_request['group_requests']['id'];
					if (!isset($requests_ids[$id])) {
						$requests_ids[$id]= $id;
						if ($id !== $request_id) {
							$requests_canceled[] = $user_request;
						}
						if ($user_request['group_requests']['student_id'] == $auth_user_id) {
							$user_to_load = $user_request['group_requests']['student_2_id'];
						} else {
							$user_to_load = $user_request['group_requests']['student_id'];
						}
						$users_to_load[$user_to_load] = $user_to_load;
					}
				}
				
				$email_models = $this->Registration->query(
					"SELECT activities.id, activities.name, subjects.id, subjects.name FROM activities"
					. " LEFT JOIN subjects on subjects.id = activities.subject_id"
					. " WHERE activities.id = $activity_id"
				);
				
				$users = $this->Registration->User->find("all", array(
					'fields' => array('User.id, User.username, User.first_name, User.last_name'),
					'conditions' => array('User.id' => $users_to_load),
					'recursive' => -1
				));
				
				if (!empty($email_models) && !empty($users) && $this->_add($auth_user_id, $activity_id, $group_id, true)) {
					$success = $this->_add($user_id, $activity_id, $actual_group_id, true);
					$success = $success & $this->GroupRequest->deleteAll(array('GroupRequest.id' => $requests_ids));
					
					$users = set::combine($users, '{n}.User.id', '{n}');
					$users[$this->Auth->user('id')] = $this->Auth->user();
					
					$this->_notifyRequestAccepted($users[$request['group_requests']['student_id']]['User'], $email_models[0]['subjects'], $email_models[0]['activities'], $this->Auth->user()['User']);
					foreach ($requests_canceled as $request_canceled) {
						if (in_array($request_canceled['group_requests']['student_id'], array($auth_user_id, $user_id))) {
							$user = $users[$request_canceled['group_requests']['student_2_id']];
							$user_2 = $users[$request_canceled['group_requests']['student_id']];
						} else {
							$user = $users[$request_canceled['group_requests']['student_id']];
							$user_2 = $users[$request_canceled['group_requests']['student_2_id']];
						}
						$this->_notifyRequestCanceled($user['User'], $email_models[0]['subjects'], $email_models[0]['activities'], $user_2['User']);
					}

					$this->set('success', $success);
				}
			}
		}
	}
	
	function request_cancel($activity_id, $user_id){
		$this->set('success', false);
		
		$auth_user_id = $this->Auth->user('id');
		
		$actual_group_id = $this->Registration->studentGroupRegistered($auth_user_id, $activity_id);
		$group_id = $this->Registration->studentGroupRegistered($user_id, $activity_id);
		
		if (!$actual_group_id || !$group_id) {
			$this->set('error', "notRegistered");
		} else if ($actual_group_id === $group_id) {
			$this->set('error', "sameGroup");
		} else {
			$group_opened = $this->Registration->Activity->_existsAndGroupOpened($activity_id, $actual_group_id);
			$group_opened = $group_opened && $this->Registration->Activity->_existsAndGroupOpened($activity_id, $group_id);
			
			$request = null;
			
			if ($group_opened) {
				$this->loadModel('GroupRequest');
				$group_requests = $this->GroupRequest->getUserRequests($auth_user_id, null, $activity_id, $group_id);
				foreach ($group_requests as $user_request) {
					if ($user_request['group_requests']['student_id'] ==  $user_id) {
						$request = $user_request;
						break;
					}
					if ($user_request['group_requests']['student_2_id'] ==  $user_id) {
						$request = $user_request;
						break;
					}
				}
			}

			if (!$request) {
				$this->set('error', "requestNotExists");
			} else {
				$email_models = $this->Registration->query(
					"SELECT users.id, users.username, users.first_name, users.last_name, activities.id, activities.name, subjects.id, subjects.name FROM users"
					. " LEFT JOIN activities on activities.id = $activity_id"
					. " LEFT JOIN subjects on subjects.id = activities.subject_id"
					. " WHERE users.id = $user_id"
				);
				
				if (!empty($email_models) && $this->GroupRequest->delete($request['group_requests']['id'])){
					$user_2 = $this->Auth->user();
					$this->_notifyRequestCanceled($email_models[0]['users'], $email_models[0]['activities'], $email_models[0]['subjects'], $user_2['User']);
					$this->set('success', true);
				}
			}
		}
	}
	
	function _authorize(){
		parent::_authorize();
		
		if (($this->Auth->user('type') != "Estudiante") && ($this->Auth->user('type') == "Becario"))
			return false;
	
		return true;
	}
	
	function _notifyRequestAdded($user, $subject, $activity, $user_2, $group, $group_2) {
		$this->Email->reset();
		$this->Email->from = 'Academic <noreply@ulpgc.es>';
		$this->Email->to = $user['username'];
		$this->Email->subject = "Cambio de grupo solicitado en Academic";
		$this->Email->sendAs = 'both';
		$this->Email->template = 'group_request_added';
		$this->set('user', $user);
		$this->set('subject', $subject);
		$this->set('activity', $activity);
		$this->set('user_2', $user_2);
		$this->set('group', $group);
		$this->set('group_2', $group_2);
		$this->Email->send();
	}
	
	function _notifyRequestAccepted($user, $subject, $activity, $user_2) {
		$this->Email->reset();
		$this->Email->from = 'Academic <noreply@ulpgc.es>';
		$this->Email->to = $user['username'];
		$this->Email->subject = "Cambio de grupo aceptado en Academic";
		$this->Email->sendAs = 'both';
		$this->Email->template = 'group_request_accepted';
		$this->set('user', $user);
		$this->set('subject', $subject);
		$this->set('activity', $activity);
		$this->set('user_2', $user_2);
		$this->Email->send();
	}
	
	function _notifyRequestCanceled($user, $subject, $activity, $user_2) {
		$this->Email->reset();
		$this->Email->from = 'Academic <noreply@ulpgc.es>';
		$this->Email->to = $user['username'];
		$this->Email->subject = "Cambio de grupo cancelado en Academic";
		$this->Email->sendAs = 'both';
		$this->Email->template = 'group_request_canceled';
		$this->set('user', $user);
		$this->set('subject', $subject);
		$this->set('activity', $activity);
		$this->set('user_2', $user_2);
		$this->Email->send();
	}
}
?>
