<?php
class EventsController extends AppController {
    var $name = 'Events';
    var $paginate = array('limit' => 10, 'order' => array('activity.initial_date' => 'asc'));
    var $helpers = array('Ajax', 'activityHelper', 'Text');
    
    function schedule($course_id) {
        $course_id = $course_id === null ? null : intval($course_id);
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
    
    function get($classroom_id = null) {
        $classroom_id = $classroom_id === null ? null : intval($classroom_id);
        $db = $this->Event->getDataSource();
        $events = $this->Event->query("SELECT DISTINCT Event.id, Event.parent_id, Event.initial_hour, Event.final_hour, Event.activity_id, Activity.name, Activity.type, Event.group_id, `Group`.name, Subject.acronym FROM events Event INNER JOIN activities Activity ON Activity.id = Event.activity_id INNER JOIN groups `Group` ON `Group`.id = Event.group_id INNER JOIN subjects Subject ON Subject.id = Activity.subject_id WHERE Event.classroom_id = {$db->value($classroom_id)}");
        
        $this->set('authorizeDelete', array($this, '_authorizeDelete'));
        $this->set('events', $events);
    }
    
    function get_by_subject($subject_id = null) {
        $subject_id = $subject_id === null ? null : intval($subject_id);
        $db = $this->Event->getDataSource();
        $events = $this->Event->query("SELECT DISTINCT Event.id, Event.parent_id, Event.initial_hour, Event.final_hour, Event.activity_id, Activity.name, Activity.type, Event.group_id, `Group`.name, Subject.acronym FROM events Event INNER JOIN activities Activity ON Activity.id = Event.activity_id INNER JOIN groups `Group` ON `Group`.id = Event.group_id INNER JOIN subjects Subject ON Subject.id = Activity.subject_id WHERE Activity.subject_id = {$db->value($subject_id)}");
        
        $this->set('authorizeDelete', array($this, '_authorizeDelete'));
        $this->set('events', $events);
    }
    
    function get_by_level($level = null) {
        $db = $this->Event->getDataSource();
        $events = $this->Event->query("SELECT DISTINCT Event.id, Event.parent_id, Event.initial_hour, Event.final_hour, Event.activity_id, Activity.name, Activity.type, Event.group_id, `Group`.name, Subject.acronym FROM events Event INNER JOIN activities Activity ON Activity.id = Event.activity_id INNER JOIN groups `Group` ON `Group`.id = Event.group_id INNER JOIN subjects Subject ON Subject.id = Activity.subject_id WHERE Subject.level = {$db->value($level)}");
        
        $this->set('authorizeDelete', array($this, '_authorizeDelete'));
        $this->set('events', $events);
    }

    function get_by_degree_and_level($degree = null, $level = null) {
        $db = $this->Event->getDataSource();
        $events = $this->Event->query("SELECT DISTINCT Event.id, Event.parent_id, Event.initial_hour, Event.final_hour, Event.activity_id, Activity.name, Activity.type, Event.group_id, `Group`.name, Subject.acronym FROM events Event INNER JOIN activities Activity ON Activity.id = Event.activity_id INNER JOIN groups `Group` ON `Group`.id = Event.group_id INNER JOIN subjects Subject ON Subject.id = Activity.subject_id WHERE Subject.degree = {$db->value($degree)} AND Subject.level = {$db->value($level)}");
        
        $this->set('authorizeDelete', array($this, '_authorizeDelete'));
        $this->set('events', $events);
    }

    function get_by_teacher($teacher_id = null) {
        $this->loadModel('User');
        $this->User->id = $teacher_id;
        $events = array();
        if ($this->User->read()) {
            $events = $this->User->getEvents();
        }
        $this->set('authorizeDelete', array($this, '_authorizeDelete'));
        $this->set('events', $events);
    }

    function _getScheduled($activity_id, $group_id) {
        $activity_id = $activity_id === null ? null : intval($activity_id);
        $group_id = $group_id === null ? null : intval($group_id);
        $db = $this->Event->getDataSource();
        $query = "SELECT sum(duration) as scheduled from events Event WHERE activity_id = {$db->value($activity_id)} AND group_id = {$db->value($group_id)}";
        
        $event = $this->Event->query($query);
        
        if ((isset($event[0])) && (isset($event[0][0])) && isset($event[0][0]["scheduled"])) {
            return $event[0][0]["scheduled"];
        } else {
            return 0;
        }
    }
    
    function _getDuration($initial_hour, $final_hour) {
        $date_components = split("-", $initial_hour->format('Y-m-d-H-i-s'));
        $initial_timestamp = mktime($date_components[3],$date_components[4],$date_components[5], $date_components[1], $date_components[2], $date_components[0]);
        
        $date_components = split("-", $final_hour->format('Y-m-d-H-i-s'));
        $final_timestamp = mktime($date_components[3],$date_components[4],$date_components[5], $date_components[1], $date_components[2], $date_components[0]);
        
        return ($final_timestamp - $initial_timestamp) / 3600.0;
    }

    function _addDuration($initial_hour, $hours) {
        $minutes = round($hours * 60);
        $new_initial_hour = clone $initial_hour;
        $new_initial_hour->add(new DateInterval("PT{$minutes}M"));
        return $new_initial_hour;
    }
    
    function add($finished_at = null, $frequency = null) {
        $events = array();
        $invalidFields = array();

        if (($finished_at != null) && ($frequency != null)) {
            $initial_hour = new DateTime($this->data['Event']['initial_hour']);
            $final_hour = new DateTime($this->data['Event']['final_hour']);
            $finished_at = new DateTime($this->_parse_date($finished_at, "-"));
            
            $scheduled = $this->_getScheduled($this->data['Event']['activity_id'], $this->data['Event']['group_id']);
            
            $this->data['Event']['owner_id'] = $this->Auth->user('id');
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
                } else {
                    if (!empty($events) && $this->data['Event']['parent_id'] != null) {
                        $this->Event->query("DELETE FROM events WHERE id = {$this->data['Event']['parent_id']} OR parent_id = {$this->data['Event']['parent_id']}");
                    }

                    unset($events);                        
                    break;
                }
            }
        } else {
            $this->data['Event']['owner_id'] = $this->Auth->user('id');
            if ($this->Event->save($this->data)) {
                array_push($events, $this->Event->read());
            }
        }

        if (!empty($events)) {
                $subject = $this->Event->Activity->Subject->find('first', array('conditions' => array('Subject.id' => $events[0]['Activity']['subject_id'])));
                $this->set('success', true);
                $this->set('events', $events);
                $this->set('subject', $subject);
        } else {
            $invalidFields = $this->Event->invalidFields();
            if ($this->Event->booking_id_overlaped) {
                $this->loadModel('Booking');
                $this->Booking->id = $this->Event->booking_id_overlaped;
                $booking_overlaped = $this->Booking->read();
                $this->set('booking_overlaped', $booking_overlaped);
            } elseif ($this->Event->event_id_overlaped) {
                $this->Event->id = $this->Event->event_id_overlaped;
                $event_overlaped = $this->Event->read();
                $activity_overlaped = $this->Event->Activity->find('first', array('conditions' => array('Activity.id' => $event_overlaped['Activity']['id'])));
                $this->set('event_overlaped', $event_overlaped);
                $this->set('activity_overlaped', $activity_overlaped);
            }
            $this->set('invalidFields', $invalidFields);
        }
        $this->set('authorizeDelete', array($this, '_authorizeDelete'));
    }
    
    function copy($id) {
        $id = $id === null ? null : intval($id);
        $events = array();
        $event = $this->Event->find('first', array('conditions' => array('Event.id' => $id), 'recursive' => -1));
        if (!$event) {
            $this->set('notAllowed', true);
        } else {
            $event_initial_hour = new DateTime($event['Event']['initial_hour']);
            if (!empty($this->params['named']['initial_hour'])) {
                $initial_hour = new DateTime($this->params['named']['initial_hour']);
            } else {
                $initial_hour = $event_initial_hour;
            }
            if (!empty($this->params['named']['classroom'])) {
                $classroom_id = intval($this->params['named']['classroom']);
            } else {
                $classroom_id = $event['Event']['classroom_id'];
            }
            $duration = $this->_getDuration($event_initial_hour, new DateTime($event['Event']['final_hour']));
            $final_hour = $this->_addDuration($initial_hour, $duration);
            $this->data = array(
                'id'           => null,
                'group_id'     => $event['Event']['group_id'],
                'activity_id'  => $event['Event']['activity_id'],
                'teacher_id'   => $event['Event']['teacher_id'],
                'initial_hour' => $initial_hour->format('Y-m-d H:i:s'),
                'final_hour'   => $final_hour->format('Y-m-d H:i:s'),
                'classroom_id' => $classroom_id,
                'duration'     => $event['Event']['duration'],
                'owner_id'     => $this->Auth->user('id'),
                'teacher_2_id' => $event['Event']['teacher_2_id'],
                'show_tv'      => $event['Event']['show_tv']
            );
            if ($this->Event->save($this->data)) {
                array_push($events, $this->Event->read());
                $subject = $this->Event->Activity->Subject->find('first', array('conditions' => array('Subject.id' => $events[0]['Activity']['subject_id'])));
                $this->set('success', true);
                $this->set('events', $events);
                $this->set('subject', $subject);
            } else {
                $invalidFields = $this->Event->invalidFields();
                if ($this->Event->booking_id_overlaped) {
                    $this->loadModel('Booking');
                    $this->Booking->id = $this->Event->booking_id_overlaped;
                    $booking_overlaped = $this->Booking->read();
                    $this->set('booking_overlaped', $booking_overlaped);
                } elseif ($this->Event->event_id_overlaped) {
                    $this->Event->id = $this->Event->event_id_overlaped;
                    $event_overlaped = $this->Event->read();
                    $activity_overlaped = $this->Event->Activity->find('first', array('conditions' => array('Activity.id' => $event_overlaped['Activity']['id'])));
                    $this->set('event_overlaped', $event_overlaped);
                    $this->set('activity_overlaped', $activity_overlaped);
                }
                $this->set('invalidFields', $invalidFields);
            }
        }
        $this->set('authorizeDelete', array($this, '_authorizeDelete'));
    }        

    function edit($id = null) {
        $id = $id === null ? null : intval($id);
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
        $id = $id === null ? null : intval($id);
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

            if (($this->Event->save($event))) {
                $this->set('success', true);
            } elseif ($this->Event->booking_id_overlaped) {
                $this->loadModel('Booking');
                $this->Booking->id = $this->Event->booking_id_overlaped;
                $booking_overlaped = $this->Booking->read();
                $this->set('booking_overlaped', $booking_overlaped);
            } elseif ($this->Event->event_id_overlaped) {
                $this->Event->id = $this->Event->event_id_overlaped;
                $event_overlaped = $this->Event->read();
                $activity_overlaped = $this->Event->Activity->find('first', array('conditions' => array('Activity.id' => $event_overlaped['Activity']['id'])));
                $this->set('event_overlaped', $event_overlaped);
                $this->set('activity_overlaped', $activity_overlaped);
            }
        } else
            $this->set('notAllowed', true);
    }
    
    function delete($id=null) {
        $id = $id === null ? null : intval($id);
        $this->Event->id = $id;
        $event = $this->Event->read();
        $ids = array();
        if ($this->_authorizeDelete($event)) {
            $ids = $this->Event->query("SELECT Event.id FROM events Event where Event.id = {$id} OR Event.parent_id = {$id}");
            $this->Event->query("DELETE FROM events WHERE id = {$id} OR parent_id = {$id}");
        }
        $this->set('events', $ids);
    }
    
    function update_classroom($event_id = null, $classroom_id = null, $teacher_id = null, $teacher_2_id = null) {
        $event_id = $event_id === null ? null : intval($event_id);
        $classroom_id = $classroom_id === null ? null : intval($classroom_id);
        $teacher_id = $teacher_id === null ? null : intval($teacher_id);
        $teacher_2_id = $teacher_2_id === null ? null : intval($teacher_2_id);
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
        $event_id = $event_id === null ? null : intval($event_id);
        $teacher_id = $teacher_id === null ? null : intval($teacher_id);
        $teacher_2_id = $teacher_2_id === null ? null : intval($teacher_2_id);
        $event_show_tv = Configure::read('app.event.show_tv');

        if (($teacher_id != null) && ($event_id != null)) {
            $teacher_id = trim("{$teacher_id}");
            if ($teacher_2_id !== null) {
                $teacher_2_id = trim("{$teacher_2_id}");
            }
            
            $error = false;
            $events = $this->Event->query("SELECT id FROM events as Event where id = {$event_id} or parent_id = {$event_id}");
            $events_ids = Set::extract('/Event/id', $events);
    
            foreach ($events_ids as $event_id) {
                $this->Event->id = $event_id;
                $event = $this->Event->read();
                  
                $old_teacher_id = $event['Event']['teacher_id'];
                $old_teacher_2_id = $event['Event']['teacher_2_id'];
                $old_show_tv = $event['Event']['show_tv'];
                  
                $event['Event']['teacher_id'] = $teacher_id;
                $event['Event']['teacher_2_id'] = $teacher_2_id;
                if ($event_show_tv && isset($this->params['named']['show_tv'])) {
                    $event['Event']['show_tv'] = boolval($this->params['named']['show_tv']);
                }
      
                if (!$this->Event->save($event)) {
                    $error = true;
        
                    $this->Event->updateAll(
                        array(
                          'Event.teacher_id' => $old_teacher_id,
                          'Event.teacher_2_id' => $old_teacher_2_id,
                          'Event.show_tv' => $old_show_tv
                        ),
                        array('Event.id' => $events_ids)
                    );
        
                    if ($this->Event->id != -1) {
                        $event = $this->Event->read();
                        $activity = $this->Event->Activity->find('first', array('conditions' => array('Activity.id' => $event['Activity']['id'])));
                        $this->set('event', $event);
                        $this->set('activity', $activity);
                    }
        
                    break;
                }
            }
            
            if (!$error) {
                $this->loadModel('AttendanceRegister');
                $this->AttendanceRegister->updateAll(
                    array(
                        'AttendanceRegister.teacher_id' => $teacher_id,
                        'AttendanceRegister.teacher_2_id' => $teacher_2_id
                    ),
                    array('AttendanceRegister.event_id' => $events_ids)
                );

                $this->set('ok', true);
            }
        }
    }
    
    function view($id) {
        $id = $id === null ? null : intval($id);
        $this->Event->id = $id;
        $event = $this->Event->read();
        $this->set('event', $this->Event->read());
        $this->set('subject', $this->Event->Activity->Subject->find('first', array('conditions' => array('Subject.id' => $event['Activity']['subject_id']))));
    }
    
    function register_student($subject_id = null) {
        $subject_id = $subject_id === null ? null : intval($subject_id);
        $this->set("section", "my_subjects");
        
        $this->set('subject', $this->Event->Activity->Subject->find('first', array('conditions' => array('Subject.id' => $subject_id))));
        
        $auth_user_id = $this->Auth->user('id');
        
        $db = $this->Event->getDataSource();
        $events = $this->Event->query("SELECT DATEDIFF(MIN(Event.initial_hour), CURDATE()) as days_to_start, UNIX_TIMESTAMP(MAX(Event.final_hour)) - UNIX_TIMESTAMP() as time_to_end, Activity.id, Activity.name, `Group`.id, `Group`.name, `Group`.capacity, Activity.duration, Activity.inflexible_groups FROM events Event INNER JOIN activities Activity ON Activity.id = Event.activity_id INNER JOIN `groups` `Group` ON `Group`.id = Event.group_id WHERE Activity.subject_id = `Group`.subject_id AND Activity.subject_id = {$db->value($subject_id)} GROUP BY Activity.id, `Group`.id ORDER BY Activity.id, `Group`.id");
        
        $activities_groups = array();
        foreach ($events as $event):
            $busy_capacity = $this->Event->query("SELECT count(*) as busy_capacity FROM registrations WHERE group_id = {$db->value($event['Group']['id'])} AND activity_id = {$db->value($event['Activity']['id'])}");
            $ended = $event[0]['time_to_end'] < 0;
            if (Configure::read('app.registration.flexible_groups')) {
                $until_days_to_start = Configure::read('app.activity.teacher_can_block_groups_if_days_to_start');
                $closed = $ended || (is_int($until_days_to_start) && $event['Activity']['inflexible_groups'] && $until_days_to_start >= $event[0]['days_to_start']);
            } else {
                $closed = true;
            }
            
            if (!isset($activities_groups[$event['Activity']['id']])) {
                $activities_groups[$event['Activity']['id']] = array('id' => $event['Activity']['id'],'name' => $event['Activity']['name'], 'duration' => $event['Activity']['duration'], 'groups_closed' => false, 'Groups' => array());
            }
            $activities_groups[$event['Activity']['id']]['Groups'][$event['Group']['id']] = array('name' => $event['Group']['name'], 'id' => $event['Group']['id'], 'free_seats' => $event['Group']['capacity'] - $busy_capacity[0][0]['busy_capacity'], 'capacity' => $event['Group']['capacity'], 'closed' => $closed, 'ended' => $ended);
        endforeach;
        
        $student_groups_activities = $this->Event->query("SELECT activity_id, group_id FROM registrations WHERE student_id = {$db->value($auth_user_id)}");
        
        $student_groups = array();
        foreach ($student_groups_activities as $sga):
            $student_groups[$sga['registrations']['activity_id']] = $sga['registrations']['group_id'];
            if ($sga['registrations']['group_id'] != -1 && isset($activities_groups[$sga['registrations']['activity_id']]['Groups'][$sga['registrations']['group_id']])) {
                //Close all groups if current group is closed
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
        
        $this->loadModel('GroupRequest');
        $groups_requests = $this->GroupRequest->getUserRequests($auth_user_id, $subject_id);
        $changes_requests = array();
        foreach ($groups_requests as $request) {
            $request = $request['group_requests'];
            $closed = $activities_groups[$request['activity_id']]['Groups'][$request['group_id']]['closed'];
            $closed = $closed || $activities_groups[$request['activity_id']]['Groups'][$request['group_2_id']]['closed'];
            if (!$closed) {
                if (!isset($changes_requests[$request['activity_id']])) {
                    $changes_requests[$request['activity_id']] = array();
                }
                if ($request['student_id'] == $auth_user_id) {
                    $group = $request['group_2_id'];
                } else {
                    $group = $request['group_id'];
                }
                if (!isset($changes_requests[$request['activity_id']][$group])) {
                    $changes_requests[$request['activity_id']][$group] = array();
                }
                $changes_requests[$request['activity_id']][$group][] = $request;
            }
        }
        
        
        $this->set('activities_groups', $activities_groups);
        $this->set('student_groups', $student_groups);
        $this->set('changes_requests', $changes_requests);
    }
    
    
    function view_info($activity_id = null, $group_id = null) {
        $activity_id = $activity_id === null ? null : intval($activity_id);
        $group_id = $group_id === null ? null : intval($group_id);
        $db = $this->Event->getDataSource();

        $activity = $this->Event->Activity->find('first', array('conditions' => array('Activity.id' => $activity_id)));
        
        $events = $this->Event->query("SELECT DISTINCT DATE_FORMAT(Event.initial_hour, '%w') AS day, DATE_FORMAT(Event.initial_hour,'%H:%i') AS initial_hour, DATE_FORMAT(Event.final_hour,'%H:%i') AS final_hour FROM events Event WHERE activity_id = {$db->value($activity_id)} AND group_id = {$db->value($group_id)} ORDER BY day, initial_hour");
        
        $event_min_date = $this->Event->Activity->query("SELECT MIN(Event.initial_hour) as initial_date FROM events Event WHERE activity_id = {$db->value($activity_id)} AND group_id = {$db->value($group_id)}");
        
        $event_max_date = $this->Event->query("SELECT MAX(Event.initial_hour) as final_date FROM events Event WHERE activity_id = {$db->value($activity_id)} AND group_id = {$db->value($group_id)}");
        
        $this->set('events', $events);
        $this->set('activity', $activity);
        $this->set('initial_date', $event_min_date[0]);
        $this->set('final_date', $event_max_date[0]);
    }
    
    function calendar_by_classroom() {
        $this->layout = 'public';
        
        $classrooms = array();
        foreach($this->Event->Classroom->find('all', array('order' => array('Classroom.name'), 'recursive' => 0)) as $classroom):
            $classrooms["{$classroom['Classroom']['id']}"] = $classroom['Classroom']['name'];
        endforeach;

        $this->set('classrooms', $classrooms);
    }

    function calendar_by_subject() {
        $this->layout = 'public';
        $this->set('courses', $this->Event->Activity->Subject->Course->find('all'));
        $this->set('current_course', $this->Event->Activity->Subject->Course->current());
    }
    
    function calendar_by_level() {
        $this->layout = 'public';
    }

    function calendar_by_teacher() {
        $this->layout = 'public';
    }
    
    function board() {
        $this->layout = 'board';

        $classroom_show_tv = Configure::read('app.classroom.show_tv');

        $dbo = $this->Event->getDataSource();
        $sql1 = $dbo->buildStatement(
            array(
                'table' => $dbo->fullTableName($this->Event),
                'alias' => 'Event',
                'fields' => array(
                    'Event.initial_hour',
                    'Event.final_hour',
                    'Activity.name',
                    'Activity.type',
                    'Subject.acronym as subject_acronym',
                    'Subject.degree as subject_degree',
                    'Subject.level as subject_level',
                    'Group.name as group_name',
                    'Teacher.first_name as teacher_first_name',
                    'Teacher.last_name as teacher_last_name',
                    'Event.classroom_id',
                    'Classroom.name as classroom_name'
                ),
                'conditions' => 'Event.initial_hour > CURDATE() AND Event.initial_hour < (CURDATE() + INTERVAL 1 DAY) AND (Event.show_tv' . ($classroom_show_tv ? ' OR Classroom.show_tv' : '') . ')',
                'joins' => array(
                    array(
                        'table' => 'classrooms',
                        'alias' => 'Classroom',
                        'type' => 'left',
                        'conditions' => 'Event.classroom_id = Classroom.id'
                    ),
                    array(
                        'table' => 'activities',
                        'alias' => 'Activity',
                        'type' => 'left',
                        'conditions' => 'Event.activity_id = Activity.id'
                    ),
                    array(
                        'table' => 'subjects',
                        'alias' => 'Subject',
                        'type' => 'left',
                        'conditions' => 'Activity.subject_id = Subject.id'
                    ),
                    array(
                        'table' => 'groups',
                        'alias' => 'Group',
                        'type' => 'left',
                        'conditions' => 'Event.group_id = Group.id'
                    ),
                    array(
                        'table' => 'users',
                        'alias' => 'Teacher',
                        'type' => 'left',
                        'conditions' => 'Event.teacher_id = Teacher.id AND (Teacher.type = "Profesor" OR Teacher.type = "Administrador")'
                    )
                ),
                'order' => null,
                'recursive' => 0,
                'limit' => null,
                'group' => null
            ),
            $this->Event
        );
                    
        $this->loadModel('Booking');
        $sql2 = $dbo->buildStatement(
            array(
                'table' => $dbo->fullTableName($this->Booking),
                'alias' => 'Booking',
                'fields' => array(
                    'Booking.initial_hour',
                    'Booking.final_hour',
                    'Booking.reason as name',
                    '"booking" as type',
                    'null as subject_acronym',
                    'null as subject_degree',
                    'null as subject_level',
                    'null as group_name',
                    'null as teacher_first_name',
                    'null as teacher_last_name',
                    'Booking.classroom_id',
                    'Classroom.name as classroom_name'
                ),
                'conditions' => 'Booking.initial_hour > CURDATE() AND Booking.initial_hour < (CURDATE() + INTERVAL 1 DAY) AND (Booking.show_tv' . ($classroom_show_tv ? ' OR Booking.classroom_id = -1 OR Classroom.show_tv' : '') . ')',
                'joins' => array(
                    array(
                        'table' => 'classrooms',
                        'alias' => 'Classroom',
                        'type' => 'left',
                        'conditions' => 'Booking.classroom_id = Classroom.id'
                    )
                ),
                'order' => null,
                'recursive' => 0,
                'limit' => null,
                'group' => null
            ),
            $this->Booking
        );
                    
        $events = $dbo->fetchAll($sql1.' UNION '.$sql2.' ORDER BY initial_hour, ISNULL(subject_acronym), subject_acronym, name, group_name');
        foreach($events as $i => &$event) {
            $event = $event[0];
            $event['sql_order'] = $i;
        }
        usort($events, array($this, '_sortBoardEvents'));
        $this->set('events', $events);
    }
    
    function _add_days(&$date, $ndays, $nminutes = 0) {
        $date_components = split("-", $date->format('Y-m-d-H-i-s'));
        $timestamp = mktime($date_components[3],$date_components[4],$date_components[5], $date_components[1], $date_components[2] + $ndays, $date_components[0]);
        $timestamp += ($nminutes * 60);
        $date_string = date('Y-m-d H:i:s', $timestamp);
        $date = new DateTime($date_string);
    }
    
    function _parse_date($date, $separator = "/") {
        $date_components = split($separator, $date);
        
        return count($date_components) != 3 ? false : date("Y-m-d", mktime(0,0,0, $date_components[1], $date_components[0], $date_components[2]));
    }

    function _authorizeDelete($event) {
        $uid = $this->Auth->user('id');

        return ($this->Auth->user('type') === "Administrador") || ($this->Auth->user('type') === "Profesor");
    }

    function _authorize() {
        parent::_authorize();

        $action = $this->params['action'];

        $children_actions = array('get_by_teacher' => 'calendar_by_teacher');

        $private_actions = array('schedule', 'add', 'copy', 'edit', 'update', 'delete', 'update_classroom', 'update_teacher');
        $student_actions = array('register_student');
        $public_actions = array('view', 'view_info', 'get', 'get_by_degree_and_level', 'get_by_level', 'get_by_subject', 'board', 'calendar_by_classroom', 'calendar_by_level', 'calendar_by_subject');

        if (isset($children_actions[$action])) {
            $action = $children_actions[$action];
        }

        if ((array_search($action, $private_actions) !== false) && ($this->Auth->user('type') != "Administrador") && ($this->Auth->user('type') != "Profesor")) {
            return false;
        }

        if ((array_search($action, $student_actions) !== false) && ($this->Auth->user('type') != "Estudiante")) {
            return false;
        }

        if ((array_search($action, $public_actions) !== false)) {
            $this->Auth->allow($action);
            return true;
        }

        return $this->Acl->check("events.{$action}");
    }
            
    function _sortBoardEvents($a, $b) {
        if ($a['initial_hour'] === $b['initial_hour']) {
            if ($a['subject_degree'] !== null && $b['subject_degree'] !== null) {
                $a_degree = $this->Event->Activity->Subject->degreeToInt($a['subject_degree']);
                $b_degree = $this->Event->Activity->Subject->degreeToInt($b['subject_degree']);
                if ($a_degree !== $b_degree) {
                    return $a_degree - $b_degree;
                }
            }
            if ($a['subject_level'] === null || $b['subject_level'] === null) {
                if ($a['subject_level'] === $b['subject_level']) {
                    return strcasecmp($a['name'], $b['name']);
                }
            } else {
                $a_level = $this->Event->Activity->Subject->levelToInt($a['subject_level']);
                $b_level = $this->Event->Activity->Subject->levelToInt($b['subject_level']);
                if ($a_level !== $b_level) {
                    return $a_level - $b_level;
                }
            }
        }
        return $a['sql_order'] - $b['sql_order'];
    }
}
