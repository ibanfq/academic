<?php
	class EventsController extends AppController {
		var $name = 'Events';
		var $paginate = array('limit' => 10, 'order' => array('activity.initial_date' => 'asc'));
		var $helpers = array('Ajax', 'activityHelper');
		
		function schedule($course_id) {
			
			$this->set('section', 'courses');
			$this->set('events_schedule', '1');
			$this->set('user_id', $this->Auth->user('id'));
			
			$this->Event->Activity->Subject->Course->id = $course_id;
			$course = $this->Event->Activity->Subject->Course->read();
			
			$classrooms = array();
            foreach($this->Event->Classroom->find('all', array('fields' => array('Classroom.id', 'Classroom.name'), 'recursive' => 0, 'order' => array('Classroom.name'))) as $classroom) {
				$classrooms["{$classroom['Classroom']['id']}"] = $classroom['Classroom']['name'];
			}
			$this->set('classrooms', $classrooms);
			$this->set('subjects', $course['Subject']);
			$this->set('course', $course);
		}
		
		function get($classroom_id = null){
			$events = $this->Event->query("SELECT DISTINCT events.id, events.initial_hour, events.final_hour, events.activity_id, activities.name AS `activity`, activities.type AS 'type', events.group_id, groups.name AS `group`, subjects.acronym AS `acronym` FROM events INNER JOIN activities ON activities.id = events.activity_id INNER JOIN groups ON groups.id = events.group_id INNER JOIN subjects ON subjects.id = activities.subject_id WHERE events.classroom_id = {$classroom_id}");
			
			$this->set('events', $events);
		}
		
		function get_by_subject($subject_id = null){
			$events = $this->Event->query("SELECT DISTINCT events.id, events.initial_hour, events.final_hour, events.activity_id, activities.name AS `activity`, activities.type AS 'type', events.group_id, groups.name AS `group`, subjects.acronym AS `acronym` FROM events INNER JOIN activities ON activities.id = events.activity_id INNER JOIN groups ON groups.id = events.group_id INNER JOIN subjects ON subjects.id = activities.subject_id WHERE activities.subject_id = {$subject_id}");
			
			$this->set('events', $events);
		}
		
		function get_by_level($level = null){
			$events = $this->Event->query("SELECT DISTINCT events.id, events.initial_hour, events.final_hour, events.activity_id, activities.name AS `activity`, activities.type AS 'type', events.group_id, groups.name AS `group`, subjects.acronym AS `acronym` FROM events INNER JOIN activities ON activities.id = events.activity_id INNER JOIN groups ON groups.id = events.group_id INNER JOIN subjects ON subjects.id = activities.subject_id WHERE subjects.level = '{$level}'");
			
			$this->set('events', $events);
			
		}
		
		function _getScheduled($activity_id, $group_id) {
			$query = "SELECT sum(duration) as scheduled from events Event WHERE activity_id = {$activity_id} AND group_id = {$group_id}";
			
			$event = $this->Event->query($query);
			
			if ((isset($event[0])) && (isset($event[0][0])) && isset($event[0][0]["scheduled"]))
				return $event[0][0]["scheduled"];
			else
				return 0;
		}
		
		function _getDuration($initial_hour, $final_hour) {
			$date_components = split("-", $initial_hour->format('Y-m-d-H-i-s'));
			$initial_timestamp = mktime($date_components[3],$date_components[4],$date_components[5], $date_components[1], $date_components[2], $date_components[0]);
			
			$date_components = split("-", $final_hour->format('Y-m-d-H-i-s'));
			$final_timestamp = mktime($date_components[3],$date_components[4],$date_components[5], $date_components[1], $date_components[2], $date_components[0]);
			
			return ($final_timestamp - $initial_timestamp) / 3600.0;
		}
		
		function add($finished_at = null, $frequency = null) {
			if (($finished_at != null) && ($frequency != null)) {
				$initial_hour = new DateTime($this->data['Event']['initial_hour']);
				$final_hour = new DateTime($this->data['Event']['final_hour']);
				$finished_at = new DateTime($this->_parse_date($finished_at, "-"));
				
				$scheduled = $this->_getScheduled($this->data['Event']['activity_id'], $this->data['Event']['group_id']);
				
				$this->data['Event']['owner_id'] = $this->Auth->user('id');
				$events = array();
				$activity = $this->Event->Activity->find('first', array('conditions' => array( 'Activity.id' => $this->data['Event']['activity_id'])));
				
				$duration = $activity['Activity']['duration'];
				
				while ($finished_at->format('Ymd') >= $initial_hour->format('Ymd')) {
					if ((($scheduled + ($this->_getDuration($initial_hour, $final_hour))) <= $duration) && ($this->Event->save($this->data))) {
						$current_event = $this->Event->read();
						
						$scheduled += $current_event['Event']['duration'];
						if (!isset($this->data['Event']['parent_id']))
							$this->data['Event']['parent_id'] = $current_event['Event']['id'];
						array_push($events, $current_event);
						$this->_add_days($initial_hour, $frequency);
						$this->_add_days($final_hour, $frequency);
						$this->data['Event']['initial_hour'] = $initial_hour->format('Y-m-d H:i:s');
						$this->data['Event']['final_hour'] = $final_hour->format('Y-m-d H:i:s');
						$this->Event->id = null;
						$this->data['Event']['id'] = null;
					}
					else 
					{
						if (($scheduled + ($this->_getDuration($initial_hour, $final_hour))) > $duration) {
							$this->set('eventExceedDuration', true);
						} 
						else {
							$event = $this->Event->read();
							$activity = $this->Event->Activity->find('first', array('conditions' => array('Activity.id' => $event['Event']['activity_id'])));
							$this->set('event', $event);
							$this->set('activity', $activity);
						}
						
						$this->Event->query("DELETE FROM events WHERE id = {$this->data['Event']['parent_id']} OR parent_id = {$this->data['Event']['parent_id']}");
						unset($events);
						
						break;
					}
				}
				
				if (isset($events)) {
					$subject = $this->Event->Activity->Subject->find('first', array('conditions' => array('Subject.id' => $events[0]['Activity']['subject_id'])));
					$this->set('events', $events);
					$this->set('subject', $subject);
				} 
			}
			else {
				$this->data['Event']['owner_id'] = $this->Auth->user('id');
				if ($this->Event->save($this->data)){
					$this->set('success', true);
					$event = $this->Event->read();
					$subject = $this->Event->Activity->Subject->find('first', array('conditions' => array('Subject.id' => $event['Activity']['subject_id'])));
					
					$this->set('events', array($event));
					$this->set('subject', $subject);
				}
				else {
					if ($this->Event->id == -1){
						$this->set('eventExceedDuration', true);
					}
					else {
						$event = $this->Event->read();
						$activity = $this->Event->Activity->find('first', array('conditions' => array('Activity.id' => $event['Activity']['id'])));
						$this->set('event', $event);
						$this->set('activity', $activity);
					}
				}
			}
		}
		
		function edit($id = null) {
			$this->Event->id = $id;
			$event = $this->Event->read();
			$subject = $this->Event->Activity->Subject->find('first', array('conditions' => array('Subject.id' => $event['Activity']['subject_id'])));
			$uid = $this->Auth->user('id');

			if (($event['Event']['owner_id'] == $this->Auth->user('id')) || ($this->Auth->user('type') == "Administrador") || ($uid == $subject['Subject']['coordinator_id']) || ($uid == $subject['Subject']['practice_responsible_id'])) {
				if ($this->Auth->user('type') == "Administrador") {
					foreach($this->Event->Classroom->find('all', array('fields' => array('Classroom.id', 'Classroom.name'), 'recursive' => 0, 'order' => array('Classroom.name'))) as $classroom) {
	                    $classrooms["{$classroom['Classroom']['id']}"] = $classroom['Classroom']['name'];
	                }
	                $this->set('classrooms', $classrooms);
				}
				$this->set('event', $event);
            }
		}
		
		function update($id, $deltaDays, $deltaMinutes, $resize = null) {
			$this->Event->id = $id;
			$event = $this->Event->read();
			$subject = $this->Event->Activity->Subject->find('first', array('conditions' => array('Subject.id' => $event['Activity']['subject_id'])));
			$uid = $this->Auth->user('id');
			if (($event['Event']['owner_id'] == $this->Auth->user('id')) || ($this->Auth->user('type') == "Administrador") || ($uid == $subject['Subject']['coordinator_id']) || ($uid == $subject['Subject']['practice_responsible_id'])) {
				
				if ($resize == null) {
					$initial_hour = date_create($event['Event']['initial_hour']);
					$this->_add_days($initial_hour, $deltaDays, $deltaMinutes);
					$event['Event']['initial_hour'] = $initial_hour->format('Y-m-d H:i:s');
				}
			
				$final_hour = date_create($event['Event']['final_hour']);
				$this->_add_days($final_hour, $deltaDays, $deltaMinutes);
				$event['Event']['final_hour'] = $final_hour->format('Y-m-d H:i:s');

				if (!($this->Event->save($event))){
					$event = $this->Event->read();
					$this->set('event', $event);
				}
			} else
				$this->set('notAllowed', true);
		}
		
		
		function delete($id=null) {
			$this->Event->id = $id;
			$event = $this->Event->read();
			
			$ids = $this->Event->query("SELECT Event.id FROM events Event where Event.id = {$id} OR Event.parent_id = {$id}");
			$this->Event->query("DELETE FROM events WHERE id = {$id} OR parent_id = {$id}");
			$this->set('events', $ids);
		}
		
		function update_classroom($event_id = null, $classroom_id = null, $teacher_id = null, $teacher_2_id = null) {
			if (($this->Auth->user('type') == "Administrador") && ($classroom_id != null) && ($teacher_id != null) && ($event_id != null)) {
				$this->Event->id = $event_id;
				$event = $this->Event->read();
				$event['Event']['classroom_id'] = $classroom_id;
				if ($this->Event->save($event)) {
					$this->update_teacher($event_id, $teacher_id, $teacher_2_id);
				} else if ($this->Event->id != -1) {
					$event = $this->Event->read();
					$activity = $this->Event->Activity->find('first', array('conditions' => array('Activity.id' => $event['Activity']['id'])));
					$this->set('event', $event);
					$this->set('activity', $activity);
				}
			}
		}
		
		function update_teacher($event_id = null, $teacher_id = null, $teacher_2_id = null) {
			if (($teacher_id != null) && ($event_id != null)) {
				$teacher_id = trim("{$teacher_id}");
				if ($teacher_2_id !== null) {
					$teacher_2_id = trim("{$teacher_2_id}");
				}
				
				$events = $this->Event->query("SELECT id FROM events as Event where id = {$event_id} or parent_id = {$event_id}");
				$events_ids = Set::extract('/Event/id', $events);
				
				$this->Event->updateAll(
					array(
						'Event.teacher_id' => $teacher_id,
						'Event.teacher_2_id' => $teacher_2_id
					),
					array('Event.id' => $events_ids)
				);
				
				$this->loadModel('AttendanceRegister');
				$this->AttendanceRegister->updateAll(
					array(
						'AttendanceRegister.teacher_id' => $teacher_id,
						'AttendanceRegister.teacher_2_id' => $teacher_2_id
					),
					array('AttendanceRegister.event_id' => $events_ids)
				);
				
				$this->Event->query("UPDATE events SET teacher_2_id = '{$teacher_2_id}' WHERE id = {$event_id} OR parent_id = {$event_id}");
							  
				$this->set('ok', true);
			}
		}
		
		function view($id) {
			$this->Event->id = $id;
			$event = $this->Event->read();
			$this->set('event', $this->Event->read());
			$this->set('subject', $this->Event->Activity->Subject->find('first', array('conditions' => array('Subject.id' => $event['Activity']['subject_id']))));
		}
		
		function register_student($subject_id = null) {
			$this->set("section", "my_subjects");
			
			
			$this->set('subject', $this->Event->Activity->Subject->find('first', array('conditions' => array('Subject.id' => $subject_id))));
			
			$events = $this->Event->query("SELECT DATEDIFF(MIN(Event.initial_hour), CURDATE()) as days_to_start, Activity.id, Activity.name, `Group`.id, `Group`.name, `Group`.capacity, Activity.duration, Activity.inflexible_groups FROM events Event INNER JOIN activities Activity ON Activity.id = Event.activity_id INNER JOIN `groups` `Group` ON `Group`.id = Event.group_id WHERE Activity.subject_id = `Group`.subject_id AND Activity.subject_id = {$subject_id} GROUP BY Activity.id, `Group`.id ORDER BY Activity.id, `Group`.id");
			
			$activities_groups = array();
			foreach ($events as $event):
				$busy_capacity = $this->Event->query("SELECT count(*) as busy_capacity FROM registrations WHERE group_id = {$event['Group']['id']} AND activity_id = {$event['Activity']['id']}");
				$closed = $event['Activity']['inflexible_groups'] && $event[0]['days_to_start'] <= Activity::DAYS_TO_BLOCK_CHANGING_GROUP;
				
				if (isset($activities_groups[$event['Activity']['id']])){
					$activities_groups[$event['Activity']['id']]['Groups'][$event['Group']['id']] = array('name' => $event['Group']['name'], 'id' => $event['Group']['id'], 'free_seats' => $event['Group']['capacity'] - $busy_capacity[0][0]['busy_capacity'], 'capacity' => $event['Group']['capacity'], 'closed' => $closed);
				}
				else {
					$activities_groups[$event['Activity']['id']] = array('id' => $event['Activity']['id'],'name' => $event['Activity']['name'], 'duration' => $event['Activity']['duration'], 'groups_closed' => false, 'Groups' => array($event['Group']['id'] => array('name' => $event['Group']['name'], 'id' => $event['Group']['id'], 'free_seats' => $event['Group']['capacity'] - $busy_capacity[0][0]['busy_capacity'], 'capacity' => $event['Group']['capacity'], 'closed' => $closed)));
				}
			endforeach;
			
			$student_groups_activities = $this->Event->query("SELECT activity_id, group_id FROM registrations WHERE student_id = {$this->Auth->user('id')}");
			
			$student_groups = array();
			foreach ($student_groups_activities as $sga):
				$student_groups[$sga['registrations']['activity_id']] = $sga['registrations']['group_id'];
				if ($sga['registrations']['group_id'] != -1 && isset($activities_groups[$sga['registrations']['activity_id']]['Groups'][$sga['registrations']['group_id']])) {
					$activities_groups[$sga['registrations']['activity_id']]['groups_closed'] = $activities_groups[$sga['registrations']['activity_id']]['Groups'][$sga['registrations']['group_id']]['closed'];
				}
			endforeach;
			
			foreach ($activities_groups as $activity):
				if (!$activity['groups_closed']) {
					//There is an open group?
					$activities_groups[$activity['id']]['groups_closed'] = true;
					foreach ($activity['Groups'] as $group) {
						if (!$group['closed']) {
							$activities_groups[$activity['id']]['groups_closed'] = false;
							break;
						}
					}
				}
			endforeach;
			
			$this->set('activities_groups', $activities_groups);
			$this->set('student_groups', $student_groups);
		}
		
		
		function view_info($activity_id = null, $group_id = null) {
			$activity = $this->Event->Activity->find('first', array('conditions' => array('Activity.id' => $activity_id)));
			
			$events = $this->Event->query("SELECT DISTINCT DATE_FORMAT(Event.initial_hour, '%w') AS day, DATE_FORMAT(Event.initial_hour,'%H:%i') AS initial_hour, DATE_FORMAT(Event.final_hour,'%H:%i') AS final_hour FROM events Event WHERE activity_id = {$activity_id} AND group_id = {$group_id} ORDER BY day, initial_hour");
			
			$event_min_date = $this->Event->Activity->query("SELECT MIN(Event.initial_hour) as initial_date FROM events Event WHERE activity_id = {$activity_id} AND group_id = {$group_id}");
			
			$event_max_date = $this->Event->query("SELECT MAX(Event.initial_hour) as final_date FROM events Event WHERE activity_id = {$activity_id} AND group_id = {$group_id}");
			
			$this->set('events', $events);
			$this->set('activity', $activity);
			$this->set('initial_date', $event_min_date[0]);
			$this->set('final_date', $event_max_date[0]);
		}
		
		function calendar_by_classroom(){
			$this->layout = 'public';
			
			$classrooms = array();
			foreach($this->Event->Classroom->find('all', array('order' => array('Classroom.name'))) as $classroom):
				$classrooms["{$classroom['Classroom']['id']}"] = $classroom['Classroom']['name'];
			endforeach;

			$this->set('classrooms', $classrooms);
		}

		function calendar_by_subject(){
			$this->layout = 'public';
			$this->set('courses', $this->Event->Activity->Subject->Course->find('all'));
			$this->set('current_course', $this->Event->Activity->Subject->Course->current());
		}
		
		function calendar_by_level(){
			$this->layout = 'public';
		}
		
		function _add_days(&$date, $ndays, $nminutes = 0){
			$date_components = split("-", $date->format('Y-m-d-H-i-s'));
			$timestamp = mktime($date_components[3],$date_components[4],$date_components[5], $date_components[1], $date_components[2] + $ndays, $date_components[0]);
			$timestamp += ($nminutes * 60);
			$date_string = date('Y-m-d H:i:s', $timestamp);
			$date = new DateTime($date_string);
		}
		
		function _parse_date($date, $separator = "/"){
			$date_components = split($separator, $date);
			
			return count($date_components) != 3 ? false : date("Y-m-d", mktime(0,0,0, $date_components[1], $date_components[0], $date_components[2]));
		}
		
		function _authorize() {
			parent::_authorize();

			$private_actions = array('schedule', 'add', 'edit', 'update', 'delete', 'update_teacher');
			$student_actions = array('register_student');

			if ((array_search($this->params['action'], $private_actions) !== false) && ($this->Auth->user('type') != "Administrador") && ($this->Auth->user('type') != "Profesor")) {
				return false;
			}

			if ((array_search($this->params['action'], $student_actions) !== false) && ($this->Auth->user('type') != "Estudiante")) {
				return false;
			}

			return true;
		}
	}
?>
