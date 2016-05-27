<?php
class ApiAttendanceRegistersController extends AppController {
  var $name = 'AttendanceRegisters';
  var $isApi = true;

  function _authorize(){
		if (!parent::_authorize()) {
      return false;
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
  
  function view($id){
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
    $status = $this->Api->getParameter('AttendanceRegister.status', array('required'));
    
    if ($this->Api->getStatus() === 'success') {
      $attendanceRegister = $this->AttendanceRegister->findById(
        $id,
        array(), // Fields
        array(), // Order
        -1 // Recursive
      );
      
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
            } else {
              $students = $this->AttendanceRegister->query("
                SELECT Student.*, UserAttendanceRegister.*
                FROM users Student
                INNER JOIN users_attendance_register UserAttendanceRegister ON UserAttendanceRegister.user_id = Student.id
                  AND UserAttendanceRegister.user_gone
                WHERE UserAttendanceRegister.attendance_register_id = {$id}
                ORDER BY Student.last_name, Student.first_name
              ");

              if ($students === false) {
                $this->Api->setError('No se ha podido finalizar el evento debido a un error con el servidor.');
              } else if (empty($students)) {
                $this->Api->setError('No se puede registrar un evento sin alumnos.');
              } else {
                $attendanceRegister['Student'] = array();
                foreach ($students as $student) {
                  $attendanceRegister['Student'][] = array(
                      'UserAttendanceRegister' => $student['UserAttendanceRegister']
                  );
                }
                $initial_date = date_create($attendanceRegister['AttendanceRegister']['initial_hour']);
                $final_date = date_create($attendanceRegister['AttendanceRegister']['final_hour']);

                $attendanceRegister['AttendanceRegister']['secret_code'] = null;
                $attendanceRegister['AttendanceRegister']['date'] = $initial_date->format('d-m-Y');
                $attendanceRegister['AttendanceRegister']['initial_hour'] = $initial_date->format('H:i');
                $attendanceRegister['AttendanceRegister']['final_hour'] = $final_date->format('H:i');
                $attendanceRegister['AttendanceRegister']['num_students'] = count($attendanceRegister['Student']);

                if ($this->AttendanceRegister->save($attendanceRegister)) {
                  $this->_view($id);
                  $attendanceRegister = $this->Api->getStatus() === 'success'? $this->Api->getData() : false;

                  if ($attendanceRegister) {
                    $this->Email->reset();
                    $this->Email->from = 'Academic <noreply@ulpgc.es>';
                    $this->Email->to = $attendanceRegister['Teacher']['username'];
                    $this->Email->subject = "Evento registrado";
                    $this->Email->sendAs = 'both';
                    $this->Email->template = 'attendance_register_closed';
                    $this->set('teacher', $attendanceRegister['Teacher']);
                    $this->set('attendanceRegister', $attendanceRegister);
                    $this->Email->send();
                    if (!empty($attendanceRegister['Teacher_2']['username'])) {
                      $this->Email->to = $attendanceRegister['Teacher_2']['username'];
                      $this->set('teacher', $attendanceRegister['Teacher_2']);
                      $this->Email->send();
                    }
                  }
                } else {
                  $this->Api->setError('No se ha podido registrar el evento debido a un error con el servidor.');
                }
              }
            }
            break;

          default:
            $this->Api->addFail('AttendanceRegistar.status', 'Invalid');
        }
      } else {
        $this->Api->setError('No se ha podido acceder a la hoja de asistencia.');
      }
    }

    $this->Api->respond($this);
  }
  
  function _view($id) {
    $this->AttendanceRegister->unbindModel(array('hasAndBelongsToMany' => array('Student')));
    $this->AttendanceRegister->bindModel(array('belongsTo' => array(
      'Classroom' => array(
        'foreignKey' => false,
        'conditions' => array('Classroom.id = Event.classroom_id')
      )
    )));
    $attendance_register = $this->AttendanceRegister->read(null, $id);
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
      $this->Api->setError('No se ha podido acceder a la la hoja de asistencia.');
    }
  }
  
  function _openByEvent($event_id) {
    $this->AttendanceRegister->Event->unbindModel(array('belongsTo' => array('Parent'), 'hasMany' => array('Events')));
    $event = $this->AttendanceRegister->Event->findById($event_id);
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
        $this->Api->setError('Sólo puedes crear las asistencias de los eventos que impartes hoy.');
      }
    } else {
      $this->Api->setError('No se ha podido acceder al evento.');
    }
  }
}
