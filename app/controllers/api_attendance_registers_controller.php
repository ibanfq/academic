<?php
class ApiAttendanceRegistersController extends AppController {
  var $name = 'AttendanceRegisters';
  var $isApi = true;

  function _authorize(){
		parent::_authorize();
    
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
    $id = intval($id);
    
    $attendance_register = $this->AttendanceRegister->read(null, $id);
    if ($attendance_register && ($this->Auth->user('type') === "Profesor")) {
      if ($attendance_register['AttendanceRegister']['teacher_id'] !== $this->Auth->user('id') && $attendance_register['AttendanceRegister']['teacher_2_id'] !== $this->Auth->user('id')) {
        $attendance_register = false;
      }
    }
    
    if ($attendance_register) {
      $attendance_register['Students'] = array();
      foreach ($attendance_register['Student'] as $value) {
        $user_attendance_register = $value['UsersAttendanceRegister'];
        unset($user_attendance_register['created']);
        unset($user_attendance_register['modified']);
        unset($value['UsersAttendanceRegister']);
        $attendance_register['Students'][] = array(
            'Student' => $value,
            'UsersAttendanceRegister' => $user_attendance_register
        );
      }
      unset($attendance_register['Student']);
      $this->Api->setData($attendance_register);
    } else {
      $this->Api->setError('No se ha podido acceder a la información de la hoja de asistencia');
    }
    
    $this->Api->setViewVars($this);
  }
  
  function add(){
    $event_id = $this->Api->getParameter('Event.id', array('required', 'integer'));

    if ($event_id) {
      $this->AttendanceRegister->Event->unbindModel(array('belongsTo' => array('Parent'), 'hasMany' => array('Events')));
      $event = $this->AttendanceRegister->Event->findById($event_id);
      if ($event && ($this->Auth->user('type') === "Profesor")) {
        if ($event['Event']['teacher_id'] !== $this->Auth->user('id') && $event['Event']['teacher_2_id'] !== $this->Auth->user('id')) {
          $event = false;
        }
      }
      
      if ($event) {
        if (empty($event['AttendanceRegister']['duration']) || !floatval($event['AttendanceRegister']['duration'])) {
          $secret_code = null;
          if (empty($event['AttendanceRegister']['secret_code'])) {
            $secret_code = strtoupper(substr(base_convert(uniqid(mt_rand(), true), 10, 36), 0, 6));
          }
          $attendance_register = $this->AttendanceRegister->createFromEvent($event, false, $secret_code);
          $attendance_register['Students'] = &$attendance_register['AttendanceRegister']['Student'];
          unset($attendance_register['AttendanceRegister']['Student']);
          $this->Api->setData($attendance_register);
        } else {
          $this->Api->setError('La hoja de asistencia ya ha sido registrada');
        }
      } else {
        $this->Api->setError('No se ha podido acceder a la información del evento');
      }
    }

    $this->Api->setViewVars($this);
  }
}
