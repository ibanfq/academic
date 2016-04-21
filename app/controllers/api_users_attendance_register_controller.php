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
    
    $private_actions = array("index");
    $private_or_teacher_actions = array("delete");
    
    if (($this->Auth->user('type') !== "Estudiante") && ($this->Auth->user('type') !== "Profesor") && ($this->Auth->user('type') !== "Administrador") && ($this->Auth->user('type') !== "Administrativo") && ($this->Auth->user('type') !== "Becario")) {
			return false;
    }

    if ((array_search($this->params['action'], $private_actions) !== false) && (($this->Auth->user('type') === "Estudiante") || $this->Auth->user('type') === "Profesor")) {
			return false;
    }
    
    if ((array_search($this->params['action'], $private_or_teacher_actions) !== false) && ($this->Auth->user('type') === "Estudiante")) {
			return false;
    }

		return true;
	}
  
  function add(){
    $attendance_id = false;
    $attendanceRegister = false;
    $student_id = false;
    $student = false;
    
    $is_anonymous = $this->Auth->user('id') === null;
    $is_student = $this->Auth->user('type') === "Estudiante";
    $is_teacher = $this->Auth->user('type') === "Profesor";
    
    $this->UserAttendanceRegister->AttendanceRegister->unbindModel(array('hasAndBelongsToMany' => array('User')), false);
    
    if (!$is_anonymous && !$is_student) {
      $attendance_id = $this->Api->getParameter('AttendanceRegister.id', array('integer'));
      $attendance_id = $this->Api->getParameter('UserAttendanceRegister.attendance_id', array('required', 'integer'), $attendance_id);
      $student_id = $this->Api->getParameter('User.id', array('integer'));
      $student_id = $this->Api->getParameter('UserAttendanceRegister.user_id', array('required', 'integer'), $student_id);
    }
    
    $dni = $this->Api->getParameter('User.dni', ($is_anonymous? array('required', 'integer') : array('integer')));
    $secret_code = $this->Api->getParameter('AttendanceRegister.secret_code', ($is_anonymous || $is_student? array('required') : array()));
    
    if ($attendance_id) {
      $attendanceRegister = $this->UserAttendanceRegister->AttendanceRegister->read(null, $attendance_id);
      if ($attendanceRegister && $is_teacher) {
        if ($attendanceRegister['AttendanceRegister']['teacher_id'] !== $this->Auth->user('id') && $attendanceRegister['AttendanceRegister']['teacher_2_id'] !== $this->Auth->user('id')) {
          $attendanceRegister = false;
        }
      }
    } else if (strlen($secret_code)) {
      $attendanceRegister = $this->UserAttendanceRegister->AttendanceRegister->findBySecretCode($secret_code);
    }
    
    if ($attendanceRegister) {
      $attendance_id = $attendanceRegister['AttendanceRegister']['id'];
      
      if ($student_id) {
        $student = $this->UserAttendanceRegister->AttendanceRegister->Student->findById(
          $student_id,
          array(), // Fields
          array(), // Order
          -1 // Recursive
        );
      } else if (strlen($dni)) {
        $student = $this->UserAttendanceRegister->AttendanceRegister->Student->findByDni(
          $dni,
          array(), // Fields
          array(), // Order
          -1 // Recursive
        );
      } else if ($is_student) {
        $student = array('Student' => $this->Auth->user());
      }
      
      if ($student && ($is_anonymous || $is_student)) {
        $subject_user_id = $this->UserAttendanceRegister->query(
          "SELECT id FROM subjects_users WHERE subject_id = {$attendanceRegister['Activity']['subject_id']} AND user_id = {$student['Student']['id']}"
        );
        if (!$subject_user_id) {
          $this->Api->setError('No puedes registrarte sin estar matriculado en la asignatura.');
          $student = false;
        }
      }

      if ($student) {
        $this->Api->clearFails();
        $student_id = $student['Student']['id'];
        
        if (($is_anonymous || $is_student || $is_teacher) && empty($attendanceRegister['AttendanceRegister']['secret_code'])) {
          $this->Api->setError('No se ha podido registar al estudiante debido a que la hoja de asistencia ya ha sido finalizada.');
        } else {
          $conditions = array(
            'UserAttendanceRegister.user_id' => $student_id,
            'UserAttendanceRegister.attendance_register_id' => $attendanceRegister['AttendanceRegister']['id'],
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
                'attendance_register_id' => $attendanceRegister['AttendanceRegister']['id'],
                'user_gone' => 1
              )
            );
            if (!$this->UserAttendanceRegister->save($userAttendanceRegister['UserAttendanceRegister'])) {
              $userAttendanceRegister = false;
            }
          }

          if ($userAttendanceRegister) {
            $attendanceRegister['Student'] = $student['Student'];
            $attendanceRegister['UserAttendanceRegister'] = $userAttendanceRegister['UserAttendanceRegister'];
            if (!$is_anonymous && !$is_student) {
              $attendanceRegister['Students'] = $this->UserAttendanceRegister->AttendanceRegister->getStudentsForApi(
                $attendanceRegister['AttendanceRegister']['id'],
                $attendanceRegister['AttendanceRegister']['activity_id'],
                $attendanceRegister['AttendanceRegister']['group_id']
              );
            }
            $this->Api->setData($attendanceRegister);
          } else {
            $this->Api->setError('No se ha podido registar al usuario debido a un error con el servidor.');
          }
        }
      } else if ($this->Api->getStatus() === 'success') {
        $this->Api->setError('No se ha podido encontrar al usuario.');
      }
    } else if ($this->Api->getStatus() === 'success') {
      $this->Api->setError('No se ha podido acceder a la hoja de asistencias.');
    }
    
    $this->Api->respond($this);
  }
  
  function delete($user_id, $attendance_id) {
    $this->UserAttendanceRegister->AttendanceRegister->unbindModel(array('hasAndBelongsToMany' => array('User')), false);
    $attendanceRegister = $this->UserAttendanceRegister->AttendanceRegister->read(null, $attendance_id);
    
    if ($attendanceRegister && $this->Auth->user('type') === "Profesor") {
      if ($attendanceRegister['AttendanceRegister']['teacher_id'] !== $this->Auth->user('id') && $attendanceRegister['AttendanceRegister']['teacher_2_id'] !== $this->Auth->user('id')) {
        $attendanceRegister = false;
      } else if (empty($attendanceRegister['AttendanceRegister']['secret_code'])) {
        $attendanceRegister = false;
        $this->Api->setError('No se ha podido quitar al estudiante debido a que la hoja de asistencia ya ha sido finalizada.');
      }
    }
    
    if ($attendanceRegister) {
      $student = $this->UserAttendanceRegister->AttendanceRegister->Student->findById(
        $user_id,
        array(), // Fields
        array(), // Order
        -1 // Recursive
      );
      
      if ($student) {
        $attendanceRegister['Student'] = $student['Student'];
        
        $conditions = array(
          'UserAttendanceRegister.user_id' => $user_id,
          'UserAttendanceRegister.attendance_register_id' => $attendance_id,
        );
        $userAttendanceRegister = $this->UserAttendanceRegister->find(
          'first',
          array('conditions' => $conditions, 'recursive' => -1)
        );

        if ($userAttendanceRegister && !empty($userAttendanceRegister['UserAttendanceRegister']['user_gone'])) {
          $this->loadModel('Registration');
          $registration = $this->Registration->findByGroupIdAndActivityIdAndStudentId(
            $attendanceRegister['AttendanceRegister']['group_id'],
            $attendanceRegister['AttendanceRegister']['activity_id'],
            $user_id,
            array(), // Fields
            array(), // Order
            -1 // Recursive
          );

          if ($registration) {
            $userAttendanceRegister['UserAttendanceRegister']['user_gone'] = 0;
            $this->UserAttendanceRegister->updateAll(array('UserAttendanceRegister.user_gone' => 0), $conditions);
          } else {
            $this->UserAttendanceRegister->query(
              "DELETE FROM users_attendance_register WHERE user_id = $user_id AND attendance_register_id = $attendance_id"
            );
          }
        }
        
        $attendanceRegister['Students'] = $this->UserAttendanceRegister->AttendanceRegister->getStudentsForApi(
          $attendanceRegister['AttendanceRegister']['id'],
          $attendanceRegister['AttendanceRegister']['activity_id'],
          $attendanceRegister['AttendanceRegister']['group_id']
        );
        
        $this->Api->setData($attendanceRegister);
      } else if ($this->Api->getStatus() === 'success') {
        $this->Api->setError('No se ha encontrado al usuario.');
      }
    } else if ($this->Api->getStatus() === 'success') {
      $this->Api->setError('No se ha podido acceder a la hoja de asistencias.');
    }
    
    $this->Api->respond($this);
  }
}
