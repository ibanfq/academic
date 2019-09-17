<?php
class UsersAttendanceRegisterController extends AppController {
    var $name = 'UsersAttendanceRegister';

    function add_by_secret_code() {
        if ($this->data) {
            $error = false;
            $data = array(
                    'User' => array(),
                    'AttendanceRegister' => array()
            );
            
            if ($this->Auth->user('id') == null || $this->Auth->user('type') !== "Estudiante") {
                if (empty($this->data['User']['username'])) {
                    $this->UserAttendanceRegister->User->invalidate('username', 'Por favor, introduzca su correo electrónico o DNI');
                    $error = true;
                } elseif (strpos($this->data['User']['username'], '@') === false) {
                    $data['User']['dni'] = $this->data['User']['username'];
                } else {
                    $data['User']['username'] = $this->data['User']['username'];
                }
                
                if (empty($_POST['data']['User']['password'])) {
                    $this->UserAttendanceRegister->User->invalidate('password', 'Por favor, introduzca su contraseña');
                    $error = true;
                } else {
                    $data['User']['password'] = $_POST['data']['User']['password'];
                }
            }
            
            if (empty($this->data['AttendanceRegister']['secret_code'])) {
                $this->UserAttendanceRegister->AttendanceRegister->invalidate('secret_code', 'Por favor, introduzca el código proporcionado por el profesor');
                $error = true;
            } else {
                $data['AttendanceRegister']['secret_code'] = $this->data['AttendanceRegister']['secret_code'];
            }
            
            $this->UserAttendanceRegister->set($this->data);
            if (!$error && $this->UserAttendanceRegister->validates()) {
                if (Environment::institution('id')) {
                    $response = $this->Api->call(
                        'POST',
                        '/api/institutions/'.Environment::institution('id').'/users_attendance_register',
                        $data
                    );
                } else {
                    $response = $this->Api->call(
                        'POST',
                        '/api/users_attendance_register',
                        $data
                    );
                }
                if ($response['status'] === 'success') {
                    $this->Session->setFlash("Te has registrado correctamente en el grupo \"{$response['data']['Group']['name']}\" de la actividad \"{$response['data']['Activity']['name']}\".");
                    $this->redirect(array('action' => 'add_by_secret_code'));
                } else if ($response['status'] === 'error') {
                    $this->Session->setFlash($response['message']);
                } else {
                    $this->Session->setFlash('No se ha podido registrar al estudiante debido a un error inesperado');
                }
            }
        }
    }
    
    function _authorize(){
        parent::_authorize();
        
        $no_institution_actions = array("add_by_secret_code");
        $public_actions = array("add_by_secret_code");
        $private_actions = array();

        if (array_search($this->params['action'], $no_institution_actions) === false && ! Environment::institution('id')) {
            return false;
        }
        
        if (array_search($this->params['action'], $public_actions) !== false) {
            return true;
        }

        if (($this->Auth->user('type') != "Profesor") && ($this->Auth->user('type') != "Administrador") && ($this->Auth->user('type') != "Administrativo") && ($this->Auth->user('type') != "Becario")) {
            return false;
        }

        if (($this->Auth->user('type') != "Administrador") && ($this->Auth->user('type') != "Becario") && ($this->Auth->user('type') != "Administrativo") && (array_search($this->params['action'], $private_actions) !== false)) {
            return false;
        }

        $this->set('section', 'users_attendance_register');
        return true;
    }
}
