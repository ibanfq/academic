<?php
class ApiAttendanceRegistersController extends AppController {
    var $name = 'AttendanceRegisters';
    var $isApi = true;

    function _authorize(){
        if (!parent::_authorize()) {
            return false;
        }

        if (! Environment::institution('id')) {
            $this->Api->setError('No se ha especificado la institución en la url de la petición.', 400);
            $this->Api->respond($this);
            return;
        }
        
        $private_actions = array("index", "delete");

        if (($this->Auth->user('type') !== "Profesor") && ($this->Auth->user('type') !== "Administrador") && ($this->Auth->user('type') !== "Administrativo") && ($this->Auth->user('type') !== "Becario")) {
            return false;
        }

        if ((array_search($this->params['action'], $private_actions) !== false) && ($this->Auth->user('type') === "Profesor")) {
            return false;
        }

        return true;
    }
    
    function view($id) {
        $id = $id === null ? null : intval($id);
        $this->_view($id);
        
        $this->Api->respond($this);
    }
    
    function add(){
        $event_id = $this->Api->getParameter('Event.id', array('integer'));
        $event_id = $this->Api->getParameter('AttendanceRegister.event_id', array('required', 'integer'), $event_id);
        
        if ($event_id) {
            $this->_openByEvent($event_id);
        }

        $this->Api->respond($this);
    }
    
    function edit($id){
        $id = $id === null ? null : intval($id);
        $status = $this->Api->getParameter('AttendanceRegister.status', array('required'));
        
        if ($this->Api->getStatus() === 'success') {
            $db = $this->AttendanceRegister->getDataSource();
            $attendanceRegister = $this->AttendanceRegister->find('first', array(
                'joins' => array(
                    array(
                        'table' => 'events',
                        'alias' => 'Event',
                        'type' => 'INNER',
                        'conditions' => 'Event.id = AttendanceRegister.event_id'
                    )
                ),
                'conditions' => array(
                    'AttendanceRegister.id' => $id,
                    "Event.classroom_id IN (SELECT classroom_id FROM classrooms_institutions ClassroomInstitution WHERE ClassroomInstitution.institution_id = {$db->value(Environment::institution('id'))})"
                ),
                'recursive' => -1
            ));
            
            if ($attendanceRegister && ($this->Auth->user('type') === "Profesor")) {
                $event = $this->AttendanceRegister->Event->findById(
                    $attendanceRegister['AttendanceRegister']['event_id'],
                    array(), // Fields
                    array(), // Order
                    -1 // Recursive
                );
                if ($event) {
                    if ($event['Event']['teacher_id'] !== $this->Auth->user('id') && $event['Event']['teacher_2_id'] !== $this->Auth->user('id')) {
                        $attendanceRegister = false;
                    } else if ($attendanceRegister['AttendanceRegister']['teacher_id'] !== $this->Auth->user('id') && $attendanceRegister['AttendanceRegister']['teacher_2_id'] !== $this->Auth->user('id')) {
                        $attendanceRegister = false;
                    }
                }
            }
            
            if ($attendanceRegister) {
                switch (strtolower($status)) {
                    case 'opened':
                        $this->_openByEvent($attendanceRegister['AttendanceRegister']['event_id']);
                        break;

                    case 'closed':
                        if (empty($attendanceRegister['AttendanceRegister']['secret_code'])) {
                            $this->_view($id);
                        } else if (Configure::read('api.test.secret_code') && Configure::read('testing.secret_code') === $attendanceRegister['AttendanceRegister']['secret_code']) {
                            $this->_view($id);
                        } else {
                            $students = $this->AttendanceRegister->getStudentsWithUserGone($id);

                            if ($students === false) {
                                $this->Api->setError('No se ha podido finalizar el evento debido a un error con el servidor.', 500);
                            } else if (empty($students)) {
                                $this->Api->setError('No se puede registrar un evento sin alumnos.', 403);
                            } else if (!$this->AttendanceRegister->close($attendanceRegister, $students)) {
                                $this->Api->setError('No se ha podido registrar el evento debido a un error con el servidor.', 500);
                            } else {
                                $this->_view($id);
                                $attendanceRegister = $this->Api->getStatus() === 'success'? $this->Api->getData() : false;
                                
                                if ($attendanceRegister) {
                                    $this->AttendanceRegister->notifyAttendanceRegisterClosed($attendanceRegister, $this);
                                } else {
                                    $this->Api->setError('No se ha podido registrar el evento debido a un error con el servidor.', 500);
                                }
                            }
                        }
                        break;

                    default:
                        $this->Api->addFail('AttendanceRegistar.status', 'Invalid');
                }
            } else {
                $this->Api->setError('No se ha podido acceder a la hoja de asistencia.', 404);
            }
        }

        $this->Api->respond($this);
    }
    
    function _view($id) {
        $id = $id === null ? null : intval($id);
        $db = $this->AttendanceRegister->getDataSource();
        $this->AttendanceRegister->unbindModel(array('hasAndBelongsToMany' => array('Student')));
        $this->AttendanceRegister->bindModel(array('belongsTo' => array(
            'Classroom' => array(
                'foreignKey' => false,
                'conditions' => array('Classroom.id = Event.classroom_id')
            )
        )));
        $attendance_register = $this->AttendanceRegister->find('first', array(
            'conditions' => array(
                'AttendanceRegister.id' => $id,
                "Event.classroom_id IN (SELECT classroom_id FROM classrooms_institutions ClassroomInstitution WHERE ClassroomInstitution.institution_id = {$db->value(Environment::institution('id'))})"
            )
        ));
        if ($attendance_register && ($this->Auth->user('type') === "Profesor")) {
            if ($attendance_register['AttendanceRegister']['teacher_id'] !== $this->Auth->user('id') && $attendance_register['AttendanceRegister']['teacher_2_id'] !== $this->Auth->user('id')) {
                $attendance_register = false;
            }
        }
        
        if ($attendance_register) {
            $attendance_register['Students'] = $this->AttendanceRegister->getStudentsForApi(
                $id,
                $attendance_register['AttendanceRegister']['activity_id'],
                $attendance_register['AttendanceRegister']['group_id']
            );
            $this->Api->setData($attendance_register);
        } else {
            $this->Api->setError('No se ha podido acceder a la la hoja de asistencia.', 404);
        }
    }
    
    function _openByEvent($event_id) {
        $event_id = $event_id === null ? null : intval($event_id);
        $db = $this->AttendanceRegister->getDataSource();
        $this->AttendanceRegister->Event->unbindModel(array('belongsTo' => array('Parent'), 'hasMany' => array('Events')));
        $event = $this->AttendanceRegister->Event->find('first', array(
            'conditions' => array(
                'Event.id' => $event_id,
                "Event.classroom_id IN (SELECT classroom_id FROM classrooms_institutions ClassroomInstitution WHERE ClassroomInstitution.institution_id = {$db->value(Environment::institution('id'))})"
            )
        ));
        if ($event && ($this->Auth->user('type') === "Profesor")) {
            if ($event['Event']['teacher_id'] !== $this->Auth->user('id') && $event['Event']['teacher_2_id'] !== $this->Auth->user('id')) {
                $event = false;
            }
        }

        if ($event) {
            $today = new DateTime("today");
            $initial_date = date_create($event['Event']['initial_hour']);
            $initial_date->setTime(0, 0, 0);
            if ($today->format('Ymd') === $initial_date->format('Ymd')) {
                $secret_code = null;
                if (empty($event['AttendanceRegister']['secret_code'])) {
                    $secret_code = mt_rand(100000, 999999);
                }
                $attendance_register = $this->AttendanceRegister->createFromEvent($event, false, $secret_code);
                $attendance_register['Students'] = &$attendance_register['AttendanceRegister']['Student'];
                unset($attendance_register['AttendanceRegister']['Student']);
                $this->Api->setData($attendance_register);
            } else {
                $this->Api->setError('Sólo puedes crear las asistencias de los eventos que impartes hoy.', 403);
            }
        } else {
            $this->Api->setError('No se ha podido acceder al evento.', 404);
        }
    }
}
