<?php
class UsersController extends AppController {
    var $name = 'Users';
    var $paginate = array('limit' => 10, 'order' => array('User.last_name' => 'asc', 'User.first_name' => 'asc'));
    var $helpers = array('modelHelper', 'activityHelper');
    var $fields_fillable = array('User');
    var $fields_guarded = array('User' => ['id', 'created', 'modified']);
    var $refs_sections = array('competence' => 'courses');

    function login() {
        if ($this->Auth->user('id')) {
            $this->redirect($this->Auth->redirect(), null, true);
        }
        
        $this->set('action', 'login');
        $this->Auth->loginError = "El nombre de usuario y contraseña no son correctos";
    }

    function login_as($type) {
        if (! $this->Auth->user('id') || ! $this->Auth->user('__LOGGED_WITH_CAS__')) {
            $this->redirect(Router::normalize($this->Auth->logoutRedirect));
        }

        $user = $this->User->find('first', array(
            'conditions' => array(
                'User.dni' => Environment::user('dni'),
                'User.type' => $type,
            ),
            'recursive' => 0
        ));

        if ($user) {
            $user = $user['User'];
            $user_types = $this->Auth->user('types');
            if ($this->Auth->login($user)) {
                unset($user['password']);
                $user['__LOGGED_WITH_CAS__'] = true;
                $user['types'] = $user_types;
                $this->Auth->Session->write($this->Auth->sessionKey, $user);
            }
        }
        
        $this->redirect(array('controller' => 'users', 'action' => 'home'));
    }

    function cas_login() {
        $this->Cas->forceAuthentication();
        $this->redirect($this->Auth->redirect(), null, true);
    }

    function logout() {
        if (! $this->Auth->user('id') || ! $this->Auth->user('__LOGGED_WITH_CAS__')) {
            $this->redirect($this->Auth->logout());
        } else {
            $this->redirect($this->Cas->logout());
        }
    }
    
    function home() {
        $this->User->id = $this->Auth->user('id');
        $this->User->data = $this->Auth->user();
        $academic_year = $this->User->Subject->Course->AcademicYear->current();
        
        $this->set('section', 'home');
        $this->set('user', $this->User);
        $this->set('academic_year', $academic_year);
        $this->set('events', $this->User->getEvents());
        $this->set('bookings', $this->User->getBookings());
    }
    
    function calendars($token) {
        if (! Environment::user('id')) {
            $user = $this->User->findByCalendarToken($token);

            if (! $user) {
                return $this->cakeError('error404');
            }

            Environment::setUser($user);
        }
        
        $this->User->id = Environment::user('id');
        $this->User->data = Environment::user();
        
        $calendarName = (Environment::institution('name') ?: 'Academic') . ' - ULPGC';

        #header('Content-type: text/calendar; charset=utf-8');
        #header('Content-Disposition: attachment; filename=calendar.ics');
        
        echo "BEGIN:VCALENDAR\r\n";
        echo "VERSION:2.0\r\n";
        echo "PRODID:-//ULPGC//Academic\r\n";
        echo "NAME:{$calendarName}\r\n";
        echo "X-WR-CALNAME:{$calendarName}\r\n";
        echo "TIMEZONE-ID:".date_default_timezone_get()."\r\n";
        echo "X-WR-TIMEZONE:".date_default_timezone_get()."\r\n";
        echo "REFRESH-INTERVAL;VALUE=DURATION:PT12H\r\n";
        echo "X-PUBLISHED-TTL:PT12H\r\n";
        echo "CALSCALE:GREGORIAN\r\n";
        echo "METHOD:PUBLISH\r\n";
        
        $utc = new DateTimeZone('UTC');

        foreach ($this->User->getEvents() as $event) {
            $initial_date = date_create($event['Event']['initial_hour']);
            $final_date = date_create($event['Event']['final_hour']);
            $initial_date->setTimezone($utc);
            $final_date->setTimezone($utc);
            
            echo "BEGIN:VEVENT\r\n";
            echo "UID:{$event['Event']['id']}\r\n";
            echo "SUMMARY:{$event['Activity']['name']} ({$event['Subject']['acronym']})\r\n";
            echo "DTSTART:{$initial_date->format('Ymd\THis\Z')}\r\n";
            echo "DTEND:{$final_date->format('Ymd\THis\Z')}\r\n";
            echo "END:VEVENT\r\n";
        }

        foreach ($this->User->getBookings() as $booking) {
            $initial_date = date_create($booking['Booking']['initial_hour']);
            $final_date = date_create($booking['Booking']['final_hour']);
            $initial_date->setTimezone($utc);
            $final_date->setTimezone($utc);
            
            echo "BEGIN:VEVENT\r\n";
            echo "UID:booking_{$booking['Booking']['id']}\r\n";
            echo "SUMMARY:{$booking['Booking']['reason']}\r\n";
            echo "DTSTART:{$initial_date->format('Ymd\THis\Z')}\r\n";
            echo "DTEND:{$final_date->format('Ymd\THis\Z')}\r\n";
            echo "END:VEVENT\r\n";
        }
        
        echo "END:VCALENDAR";
        exit;
    }
    
    function index() {
        App::import('Core', 'Sanitize');
        $db = $this->User->getDataSource();
        
        if (isset($this->params['url']['q'])) {
            $q = Sanitize::escape($this->params['url']['q']);
        } elseif (isset($this->passedArgs['q'])) {
            $q = Sanitize::escape($this->passedArgs['q']);
        } else {
            $q = '';
        }

        $scope = array();
        
        if (empty($this->params['named']['course'])) {
            if (isset($this->params['named']['ref']) && $this->params['named']['ref'] === 'competence') {
                $this->Session->setFlash('No se ha podido acceder al curso.');
                $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
            }

            if (Environment::institution('id')) {
                $scope[] = "
                    User.id IN (
                        SELECT UserInstitution.user_id
                        FROM users_institutions UserInstitution
                        WHERE UserInstitution.institution_id = {$db->value(Environment::institution('id'))}
                    )
                ";
            } elseif (! $this->Auth->user('super_admin')) {
                $this->Session->setFlash('No se ha podido acceder al centro.');
                $this->redirect(array('controller' => 'institutions', 'action' => 'index', 'base' => false, 'ref' => 'users'));
            }
        } elseif (!Environment::institution('id')) {
            $this->Session->setFlash('No tiene permisos para realizar esta acción.');
            $this->redirect(array('controller' => 'institutions', 'action' => 'index', 'base' => false, 'ref' => 'users'));
        } else {
            $course_id = intval($this->params['named']['course']);
            
            if (is_null($course_id)) {
                $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
            }
            
            $this->loadModel('Course');

            $course = $this->Course->find('first', array(
                'fields' => array('Course.*', 'Degree.*'),
                'joins' => array(
                    array(
                        'table' => 'degrees',
                        'alias' => 'Degree',
                        'type' => 'INNER',
                        'conditions' => 'Degree.id = Course.degree_id'
                    )
                ),
                'conditions' => array(
                    'Course.id' => $course_id,
                    'Course.institution_id' => Environment::institution('id')
                ),
                'recursive' => -1
            ));

            if (!$course) {
                $this->Session->setFlash('No se ha podido acceder al curso.');
                $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
            }
            
            $scope[] = "
                User.id IN (
                    SELECT distinct SubjectUser.user_id FROM subjects_users SubjectUser
                    INNER JOIN subjects Subject ON Subject.id = SubjectUser.subject_id
                    WHERE Subject.course_id = {$db->value($this->params['named']['course'])}
                )";
        }

        if (!empty($this->params['named']['type'])) {
            $scope[] = array('User.type' => $this->params['named']['type']);
        }
        
        if (!empty($q)) {
            $scope['OR'] = array(
                'User.first_name LIKE' => "%$q%",
                'User.last_name LIKE' => "%$q%",
                'User.username LIKE' => "%$q%",
                'User.dni LIKE' => "%$q%",
                'User.type LIKE' => "%$q%"
            );
        }

        $users = $this->paginate('User', $scope);

        $this->set('users', $users);
        $this->set('q', $q);
        $this->set('course', isset($course) ? $course : null);
        $this->set('type', isset($this->params['named']['type']) ? $this->params['named']['type'] : null);
        $this->set('ref', isset($this->params['named']['ref']) ? $this->params['named']['ref'] : null);
    }
    
    function view($id = null) {
        $id = $id === null ? null : intval($id);
        
        if (! $id) {
            $this->Session->setFlash('No se ha podido acceder al usuario.');
            $this->redirect(array('action' => 'index'));
        }

        $fields = array('User.*');
        $joins = array();

        if (Environment::institution('id')) {
            $fields[] = 'UserInstitution.*';
            $joins[] = array(
                'table' => 'users_institutions',
                'alias' => 'UserInstitution',
                'type' => 'INNER',
                'conditions' => array(
                    'UserInstitution.user_id = User.id',
                    'UserInstitution.institution_id' => Environment::institution('id')
                )
            );
        } elseif (! $this->Auth->user('super_admin')) {
            $this->Session->setFlash('No tiene permisos para realizar esta acción.');
            $this->redirect(array('controller' => 'institutions', 'action' => 'index', 'base' => false, 'ref' => 'users'));
        }

        $user = $this->User->find('first', array(
            'fields' => $fields,
            'joins' => $joins,
            'conditions' => array(
                'User.id' => $id
            )
        ));

        if (! $user) {
            $this->Session->setFlash('No se ha podido acceder al usuario.');
            $this->redirect(array('action' => 'index'));
        }

        if (! Environment::institution('id') && $this->Auth->user('super_admin')) {
            $this->loadModel('UserInstitution');

            $user_institutions = $this->UserInstitution->find('all', array(
                'fields' => array('Institution.*', 'UserInstitution.*'),
                'joins' => array(
                    array(
                        'table' => 'institutions',
                        'alias' => 'Institution',
                        'type' => 'INNER',
                        'conditions' => array(
                            'Institution.id = UserInstitution.institution_id'
                        )
                    )
                ),
                'conditions' => array(
                    'UserInstitution.user_id' => $id
                )
            ));

            $this->set('user_institutions', $user_institutions);
        }

        $this->set('user', $user);
    }
    
    function add() {
        if (!empty($this->data)) {
            $this->loadModel('UserInstitution');

            $this->data = $this->Form->filter($this->data);
            
            $user = null;
            $userUpdatedBlocked = false;

            if (!empty($this->data['User']['username'])) {
                $this->data['User']['username'] = trim($this->data['User']['username']);

                if (Environment::institution('id')) {
                    $user = $this->User->find('first', array(
                        'fields' => array('User.*', 'UserInstitution.*'),
                        'joins' => array(
                            array(
                                'table' => 'users_institutions',
                                'alias' => 'UserInstitution',
                                'type' => 'LEFT',
                                'conditions' => array(
                                    'UserInstitution.user_id = User.id',
                                    'UserInstitution.institution_id' => Environment::institution('id')
                                )
                            )
                        ),
                        'conditions' => array(
                            'User.username' => $this->data['User']['username']
                        ),
                        'recursive' => -1
                    ));

                    if ($user) {
                        if (! empty($user['UserInstitution']['active'])) {
                            $this->Session->setFlash('El usuario ya está registrado en el centro.');
                            $this->redirect($this->referer());
                        }

                        if (!empty($this->data['User']['type']) && $user['User']['type'] !== $this->data['User']['type']) {
                            $this->Session->setFlash('El usuario ya está registrado en el sistema como otro tipo de usuario.');
                            $this->redirect($this->referer());
                        }

                        $userUpdatedBlocked = true;
                    }
                } elseif (! $this->Auth->user('super_admin')) {
                    $this->Session->setFlash('No tiene permisos para realizar esta acción.');
                    $this->redirect(array('controller' => 'institutions', 'action' => 'index', 'base' => false, 'ref' => 'users'));
                } else {
                    $user = $this->User->find('first', array(
                        'conditions' => array(
                            'User.username' => $this->data['User']['username']
                        ),
                        'recursive' => -1
                    ));

                    if ($user) {
                        $this->Session->setFlash('El usuario ya está registrado en el sistema.');
                        $this->redirect($this->referer());
                    }
                }
            }

            if ($user) {
                $is_new = false;
                $this->User->set($user);
            } else {
                $is_new = true;
                ///** @deprecated in favour CAS auth */
                // $password = substr(md5(uniqid(mt_rand(), true)), 0, 8);
                // $this->data['User']['password'] = $this->Auth->password($password);
            }

            $dataToSave = $this->data;

            if (isset($dataToSave['User']['type']) && $dataToSave['User']['type'] === 'Super administrador') {
                $dataToSave['User']['type'] = 'Administrador';
                $dataToSave['User']['super_admin'] = $this->Auth->user('super_admin');
            }

            if ($userUpdatedBlocked) {
                unset($dataToSave['User']['first_name']);
                unset($dataToSave['User']['last_name']);
                unset($dataToSave['User']['dni']);
                unset($dataToSave['User']['phone']);
            }

            if ($this->User->save($dataToSave)) {
                $ok = true;

                if (Environment::institution('id')) {
                    if (! empty($user['UserInstitution']['id'])) {
                        $this->UserInstitution->id = $user['UserInstitution']['id'];
                    }

                    $user_institution = array(
                        'UserInstitution' => array(
                            'user_id' => $this->User->id,
                            'institution_id' => Environment::institution('id'),
                            'active' => 1 
                        )
                    );

                    $ok = $this->UserInstitution->save($user_institution);
                }

                if ($ok || $is_new) {
                    $this->Email->from = 'Academic <noreply@ulpgc.es>';
                    $this->Email->to = $dataToSave['User']['username'];
                    $this->Email->subject = "Alta en Academic";
                    $this->Email->sendAs = 'both';
                    $this->Email->template = Configure::read('app.email.user_registered')
                        ? Configure::read('app.email.user_registered')
                        : 'user_registered';
                    $this->set('user', $dataToSave);
                    ///** @deprecated in favour CAS auth */
                    // if ($is_new) {
                    //    $this->set('password', $password);
                    //}
                    $this->Email->send();
                    
                    if ($userUpdatedBlocked) {
                        $this->Session->setFlash('El usuario ya está dado de alta en en sistema. Se añadirá al centro sin actualizar sus datos personales.');
                    } else {
                        $this->Session->setFlash('El usuario se ha guardado correctamente');
                    }
                    $this->redirect(array('action' => 'index'));
                }

                if (!$ok) {
                    $this->Session->setFlash('No se ha podido registrar al usuario en el centro.');
                    $this->redirect($this->referer());
                }
            }
        }
    }
    
    function edit($id = null) {
        $id = $id === null ? null : intval($id);

        $id = $id === null ? null : intval($id);

        if (! $id) {
            $this->Session->setFlash('No se ha podido acceder al usuario.');
            $this->redirect(array('action' => 'index'));
        }

        $fields = array('User.*');
        $joins = array();

        if (Environment::institution('id')) {
            $fields[] = 'UserInstitution.*';
            $joins[] = array(
                'table' => 'users_institutions',
                'alias' => 'UserInstitution',
                'type' => 'INNER',
                'conditions' => array(
                    'UserInstitution.user_id = User.id',
                    'UserInstitution.institution_id' => Environment::institution('id')
                )
            );
        } elseif (! $this->Auth->user('super_admin')) {
            $this->Session->setFlash('No se ha podido acceder al centro.');
            $this->redirect(array('controller' => 'institutions', 'action' => 'index', 'base' => false, 'ref' => 'users'));
        }

        $user = $this->User->find('first', array(
            'fields' => $fields,
            'joins' => $joins,
            'conditions' => array(
                'User.id' => $id
            )
        ));

        if (! $user) {
            $this->Session->setFlash('No se ha podido acceder al usuario.');
            $this->redirect(array('action' => 'index'));
        }

        $this->User->set($user);

        if (Environment::institution('id')) {
            $this->loadModel('UserInstitution');
            $institutionsCount = $this->UserInstitution->find('count', array(
                'conditions' => array(
                    'UserInstitution.user_id' => $id
                )
            ));

            $type_editable = $institutionsCount === 1 && (! $user['User']['super_admin'] || $this->Auth->user('super_admin'));
            $dni_editable = $this->Auth->user('super_admin') || $institutionsCount === 1;
            $betaTesters = (array) Configure::read('app.beta.testers');
        } else {
            $type_editable = true;
            $dni_editable = true;
            $betaTesters = array();
        }


        if (empty($this->data)) {
            $this->data = $user;
            $this->data['User']['beta_tester'] = !empty($betaTesters[$user['User']['username']]);
            $this->data['User']['active'] = !empty($user['UserInstitution']['active']);
            if ($this->Auth->user('super_admin') && $this->data['User']['super_admin']) {
                $this->data['User']['type'] = 'Super Administrador';
            }
        } else {
            $dataToSave = $this->Form->filter($this->data);

            $dataToSave['User']['id'] = $user['User']['id'];
            $dataToSave['User']['modified'] = null;

            if (!$type_editable) {
                unset($dataToSave['User']['type']);
            } elseif (isset($dataToSave['User']['type']) && $dataToSave['User']['type'] === 'Super administrador') {
                $dataToSave['User']['type'] = 'Administrador';
                $dataToSave['User']['super_admin'] = $this->Auth->user('super_admin');
            } elseif ($this->Auth->user('super_admin')) {
                $dataToSave['User']['super_admin'] = false;
            }

            if (!$dni_editable) {
                unset($dataToSave['User']['dni']);
            }

            if ($this->User->save($dataToSave)) {
                $ok = true;

                if (Environment::institution('id')) {
                    if (isset($dataToSave['User']['active']) && ($this->Auth->user('type') === 'Administrativo' || $this->Auth->user('type') === 'Administrador')) {
                        $this->UserInstitution->save(array(
                            'UserInstitution' => array(
                                'id' => $user['UserInstitution']['id'],
                                'active' => !empty($dataToSave['User']['active'])
                            )
                        ));
                    }

                    if (isset($dataToSave['User']['beta_tester']) && $this->Auth->user('type') === 'Administrador') {
                        $institution_id = Environment::institution('id');
                        $config_file = CONFIGS . "institutions/$institution_id/app.options.php";
                        $app_options = include $config_file;
                        if (!is_array($app_options)) {
                            $app_options = array();
                        }

                        $username = $dataToSave['User']['username'];
                        if (empty($dataToSave['User']['beta_tester'])) {
                            unset($app_options['beta']['testers'][$username]);
                        } else {
                            $app_options['beta']['testers'][$username] = true;
                        }

                        $fp = fopen($config_file, 'w');
                        if ($fp === false || !fwrite($fp, '<?php return ' . var_export($app_options, true) . ';')) {
                            $error = false;
                            $this->Session->setFlash('En un error de escritura no ha permitido guardar todos los cambios.');
                        }
                        if ($fp !== false) {
                            fclose($fp);
                        }
                    }
                }
                if ($ok) {
                    $this->Session->setFlash('El usuario se ha actualizado correctamente.');
                    $this->redirect(array('action' => 'view', $id));
                }
            }
        }

        $this->set('user', $user);
        $this->set('type_editable', $type_editable);
        $this->set('dni_editable', $dni_editable);
    }
    
    function delete($id = null) {
        $id = $id === null ? null : intval($id);

        $db = $this->User->getDataSource();

        if (! $id) {
            $this->Session->setFlash('No se ha podido acceder al usuario.');
            $this->redirect(array('action' => 'index'));
        }

        $fields = array('User.*');
        $joins = array();

        if (Environment::institution('id')) {
            $fields[] = 'UserInstitution.*';
            $joins[] = array(
                'table' => 'users_institutions',
                'alias' => 'UserInstitution',
                'type' => 'INNER',
                'conditions' => array(
                    'UserInstitution.user_id = User.id',
                    'UserInstitution.institution_id' => Environment::institution('id')
                )
            );
        } elseif (! $this->Auth->user('super_admin')) {
            $this->Session->setFlash('No se ha podido acceder al centro.');
            $this->redirect(array('controller' => 'institutions', 'action' => 'index', 'base' => false, 'ref' => 'users'));
        }

        $user = $this->User->find('first', array(
            'fields' => $fields,
            'joins' => $joins,
            'conditions' => array(
                'User.id' => $id
            )
        ));

        if (! $user) {
            $this->Session->setFlash('No se ha podido acceder al usuario.');
            $this->redirect(array('action' => 'index'));
        }

        if (Environment::institution('id')) {
            $and_course_in_institution = "AND course.institution_id = {$db->value(Environment::institution('id'))}";
        } else {
            $and_course_in_institution = '';
        }

        $this->User->query("
            DELETE group_request FROM `group_requests` group_request
            INNER JOIN activities activity ON activity.id = group_request.activity_id
            INNER JOIN subjects subject ON subject.id = activity.subject_id
            INNER JOIN courses course ON course.id = subject.course_id $and_course_in_institution
            WHERE group_request.student_id = {$id} OR group_request.student_2_id = {$id}
        ");
        $this->User->query("
            DELETE competence_criterion_teacher FROM `competence_criterion_teachers` competence_criterion_teacher
            INNER JOIN competence_criteria competence_criterion ON competence_criterion.id = competence_criterion_teacher.criterion_id
            INNER JOIN competence_goals competence_goal ON competence_goal.id = competence_criterion.goal_id
            INNER JOIN competence ON competence.id = competence_goal.competence_id
            INNER JOIN courses course ON course.id = competence.course_id $and_course_in_institution
            WHERE competence_criterion_teacher.teacher_id = {$id}
        ");
        $this->User->query("
            DELETE competence_criterion_grade FROM `competence_criterion_grades` competence_criterion_grade
            INNER JOIN competence_criteria competence_criterion ON competence_criterion.id = competence_criterion_grade.criterion_id
            INNER JOIN competence_goals competence_goal ON competence_goal.id = competence_criterion.goal_id
            INNER JOIN competence ON competence.id = competence_goal.competence_id
            INNER JOIN courses course ON course.id = competence.course_id $and_course_in_institution
            WHERE competence_criterion_grade.student_id = {$id}
        ");
        $this->User->query("
            DELETE competence_goal_request FROM `competence_goal_requests` competence_goal_request
            INNER JOIN competence_goals competence_goal ON competence_goal.id = competence_goal_request.goal_id
            INNER JOIN competence ON competence.id = competence_goal.competence_id
            INNER JOIN courses course ON course.id = competence.course_id $and_course_in_institution
            WHERE competence_goal_request.student_id = {$id} OR competence_goal_request.teacher_id = {$id}
        ");
        $this->User->query("
            DELETE subject_user FROM `subjects_users` subject_user
            INNER JOIN subjects subject ON subject.id = subject_user.subject_id
            INNER JOIN courses course ON course.id = subject.course_id $and_course_in_institution
            WHERE subject_user.user_id = {$id}
        ");
        $this->User->query("
            DELETE registration FROM `registrations` registration
            INNER JOIN activities activity ON activity.id = registration.activity_id
            INNER JOIN subjects subject ON subject.id = activity.subject_id
            INNER JOIN courses course ON course.id = subject.course_id $and_course_in_institution
            WHERE registration.student_id = {$id}
        ");

        if (Environment::institution('id')) {
            $this->loadModel('UserInstitution');

            $this->User->query("
                DELETE user_institution FROM `users_institutions` user_institution
                WHERE user_institution.user_id = {$id} AND user_institution.institution_id = {$db->value(Environment::institution('id'))}
            ");

            $institutionsCount = $this->UserInstitution->find('count', array(
                'conditions' => array(
                    'UserInstitution.user_id' => $id
                )
            ));

            if ($institutionsCount === 0) {
                $this->User->query("DELETE user FROM `users` user WHERE user.id = {$id}"); 
            }
        } else {
            $this->User->query("DELETE user_institution FROM `users_institutions` user_institution WHERE user_institution.user_id = {$id}}");
            $this->User->query("DELETE user FROM `users` user WHERE user.id = {$id}"); 
        }

        $this->Session->setFlash('El usuario ha sido eliminado correctamente');
        $this->redirect(array('contoller' => 'users', 'action' => 'index'));
    }

    function acl_edit() {
        $roleOptions = $this->User->getTypeOptions();
        $resourcesOptions = array(
            'events.calendar_by_teacher' => 'Ver calendario por profesor'
        );
        if (empty($this->data)) {
            $acl = Configure::read('app.acl');
        } else {
            $acl = array();
            if (!isset($this->data['acl'])) {
                $this->data['acl'] = array();
            }
            foreach ($this->data['acl'] as $data_role => $data_resources) {
                foreach ($data_resources as $data_resource => $data_value) {
                    if (!empty($data_value) && ($data_role === 'all' || array_key_exists($data_role, $roleOptions)) && array_key_exists($data_resource, $resourcesOptions)) {
                        $acl[$data_role][$data_resource] = true;
                    }
                }
            }
            $institution_id = Environment::institution('id');
            $config_file = CONFIGS . "institutions/$institution_id/app.options.php";
            $app_options = include $config_file;
            if (!is_array($app_options)) {
                $app_options = array();
            }
            $app_options['acl'] = $acl;
            $fp = fopen($config_file, 'w');
            $ok = true;
            if ($fp === false || !fwrite($fp, '<?php return ' . var_export($app_options, true) . ';')) {
                $ok = false;
                $this->Session->setFlash('En un error de escritura no ha permitido guardar los cambios.');
            }
            if ($fp !== false) {
                fclose($fp);
            }
            if ($ok) {
                $this->Session->setFlash('Sus datos han sido actualizados correctamente.');
                $this->redirect(array('controller' => 'users', 'action' => 'index'));
            }
        }
        $this->set('roleOptions', $roleOptions);
        $this->set('resourcesOptions', $resourcesOptions);
        $this->set('acl', is_array($acl) ? $acl : array());
    }
    
    /**
     * Find by name
     */
    function find_by_name() {
        App::import('Core', 'Sanitize');

        $name_conditions = array();
        foreach (explode(' ', $this->params['url']['q']) as $q) {
            $q = '%'.Sanitize::escape($q).'%';
            $name_conditions[] = array(
                'OR' => array(
                    'User.first_name like' => $q,
                    'User.last_name like' => $q
                )
            );
        }

        $users = $this->User->find('all', array(
            'fields' => array('User.id', 'User.first_name', 'User.last_name'),
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
                "AND" => $name_conditions
            ),
            'recursive' => 0,
            'order' => array('User.first_name', 'User.last_name')
        ));
        $this->set('users', $users);
    }
  
    function find_teachers_by_name(){
        App::import('Core', 'Sanitize');

        $name_conditions = array();
        foreach (explode(' ', $this->params['url']['q']) as $q) {
            $q = '%'.Sanitize::escape($q).'%';
            $name_conditions[] = array(
                'OR' => array(
                    'User.first_name like' => $q,
                    'User.last_name like' => $q
                )
            );
        }
        
        $users = $this->User->find('all', array(
            'fields' => array('User.id', 'User.first_name', 'User.last_name'),
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
                'OR' => array(
                    array('User.type' => 'Profesor'),
                    array('User.type' => 'Administrador')
                ),
                'AND' => $name_conditions
            ),
            'recursive' => 0,
            'order' => array('User.first_name', 'User.last_name')
        ));
        $this->set('users', $users);
    }

    function find_teachers_by_competence_goal_and_name($goal_id) {
        $goal_id = $goal_id === null ? null : intval($goal_id);
        App::import('Core', 'Sanitize');

        $users = array();

        $this->loadModel('CompetenceGoal');
        $competence_goal = $this->CompetenceGoal->find('first', array(
            'fields' => 'CompetenceGoal.id',
            'joins' => array(
                array(
                    'table' => 'competence',
                    'alias' => 'Competence',
                    'type'  => 'INNER',
                    'conditions' => array(
                        'Competence.id = CompetenceGoal.competence_id',
                    )
                ),
                array(
                    'table' => 'courses',
                    'alias' => 'Course',
                    'type'  => 'INNER',
                    'conditions' => array(
                        'Course.id = Competence.course_id',
                        'Course.institution_id' => Environment::institution('id')
                    )
                )
            ),
            'conditions' => array(
                'CompetenceGoal.id' => $goal_id
            ),
            'recursive' => -1,
        ));

        if ($competence_goal) {
            $name_conditions = array();
            foreach (explode(' ', $this->params['url']['q']) as $q) {
                $q = '%'.Sanitize::escape($q).'%';
                $name_conditions[] = array(
                    'OR' => array(
                        'User.first_name like' => $q,
                        'User.last_name like' => $q
                    )
                );
            }

            $users = $this->User->find('all', array(
                'fields' => array('DISTINCT User.id', 'User.first_name', 'User.last_name'),
                'joins' => array(
                    array(
                        'table' => 'competence_criteria',
                        'alias' => 'CompetenceCriterion',
                        'type'  => 'LEFT',
                        'conditions' => array(
                            'CompetenceCriterion.goal_id' => $goal_id
                        )
                    ),
                    array(
                        'table' => 'competence_criterion_subjects',
                        'alias' => 'CompetenceCriterionSubject',
                        'type'  => 'LEFT',
                        'conditions' => array(
                            'CompetenceCriterionSubject.criterion_id = CompetenceCriterion.id'
                        )
                    ),
                    array(
                        'table' => 'subjects',
                        'alias' => 'Subject',
                        'type'  => 'LEFT',
                        'conditions' => array(
                            'Subject.id = CompetenceCriterionSubject.subject_id',
                            'OR' => array(
                                'Subject.coordinator_id = User.id',
                                'Subject.practice_responsible_id = User.id'
                            )
                        )
                    ),
                    array(
                        'table' => 'competence_criterion_teachers',
                        'alias' => 'CompetenceCriterionTeacher',
                        'type'  => 'LEFT',
                        'conditions' => array(
                            'CompetenceCriterionTeacher.criterion_id = CompetenceCriterion.id',
                            'CompetenceCriterionTeacher.teacher_id = User.id'
                        )
                    ),
                ),
                'recursive' => 0,
                'conditions' => array(
                    'OR' => array(
                        array('User.type' => 'Profesor'),
                        array('User.type' => 'Administrador')
                    ),
                    'AND' => $name_conditions,
                    'OR' => array(
                        array('User.type' => 'Profesor'),
                        array('User.type' => 'Administrador')
                    ),
                    'OR' => array(
                        'Subject.id IS NOT NULL',
                        'CompetenceCriterionTeacher.id IS NOT NULL'
                    )
                ),
                'order' => array('User.first_name', 'User.last_name')
            ));
        }
        $this->set('users', $users);
    }
    
    /**
     * Find students by name
     */
    function find_students_by_name() {
        App::import('Core', 'Sanitize');

        $name_conditions = array();
        foreach (explode(' ', $this->params['url']['q']) as $q) {
            $q = '%'.Sanitize::escape($q).'%';
            $name_conditions[] = array(
                'OR' => array(
                    'User.first_name like' => $q,
                    'User.last_name like' => $q
                )
            );
        }

        $users = $this->User->find('all', array(
            'fields' => array('User.id', 'User.first_name', 'User.last_name'),
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
                'User.type' => 'Estudiante',
                "AND" => $name_conditions
            ),
            'recursive' => 0,
            'order' => array('User.first_name', 'User.last_name')
        ));
        $this->set('users', $users);
    }

    function editProfile() {
        $this->User->id = $this->Auth->user('id');
        
        $user = $this->User->read();

        if (empty($this->data)) {
            $this->data = $user;
            $this->set('user', $user);
        } else {
            if (in_array($this->Auth->user('type'), array('Estudiante', 'Profesor'))) {
                $this->data['User'] = array_intersect_key(
                    $this->data['User'],
                    array_flip(array(
                        'old_password', 'new_password', 'password_confirmation', 'notify_all'
                    ))
                );
            } elseif ($this->Auth->user('__LOGGED_WITH_CAS__')) {
                $this->data['User'] = array_intersect_key(
                    $this->data['User'],
                    array_flip(array(
                        'dni', 'phone',
                        'old_password', 'new_password', 'password_confirmation', 'notify_all'
                    ))
                );
            } else {
                $this->data['User'] = array_intersect_key(
                    $this->data['User'],
                    array_flip(array(
                        'first_name', 'last_name', 'dni', 'phone',
                        'old_password', 'new_password', 'password_confirmation', 'notify_all'
                    ))
                );
            }
            if (($this->_changePasswordValidation()) && ($this->User->save($this->data))) {
                $this->Session->setFlash('Sus datos han sido actualizados correctamente.');
                $this->redirect(array('controller' => 'users', 'action' => 'home'));
            } else {
                $this->data = $this->User->read();
                $this->set('user', $this->data);
            }
        }

        $authTokenModel = ClassRegistry::init('AuthToken');
        $auth_token = $authTokenModel->find(
            'first',
            array(
                'conditions' => array(
                    'user_id' => $this->Auth->user('id'),
                    'last_used' => null
                )
            )
        );
        if ($auth_token) {
            $authTokenModel->delete($auth_token['AuthToken']['token']);
            $auth_token = null;
        }
        while (!$auth_token) {
            $auth_token = array(
                'AuthToken' => array(
                    'token' => base64_encode(random_bytes(64)),
                    'user_id' => $this->Auth->user('id')
                )
            );
            $authTokenModel->save($auth_token);
        }

        $qrCode = new \Endroid\QrCode\QrCode();
        $qrCode
            ->setText($auth_token['AuthToken']['token'])
            ->setSize(200)
            ->setPadding(10)
            ->setErrorCorrection('high')
            ->setForegroundColor(['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0])
            ->setBackgroundColor(['r' => 255, 'g' => 255, 'b' => 255, 'a' => 0])
            ->setLabelFontSize(16)
            ->setImageType(\Endroid\QrCode\QrCode::IMAGE_TYPE_PNG)
        ;
        $this->set('qr_image', $qrCode->getDataUri());
    }
    
    function rememberPassword() {
        if (!empty($this->data)) {
            $this->data = $this->User->find('first', array(
                'conditions' => array('username' => $this->data['User']['username'])
            ));
            if ($this->data != null){
                $password = substr(md5(uniqid(mt_rand(), true)), 0, 8);
                $this->data['User']['password'] = $this->Auth->password($password);
                $this->User->save($this->data);
                
                $this->Email->from = 'Academic <noreply@ulpgc.es>';
                $this->Email->to = $this->data['User']['username'];
                $this->Email->subject = "Recordatorio de contraseña";
                $this->Email->sendAs = 'both';
                $this->Email->template = Configure::read('app.email.user_remember_password')
                    ? Configure::read('app.email.user_remember_password')
                    : 'user_remember_password';
                $this->set('user', $this->data);
                $this->set('password', $password);
                $this->Email->send();
                $this->Session->setFlash('Se ha enviado una nueva contraseña a su correo electrónico.');
                $this->redirect(array('action' => 'login', 'base' => false));
            } else {
                $this->Session->setFlash('No se ha podido encontrar un usuario con el correo electrónico especificado');
            }
        }
    }
    
    function edit_registration($id = null) {
        $id = $id === null ? null : intval($id);

        if (! $id) {
            $this->Session->setFlash('No se ha podido acceder al usuario.');
            $this->redirect(array('action' => 'index'));
        }
        
        $user = $this->User->find('first', array(
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
                'User.id' => $id
            ),
        ));

        if (! $user) {
            $this->Session->setFlash('No se ha podido acceder al usuario.');
            $this->redirect(array('action' => 'index'));
        }

        $current_courses = $this->User->Subject->Course->current();
        $courses_id = Set::extract($current_courses, '{n}.id');

        $subjects = $this->User->Subject->find('all', array(
            'fields' => array('Subject.*', 'Course.*', 'Degree.*'),
            'joins' => array(
                array(
                    'table' => 'courses',
                    'alias' => 'Course',
                    'type' => 'INNER',
                    'conditions' => 'Course.id = Subject.course_id'
                ),
                array(
                    'table' => 'subjects_users',
                    'alias' => 'SubjectUser',
                    'type' => 'INNER',
                    'conditions' => 'SubjectUser.child_subject_id IS NULL AND SubjectUser.subject_id = Subject.id OR SubjectUser.child_subject_id = Subject.id'
                ),
                array(
                    'table' => 'degrees',
                    'alias' => 'Degree',
                    'type' => 'INNER',
                    'conditions' => 'Degree.id = Course.degree_id'
                )
            ),
            'conditions' => array(
                'Subject.course_id' => $courses_id,
                'SubjectUser.user_id' => $id,
            ),
            'recursive' => -1
        ));

        $this->set('user', $user);
        $this->set('subjects', $subjects);
    }
    
    function delete_subject($student_id, $subject_id) {
        $student_id = $student_id === null ? null : intval($student_id);
        $subject_id = $subject_id === null ? null : intval($subject_id);
        
        if (! isset($student_id, $subject_id)) {
            $this->Session->setFlash('No tiene permisos para realizar esta acción');
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        $user = $this->User->find('first', array(
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
                'User.id' => $student_id
            ),
        ));

        if (! $user) {
            $this->Session->setFlash('No tiene permisos para realizar esta acción');
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        $subject = $this->User->Subject->find('first', array(
            'conditions' => array(
                'Subject.id' => $subject_id,
                'Course.institution_id' => Environment::institution('id')
            )
        ));

        if (! $subject) {
            $this->Session->setFlash('No tiene permisos para realizar esta acción');
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        $activities = $this->User->Subject->Activity->find('all', array('conditions' => array('Activity.subject_id' => $subject_id)));

        $this->User->query("DELETE FROM subjects_users WHERE user_id = {$student_id} AND subject_id = {$subject_id} OR child_subject_id = {$subject_id}");

        if (count(array_values($activities)) > 0) {
            $activities_id = array();
            foreach($activities as $activity):
                array_push($activities_id, $activity['Activity']['id']);
            endforeach;
            $activities_id = implode(",", $activities_id);
            $this->User->query("DELETE FROM registrations WHERE student_id = {$student_id} AND activity_id IN ($activities_id)");
        }

        $this->Session->setFlash('La asignatura se ha eliminado correctamente');
        $this->redirect(array('controller' => 'users', 'action' => 'edit_registration', $student_id));
    }
    
    function save_subject($student_id = null, $subject_id = null) {
        $student_id = $student_id === null ? null : intval($student_id);
        $subject_id = $subject_id === null ? null : intval($subject_id);
        
        if (! isset($student_id, $subject_id)) {
            return false;
        }

        $user = $this->User->find('first', array(
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
                'User.id' => $student_id
            ),
        ));

        if (! $user) {
            return false;
        }

        $subject = $this->User->Subject->find('first', array(
            'conditions' => array(
                'Subject.id' => $subject_id,
                'Course.institution_id' => Environment::institution('id')
            )
        ));

        if (! $subject) {
            return false;
        }

        if (($user != null) && ($subject != null)) {
            $master_subject_id = $subject['Subject']['parent_id'] ?: $subject['Subject']['id'];
            foreach ($user['Subject'] as $user_subject) {
                if ($user_subject['id'] == $master_subject_id) {
                    return;
                }
            }
            if ($subject['Subject']['parent_id']) {
                $success = $this->User->query("INSERT INTO subjects_users(subject_id, child_subject_id, user_id) VALUES({$master_subject_id}, {$subject_id}, {$student_id})");
            } else {
                $success = $this->User->query("INSERT INTO subjects_users(subject_id, user_id) VALUES({$subject_id}, {$student_id})");
            }
            $this->set('success', $success);
            $this->set('user', $user);
            $this->set('subject', $subject);
        } 
    }
    
    function _changePasswordValidation() {
        if ($this->data['User']['new_password'] != "") {
            $user = $this->User->read();
            $old_password_hashed = $this->Auth->password($this->data['User']['old_password']);
            if ($old_password_hashed == $user['User']['password']) {
                if ($this->data['User']['new_password'] == $this->data['User']['password_confirmation']) {
                    $this->data['User']['password'] = $this->Auth->password($this->data['User']['new_password']);
                    return true;
                } else {
                    $this->Session->setFlash('No se ha podido actualizar su contraseña debido a que la contraseña y su confirmación no coinciden');
                    return false;
                }
            } else {
                $this->Session->setFlash('No se ha podido actualizar su contraseña debido a que la antigua contraseña es incorrecta');
                return false;
            }
        }
        
        return true;
    }
    
    function import($course_id = null) {
        $course_id = $course_id === null ? null : intval($course_id);

        if (is_null($course_id)) {
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        $course = $this->User->Subject->Course->find('first', array(
            'fields' => array('Course.*', 'Degree.*'),
            'joins' => array(
                array(
                    'table' => 'degrees',
                    'alias' => 'Degree',
                    'type' => 'INNER',
                    'conditions' => 'Degree.id = Course.degree_id'
                )
            ),
            'conditions' => array(
                'Course.id' => $course_id,
                'Course.institution_id' => Environment::institution('id')
            ),
            'recursive' => -1
        ));

        if (!$course) {
            $this->Session->setFlash('No se ha podido acceder al curso.');
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        if (!empty($this->data)) {
            $lines = array();
            $has_error = false;

            if ($this->data['User']['file']['error'] !== UPLOAD_ERR_OK) {
                $has_error = true;
            }
            
            if (!$has_error && ($file = file($this->data['User']['file']['tmp_name']))) {
                set_time_limit(0);

                foreach ($file as $line):
                    $values = array_map('trim', str_getcsv($line, ',', '"', '"'));
                    $args = array(
                        'dni' => $values[0],
                        'first_name' => $values[1],
                        'last_name' => trim("{$values[2]} {$values[3]}"),
                        'username' => $values[4],
                        'phone' => $values[5] . (empty($values[6]) ? '' : " // {$values[6]}"),
                        'subjects' => array_slice($values, 7, count($values) - 7)
                    );

                    if (empty($args['dni']) || empty($args['username']) || empty($args['first_name'])) {
                        if (!$has_error) {
                            $has_error = true;
                            $this->Session->setFlash('Hay lineas con datos incompletos.');
                        }
                    } else {
                        $user = $this->User->find('first', array(
                            'fields' => array('User.*', 'UserInstitution.*'),
                            'joins' => array(
                                array(
                                    'table' => 'users_institutions',
                                    'alias' => 'UserInstitution',
                                    'type' => 'LEFT',
                                    'conditions' => array(
                                        'UserInstitution.user_id = User.id',
                                        'UserInstitution.institution_id' => Environment::institution('id')
                                    )
                                )
                            ),
                            'conditions' => array(
                                'User.username' => $args['username']
                            ),
                            'recursive' => -1
                        ));

                        if ($user && $user['User']['type'] !== 'Estudiante') {
                            if (!$has_error) {
                                $has_error = true;
                                $this->Session->setFlash(sprintf(
                                    'El alumno %s ya está registrado en el sistema como otro tipo de usuario.',
                                    $args['username']
                                ));
                            }
                        }

                        $lines[] = array(
                            'user' => $user,
                            'args' => $args
                        );
                    }
                endforeach;
            } else {
                $has_error = true;
                $this->Session->setFlash('No se ha podido leer correctamente el archivo.');
            }

            if (!$has_error) {
                $subjects = $this->_get_course_subjects($course_id);
                $imported_subjects = array();
                $saved_students = 0;
                $failed_students = [];

                foreach ($lines as $line):
                    if ($this->_import_student($line['user'], $line['args'], $course_id, $subjects)) {
                        $saved_students++;
                    } else {
                        $failed_students[]= $line['args']['dni'];
                    }

                    $imported_subjects = array_merge(
                        $imported_subjects,
                        array_diff($line['args']['subjects'], $imported_subjects)
                    );
                endforeach;

                $inexistent_subjects = implode(';', array_diff(array_unique($imported_subjects), array_keys($subjects)));
                $failed_students = implode(';', array_unique($failed_students));
                            
                $this->redirect(array('action' => 'import_finished', $course_id, $saved_students, $inexistent_subjects, $failed_students));
            }
        }

        $this->set('course_id', $course_id);
        $this->set('course', $course);
    }
    
    function import_finished($course_id = null, $imported_students = null, $inexistent_subjects = null, $failed_students = null) {
        $inexistent_subjects = trim($inexistent_subjects);
        $failed_students = trim($failed_students);

        $course_id = $course_id === null ? null : intval($course_id);
        $imported_students = $imported_students === null ? null : intval($imported_students);
        $inexistent_subjects = $inexistent_subjects ? array_map('intval', explode(';', trim($inexistent_subjects))) : array();
        $failed_students = $failed_students ? array_map('intval', explode(';', trim($failed_students))) : array();

        $course = $this->User->Subject->Course->find('first', array(
            'fields' => array('Course.*', 'Degree.*'),
            'joins' => array(
                array(
                    'table' => 'degrees',
                    'alias' => 'Degree',
                    'type' => 'INNER',
                    'conditions' => 'Degree.id = Course.degree_id'
                )
            ),
            'conditions' => array(
                'Course.id' => $course_id,
                'Course.institution_id' => Environment::institution('id')
            ),
            'recursive' => -1
        ));

        $this->set('course', $course);
        $this->set('imported_students', $imported_students);
        $this->set('inexistent_subjects', $inexistent_subjects);
        $this->set('failed_students', $failed_students);
    }

    function my_subjects() {
        $this->set('section', 'my_subjects');
        $user_id = intval($this->Auth->user('id'));
        $this->User->id = $user_id;
        
        $academic_year = $this->User->Subject->Course->AcademicYear->current();
        $courses_id = null;
        $subjects = null;

        if ($academic_year) {
            $courses = $this->User->Subject->Course->current(array('user_id' => $user_id));
            $courses_id = Set::extract($courses, '{n}.id');
        }

        if (empty($courses_id)) {
            $subjects = null;
        } else if ($this->Auth->user('type') == 'Estudiante') {
            $subjects = $this->User->Subject->find('all', array(
                'fields' => array('Subject.*', 'Course.*', 'Degree.*'),
                'joins' => array(
                    array(
                        'table' => 'courses',
                        'alias' => 'Course',
                        'type' => 'INNER',
                        'conditions' => 'Course.id = Subject.course_id'
                    ),
                    array(
                        'table' => 'subjects_users',
                        'alias' => 'SubjectUser',
                        'type' => 'INNER',
                        'conditions' => 'SubjectUser.subject_id = Subject.id'
                    ),
                    array(
                        'table' => 'degrees',
                        'alias' => 'Degree',
                        'type' => 'INNER',
                        'conditions' => 'Degree.id = Course.degree_id'
                    )
                ),
                'conditions' => array(
                    'Subject.course_id' => $courses_id,
                    'SubjectUser.user_id' => $user_id,
                ),
                'recursive' => -1
            ));
        } else {
            $subjects = $this->User->Subject->find('all', array(
                'fields' => array('Subject.*', 'Course.*', 'Degree.*'),
                'joins' => array(
                    array(
                        'table' => 'courses',
                        'alias' => 'Course',
                        'type' => 'INNER',
                        'conditions' => 'Course.id = Subject.course_id'
                    ),
                    array(
                        'table' => 'degrees',
                        'alias' => 'Degree',
                        'type' => 'INNER',
                        'conditions' => 'Degree.id = Course.degree_id'
                    )
                ),
                'conditions' => array(
                    'Subject.course_id' => $courses_id,
                    'OR' => array(
                        'Subject.coordinator_id' => $user_id,
                        'Subject.practice_responsible_id' => $user_id,
                        "Subject.id IN (
                            SELECT Activity.subject_id
                            FROM events Event 
                            INNER JOIN activities Activity ON Activity.id = Event.activity_id
                            WHERE Event.teacher_id = $user_id OR Event.teacher_2_id = $user_id
                        )"
                    )
                ),
                'recursive' => -1
            ));
        }

        $this->set('academic_year', $academic_year);
        $this->set('subjects', $subjects);
        $this->set('user', $this->User->read());
    }
    
    function student_stats($id = null) {
        $id = $id === null ? null : intval($id);

        if (! $id) {
            $this->Session->setFlash('No se ha podido acceder al usuario.');
            $this->redirect(array('action' => 'index'));
        }

        $user = $this->User->find('first', array(
            'fields' => array('User.*', 'UserInstitution.*'),
            'joins' => array(
                array(
                    'table' => 'users_institutions',
                    'alias' => 'UserInstitution',
                    'type' => 'INNER',
                    'conditions' => array(
                        'UserInstitution.user_id = User.id',
                        'UserInstitution.institution_id' => Environment::institution('id')
                    )
                )
            ),
            'conditions' => array(
                'User.id' => $id
            )
        ));

        if (! $user) {
            $this->Session->setFlash('No se ha podido acceder al usuario.');
            $this->redirect(array('action' => 'index'));
        }

        $academic_years = $this->User->Subject->Course->AcademicYear->find('all', array(
            'recursive' => -1,
            'order' => array('AcademicYear.initial_date' => 'desc')
        ));

        $academic_years = Set::combine($academic_years, '{n}.AcademicYear.id', '{n}.AcademicYear');

        $courses = $this->User->Subject->Course->find('all', array(
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

        $this->set('user', $user);
        $this->set('academic_years', $academic_years);
        $this->set('current_academic_year', $this->User->Subject->Course->AcademicYear->current());
    }
    
    function get_student_subjects($id = null) {
        $id = $id === null ? null : intval($id);
        $course_id = intval($this->params['url']['course_id']);

        $user = null;
        $course = null;
        $subjects = array();

        if ($id && $course_id) {
            $user = $this->User->find('first', array(
                'fields' => array('User.*', 'UserInstitution.*'),
                'joins' => array(
                    array(
                        'table' => 'users_institutions',
                        'alias' => 'UserInstitution',
                        'type' => 'INNER',
                        'conditions' => array(
                            'UserInstitution.user_id = User.id',
                            'UserInstitution.institution_id' => Environment::institution('id')
                        )
                    )
                ),
                'conditions' => array(
                    'User.id' => $id
                )
            ));
        }

        if ($user && $course_id) {
            $course = $this->User->Subject->Course->find('first', array(
                'conditions' => array(
                    'Course.id' => $course_id,
                    'Course.institution_id' => Environment::institution('id')
                )
            ));
        }
      
        if ($user && $course) {
            $subjects = $this->User->Subject->query("SELECT Subject.* FROM subjects Subject INNER JOIN subjects_users ON subjects_users.subject_id = Subject.id WHERE subjects_users.user_id = {$id} AND Subject.course_id = {$course_id} ORDER BY Subject.name");
        }

        $this->set('subjects', $subjects);
    }
    
    function student_stats_details($id = null) {
        $id = $id === null ? null : intval($id);
        $subject_id = intval($this->params['url']['subject_id']);

        $user = null;
        $subject = null;
        $registers = array();

        if ($id && $subject_id) {
            $user = $this->User->find('first', array(
                'fields' => array('User.*', 'UserInstitution.*'),
                'joins' => array(
                    array(
                        'table' => 'users_institutions',
                        'alias' => 'UserInstitution',
                        'type' => 'INNER',
                        'conditions' => array(
                            'UserInstitution.user_id = User.id',
                            'UserInstitution.institution_id' => Environment::institution('id')
                        )
                    )
                ),
                'conditions' => array(
                    'User.id' => $id
                )
            ));
        }

        if ($user && $subject_id) {
            $subject = $this->User->Subject->find('first', array(
                'conditions' => array(
                    'Subject.id' => $subject_id,
                    'Course.institution_id' => Environment::institution('id')
                )
            ));
        }

        if ($user && $subject) {
            $registers = $this->User->query("SELECT AttendanceRegister.*, Event.*, Activity.name, Teacher.first_name, Teacher.last_name, Teacher2.first_name, Teacher2.last_name FROM attendance_registers AttendanceRegister INNER JOIN users_attendance_register uat ON uat.attendance_register_id = AttendanceRegister.id AND uat.user_gone INNER JOIN events Event ON Event.id = AttendanceRegister.event_id INNER JOIN activities Activity ON Activity.id = AttendanceRegister.activity_id LEFT JOIN users Teacher ON Teacher.id = AttendanceRegister.teacher_id LEFT JOIN users Teacher2 ON Teacher2.id = AttendanceRegister.teacher_2_id WHERE uat.user_id = {$id} AND Activity.subject_id = {$subject_id} ORDER BY Event.initial_hour DESC");
        }

        $this->set('user', $user);
        $this->set('registers', $registers);
    }
    
    function teacher_stats($id = null) {
        $id = $id === null ? null : intval($id);
        
        if (! $id) {
            $this->Session->setFlash('No se ha podido acceder al usuario.');
            $this->redirect(array('action' => 'index'));
        }

        $user = $this->User->find('first', array(
            'fields' => array('User.*', 'UserInstitution.*'),
            'joins' => array(
                array(
                    'table' => 'users_institutions',
                    'alias' => 'UserInstitution',
                    'type' => 'INNER',
                    'conditions' => array(
                        'UserInstitution.user_id = User.id',
                        'UserInstitution.institution_id' => Environment::institution('id')
                    )
                )
            ),
            'conditions' => array(
                'User.id' => $id
            )
        ));

        if (! $user) {
            $this->Session->setFlash('No se ha podido acceder al usuario.');
            $this->redirect(array('action' => 'index'));
        }
        
        $academic_years = $this->User->Subject->Course->AcademicYear->find('all', array(
            'recursive' => -1,
            'order' => array('AcademicYear.initial_date' => 'desc')
        ));

        $academic_years = Set::combine($academic_years, '{n}.AcademicYear.id', '{n}.AcademicYear');

        $courses = $this->User->Subject->Course->find('all', array(
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

        $this->set('user', $user);
        $this->set('academic_years', $academic_years);
        $this->set('current_academic_year', $this->User->Subject->Course->AcademicYear->current());
    }
    
    /**
     * Shows detailed summary about teaching statistics
     *
     * @param integer $id ID of a teacher
     * @version 2012-06-04
     */
    function teacher_stats_details($id = null) {
        $id = $id === null ? null : intval($id);
        $course_id = intval($this->params['url']['course_id']);
        
        $user = null;
        $course = null;
        $subjects_as_coordinator = array();
        $subjects_as_practice_responsible = array();
        $registrations = array();
        $total_hours = array();
        $practice_hours = array();
        $theoretical_hours = array();
        $other_hours = array();
        $hours_group_by_subject = array();

        if ($id && $course_id) {
            $user = $this->User->find('first', array(
                'fields' => array('User.*', 'UserInstitution.*'),
                'joins' => array(
                    array(
                        'table' => 'users_institutions',
                        'alias' => 'UserInstitution',
                        'type' => 'INNER',
                        'conditions' => array(
                            'UserInstitution.user_id = User.id',
                            'UserInstitution.institution_id' => Environment::institution('id')
                        )
                    )
                ),
                'conditions' => array(
                    'User.id' => $id
                )
            ));
        }

        if ($user && $course_id) {
            $course = $this->User->Subject->Course->find('first', array(
                'conditions' => array(
                    'Course.id' => $course_id,
                    'Course.institution_id' => Environment::institution('id')
                )
            ));
        }
      
        if ($user && $course) {
            $subjects_as_coordinator = $this->User->query("SELECT Subject.* FROM subjects Subject WHERE Subject.course_id = {$course_id} AND Subject.coordinator_id = {$user["User"]["id"]} ORDER BY Subject.code");
            $subjects_as_practice_responsible = $this->User->query("SELECT Subject.* FROM subjects Subject WHERE Subject.course_id = {$course_id} AND Subject.practice_responsible_id = {$user["User"]["id"]} ORDER BY Subject.code");

            $registrations = $this->User->query("
                SELECT Subject.code, AttendanceRegister.*, Activity.name
                FROM attendance_registers AttendanceRegister
                INNER JOIN activities Activity ON Activity.id = AttendanceRegister.activity_id
                INNER JOIN subjects Subject ON Subject.id = Activity.subject_id
                WHERE (AttendanceRegister.teacher_id = {$user["User"]["id"]} OR AttendanceRegister.teacher_2_id = {$user["User"]["id"]})
                AND AttendanceRegister.duration > 0 AND Subject.course_id = {$course_id}
                ORDER BY AttendanceRegister.initial_hour DESC
            ");

            $total_hours = $this->User->teachingHours($user['User']['id'], $course_id);
            $theoretical_hours = $this->User->teachingHours($user['User']['id'], $course_id, 'theory');
            $practice_hours = $this->User->teachingHours($user['User']['id'], $course_id, 'practice');
            $other_hours = $this->User->teachingHours($user['User']['id'], $course_id, 'other');

            $hours_group_by_activity_type = $this->User->query("
                SELECT subjects.id, subjects.code, subjects.name, IF(activities.type IN ('Clase magistral', 'Seminario'), 'T', IF(activities.type IN ('Tutoría', 'Evaluación', 'Otra presencial'), 'O', 'P')) as `type`, SUM(IFNULL(AttendanceRegister.duration, 0)) as total
                FROM attendance_registers AttendanceRegister
                INNER JOIN activities ON activities.id = AttendanceRegister.activity_id
                INNER JOIN subjects ON subjects.id = activities.subject_id
                WHERE (AttendanceRegister.teacher_id = {$user["User"]["id"]} OR AttendanceRegister.teacher_2_id = {$user["User"]["id"]})
                AND subjects.course_id = {$course_id}
                GROUP BY subjects.id, type
                ORDER BY subjects.code
            ");

            $hours_group_by_subject = array();
            foreach($hours_group_by_activity_type as $record) {
                $id = $record['subjects']['id'];
                if (!isset($hours_group_by_subject[$id])) {
                    $hours_group_by_subject[$id] = array();
                    $hours_group_by_subject[$id]['code'] = $record['subjects']['code'];
                    $hours_group_by_subject[$id]['name'] = $record['subjects']['name'];
                }
                $hours_group_by_subject[$id][$record[0]['type']] = $record[0]['total'];
            }
        }

        $this->set('user', $user);
        $this->set('subjects_as_coordinator', $subjects_as_coordinator);
        $this->set('subjects_as_practice_responsible', $subjects_as_practice_responsible);
        $this->set('registers', $registrations);
        $this->set('total_hours', $total_hours);
        $this->set('practical_hours', $practice_hours);
        $this->set('teorical_hours', $theoretical_hours);
        $this->set('other_hours', $other_hours);
        $this->set('hours_group_by_subject', $hours_group_by_subject);    
}
    
    function teacher_schedule($id = null) {
        $id = $id === null ? null : intval($id);
        
        if (! $id) {
            $this->Session->setFlash('No se ha podido acceder al usuario.');
            $this->redirect(array('action' => 'index'));
        }

        $user = $this->User->find('first', array(
            'fields' => array('User.*', 'UserInstitution.*'),
            'joins' => array(
                array(
                    'table' => 'users_institutions',
                    'alias' => 'UserInstitution',
                    'type' => 'INNER',
                    'conditions' => array(
                        'UserInstitution.user_id = User.id',
                        'UserInstitution.institution_id' => Environment::institution('id')
                    )
                )
            ),
            'conditions' => array(
                'User.id' => $id
            )
        ));

        if (! $user) {
            $this->Session->setFlash('No se ha podido acceder al usuario.');
            $this->redirect(array('action' => 'index'));
        }
        
        $academic_years = $this->User->Subject->Course->AcademicYear->find('all', array(
            'recursive' => -1,
            'order' => array('AcademicYear.initial_date' => 'desc')
        ));

        $academic_years = Set::combine($academic_years, '{n}.AcademicYear.id', '{n}.AcademicYear');

        $courses = $this->User->Subject->Course->find('all', array(
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

        $this->set('user', $user);
        $this->set('academic_years', $academic_years);
        $this->set('current_academic_year', $this->User->Subject->Course->AcademicYear->current());
    }

    function teacher_schedule_details($id = null) {
        $id = $id === null ? null : intval($id);
        $course_id = intval($this->params['url']['course_id']);

        $user = null;
        $course = null;
        $subjects_as_coordinator = array();
        $subjects_as_practice_responsible = array();
        $events = array();
        $total_hours = array();
        $practice_hours = array();
        $theoretical_hours = array();
        $other_hours = array();
        $hours_group_by_subject = array();
        
        if ($id && $course_id) {
            $user = $this->User->find('first', array(
                'fields' => array('User.*', 'UserInstitution.*'),
                'joins' => array(
                    array(
                        'table' => 'users_institutions',
                        'alias' => 'UserInstitution',
                        'type' => 'INNER',
                        'conditions' => array(
                            'UserInstitution.user_id = User.id',
                            'UserInstitution.institution_id' => Environment::institution('id')
                        )
                    )
                ),
                'conditions' => array(
                    'User.id' => $id
                )
            ));
        }

        if ($user && $course_id) {
            $course = $this->User->Subject->Course->find('first', array(
                'conditions' => array(
                    'Course.id' => $course_id,
                    'Course.institution_id' => Environment::institution('id')
                )
            ));
        }
      
        if ($user && $course) {
            $subjects_as_coordinator = $this->User->query("SELECT Subject.* FROM subjects Subject WHERE Subject.course_id = {$course_id} AND Subject.coordinator_id = {$user["User"]["id"]} ORDER BY Subject.code");
            $subjects_as_practice_responsible = $this->User->query("SELECT Subject.* FROM subjects Subject WHERE Subject.course_id = {$course_id} AND Subject.practice_responsible_id = {$user["User"]["id"]} ORDER BY Subject.code");
        
            $events = $this->User->query("
                SELECT Subject.code, Event.*, Activity.name
                FROM events Event
                INNER JOIN activities Activity ON Activity.id = Event.activity_id
                INNER JOIN subjects Subject ON Subject.id = Activity.subject_id
                WHERE (Event.teacher_id = {$user["User"]["id"]} OR Event.teacher_2_id = {$user["User"]["id"]})
                AND Event.duration > 0 AND Subject.course_id = {$course_id}
                ORDER BY Event.initial_hour DESC
            ");
        
            $total_hours = $this->User->ScheduledHours($user['User']['id'], $course_id);
            $theoretical_hours = $this->User->ScheduledHours($user['User']['id'], $course_id, 'theory');
            $practice_hours = $this->User->ScheduledHours($user['User']['id'], $course_id, 'practice');
            $other_hours = $this->User->ScheduledHours($user['User']['id'], $course_id, 'other');
        
            $hours_group_by_activity_type = $this->User->query("
                SELECT subjects.id, subjects.code, subjects.name, IF(activities.type IN ('Clase magistral', 'Seminario'), 'T', IF(activities.type IN ('Tutoría', 'Evaluación', 'Otra presencial'), 'O', 'P')) as `type`, SUM(IFNULL(Event.duration, 0)) as total
                FROM events Event
                INNER JOIN activities ON activities.id = Event.activity_id
                INNER JOIN subjects ON subjects.id = activities.subject_id
                WHERE (Event.teacher_id = {$user["User"]["id"]} OR Event.teacher_2_id = {$user["User"]["id"]})
                AND subjects.course_id = {$course_id}
                GROUP BY subjects.id, type
                ORDER BY subjects.code
            ");

            $hours_group_by_subject = array();
            foreach($hours_group_by_activity_type as $record) {
                $id = $record['subjects']['id'];
                if (!isset($hours_group_by_subject[$id])) {
                    $hours_group_by_subject[$id] = array();
                    $hours_group_by_subject[$id]['code'] = $record['subjects']['code'];
                    $hours_group_by_subject[$id]['name'] = $record['subjects']['name'];
                }
                $hours_group_by_subject[$id][$record[0]['type']] = $record[0]['total'];
            }
        }
    
        $this->set('user', $user);
        $this->set('subjects_as_coordinator', $subjects_as_coordinator);
        $this->set('subjects_as_practice_responsible', $subjects_as_practice_responsible);
        $this->set('events', $events);
        $this->set('total_hours', $total_hours);
        $this->set('practical_hours', $practice_hours);
        $this->set('teorical_hours', $theoretical_hours);
        $this->set('other_hours', $other_hours);
        $this->set('hours_group_by_subject', $hours_group_by_subject);
    }
    
    function _import_student($user, $args, $course_id, $subjects) {
        if (!isset($this->SubjectUser)) {
            $this->loadModel('SubjectUser');
        }

        if (!isset($this->UserInstitution)) {
            $this->loadModel('UserInstitution');
        }

        $this->User->id = null;
        $this->UserInstitution->id = null;

        if (!$user) {
            $user = array();
            $registered_subjects = array();
        } else {
            $this->User->id = $user['User']['id'];

            if (! empty($user['UserInstitution']['id'])) {
                $this->UserInstitution->id = $user['UserInstitution']['id'];
            }

            $registered_subjects = $this->_get_registered_subjects($course_id, $user['User']['id']);
        }
        
        $user['User']['type'] = "Estudiante";
        $user['User']['dni'] = $args['dni'];
        $user['User']['first_name'] = $args['first_name'];
        $user['User']['last_name'] = $args['last_name'];
        $user['User']['username'] = $args['username'];
        $user['User']['phone'] = $args['phone'];
        
        $subjects_codes_to_register = $args['subjects'];
        
        $subjects_to_register = Set::combine(
            array_intersect_key($subjects, array_flip($subjects_codes_to_register)),
            '{n}.subject_id',
            '{n}'
        );

        $subjects_to_add = array_diff_key($subjects_to_register, array_flip($registered_subjects));
        
        ///** @deprecated in favour CAS auth */
        //if ($this->User->id) {
        //    $password = null;
        //} else {
        //    $password = substr(md5(uniqid(mt_rand(), true)), 0, 8);
        //    $user['User']['password'] = $this->Auth->password($password);
        //}
        
        if ($this->User->save($user)) {
            $subjects_user = [];

            foreach ($subjects_to_add as $subject_to_add) {
                $subjects_user []= array(
                    'SubjectUser' => array('user_id' => $this->User->id) + $subject_to_add
                );
            }

            if ($this->SubjectUser->saveAll($subjects_user)) {
                if (!empty($user['UserInstitution']['active'])) {
                    return true;
                }

                $user_institution = array(
                    'UserInstitution' => array(
                        'user_id' => $this->User->id,
                        'institution_id' => Environment::institution('id'),
                        'active' => 1 
                    )
                );

                if ($this->UserInstitution->save($user_institution)) {
                    $this->Email->from = 'Academic <noreply@ulpgc.es>';
                    $this->Email->to = $user['User']['username'];
                    $this->Email->subject = "Alta en Academic";
                    $this->Email->sendAs = 'both';
                    $this->Email->template = Configure::read('app.email.user_registered')
                        ? Configure::read('app.email.user_registered')
                        : 'user_registered';
                    $this->set('user', $user);
                    ///** @deprecated in favour CAS auth */
                    //if ($password) {
                    //    $this->set('password', $password);
                    //}
                    $this->Email->send();

                    return true;
                }
            }
        }

        return false;
    }
    
    function _get_course_subjects($course_id) {
        $subjects = $this->User->Subject->find('all', array(
            'fields' => array('Subject.id', 'Subject.code', 'Subject.parent_id'),
            'conditions' => array('Subject.course_id' => $course_id),
            'recursive' => -1
        ));
        $result = array();
        foreach ($subjects as $subject):
            $result[$subject['Subject']['code']] = array(
                'subject_id' => isset($subject['Subject']['parent_id']) ? $subject['Subject']['parent_id'] : $subject['Subject']['id'],
                'child_subject_id' => isset($subject['Subject']['parent_id']) ? $subject['Subject']['id'] : null,
            );
        endforeach;
        
        return $result;
    }

    function _get_registered_subjects($course_id, $user_id) {
        $subjects = $this->User->Subject->find('all', array(
            'fields' => array('Subject.id', 'Subject.code'),
            'joins' => array(
                array(
                    'table' => 'subjects_users',
                    'alias' => 'SubjectUser',
                    'type'  => 'INNER',
                    'conditions' => array(
                        'SubjectUser.subject_id = Subject.id',
                        'SubjectUser.user_id' => $user_id
                    )
                )
            ),
            'conditions' => array(
                'Subject.course_id' => $course_id
            ),
            'recursive' => -1
        ));
        $result = array();
        foreach ($subjects as $subject):
            $result[$subject['Subject']['code']] = $subject['Subject']['id'];
        endforeach;
        
        return $result;
    }

    function _allowAnonymousActions() {
        $this->Auth->allow('login', 'cas_login', 'rememberPassword', 'calendars');
        
        $acl = Configure::read('app.acl');
        if (!empty($acl['all']['events.calendar_by_teacher'])) {
            $this->Auth->allow('find_teachers_by_name');
        }
    }
    
    function _authorize() {
        parent::_authorize();
            
        $ref = isset($this->params['named']['ref']) ? $this->params['named']['ref'] : null;

        if ($ref && array_key_exists($ref, $this->refs_sections)) {
            $this->set('section', $this->refs_sections[$ref]);
        } else {
            $this->set('section', 'users');
        }

        if ($this->params['action'] === 'calendars') {
            if (empty($this->params['pass'][0])) {
                return false;
            }
            
            $user = $this->User->findByCalendarToken($this->params['pass'][0]);

            if ($user === false) {
                return false;
            }

            Environment::setUser($user);
        } 
        
        $no_institution_actions = array('index', 'edit', 'add', 'view', 'delete', 'calendars', 'editProfile', 'home', 'login', 'login_as', 'cas_login', 'logout', 'my_subjects', 'rememberPassword');
        $administrator_actions = array('delete', 'import', 'acl_edit');
        $administrative_actions = array('edit_registration', 'delete_subject', 'edit', 'add');
        $stats_actions = array('index', 'teacher_stats', 'student_stats', 'teacher_stats_details', 'student_stats_details', 'get_student_subjects', 'view');
        $my_subjects_actions = array('my_subjects');

        if (array_search($this->params['action'], $no_institution_actions) === false && ! Environment::institution('id')) {
            return false;
        }

        $auth_type = $this->Auth->user('type');

        if ($auth_type) {
            $acl = Configure::read('app.acl');
            if (!empty($acl[$auth_type]['events.calendar_by_teacher'])) {
                $this->Auth->allow('find_teachers_by_name');
            }
        }
        
        if ((array_search($this->params['action'], $administrator_actions) !== false) && ($this->Auth->user('type') != "Administrador")) {
            return false;
        }
        
        if ((array_search($this->params['action'], $stats_actions) !== false) && (($this->Auth->user('type') == "Estudiante") || ($this->Auth->user('type') == "Conserje") )) {
            return false;
        }
        
        if ((array_search($this->params['action'], $administrative_actions) !== false) && ($this->Auth->user('type') != "Administrador") && ($this->Auth->user('type') != "Administrativo")) {
            return false;
        }
        
        if ((array_search($this->params['action'], $my_subjects_actions) !== false) && (! in_array($this->Auth->user('type'), array("Administrador", "Profesor", "Estudiante")))) {
            return false;
        }

        return true;
    }

}
