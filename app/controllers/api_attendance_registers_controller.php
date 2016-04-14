<?php
class ApiAttendanceRegistersController extends AppController {
  var $name = 'AttendanceRegisters';
  var $isApi = true;

  function _authorize(){
		parent::_authorize();

		if (($this->Auth->user('type') != "Profesor") && ($this->Auth->user('type') != "Administrador") && ($this->Auth->user('type') != "Administrativo") && ($this->Auth->user('type') != "Becario")) {
			return false;
    }

		return true;
	}
  
  function add()
  {
    $event_id = $this->Api->getParameter('Event.id', array('required', 'integer'));

    if ($event_id) {
      $event = $this->AttendanceRegister->Event->findById($event_id);
      $attendance_register = false;
      if ($event) {
        $secret_code = null;
        if (empty($event['AttendanceRegister']['secret_code'])) {
          $secret_code = strtoupper(substr(base_convert(uniqid(mt_rand(), true), 10, 36), 0, 6));
        }
        $attendance_register = $this->AttendanceRegister->createFromEvent($event, $secret_code);
        $attendance_register['AttendanceRegister']['Students'] = &$attendance_register['AttendanceRegister']['Student'];
        unset($attendance_register['AttendanceRegister']['Student']);
      }
      $this->Api->setData($attendance_register);
    }

    $this->Api->setViewVars($this);
  }
}
