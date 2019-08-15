<?php
class BookingsController extends AppController {
    var $name = 'Bookings';
    var $paginate = array('limit' => 10, 'order' => array('Bookings.initial_hour' => 'asc'));
    var $helpers = array('Ajax', 'activityHelper');
    
    function index() {
        $this->set('bookings_schedule', true);
  
        $this->set('section', 'bookings');

        $db = $this->Booking->getDataSource();
        $classrooms = $this->Booking->Classroom->find('all', array(
            'fields' => array('Classroom.id', 'Classroom.institution_id', 'Classroom.name, Classroom.teachers_can_booking'),
            'recursive' => 0,
            'conditions' => array(
                "Classroom.id IN (SELECT classroom_id FROM classrooms_institutions ClassroomInstitution WHERE ClassroomInstitution.institution_id = {$db->value(Environment::institution('id'))})"
            ),
            'order' => array('Classroom.name')
        ));
        
        $this->set('classrooms', $classrooms);
    }
    
    function add($finished_at = null, $frequency = null) {
        $bookings = array();
  
        if (empty($this->data['Booking']['classroom_id'])) {
            $this->set('notAllowed', true);
            return;
        }

        if ($this->Auth->user('type') != "Administrador" && $this->data['Booking']['classroom_id'] == -1) {
            $this->set('notAllowed', true);
            return;
        }

        $db = $this->Booking->getDataSource();

        if ($this->data['Booking']['classroom_id'] != -1) {
            $classroom = $this->Booking->Classroom->find('first', array(
                'conditions' => array(
                    'Classroom.id' => $this->data['Booking']['classroom_id'],
                    "Classroom.id IN (SELECT classroom_id FROM classrooms_institutions ClassroomInstitution WHERE ClassroomInstitution.institution_id = {$db->value(Environment::institution('id'))})"
                ),
                'recursive' => -1
            ));

            if (!$classroom) {
                $this->set('notAllowed', true);
            }

            if (Configure::read('app.classroom.teachers_can_booking') && $this->Auth->user('type') === 'Profesor') {
                if (!$classroom['Classroom']['teachers_can_booking']) {
                    $this->set('notAllowed', true);
                    return;
                }
            }
        }
  
        if (($finished_at != null) && ($frequency != null)) {
            $initial_hour = new DateTime($this->data['Booking']['initial_hour']);
            $final_hour = new DateTime($this->data['Booking']['final_hour']);
            $finished_at = new DateTime($this->_parse_date($finished_at, "-"));
            
            $this->data['Booking']['user_id'] = $this->Auth->user('id');
            $this->data['Booking']['institution_id'] = Environment::institution('id');
            
            while ($finished_at->format('Ymd') >= $initial_hour->format('Ymd')) {
                if ($this->Booking->save($this->data)){
                    
                    $current_booking = $this->Booking->read();
                    
                    if (!isset($this->data['Booking']['parent_id']))
                        $this->data['Booking']['parent_id'] = $current_booking['Booking']['id'];
                    array_push($bookings, $current_booking);
                    $this->_add_days($initial_hour, $frequency);
                    $this->_add_days($final_hour, $frequency);
                    $this->data['Booking']['initial_hour'] = $initial_hour->format('Y-m-d H:i:s');
                    $this->data['Booking']['final_hour'] = $final_hour->format('Y-m-d H:i:s');
                    $this->Booking->id = null;
                    $this->data['Booking']['id'] = null;
                }
                else 
                {
                    if ($this->data['Booking']['parent_id'] != null) {
                        $this->Booking->query("DELETE FROM bookings WHERE id = {$this->data['Booking']['parent_id']} OR parent_id = {$this->data['Booking']['parent_id']}");
                    }
                    
                    unset($bookings);
                    break;
                }
            }
        } else {
            $this->data['Booking']['user_id'] = $this->Auth->user('id');
            $this->data['Booking']['institution_id'] = Environment::institution('id');
            if ($this->Booking->save($this->data)){
                array_push($bookings, $this->Booking->read());
            }
        }
  
        if (!empty($bookings)) {
            $this->set('success', true);
            $this->set('bookings', $bookings);
        } else {
            if ($this->Booking->booking_id_overlaped) {
                $this->Booking->id = $this->Booking->booking_id_overlaped;
                $booking_overlaped = $this->Booking->read();
                $this->set('booking_overlaped', $booking_overlaped);
            } elseif ($this->Booking->event_id_overlaped) {
                $this->loadModel('Event');
                $this->Event->id = $this->Booking->event_id_overlaped;
                $event_overlaped = $this->Event->read();
                $activity_overlaped = $this->Event->Activity->find('first', array('conditions' => array('Activity.id' => $event_overlaped['Activity']['id'])));
                $this->set('event_overlaped', $event_overlaped);
                $this->set('activity_overlaped', $activity_overlaped);
            }
        }
        $this->set('authorizeDelete', array($this, '_authorizeDelete'));
    }

    function copy($id) {
        $id = $id === null ? null : intval($id);
        $bookings = array();

        $db = $this->Booking->getDataSource();
        $booking = $this->Booking->find('first', array(
            'conditions' => array(
                'Booking.id' => $id,
                'OR' => array(
                    "Booking.classroom_id = -1 AND Booking.institution_id = {$db->value(Environment::institution('id'))}",
                    "Booking.classroom_id IN (SELECT classroom_id FROM classrooms_institutions ClassroomInstitution WHERE ClassroomInstitution.institution_id = {$db->value(Environment::institution('id'))})"
                )
            ),
            'recursive' => -1
        ));

        if (!$booking) {
            $this->set('notAllowed', true);
        } else {
            $booking_initial_hour = new DateTime($booking['Booking']['initial_hour']);
            if (!empty($this->params['named']['initial_hour'])) {
                $initial_hour = new DateTime($this->params['named']['initial_hour']);
            } else {
                $initial_hour = $booking_initial_hour;
            }
            if (!empty($this->params['named']['classroom'])) {
                $classroom_id = intval($this->params['named']['classroom']);
            } else {
                $classroom_id = $booking['Booking']['classroom_id'];
            }

            if ($classroom_id == -1 && ($this->params['named']['classroom'] != -1 || $this->Auth->user('type') != "Administrador")) {
                $this->set('notAllowed', true);
                return;
            }
    
            if ($classroom_id != -1) {
                $classroom = $this->Booking->Classroom->find('first', array(
                    'conditions' => array(
                        'Classroom.id' => $classroom_id,
                        "Classroom.id IN (SELECT classroom_id FROM classrooms_institutions ClassroomInstitution WHERE ClassroomInstitution.institution_id = {$db->value(Environment::institution('id'))})"
                    ),
                    'recursive' => -1
                ));
    
                if (!$classroom) {
                    $this->set('notAllowed', true);
                }
    
                if (Configure::read('app.classroom.teachers_can_booking') && $this->Auth->user('type') === 'Profesor') {
                    if (!$classroom['Classroom']['teachers_can_booking']) {
                        $this->set('notAllowed', true);
                        return;
                    }
                }
            }

            $duration = $this->_getDuration($booking_initial_hour, new DateTime($booking['Booking']['final_hour']));
            $final_hour = $this->_addDuration($initial_hour, $duration);
            $this->data = array(
                'id'           => null,
                'institution_id' => Environment::institution('id'),
                'user_id'      => $this->Auth->user('id'),
                'user_type'    => $booking['Booking']['user_type'],
                'initial_hour' => $initial_hour->format('Y-m-d H:i:s'),
                'classroom_id' => $classroom_id,
                'final_hour'   => $final_hour->format('Y-m-d H:i:s'),
                'reason'    => $booking['Booking']['reason'],
                'required_equipment'    => $booking['Booking']['required_equipment'],
                'show_tv'      => $booking['Booking']['show_tv']
            );
            if ($this->Booking->save($this->data)){
                array_push($bookings, $this->Booking->read());
                $this->set('success', true);
                $this->set('bookings', $bookings);
            } elseif ($this->Booking->booking_id_overlaped) {
                $this->Booking->id = $this->Booking->booking_id_overlaped;
                $booking_overlaped = $this->Booking->read();
                $this->set('booking_overlaped', $booking_overlaped);
            } elseif ($this->Booking->event_id_overlaped) {
                $this->loadModel('Event');
                $this->Event->id = $this->Booking->event_id_overlaped;
                $event_overlaped = $this->Event->read();
                $activity_overlaped = $this->Event->Activity->find('first', array('conditions' => array('Activity.id' => $event_overlaped['Activity']['id'])));
                $this->set('event_overlaped', $event_overlaped);
                $this->set('activity_overlaped', $activity_overlaped);
            }
        }
        $this->set('authorizeDelete', array($this, '_authorizeDelete'));
    }
    
    function get($classroom_id = null) {
        $classroom_id = $classroom_id === null ? null : intval($classroom_id);
        $db = $this->Booking->getDataSource();

        $where = "(Booking.classroom_id = -1 AND Booking.institution_id = {$db->value(Environment::institution('id'))})";

        if ($classroom_id && $classroom_id != -1) {
            $classroom = $this->Booking->Classroom->find('first', array(
                'conditions' => array(
                    'Classroom.id' => $classroom_id,
                    "Classroom.id IN (SELECT classroom_id FROM classrooms_institutions ClassroomInstitution WHERE ClassroomInstitution.institution_id = {$db->value(Environment::institution('id'))})"
                ),
                'recursive' => -1
            ));

            if ($classroom) {
                $classroom_environment_id = $classroom['Classroom']['institution_id'];
                $where = "(Booking.classroom_id = {$db->value($classroom_id)}) OR (Booking.classroom_id = -1 AND Booking.institution_id = {$db->value($classroom_environment_id)})";
            }
        }

        // @todo: update fullcalender request to with range of dates
        $from_date = date('Y-m-d H:i:s', strtotime('- 3 years')); 
        $where = "($where) AND Booking.initial_hour >= '$from_date'";

        $bookings = $this->Booking->query("SELECT DISTINCT Booking.id, Booking.institution_id, Booking.parent_id, Booking.initial_hour, Booking.final_hour, Booking.reason, Booking.user_id, Booking.classroom_id, Classroom.id, Classroom.institution_id, Classroom.teachers_can_booking FROM bookings Booking LEFT JOIN classrooms Classroom ON Booking.classroom_id = Classroom.id WHERE $where");

        $this->set('authorizeDelete', array($this, '_authorizeDelete'));
        $this->set('bookings', $bookings);
    }
    
    function view($id = null) {
        $id = $id === null ? null : intval($id);
        $db = $this->Booking->getDataSource();

        $booking = $this->Booking->find('first', array(
            'conditions' => array(
                'Booking.id' => $id,
                'OR' => array(
                    "Booking.classroom_id = -1 AND Booking.institution_id = {$db->value(Environment::institution('id'))}",
                    "Booking.classroom_id IN (SELECT classroom_id FROM classrooms_institutions ClassroomInstitution WHERE ClassroomInstitution.institution_id = {$db->value(Environment::institution('id'))})"
                )
            )
        ));

        $this->set('booking', $booking);
        $this->set('authorizeEdit', array($this, '_authorizeEdit'));
        $this->set('authorizeDelete', array($this, '_authorizeDelete'));
        $this->set('isAjax', $this->RequestHandler->isAjax());
    }

    function edit($id = null) {
        $id = $id === null ? null : intval($id);

        $db = $this->Booking->getDataSource();

        $booking = $this->Booking->find('first', array(
            'conditions' => array(
                'Booking.id' => $id,
                'OR' => array(
                    "Booking.classroom_id = -1 AND Booking.institution_id = {$db->value(Environment::institution('id'))}",
                    "Booking.classroom_id IN (SELECT classroom_id FROM classrooms_institutions ClassroomInstitution WHERE ClassroomInstitution.institution_id = {$db->value(Environment::institution('id'))})"
                )
            ),
            'recursive' => 0
        ));
        
        if (!$booking || !$this->_authorizeEdit($booking)) {
            $this->Session->setFlash('No tienes los permisos necesarios para editar la reserva.');
            $this->redirect(array('action' => 'view', $id));
        }
  
        if (!empty($this->data)) {
            # Sanatize the data
            $this->data = array('Booking' => $this->data['Booking']);
            $this->data['Booking']['id'] = $id;
            $this->data['Booking']['institution_id'] = $booking['Booking']['institution_id'];

            if ($this->data['Booking']['classroom_id'] == -1) {
                if ($booking['Booking']['institution_id'] != Environment::institution('id') || $this->Auth->user('type') != "Administrador") {
                    $this->Session->setFlash('No se ha podido guardar la reserva. Por favor, revise que ha introducido todos los datos correctamente.');
                    $this->redirect(array('action' => 'view', $id));
                }
            } else {
                $classroom = $this->Booking->Classroom->find('first', array(
                    'conditions' => array(
                        'Classroom.id' => $this->data['Booking']['classroom_id'],
                        "Classroom.id IN (SELECT classroom_id FROM classrooms_institutions ClassroomInstitution WHERE ClassroomInstitution.institution_id = {$db->value(Environment::institution('id'))})"
                    ),
                    'recursive' => -1
                ));

                if (!$classroom) {
                    $this->Session->setFlash('No se ha podido guardar la reserva. Por favor, revise que ha introducido todos los datos correctamente.');
                    $this->redirect(array('action' => 'view', $id));
                }

                if (Configure::read('app.classroom.teachers_can_booking') && $this->Auth->user('type') === 'Profesor') {
                    if (!$classroom['Classroom']['teachers_can_booking']) {
                        $this->Session->setFlash('No se ha podido guardar la reserva. Por favor, revise que ha introducido todos los datos correctamente.');
                        $this->redirect(array('action' => 'view', $id));
                    }
                }
            }

            $internal_date_format = $this->Booking->dateFormatInternal($this->data['Booking']['date']);
            $this->data['Booking']['initial_hour'] = "{$internal_date_format} {$this->data['Booking']['initial_hour']['hour']}:{$this->data['Booking']['initial_hour']['minute']}";
            $this->data['Booking']['final_hour'] = "{$internal_date_format} {$this->data['Booking']['final_hour']['hour']}:{$this->data['Booking']['final_hour']['minute']}";

            if (isset($this->data['Booking']['attendees'])) {
                $selected_attendees = array_unique(array_keys($this->data['Booking']['attendees']));
                $this->data['Attendee'] = array();
                foreach ($selected_attendees as $attendee_id) {
                    $this->data['Attendee'][] = array(
                        'UserBooking' => array(
                            'user_id' => $attendee_id,
                            'booking_id' => $id
                        )
                    );
                }
                unset($this->data['Booking']['attendees']);
            } else {
                $this->data['Attendee'][0] = array();  // Remove all attendees
            }

            if ($this->Booking->saveAll($this->data)){
                $this->Session->setFlash('La reserva se ha guardado correctamente.');
                $this->redirect(array('action' => 'view', $id));
            } else {
                if ($this->Booking->booking_id_overlaped) {
                    $this->Booking->id = $this->Booking->booking_id_overlaped;
                    $booking_overlaped = $this->Booking->read();
                    $initial_date = date_create($booking_overlaped['Booking']['initial_hour']);
                    $message = "No ha sido posible crear la/s reserva/s en la fecha señalada porque coincide el día <strong>{$initial_date->format('d-m-Y')}</strong> con la reserva <strong>{$booking_overlaped['Booking']['reason']}</strong>";
                    if ($booking_overlaped['Classroom']['name']) {
                        $message .= " del aula <strong>{$booking_overlaped['Classroom']['name']}</strong>";
                    }
                    $this->Session->setFlash($message);
                } elseif ($this->Booking->event_id_overlaped) {
                    $this->loadModel('Event');
                    $this->Event->id = $this->Booking->event_id_overlaped;
                    $event_overlaped = $this->Event->read();
                    $activity_overlaped = $this->Event->Activity->find('first', array('conditions' => array('Activity.id' => $event_overlaped['Activity']['id'])));
                    $initial_date = date_create($event_overlaped['Event']['initial_hour']);
                    $message = "No ha sido posible crear la/s reserva/s en la fecha señalada porque coincide el día <strong>{$initial_date->format('d-m-Y')}</strong> con la actividad <strong>{$activity_overlaped['Activity']['name']}</strong> de la asignatura <strong>{$activity_overlaped['Subject']['name']}</strong> del aula <strong>{$event_overlaped['Classroom']['name']}</strong>";
                    $this->Session->setFlash($message);
                } else {
                    $this->Session->setFlash('No se ha podido guardar la reserva. Por favor, revise que ha introducido todos los datos correctamente.');
                }
                $this->redirect(array('action' => 'view', $id));
            }

        } else {
            $this->data = $this->Booking->read(null, $id);

            if (!$this->data) {
                $this->Session->setFlash('La reserva a la que intenta acceder ya no existe.');
                $this->redirect(array('action' => 'index'));
            }
            
            $attendees = $this->Booking->Attendee->query("SELECT Attendee.*
                FROM users Attendee
                INNER JOIN users_booking UB ON UB.user_id = Attendee.id
                WHERE UB.booking_id = {$id}
                ORDER BY Attendee.last_name, Attendee.first_name
            ");

            $classrooms = $this->Booking->Classroom->find('all', array(
                'fields' => array('Classroom.id', 'Classroom.institution_id', 'Classroom.name, Classroom.teachers_can_booking'),
                'recursive' => 0,
                'conditions' => array(
                    "Classroom.id IN (SELECT classroom_id FROM classrooms_institutions ClassroomInstitution WHERE ClassroomInstitution.institution_id = {$db->value(Environment::institution('id'))})"
                ),
                'order' => array('Classroom.name')
            ));

            $this->set('classrooms', $classrooms);
            $this->set('booking', $this->data);
            $this->set('attendees', $attendees);
        }
    }
    
    function delete($id=null) {
        $id = $id === null ? null : intval($id);

        $db = $this->Booking->getDataSource();

        $booking = $this->Booking->find('first', array(
            'conditions' => array(
                'Booking.id' => $id,
                'OR' => array(
                    "Booking.classroom_id = -1 AND Booking.institution_id = {$db->value(Environment::institution('id'))}",
                    "Booking.classroom_id IN (SELECT classroom_id FROM classrooms_institutions ClassroomInstitution WHERE ClassroomInstitution.institution_id = {$db->value(Environment::institution('id'))})"
                )
            ),
            'recursive' => 0
        ));

        $ids = array();

        if (!empty($booking['Booking']['id'])) {
            if (!$this->_authorizeDelete($booking)) {
                $this->set('notAllowed', true);
                return;
            }
            $ids = $this->Booking->query("SELECT `Booking`.id FROM bookings `Booking` where `Booking`.id = {$id} OR `Booking`.parent_id = {$id}");
            $this->Booking->query("DELETE FROM bookings WHERE id = {$id} OR parent_id = {$id}");
            $this->Booking->query("DELETE FROM users_booking WHERE booking_id NOT IN (SELECT DISTINCT `Booking`.id FROM bookings `Booking`)");
        }

        if ($this->RequestHandler->isAjax()) {
            $this->set('bookings', $ids);
        } else {
            $this->Session->setFlash('La reserva se eliminó correctamente.');
            $this->redirect(array('action' => 'index'));
        }            
    }
    
    function update($id, $deltaDays, $deltaMinutes, $resize = null) {
        $id = $id === null ? null : intval($id);
        
        $db = $this->Booking->getDataSource();

        $booking = $this->Booking->find('first', array(
            'conditions' => array(
                'Booking.id' => $id,
                'OR' => array(
                    "Booking.classroom_id = -1 AND Booking.institution_id = {$db->value(Environment::institution('id'))}",
                    "Booking.classroom_id IN (SELECT classroom_id FROM classrooms_institutions ClassroomInstitution WHERE ClassroomInstitution.institution_id = {$db->value(Environment::institution('id'))})"
                )
            ),
            'recursive' => 0
        ));

        if (!$this->_authorizeEdit($booking)) {
            $this->set('notAllowed', true);
            return;
        }
  
        if ($resize == null) {
            $initial_hour = date_create($booking['Booking']['initial_hour']);
            $this->_add_days($initial_hour, $deltaDays, $deltaMinutes);
            $booking['Booking']['initial_hour'] = $initial_hour->format('Y-m-d H:i:s');
        }
    
        $final_hour = date_create($booking['Booking']['final_hour']);
        $this->_add_days($final_hour, $deltaDays, $deltaMinutes);
        $booking['Booking']['final_hour'] = $final_hour->format('Y-m-d H:i:s');

        if (!($this->Booking->save($booking))) {
            if ($this->Booking->booking_id_overlaped) {
                $this->Booking->id = $this->Booking->booking_id_overlaped;
                $booking_overlaped = $this->Booking->read();
                $this->set('booking_overlaped', $booking_overlaped);
            } elseif ($this->Booking->event_id_overlaped) {
                $this->loadModel('Event');
                $this->Event->id = $this->Booking->event_id_overlaped;
                $event_overlaped = $this->Event->read();
                $activity_overlaped = $this->Event->Activity->find('first', array('conditions' => array('Activity.id' => $event_overlaped['Activity']['id'])));
                $this->set('event_overlaped', $event_overlaped);
                $this->set('activity_overlaped', $activity_overlaped);
            }
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
    
    function _add_days(&$date, $ndays, $nminutes = 0){
        $date_components = explode('-', $date->format('Y-m-d-H-i-s'));
        $timestamp = mktime($date_components[3],$date_components[4],$date_components[5], $date_components[1], $date_components[2] + $ndays, $date_components[0]);
        $timestamp += ($nminutes * 60);
        $date_string = date('Y-m-d H:i:s', $timestamp);
        $date = new DateTime($date_string);
    }
    
    function _parse_date($date, $separator = '/'){
        $date_components = explode($separator, $date);
        
        return count($date_components) != 3 ? false : date("Y-m-d", mktime(0,0,0, $date_components[1], $date_components[0], $date_components[2]));
    }
    
    function _authorizeEdit($booking) {
        $uid = $this->Auth->user('id');

        if (empty($booking['Classroom']['id']) && !empty($booking['Booking']['classroom_id']) && $booking['Booking']['classroom_id'] != -1) {
            $classroom = $this->Booking->Classroom->find('first', array(
                'fields' => array('Classroom.id', 'Classroom.institution_id', 'Classroom.teachers_can_booking'), 'conditions' => array('Classroom.id' => $booking['Booking']['classroom_id']),
                'recursive' => -1
            ));
            if ($classroom) {
                $booking['Classroom'] = $classroom['Classroom'];
            }
        }

        if ($booking['Booking']['user_id'] != $uid && $booking['Booking']['institution_id'] != Environment::institution('id')) {
            if (empty($booking['Classroom']['institution_id']) || $booking['Classroom']['institution_id'] != Environment::institution('id')) {
                return false;
            }
        }

        if (Configure::read('app.classroom.teachers_can_booking') && $this->Auth->user('type') === 'Profesor') {
            return $booking['Booking']['user_id'] == $uid && !empty($booking['Classroom']['teachers_can_booking']);
        }

        return ($this->Auth->user('type') === "Administrador") || ($this->Auth->user('type') === "Administrativo") || ($this->Auth->user('type') === "Conserje");
    }

    function _authorizeDelete($booking) {
        return $this->_authorizeEdit($booking);
    }

    function _authorize() {
        parent::_authorize();
        
        if (($this->params['action'] == "get") || ($this->params['action'] == "view")) {
            return true;
        }

        if (($this->Auth->user('type') != "Administrador") && ($this->Auth->user('type') != "Administrativo") && ($this->Auth->user('type') != "Conserje")) {
            if (!Configure::read('app.classroom.teachers_can_booking') || $this->Auth->user('type') !== "Profesor") {
                return false;
            }
        }
        
        return true;
    }
}
