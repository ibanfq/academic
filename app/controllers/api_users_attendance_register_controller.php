<?php
class ApiUsersAttendanceRegisterController extends AppController {
  var $name = 'UsersAttendanceRegister';
  var $isApi = true;
  

  function _authorize(){
    $public_actions = array("add");
    
		if (!parent::_authorize()) {
      if (array_search($this->params['action'], $public_actions) !== false) {
        return true;
      }
      return false;
    }
    
    $private_actions = array("index", "delete");
    
    if (($this->Auth->user('type') !== "Estudiante") && ($this->Auth->user('type') !== "Profesor") && ($this->Auth->user('type') !== "Administrador") && ($this->Auth->user('type') !== "Administrativo") && ($this->Auth->user('type') !== "Becario")) {
			return false;
    }

    if ((array_search($this->params['action'], $private_actions) !== false) && (($this->Auth->user('type') === "Estudiante") || $this->Auth->user('type') === "Profesor")) {
			return false;
    }

		return true;
	}
  
  function add(){
    $attendance_register = false;
    
    $is_anonymous = $this->Auth->user('id') === null;
    $is_student = $this->Auth->user('type') === "Estudiante";
    $is_teacher = $this->Auth->user('type') === "Profesor";
    
    if (!$is_anonymous && !$is_student) {
      $attendance_id = $this->Api->getParameter('AttendanceRegister.id', array('integer'));
      $attendance_id = $this->Api->getParameter('UserAttendanceRegister.attendance_id', array('required', 'integer'), $attendance_id);
      $user_id = $this->Api->getParameter('User.id', array('integer'));
      $user_id = $this->Api->getParameter('UserAttendanceRegister.user_id', array('required', 'integer'), $user_id);
      
      if ($attendance_id && $user_id) {
        $attendance_register = $this->UserAttendanceRegister->AttendanceRegister->read(null, $attendance_id);
        if ($attendance_register && $is_teacher) {
          if ($attendance_register['AttendanceRegister']['teacher_id'] !== $this->Auth->user('id') && $attendance_register['AttendanceRegister']['teacher_2_id'] !== $this->Auth->user('id')) {
            $attendance_register = false;
          }
        }
      }
    }
    
    if (!$attendance_register) {
      $student_id = $is_student? $this->Auth->user('id') : false;
      if (empty($student_id)) {
        $dni = $this->Api->getParameter('User.dni', ($is_anonymous? array('required', 'integer') : array('integer')));
        if (!empty($dni)) {
          $this->UserAttendanceRegister->AttendanceRegister->Student->unbindModel(array('hasAndBelongsToMany' => array('Subject'), 'hasMany' => array('Registration')));
          $student = $this->UserAttendanceRegister->AttendanceRegister->Student->findByDni($dni);
          if ($student) {
            $student_id = $student['Student']['id'];
          }
        }
      }
      $secret_code = $this->Api->getParameter('AttendanceRegister.secret_code', ($is_anonymous || $is_student? array('required') : array()));
      
      if (!empty($student_id) && !empty($secret_code)) {
        $attendance_register = $this->UserAttendanceRegister->AttendanceRegister->findBySecretCode($secret_code);
      }
    }

    if ($attendance_register) {
      $this->Api->clearFails();
      if (($is_anonymous || $is_student || $is_teacher) && empty($attendance_register['AttendanceRegister']['secret_code'])) {
        $this->Api->setError('No se ha podido registar al estudiante debido a que la hoja de asistencia ya ha sido finalizada');
      } else {
        $conditions = array(
          'UserAttendanceRegister.user_id' => $student_id,
          'UserAttendanceRegister.attendance_register_id' => $attendance_register['AttendanceRegister']['id'],
        );
        $userAttendanceRegister = $this->UserAttendanceRegister->find('first', array('conditions' => $conditions, 'recursive' => -1));
        
        if ($userAttendanceRegister) {
          if (empty($userAttendanceRegister['UserAttendanceRegister']['user_gone'])) {
            if ($this->UserAttendanceRegister->updateAll(array('UserAttendanceRegister.user_gone' => 1), $conditions)) {
              $userAttendanceRegister['UserAttendanceRegister']['user_gone'] = 1;
            } else {
              $userAttendanceRegister = false;
            }
          }
        } else {
          $userAttendanceRegister = array(
            'UserAttendanceRegister' => array(
              'user_id' => $student_id,
              'attendance_register_id' => $attendance_register['AttendanceRegister']['id'],
              'user_gone' => 1
            )
          );
          if (!$this->UserAttendanceRegister->save($userAttendanceRegister['UserAttendanceRegister'])) {
            $userAttendanceRegister = false;
          }
        }
        
        if ($userAttendanceRegister) {
          unset($userAttendanceRegister['UserAttendanceRegister']['created']);
          unset($userAttendanceRegister['UserAttendanceRegister']['modified']);
          $this->Api->setData($userAttendanceRegister);
        } else {
          $this->Api->setError('No se ha podido registar al usuario debido a un error con el servidor');
        }
      }
    } else if ($this->Api->getStatus() === 'success') {
      $this->Api->setError('No se ha podido acceder a la hoja de asistencias');
    }
    
    $this->Api->setViewVars($this);
  }
}
