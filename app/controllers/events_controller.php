<?php

App::import('lib', 'Environment');

class EventsController extends AppController {
    var $name = 'Events';
    var $paginate = array('limit' => 10, 'order' => array('activity.initial_date' => 'asc'));
    var $helpers = array('Ajax', 'ModelHelper', 'activityHelper', 'Text');
    
    function schedule($course_id) {
        $course_id = $course_id === null ? null : intval($course_id);

        $course = $this->Event->Activity->Subject->Course->find('first', array(
            'conditions' => array(
                'Course.id' => $course_id,
                'Course.institution_id' => Environment::institution('id')
            )
        ));

        if (!$course) {
            $this->Session->setFlash('No se ha podido acceder al curso.');
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        $this->set('section', 'courses');
        $this->set('events_schedule', '1');
        $this->set('user_id', $this->Auth->user('id'));

        $db = $this->Event->getDataSource();

        $classrooms = $this->Event->Classroom->find('all', array(
            'conditions' => array(
                "Classroom.id IN (SELECT classroom_id FROM classrooms_institutions ClassroomInstitution WHERE ClassroomInstitution.institution_id = {$db->value(Environment::institution('id'))})"
            ),
            'order' => "Classroom.name ASC",
            'recursive' => 0
        ));

        $classrooms_mapped = array();
        foreach($classrooms as $cl):
            $classrooms_mapped[$cl['Classroom']['id']] = $cl['Classroom']['name'];
        endforeach;

        $this->set('classrooms', $classrooms_mapped);
        $this->set('subjects', $course['Subject']);
        $this->set('course', $course);
    }
    
    function get($classroom_id = null) {
        $classroom_id = $classroom_id === null ? null : intval($classroom_id);
        $db = $this->Event->getDataSource();

        $classroom = $this->Event->Classroom->find('first', array(
            'conditions' => array(
                'Classroom.id' => $classroom_id,
                "Classroom.id IN (SELECT classroom_id FROM classrooms_institutions ClassroomInstitution WHERE ClassroomInstitution.institution_id = {$db->value(Environment::institution('id'))})"
            ),
            'recursive' => -1
        ));

        if ($classroom) {
            $select = 'DISTINCT Event.id, Event.parent_id, Event.initial_hour, Event.final_hour, Event.owner_id, Event.activity_id, Activity.name, Activity.type, Event.group_id, `Group`.name, Subject.id, Subject.coordinator_id, Subject.practice_responsible_id, Subject.acronym';
            $from = 'events Event';
            $joins = array(
                'INNER JOIN activities Activity ON Activity.id = Event.activity_id',
                'INNER JOIN subjects Subject ON Subject.id = Activity.subject_id',
                'INNER JOIN groups `Group` ON `Group`.id = Event.group_id'
            );
            $conditions = array(
                "Event.classroom_id = {$db->value($classroom_id)}"
            );

            // @todo: update fullcalender request to a range of dates
            $from_date = date('Y-m-d H:i:s', strtotime('- 3 years'));
            $where[] = "Event.initial_hour >= '$from_date'";

            $with_joins = implode(' ', $joins);
            $where = implode(' AND ', $conditions);
    
            $events = $this->Event->query("SELECT $select FROM $from $with_joins WHERE $where");
        } else {
            $events = array();
        }
        
        $this->set('authorizeDelete', array($this, '_authorizeDelete'));
        $this->set('events', $events);
    }
    
    function get_by_subject($subject_id = null) {
        $subject_id = $subject_id === null ? null : intval($subject_id);
        $db = $this->Event->getDataSource();

        $subject = $this
            ->Event
            ->Activity
            ->Subject->find(
                'first',
                array(
                    'fields' => array('Subject.id'),
                    'joins' => array(
                        array(
                            'table' => 'courses',
                            'alias' => 'Course',
                            'type'  => 'INNER',
                            'conditions' => array(
                                'Course.id = Subject.course_id',
                                'Course.institution_id' => Environment::institution('id')
                            )
                        )
                    ),
                    'conditions' => array('Subject.id' => $subject_id),
                    'recursive' => -1,
                )
            );
        
        $events = array();

        if ($subject) {
            $select = 'DISTINCT Event.id, Event.parent_id, Event.initial_hour, Event.final_hour, Event.owner_id, Event.activity_id, Activity.name, Activity.type, Event.group_id, `Group`.name, Subject.id, Subject.coordinator_id, Subject.practice_responsible_id, Subject.acronym';
            $from = 'events Event';
            $joins = array(
                'INNER JOIN activities Activity ON Activity.id = Event.activity_id',
                'INNER JOIN subjects Subject ON Subject.id = Activity.subject_id',
                'INNER JOIN groups `Group` ON `Group`.id = Event.group_id'
            );
            $conditions = array(
                "Activity.subject_id = {$db->value($subject_id)}"
            );
            
            $with_joins = implode(' ', $joins);
            $where = implode(' AND ', $conditions);

            $events = $this->Event->query("SELECT $select FROM $from $with_joins WHERE $where");
        }
        
        $this->set('authorizeDelete', array($this, '_authorizeDelete'));
        $this->set('events', $events);
    }
    
    function get_by_level($level = null) {
        $db = $this->Event->getDataSource();
        
        $select = 'DISTINCT Event.id, Event.parent_id, Event.initial_hour, Event.final_hour, Event.owner_id, Event.activity_id, Activity.name, Activity.type, Event.group_id, `Group`.name, Subject.id, Subject.coordinator_id, Subject.practice_responsible_id, Subject.acronym';
        $from = 'events Event';
        $joins = array(
            'INNER JOIN activities Activity ON Activity.id = Event.activity_id',
            'INNER JOIN subjects Subject ON Subject.id = Activity.subject_id',
            'INNER JOIN groups `Group` ON `Group`.id = Event.group_id'
        );
        $conditions = [
            "Event.classroom_id IN (SELECT classroom_id FROM classrooms_institutions ClassroomInstitution WHERE ClassroomInstitution.institution_id = {$db->value(Environment::institution('id'))})"
        ];

        if (!empty($this->params['named']['academic_year'])) {
            $academic_year_id = $this->params['named']['academic_year'];
            $joins[] = 'INNER JOIN courses Course ON Course.id = Subject.course_id';
            $conditions[] = "Course.academic_year_id = {$db->value($academic_year_id)}";
        } else {
            // @todo: update fullcalender request to a range of dates
            $from_date = date('Y-m-d H:i:s', strtotime('- 3 years'));
            $conditions[] = "Event.initial_hour >= '$from_date'";
        }

        if (!empty($this->params['named']['course'])) {
            $course_id = $this->params['named']['course'];
            if ($course_id !== 'all') {
                $conditions[] = "Subject.course_id = {$db->value($course_id)}";
            }
        }

        if ($level !== 'all') {
            $conditions[] = "Subject.level = {$db->value($level)}";
        }

        $with_joins = implode(' ', $joins);
        $where = implode(' AND ', $conditions);

        $events = $this->Event->query("SELECT $select FROM $from $with_joins WHERE $where");

        if (empty($this->params['named']['booking'])) {
            $bookings = array();
        } else {
            // @todo: update fullcalender request to with range of dates
            $from_date = date('Y-m-d H:i:s', strtotime('- 3 years'));
            $bookings_where = "Booking.initial_hour >= '$from_date'";
            $bookings = $this->Event->query("SELECT DISTINCT Booking.id, Booking.initial_hour, Booking.final_hour, Booking.reason FROM bookings Booking WHERE $bookings_where");
        }
        
        $this->set('authorizeDelete', array($this, '_authorizeDelete'));
        $this->set('events', $events);
        $this->set('bookings', $bookings);
    }

    function get_by_teacher($teacher_id = null) {
        $this->loadModel('User');

        $teacher = $this->User->find('first', array(
            'joins' => array(
                array(
                    'table' => 'users_institutions',
                    'alias' => 'UserInstitution',
                    'type' => 'INNER',
                    'conditions' => array(
                        'UserInstitution.user_id = User.id',
                        'UserInstitution.institution_id' => Environment::institution('id'),
                        'UserInstitution.active'
                    )
                )
            ),
            'conditions' => array(
                'User.id' => $teacher_id,
                'OR' => array(
                    array('User.type' => 'Profesor'),
                    array('User.type' => 'Administrador')
                ),
            )
        ));

        $events = array();

        if ($teacher) {
            $this->User->set($teacher);

            // @todo: update fullcalender request to with range of dates
            $events = $this->User->getEvents(strtotime('- 3 years'));
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
        $date_components = explode('-', $initial_hour->format('Y-m-d-H-i-s'));
        $initial_timestamp = mktime($date_components[3],$date_components[4],$date_components[5], $date_components[1], $date_components[2], $date_components[0]);
        
        $date_components = explode('-', $final_hour->format('Y-m-d-H-i-s'));
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

        if (empty($this->data['Event']['classroom_id'])) {
            $this->set('notAllowed', true);
            return;
        }

        $db = $this->Event->getDataSource();

        $classroom = $this->Event->Classroom->find('first', array(
            'conditions' => array(
                'Classroom.id' => $this->data['Event']['classroom_id'],
                "Classroom.id IN (SELECT classroom_id FROM classrooms_institutions ClassroomInstitution WHERE ClassroomInstitution.institution_id = {$db->value(Environment::institution('id'))})"
            ),
            'recursive' => -1
        ));

        if (!$classroom) {
            $this->set('notAllowed', true);
        }

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

        $db = $this->Event->getDataSource();

        $event = $this->Event->find('first', array(
            'conditions' => array(
                'Event.id' => $id,
                "Event.classroom_id IN (SELECT classroom_id FROM classrooms_institutions ClassroomInstitution WHERE ClassroomInstitution.institution_id = {$db->value(Environment::institution('id'))})"
            ),
            'recursive' => -1
        ));

        $events = array();

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

                $classroom = $this->Event->Classroom->find('first', array(
                    'conditions' => array(
                        'Classroom.id' => $classroom_id,
                        "Classroom.id IN (SELECT classroom_id FROM classrooms_institutions ClassroomInstitution WHERE ClassroomInstitution.institution_id = {$db->value(Environment::institution('id'))})"
                    ),
                    'recursive' => -1
                ));

                if (!$classroom) {
                    $this->set('notAllowed', true);
                    $classroom_id = false;
                }
            } else {
                $classroom_id = $event['Event']['classroom_id'];
            }

            if ($classroom_id) {
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
        }
        $this->set('events', $events);
        $this->set('authorizeDelete', array($this, '_authorizeDelete'));
    }        

    function edit($id = null) {
        $id = $id === null ? null : intval($id);
        
        $db = $this->Event->getDataSource();

        $event = $this->Event->find('first', array(
            'conditions' => array(
                'Event.id' => $id,
                "Event.classroom_id IN (SELECT classroom_id FROM classrooms_institutions ClassroomInstitution WHERE ClassroomInstitution.institution_id = {$db->value(Environment::institution('id'))})"
            )
        ));

        if (!$event || !$this->_authorizeEdit($event)) {
            $this->Session->setFlash('No tienes los permisos necesarios para editar el evento.');
            $this->redirect($this->referer());
        }

        if ($this->Auth->user('type') == "Administrador") {
            $classrooms = $this->Event->Classroom->find('all', array(
                'conditions' => array(
                    "Classroom.id IN (SELECT classroom_id FROM classrooms_institutions ClassroomInstitution WHERE ClassroomInstitution.institution_id = {$db->value(Environment::institution('id'))})"
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
        
        $this->set('event', $event);
    }
    
    function update($id, $deltaDays, $deltaMinutes, $resize = null) {
        $id = $id === null ? null : intval($id);
        
        $db = $this->Event->getDataSource();
        
        $event = $this->Event->find('first', array(
            'conditions' => array(
                'Event.id' => $id,
                "Event.classroom_id IN (SELECT classroom_id FROM classrooms_institutions ClassroomInstitution WHERE ClassroomInstitution.institution_id = {$db->value(Environment::institution('id'))})"
            )
        ));

        if ($event && $this->_authorizeEdit($event)) {
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
        } else {
            $this->set('notAllowed', true);
        }
    }
    
    function delete($id=null) {
        $id = $id === null ? null : intval($id);
        
        $db = $this->Event->getDataSource();

        $event = $this->Event->find('first', array(
            'conditions' => array(
                'Event.id' => $id,
                "Event.classroom_id IN (SELECT classroom_id FROM classrooms_institutions ClassroomInstitution WHERE ClassroomInstitution.institution_id = {$db->value(Environment::institution('id'))})"
            )
        ));

        $ids = array();
        if ($event && $this->_authorizeDelete($event)) {
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

        $db = $this->Event->getDataSource();

        $event = null;
        
        if ($this->Auth->user('type') != "Administrador" || !isset($classroom_id, $teacher_id, $event_id)) {
            $this->set('notAllowed', true);
            $is_valid = false;
        } else {
            $event = $this->Event->find('first', array(
                'conditions' => array(
                    'Event.id' => $event_id,
                    "Event.classroom_id IN (SELECT classroom_id FROM classrooms_institutions ClassroomInstitution WHERE ClassroomInstitution.institution_id = {$db->value(Environment::institution('id'))})"
                )
            ));
        }

        if ($event) {
            $classroom = $this->Event->Classroom->find('first', array(
                'conditions' => array(
                    'Classroom.id' => $classroom_id,
                    "Classroom.id IN (SELECT classroom_id FROM classrooms_institutions ClassroomInstitution WHERE ClassroomInstitution.institution_id = {$db->value(Environment::institution('id'))})"
                ),
                'recursive' => -1
            ));
            
            if ($classroom) {
                $event['Event']['classroom_id'] = $classroom_id;

                $this->Event->set($event);

                $is_valid = $this->Event->eventDontOverlap();

                if ($is_valid && $this->update_teacher($event_id, $teacher_id, $teacher_2_id)) {
                    $this->Event->updateAll(
                        array('Event.classroom_id' => $classroom_id,),
                        array('Event.id' => $event_id)
                    );
                    $this->set('ok', true);
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
            }
        }
    }
    
    function update_teacher($event_id = null, $teacher_id = null, $teacher_2_id = null) {
        $event_id = $event_id === null ? null : intval($event_id);
        $teacher_id = $teacher_id === null ? null : intval($teacher_id);
        $teacher_2_id = $teacher_2_id === null ? null : intval($teacher_2_id);
        $event_show_tv = Configure::read('app.event.show_tv');

        $this->loadModel('User');
        $db = $this->Event->getDataSource();
        $event = false;
        $teacher = false;
        $teacher2 = false;

        if ($event_id && $teacher_id) {
            $event = $this->Event->find('first', array(
                'conditions' => array(
                    'Event.id' => $event_id,
                    "Event.classroom_id IN (SELECT classroom_id FROM classrooms_institutions ClassroomInstitution WHERE ClassroomInstitution.institution_id = {$db->value(Environment::institution('id'))})"
                ),
                'recursive' => -1
            ));

            if ($event) {
                $teacher = $this->User->find('first', array(
                    'joins' => array(
                        array(
                            'table' => 'users_institutions',
                            'alias' => 'UserInstitution',
                            'type' => 'INNER',
                            'conditions' => array(
                                'UserInstitution.user_id = User.id',
                                'UserInstitution.institution_id' => Environment::institution('id'),
                                'UserInstitution.active'
                            )
                        )
                    ),
                    'conditions' => array(
                        'User.id' => $teacher_id,
                        'OR' => array(
                            array('User.type' => 'Profesor'),
                            array('User.type' => 'Administrador')
                        ),
                    )
                ));

                if (!$teacher) {
                    $event = false;
                }
            }
        }

        if ($event && $teacher_2_id) {
            $teacher2 = $this->User->find('first', array(
                'joins' => array(
                    array(
                        'table' => 'users_institutions',
                        'alias' => 'UserInstitution',
                        'type' => 'INNER',
                        'conditions' => array(
                            'UserInstitution.user_id = User.id',
                            'UserInstitution.institution_id' => Environment::institution('id'),
                            'UserInstitution.active'
                        )
                    )
                ),
                'conditions' => array(
                    'User.id' => $teacher_2_id,
                    'OR' => array(
                        array('User.type' => 'Profesor'),
                        array('User.type' => 'Administrador')
                    ),
                )
            ));

            if (!$teacher2) {
                $event = false;
            }
        }

        if ($event) {
            $error = false;
            $events = $this->Event->query("SELECT id FROM events as Event where id = {$event_id} or parent_id = {$event_id}");
            $events_ids = Set::extract('/Event/id', $events);
            $old_teacher_ids = array();
            $old_teacher_2_ids = array();
            $old_show_tvs = array();
    
            foreach ($events_ids as $event_id) {
                $this->Event->id = $event_id;
                $event = $this->Event->read();
                  
                $old_teacher_ids[$event_id] = $event['Event']['teacher_id'];
                $old_teacher_2_ids[$event_id] = $event['Event']['teacher_2_id'];
                $old_show_tvs[$event_id] = $event['Event']['show_tv'];
                  
                $event['Event']['teacher_id'] = $teacher_id;
                $event['Event']['teacher_2_id'] = $teacher_2_id;
                if ($event_show_tv && isset($this->params['named']['show_tv'])) {
                    $event['Event']['show_tv'] = (bool) $this->params['named']['show_tv'];
                }
      
                if (!$this->Event->save($event)) {
                    $error = true;
        
                    foreach (array_keys($old_teacher_ids) as $event_id) {
                        $this->Event->updateAll(
                            array(
                                'Event.teacher_id' => $old_teacher_ids[$event_id],
                                'Event.teacher_2_id' => $old_teacher_2_ids[$event_id],
                                'Event.show_tv' => $old_show_tvs[$event_id]
                            ),
                            array('Event.id' => $event_id)
                        );
                    }
        
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
        
                    break; // Break save all events loop
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
                return true;
            }
        }
    }
    
    function view($id) {
        $id = $id === null ? null : intval($id);
        
        $db = $this->Event->getDataSource();

        $conditions = array(
            'Event.id' => $id,
        );

        if (Environment::institution('id')) {
            $conditions[] = "Event.classroom_id IN (SELECT classroom_id FROM classrooms_institutions ClassroomInstitution WHERE ClassroomInstitution.institution_id = {$db->value(Environment::institution('id'))})";
        }

        $event = $this->Event->find('first', array(
            'conditions' => $conditions
        ));

        if (!$event) {
            $this->set('notAllowed', true);
        } else {
            $this->set('event', $event);
            $this->set('subject', $this->Event->Activity->Subject->find('first', array('conditions' => array('Subject.id' => $event['Activity']['subject_id']))));
        }
    }
    
    function register_student($subject_id = null) {
        $subject_id = $subject_id === null ? null : intval($subject_id);
        $auth_user_id = $this->Auth->user('id');

        $subject = $this->Event->Activity->Subject->find('first', array(
            'joins' => array(
                array(
                    'table' => 'subjects_users',
                    'alias' => 'SubjectUser',
                    'type'  => 'INNER',
                    'conditions' => array(
                        'SubjectUser.subject_id = Subject.id',
                        'SubjectUser.user_id' => $auth_user_id,
                    )
                )
            ),
            'conditions' => array(
                'Subject.id' => $subject_id,
                'Course.institution_id' => Environment::institution('id')
            )
        ));

        if (!$subject) {
            $this->Session->setFlash('No se ha podido acceder a la asignatura.');
            $this->redirect(array('controller' => 'users', 'action' => 'my_subjects'));
        }

        $this->set("section", "my_subjects");
        $this->set('subject', $subject);
        
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

        $activity = $this->Event->Activity->find('first', array(
            'joins' => array(
                array(
                    'table' => 'subjects',
                    'alias' => 'Subject',
                    'type'  => 'INNER',
                    'conditions' => array(
                        'Subject.id = Activity.subject_id',
                    )
                ),
                array(
                    'table' => 'courses',
                    'alias' => 'Course',
                    'type'  => 'INNER',
                    'conditions' => array(
                        'Course.id = Subject.course_id',
                        'Course.institution_id' => Environment::institution('id')
                    )
                )
            ),
            'conditions' => array(
                'Activity.id' => $activity_id
            ),
            'recursive' => -1
        ));
        
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
        
        $db_classrooms = $this->Event->Classroom->find('all', array(
            'conditions' => array(
                'Classroom.institution_id' => Environment::institution('id')
            ),
            'order' => array('Classroom.name'),
            'recursive' => 0
        ));

        $classrooms = array();

        foreach($db_classrooms as $classroom):
            $classrooms["{$classroom['Classroom']['id']}"] = $classroom['Classroom']['name'];
        endforeach;

        $this->set('classrooms', $classrooms);
    }

    function calendar_by_subject() {
        $this->layout = 'public';
        
        $academic_years = $this->Event->Activity->Subject->Course->AcademicYear->find('all', array(
            'recursive' => -1,
            'order' => array('AcademicYear.initial_date' => 'desc')
        ));

        $academic_years = Set::combine($academic_years, '{n}.AcademicYear.id', '{n}.AcademicYear');

        $courses = $this->Event->Activity->Subject->Course->find('all', array(
            'fields' => array('Course.*', 'Degree.*'),
            'joins' => array(
                array(
                    'table' => 'degrees',
                    'alias' => 'Degree',
                    'type' => 'INNER',
                    'conditions' => array(
                        'Degree.id = Course.degree_id'
                    )
                )
            ),
            'conditions' => array(
                'Course.institution_id' => Environment::institution('id'),
            ),
            'order' => array('Degree.name' => 'desc'),
            'recursive' => -1
        ));

        foreach ($courses as $course) {
            $academic_years[$course['Course']['academic_year_id']]['Course'][] = array_merge($course['Course'], array('Degree' => $course['Degree']));
        }

        $this->set('academic_years', $academic_years);
        $this->set('current_academic_year', $this->Event->Activity->Subject->Course->AcademicYear->current());
    }
    
    function calendar_by_level() {
        $this->layout = 'public';

        $academic_years = $this->Event->Activity->Subject->Course->AcademicYear->find('all', array(
            'recursive' => -1,
            'order' => array('AcademicYear.initial_date' => 'desc')
        ));

        $academic_years = Set::combine($academic_years, '{n}.AcademicYear.id', '{n}.AcademicYear');

        $courses = $this->Event->Activity->Subject->Course->find('all', array(
            'fields' => array('Course.*', 'Degree.*'),
            'joins' => array(
                array(
                    'table' => 'degrees',
                    'alias' => 'Degree',
                    'type' => 'INNER',
                    'conditions' => array(
                        'Degree.id = Course.degree_id'
                    )
                )
            ),
            'conditions' => array(
                'Course.institution_id' => Environment::institution('id'),
            ),
            'order' => array('Degree.name' => 'desc'),
            'recursive' => -1
        ));

        foreach ($courses as $course) {
            $academic_years[$course['Course']['academic_year_id']]['Course'][] = array_merge($course['Course'], array('Degree' => $course['Degree']));
        }

        $this->set('academic_years', $academic_years);
        $this->set('current_academic_year', $this->Event->Activity->Subject->Course->AcademicYear->current());
    }

    function calendar_by_teacher($id = null) {
        $id = $id === null ? null : intval($id);
        $teacher = null;

        if ($id) {
            $teacher = $this->Event->Teacher->find(
                'first',
                array(
                    'joins' => array(
                        array(
                            'table' => 'users_institutions',
                            'alias' => 'UserInstitution',
                            'type' => 'LEFT',
                            'conditions' => array(
                                'UserInstitution.user_id = Teacher.id',
                                'UserInstitution.institution_id' => Environment::institution('id')
                            )
                        )
                    ),
                    'conditions' => array('Teacher.id' => $id),
                    'recursive' => -1
                )
            );
        }

        $this->set('teacher', $teacher);
        $this->layout = 'public';
    }
    
    function _add_days(&$date, $ndays, $nminutes = 0) {
        $date_components = explode('-', $date->format('Y-m-d-H-i-s'));
        $timestamp = mktime($date_components[3],$date_components[4],$date_components[5], $date_components[1], $date_components[2] + $ndays, $date_components[0]);
        $timestamp += ($nminutes * 60);
        $date_string = date('Y-m-d H:i:s', $timestamp);
        $date = new DateTime($date_string);
    }
    
    function _parse_date($date, $separator = '/') {
        $date_components = explode($separator, $date);
        
        return count($date_components) != 3 ? false : date("Y-m-d", mktime(0,0,0, $date_components[1], $date_components[0], $date_components[2]));
    }

    function _authorizeEdit($event) {
        $ref = isset($this->params['named']['ref']) ? $this->params['named']['ref'] : null;

        if (! Environment::institution('id') || ($ref && $ref !== 'events')) {
            return false;
        }

        if ($this->Auth->user('type') == "Administrador") {
            return true;
        }

        if (isset($event['Event']['owner_id']) && $event['Event']['owner_id'] == $this->Auth->user('id')) {
            return true;
        }

        $subject = isset($event['Subject']) &&
            array_key_exists('coordinator_id', $event['Subject']) &&
            array_key_exists('practice_responsible_id', $event['Subject'])
            ? $event
            : false;

        if (! $subject) {
            $subject_id = isset($event['Subject']['id'])
                ? $event['Subject']['id']
                : (isset($event['Activity']['subject_id']) ? $event['Activity']['subject_id'] : null);
        
            if ($subject_id) {
                $subject = $this->Event->Activity->Subject->find('first', array(
                    'conditions' => array('Subject.id' => $subject_id),
                    'recursive' => -1
                ));
            }
        }

        if ($subject) {
            $uid = $this->Auth->user('id');
            return  $uid == $subject['Subject']['coordinator_id'] || $uid == $subject['Subject']['practice_responsible_id'];
        }

        return false;
    }

    function _authorizeDelete($event) {
        return $this->_authorizeEdit($event);
    }

    function _allowAnonymousActions() {
        $this->Auth->allow('view', 'view_info', 'calendar_by_classroom', 'calendar_by_subject', 'calendar_by_level', 'board', 'get', 'get_by_level', 'get_by_degree_and_level', 'get_by_subject');

        $children_actions = array('get_by_teacher' => 'calendar_by_teacher');

        $action = $this->params['action'];
        if (isset($children_actions[$action])) {
            $action = $children_actions[$action];
        }

        $acl = Configure::read('app.acl');

        if (!empty($acl['all']["{$this->params['controller']}.{$action}"])) {
            $this->Auth->allow($this->params['action']);
        }

        parent::_allowAnonymousActions();
    }

    function _authorize() {
        parent::_authorize();

        $action = $this->params['action'];

        $no_institution_actions = array('view');

        $children_actions = array('get_by_teacher' => 'calendar_by_teacher');

        $private_actions = array('schedule', 'add', 'copy', 'edit', 'update', 'delete', 'update_classroom', 'update_teacher');
        $student_actions = array('register_student');
        /* @todo: remove board action */
        $public_actions = array('view', 'view_info', 'calendar_by_classroom', 'calendar_by_subject', 'calendar_by_level', 'board', 'get', 'get_by_level', 'get_by_degree_and_level', 'get_by_subject');

        if (isset($children_actions[$action])) {
            $action = $children_actions[$action];

            if (array_search($action, $no_institution_actions) === false && ! Environment::institution('id')) {
                return false;
            }

            $auth_type = $this->Auth->user('type');
            $acl = Configure::read('app.acl');

            if ($auth_type && !empty($acl[$auth_type]["{$this->params['controller']}.{$action}"])) {
                $this->Auth->allow($this->params['action']);
                return true;
            } elseif (!empty($acl['all']["{$this->params['controller']}.{$action}"])) {
                return true;
            }
        }

        if (array_search($this->params['action'], $no_institution_actions) === false && ! Environment::institution('id')) {
            return false;
        }

        if (array_search($action, $private_actions) !== false) {
            return ($this->Auth->user('type') == "Administrador") || ($this->Auth->user('type') == "Profesor");
        }

        if ((array_search($action, $student_actions) !== false)) {
            return ($this->Auth->user('type') == "Estudiante");
        }

        if ((array_search($action, $public_actions) !== false)) {
            $this->Auth->allow($this->params['action']);
            return true;
        }

        return $this->Acl->check("events.{$action}");
    }
}
