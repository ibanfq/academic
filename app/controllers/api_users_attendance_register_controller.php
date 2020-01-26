<?php
class ApiUsersAttendanceRegisterController extends AppController {
    var $name = 'UsersAttendanceRegister';
    var $isApi = true;
    

    function _authorize(){
        $authorize = parent::_authorize();

        $no_institution_actions = array("add");
        $public_actions = array("add");
        $private_actions = array("index");
        $private_or_teacher_actions = array("delete");

        if (array_search($this->params['action'], $no_institution_actions) === false && ! Environment::institution('id')) {
            $this->Api->setError('No se ha especificado la institución en la url de la petición.', 400);
            $this->Api->respond($this);
            return;
        }

        if (!$authorize) {
            if (array_search($this->params['action'], $public_actions) !== false) {
                return true;
            }
            return false;
        }
        
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

    function _allowAnonymousActions() {
        if (Configure::read('app.users_attendance_register.by_password')) {
            $this->Auth->allow('add');
        }

        parent::_allowAnonymousActions();
    }
    
    function add() {
        $attendance_id = false;
        $attendanceRegister = false;
        $student_id = false;
        $student = false;
        
        $is_anonymous = $this->Auth->user('id') === null;
        $is_student = $this->Auth->user('type') === "Estudiante";
        $is_teacher = $this->Auth->user('type') === "Profesor";
        
        $this->UserAttendanceRegister->AttendanceRegister->unbindModel(array('hasAndBelongsToMany' => array('User')), false);
        $this->UserAttendanceRegister->AttendanceRegister->bindModel(array('belongsTo' => array(
            'Classroom' => array(
                'foreignKey' => false,
                'conditions' => array('Classroom.id = Event.classroom_id')
            )
        )));
        
        if (!$is_anonymous && !$is_student) {
            $attendance_id = $this->Api->getParameter('AttendanceRegister.id', array('integer'));
            $attendance_id = $this->Api->getParameter('UserAttendanceRegister.attendance_id', array('required', 'integer'), $attendance_id);
            $student_id = $this->Api->getParameter('User.id', array('integer'));
            $student_id = $this->Api->getParameter('UserAttendanceRegister.user_id', array('required', 'integer'), $student_id);
        }
        
        $username = $this->Api->getParameter('User.username');
        $dni = $this->Api->getParameter('User.dni', ($is_anonymous && empty($username)? array('required') : array()));
        $password = $this->Api->getParameter('User.password', ($is_anonymous? array('required', 'password') : array('password')));
        $secret_code = $this->Api->getParameter('AttendanceRegister.secret_code', ($is_anonymous || $is_student? array('required') : array()));
        
        if ($attendance_id) {
            if (! Environment::institution('id')) {
                $this->Api->setError('No se ha especificado la institución en la url de la petición.', 400);
                $this->Api->respond($this);
                return;
            }

            $db = $this->UserAttendanceRegister->getDataSource();
            $attendanceRegister = $this->UserAttendanceRegister->AttendanceRegister->find('first', array(
                'conditions' => array(
                    'AttendanceRegister.id' => $attendance_id,
                    "Event.classroom_id IN (SELECT classroom_id FROM classrooms_institutions ClassroomInstitution WHERE ClassroomInstitution.institution_id = {$db->value(Environment::institution('id'))})"
                )
            ));
            if ($attendanceRegister && $is_teacher) {
                if ($attendanceRegister['AttendanceRegister']['teacher_id'] !== $this->Auth->user('id') && $attendanceRegister['AttendanceRegister']['teacher_2_id'] !== $this->Auth->user('id')) {
                    $attendanceRegister = false;
                } else {
                    $students_count = $this->UserAttendanceRegister->query("
                        SELECT count('') as total
                        FROM users_attendance_register
                        WHERE attendance_register_id = {$attendanceRegister['AttendanceRegister']['id']} AND user_gone
                    ");
                    if ($students_count && intval($students_count[0][0]['total']) === 0) {
                        $this->Api->setError('No puedes añadir estudiantes hasta que al menos uno se haya registrado ya usando el código de acceso.', 403);
                        $attendanceRegister = false;
                    }
                }
            }
        } elseif (strlen($secret_code)) {
            $today = date('Y-m-d');
            $tomorrow = date('Y-m-d', strtotime('tomorrow'));
            $attendanceRegister = $this->UserAttendanceRegister->AttendanceRegister->find('first', array(
                'conditions' => array(
                    'AttendanceRegister.secret_code' => $secret_code,
                    'OR' => array(
                        'AttendanceRegister.initial_hour BETWEEN ? AND ?' => array($today, $tomorrow),
                        'Event.initial_hour BETWEEN ? AND ?' => array($today, $tomorrow)
                    )
                )
            ));
            if (!$attendanceRegister) {
                $this->Api->setError('No existe ningún evento con ese código.', 404);
            }
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
            } elseif (strlen($dni)) {
                $student = $this->UserAttendanceRegister->AttendanceRegister->Student->find('first', array(
                    'conditions' => array(
                        'dni' => $dni,
                        'type' => 'Estudiante',
                        'password' => $password
                    ),
                    'recursive' => -1
                ));
            } elseif (strlen($username)) {
                $student = $this->UserAttendanceRegister->AttendanceRegister->Student->find('first', array(
                    'conditions' => array(
                        'OR' => array(
                            'username' => $username,
                            'dni' => $username
                        ),
                        'type' => 'Estudiante',
                        'password' => $password
                    ),
                    'recursive' => -1
                ));
            } elseif ($is_student) {
                $user = $this->Auth->user();
                $student = array('Student' => $user['User']);
            }
            
            if ($student && ($is_anonymous || $is_student)) {
                $student_id = intval($student['Student']['id']);
                $student_in_subject = (bool) $this->UserAttendanceRegister->query(
                    "SELECT id FROM subjects_users WHERE subject_id = {$attendanceRegister['Activity']['subject_id']} AND user_id = {$student_id}"
                );
                if (!$student_in_subject) {
                    $this->Api->setError('No puedes registrarte sin estar matriculado en la asignatura.', 403);
                    $student = false;
                }
                $closed_attendance_groups = (bool) $this->UserAttendanceRegister->query(
                    "SELECT closed_attendance_groups FROM subjects WHERE id = {$attendanceRegister['Activity']['subject_id']} and closed_attendance_groups"
                );
                if ($closed_attendance_groups) {
                    $student_in_group = (bool) $this->UserAttendanceRegister->query(
                        "SELECT id FROM registrations WHERE group_id = {$attendanceRegister['AttendanceRegister']['group_id']} AND activity_id = {$attendanceRegister['AttendanceRegister']['activity_id']} AND student_id = {$student_id}"
                    );
                    if (!$student_in_group) {
                        $this->Api->setError('No puedes registrarte sin estar matriculado en el grupo.', 403);
                        $student = false;
                    }
                }
            }

            if ($student) {
                $this->Api->clearFails();
                $student_id = $student['Student']['id'];
                
                if (($is_anonymous || $is_student || $is_teacher) && empty($attendanceRegister['AttendanceRegister']['secret_code'])) {
                    $this->Api->setError('No se ha podido registar al estudiante debido a que la hoja de asistencia ya ha sido finalizada.', 403);
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
                        $this->Api->setError('No se ha podido registar al usuario debido a un error con el servidor.', 500);
                    }
                }
            } else if ($this->Api->getStatus() === 'success') {
                $this->Api->setError('No se ha podido encontrar al usuario.', 404);
            }
        } else if ($this->Api->getStatus() === 'success') {
            $this->Api->setError('No se ha podido acceder a la hoja de asistencia.', 404);
        }
        
        $this->Api->respond($this);
    }
    
    function delete($user_id, $attendance_id) {
        $user_id = $user_id === null ? null : intval($user_id);
        $attendance_id = $attendance_id === null ? null : intval($attendance_id);
        $this->UserAttendanceRegister->AttendanceRegister->unbindModel(array('hasAndBelongsToMany' => array('User')), false);
        $this->UserAttendanceRegister->AttendanceRegister->bindModel(array('belongsTo' => array(
            'Classroom' => array(
                'foreignKey' => false,
                'conditions' => array('Classroom.id = Event.classroom_id')
            )
        )));
        $db = $this->UserAttendanceRegister->getDataSource();
        $attendanceRegister = $this->UserAttendanceRegister->AttendanceRegister->find('first', array(
            'conditions' => array(
                'AttendanceRegister.id' => $attendance_id,
                "Event.classroom_id IN (SELECT classroom_id FROM classrooms_institutions ClassroomInstitution WHERE ClassroomInstitution.institution_id = {$db->value(Environment::institution('id'))})"
            )
        ));
        
        if ($attendanceRegister && $this->Auth->user('type') === "Profesor") {
            if ($attendanceRegister['AttendanceRegister']['teacher_id'] !== $this->Auth->user('id') && $attendanceRegister['AttendanceRegister']['teacher_2_id'] !== $this->Auth->user('id')) {
                $attendanceRegister = false;
            } else if (empty($attendanceRegister['AttendanceRegister']['secret_code'])) {
                $attendanceRegister = false;
                $this->Api->setError('No se ha podido quitar al estudiante debido a que la hoja de asistencia ya ha sido finalizada.', 403);
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
                $this->Api->setError('No se ha encontrado al usuario.', 404);
            }
        } else if ($this->Api->getStatus() === 'success') {
            $this->Api->setError('No se ha podido acceder a la hoja de asistencia.', 404);
        }
        
        $this->Api->respond($this);
    }
}
