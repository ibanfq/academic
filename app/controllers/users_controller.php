<?php
class UsersController extends AppController {
    var $name = 'Users';
    var $paginate = array('limit' => 10, 'order' => array('User.last_name' => 'asc', 'User.first_name' => 'asc'));
    var $helpers = array('UserModel', 'activityHelper');

    function beforeFilter() 
    {
        parent::beforeFilter();
        $this->Auth->allow('calendars');
    }

    function login() {
        $this->set('action', 'login');
        $this->Auth->loginError = "El nombre de usuario y contraseña no son correctos";
    }

    function logout() {
        $this->redirect($this->Auth->logout());
    }
    
    function home() {
        $this->User->id = $this->Auth->user('id');
        $this->User->data = $this->Auth->user();
        
        $this->set('section', 'home');
        $this->set('user', $this->User);
        $this->set('events', $this->User->getEvents());
        $this->set('bookings', $this->User->getBookings());
    }
    
    function calendars($token) {
        $user = $this->User->findByCalendarToken($token);
        if ($user === false) {
            return $this->cakeError('error404');
        }
        $this->User->id = $user['User']['id'];
        $this->User->data = $user;
        
        $calendarName = Configure::read('app.vcalendar.name')
            ? Configure::read('app.vcalendar.name')
            : 'Academic';

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
        App::import('Sanitize');
        
        if (isset($this->params['url']['q'])) {
            $q = Sanitize::escape($this->params['url']['q']);
        } else {
            if (isset($this->passedArgs['q'])) {
                $q = Sanitize::escape($this->passedArgs['q']);
            } else {
                $q = '';
            }
        }

        $users = $this->paginate('User', array("OR" => array('User.first_name LIKE' => "%$q%", 'User.last_name LIKE' => "%$q%", 'User.type LIKE' => "%$q%", 'User.username LIKE' => "%$q%", 'User.dni LIKE' => "%$q%")));

        $this->set('users', $users);
        $this->set('q', $q);
    }
    
    function view($id = null){
        $id = $id === null ? null : intval($id);
        $this->User->id = $id;
        $this->set('user', $this->User->read());
    }
    
    function add() {
        if (!empty($this->data)){
            $password = substr(md5(uniqid(mt_rand(), true)), 0, 8);
            $this->data['User']['password'] = $this->Auth->password($password);
            if ($this->User->save($this->data)){
                $this->Email->from = 'Academic <noreply@ulpgc.es>';
                $this->Email->to = $this->data['User']['username'];
                $this->Email->subject = "Alta en Academic";
                $this->Email->sendAs = 'both';
                $this->Email->template = Configure::read('app.email.user_registered')
                    ? Configure::read('app.email.user_registered')
                    : 'user_registered';
                $this->set('user', $this->data);
                $this->set('password', $password);
                $this->Email->send();
                $this->Session->setFlash('El usuario se ha guardado correctamente');
                $this->redirect(array('action' => 'index'));
            }
        }
    }
    
    function edit($id = null) {
        $id = $id === null ? null : intval($id);
        $this->User->id = $id;
        if (empty($this->data)) {
            $this->data = $this->User->read();
            $betaTesters = (array) Configure::read('app.beta.testers');
            $this->set('user', $this->data);
            $this->set('isBetaTester', !empty($betaTesters[$this->data['User']['username']]));
        } else {
            if ($this->User->save($this->data)) {
                $ok = true;

                if ($this->Auth->user('type') === 'Administrador' && isset($this->data['User']['is_beta_tester'])) {
                    $config_file = ROOT . DS . APP_DIR . DS . 'config' . DS . 'app.options.php';
                    $app_options = include $config_file;
                    if (!is_array($app_options)) {
                        $app_options = array();
                    }

                    $username = $this->data['User']['username'];
                    if (empty($this->data['User']['is_beta_tester'])) {
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
                if ($ok) {
                    $this->Session->setFlash('El usuario se ha actualizado correctamente.');
                    $this->redirect(array('action' => 'view', $id));
                }
            }
            $this->set('user', $this->data);
        }
    }
    
    function delete($id = null){
        $id = $id === null ? null : intval($id);
        $this->User->query("DELETE FROM group_requests WHERE student_id = {$id} OR student_2_id = {$id}");
        $this->User->query("DELETE FROM `competence_criterion_teachers` WHERE teacher_id = {$id}");
        $this->User->query("DELETE FROM `competence_criterion_grades` WHERE student_id = {$id}");
        $this->User->query("DELETE FROM `competence_goal_requests` WHERE student_id = {$id} OR teacher_id = {$id}");
        $this->User->delete($id);
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
            $config_file = ROOT . DS . APP_DIR . DS . 'config' . DS . 'app.options.php';
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
        App::import('Sanitize');

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
            'recursive' => 0,
            'conditions' => array(
                "AND" => $name_conditions
            ),
            'order' => array('User.first_name', 'User.last_name')
        ));
        $this->set('users', $users);
    }
  
    function find_teachers_by_name(){
        App::import('Sanitize');

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
            'recursive' => 0,
            'conditions' => array(
                'OR' => array(
                    array('User.type' => 'Profesor'),
                    array('User.type' => 'Administrador')
                ),
                'AND' => $name_conditions
            ),
            'order' => array('User.first_name', 'User.last_name')
        ));
        $this->set('users', $users);
    }

    function find_teachers_by_competence_goal_and_name($goal_id) {
        $goal_id = $goal_id === null ? null : intval($goal_id);
        App::import('Sanitize');

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

        $student_id = null;
        if ($this->Auth->user('type') === 'Estudiante') {
            $student_id = $this->Auth->user('id');
        }
        
        $users = $this->User->find('all', array(
            'fields' => array('distinct User.id', 'User.first_name', 'User.last_name'),
            'joins' => array(
                array(
                    'table' => 'competence_criteria',
                    'alias' => 'CompetenceCriterion',
                    'type'  => 'LEFT',
                    'conditions' => array(
                        'CompetenceCriterion.goal_id', $goal_id
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
        $this->set('users', $users);
    }
    
    /**
     * Find students by name
     */
    function find_students_by_name() {
        App::import('Sanitize');

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
            'recursive' => 0,
            'conditions' => array(
                'User.type' => 'Estudiante',
                "AND" => $name_conditions
            ),
            'order' => array('User.first_name', 'User.last_name')
        ));
        $this->set('users', $users);
    }

    function editProfile() {
        $this->User->id = $this->Auth->user('id');
        if (empty($this->data)) {
            $this->data = $this->User->read();
            $this->set('user', $this->data);
        } else {
                    if (in_array($this->Auth->user('type'), array('Estudiante', 'Profesor'))) {
                        $this->data['User'] = array_intersect_key(
                            $this->data['User'],
                            array_flip(array(
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
            }
            else {
                $this->data = $this->User->read();
                $this->set('user', $this->data);
                        }
        }
    }
    
    function rememberPassword(){
        if (!empty($this->data)) {
            $this->data = $this->User->find('first', array('conditions' => array('username' => $this->data['User']['username'])));
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
                $this->redirect(array('action' => 'login'));
            }
            else
                $this->Session->setFlash('No se ha podido encontrar un usuario con el correo electrónico especificado');
        }
    }
    
    function edit_registration($id = null){
        $id = $id === null ? null : intval($id);
        $this->User->id = $id;
        $user = $this->User->read();
        if (!empty($this->data)){
            
        }
        else {
            $current_course = $this->User->Subject->Course->current();
            $subjects = $this->User->Subject->query("SELECT Subject.* FROM subjects Subject INNER JOIN subjects_users SubjectUser ON SubjectUser.subject_id = Subject.id WHERE SubjectUser.user_id = {$id} AND Subject.course_id = {$current_course['id']}");
            $this->set('user', $user);
            $this->set('subjects', $subjects);
        }
    }
    
    function delete_subject($student_id, $subject_id) {
        $student_id = $student_id === null ? null : intval($student_id);
        $subject_id = $subject_id === null ? null : intval($subject_id);
        $this->User->id = $student_id;
        $user = $this->User->read();
        $subject = $this->User->Subject->findById($subject_id);
        if (($user != null) && ($subject != null)){
            $activities = $this->User->Subject->Activity->find('all', array('conditions' => array('Activity.subject_id' => $subject_id)));

            $this->User->query("DELETE FROM subjects_users WHERE user_id = {$student_id} AND subject_id = {$subject_id}");

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
        } else {
            $this->Session->setFlash('No tiene permisos para realizar esta acción');
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }
    }
    
    function save_subject($student_id = null, $subject_id = null){
        $student_id = $student_id === null ? null : intval($student_id);
        $subject_id = $subject_id === null ? null : intval($subject_id);
        $this->User->id = $student_id;
        $user = $this->User->read();
        $subject = $this->User->Subject->findById($subject_id);
        if (($user != null) && ($subject != null)){
            $this->set('success', true);
            $this->set('user', $user);
            $this->set('subject', $subject);
            foreach ($user['Subject'] as $subject) {
                if ($subject['id'] == $subject_id) {
                    return;
                }
            }
            $this->User->query("INSERT INTO subjects_users(subject_id, user_id) VALUES({$subject_id}, {$student_id})");
        } 
    }
    
    function _changePasswordValidation() {
        if ($this->data['User']['new_password'] != "") {
            $user = $this->User->read();
            $old_password_hashed = $this->Auth->password($this->data['User']['old_password']);
            if ($old_password_hashed == $user['User']['password']) {
                if ($this->data['User']['new_password'] == $this->data['User']['password_confirmation']){
                    $this->data['User']['password'] = $this->Auth->password($this->data['User']['new_password']);
                    return true;
                }
                else{
                    $this->Session->setFlash('No se ha podido actualizar su contraseña debido a que la contraseña y su confirmación no coinciden');
                    return false;
                }
            }
            else {
                $this->Session->setFlash('No se ha podido actualizar su contraseña debido a que la antigua contraseña es incorrecta');
                return false;
            }
        }
        
        return true;
    }
    
    function import(){
        if (!empty($this->data)){
            $saved_students = 0;
            
            if (($file = file($this->data['User']['file']['tmp_name']))) {
                $subjects = $this->_get_subjects();
                $imported_subjects = array();
                foreach ($file as $line):
                    $is = $this->_save_student(split("(,[ ]*)", $line), $subjects, $saved_students);
                    $imported_subjects = array_merge($imported_subjects, array_diff($is, $imported_subjects));
                endforeach;
            } 
            
            $inexistent_subjects = implode(" ", array_diff(array_unique($imported_subjects), array_flip($subjects)));
                        
            $this->redirect(array('action' => 'import_finished', $saved_students, $inexistent_subjects));
        }
    }
    
    function import_finished($imported_students, $inexistent_subjects = null){
        $this->set('imported_students', $imported_students);
        $this->set('inexistent_subjects', split(" ", $inexistent_subjects));
    }

    function my_subjects(){
        $this->set('section', 'my_subjects');
        $user_id = intval($this->Auth->user('id'));
        $this->User->id = $user_id;
        
        $course = $this->User->Subject->Course->current();

        $subjects = $this->User->Subject->query("SELECT Subject.* FROM subjects Subject INNER JOIN subjects_users su ON su.subject_id = Subject.id WHERE Subject.course_id = {$course['id']} AND su.user_id = {$user_id}");

        $this->set('course', $course);
        $this->set('subjects', $subjects);
        $this->set('user', $this->User->read());
    }
    
    function student_stats($id = null) {
        $id = $id === null ? null : intval($id);
        $this->User->id = $id;
        $courses = $this->User->Subject->Course->find('all', array('order' => 'initial_date desc'));
      
        $this->set('user', $this->User->read());
        $this->set('courses', $courses);
    }
    
    function get_student_subjects($id = null) {
        $id = $id === null ? null : intval($id);
        $course_id =  intval($this->params['url']['course_id']);
      
        $subjects = $this->User->Subject->query("SELECT Subject.* FROM subjects Subject INNER JOIN subjects_users ON subjects_users.subject_id = Subject.id WHERE subjects_users.user_id = {$id} AND Subject.course_id = {$course_id} ORDER BY Subject.name");
        $this->set('subjects', $subjects);
    }
    
    function student_stats_details($id = null){
        $id = $id === null ? null : intval($id);
        $this->User->id = $id;
        $subject_id = intval($this->params['url']['subject_id']);
        $registers = $this->User->query("SELECT AttendanceRegister.*, Event.*, Activity.name, Teacher.first_name, Teacher.last_name, Teacher2.first_name, Teacher2.last_name FROM attendance_registers AttendanceRegister INNER JOIN users_attendance_register uat ON uat.attendance_register_id = AttendanceRegister.id AND uat.user_gone INNER JOIN events Event ON Event.id = AttendanceRegister.event_id INNER JOIN activities Activity ON Activity.id = AttendanceRegister.activity_id LEFT JOIN users Teacher ON Teacher.id = AttendanceRegister.teacher_id LEFT JOIN users Teacher2 ON Teacher2.id = AttendanceRegister.teacher_2_id WHERE uat.user_id = {$id} AND Activity.subject_id = {$subject_id} ORDER BY Event.initial_hour DESC");
        $this->set('user', $this->User->read());
        $this->set('registers', $registers);
    }
    
    function teacher_stats($id = null) {
        $id = $id === null ? null : intval($id);
        $this->User->id = $id;
        $user = $this->User->read();
        
        $courses = $this->User->Subject->Course->find('all', array('order' => 'initial_date desc'));
        $this->set('courses', $courses);
        $this->set('user', $user);
    }
    
    /**
     * Shows detailed summary about teaching statistics
     *
     * @param integer $id ID of a teacher
     * @version 2012-06-04
     */
    function teacher_stats_details($id = null) {
        $id = $id === null ? null : intval($id);
        $user = $this->User->read(null,$id);
        $course_id = intval($this->params['url']['course_id']);
        
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
        $this->User->id = $id;
        $user = $this->User->read();
        
        $courses = $this->User->Subject->Course->find('all', array('order' => 'initial_date desc'));
        $this->set('courses', $courses);
        $this->set('user', $user);
    }

    function teacher_schedule_details($id = null) {
        $id = $id === null ? null : intval($id);
        $user = $this->User->read(null,$id);
        $course_id = intval($this->params['url']['course_id']);
        
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
    
    function _save_student($args, $subjects, &$imported_subjects){
        $this->User->id = null;
        $course = $this->User->Subject->Course->current();
        
        $user = $this->User->find('first', 
            array('conditions' =>
                array('User.dni' => $args[0])
        ));
    
        if (!$user) {
            $user = array();
            $new_user = true;
        } else {
            $registered_subjects = array();
            $new_user = false;
            
            $this->User->id = $user['User']['id'];
            
            foreach ($user['Subject'] as $subject):
              if ($subject['course_id'] == $course['id']) {
                  $registered_subjects[$subject['code']] = $subject['id'];
              }
            endforeach;
        }
        
        $user['User']['type'] = "Estudiante";
        $user['User']['dni'] = $args[0];
        $user['User']['first_name'] = $args[1];
        $user['User']['last_name'] = "{$args[2]} {$args[3]}";
        $user['User']['username'] = $args[4];
        $user['User']['phone'] = $args[5];
        
        
        if (($args[6] != null) && ($args[6] != "")) {
            $user['User']['phone'] .= " // $args[6]";
        }

        $subjects_to_register = array_slice($args, 7, count($args) - 7 );
        
        $subjects_to_register[count($subjects_to_register) - 1] = trim($subjects_to_register[count($subjects_to_register) - 1]);
        
        $user_subjects = array_intersect_key($subjects, array_flip($subjects_to_register));
        
        if (isset($registered_subjects) && (count($registered_subjects) > 0)) {
            $user_subjects = array_diff($user_subjects, $registered_subjects);
            $subjects_to_delete = implode(",", array_values(array_diff($registered_subjects, $subjects)));
        }
        
        $user_subjects = array_values($user_subjects);
        
        $user['Subject']['Subject'] = array_unique($user_subjects);
        
        if ($new_user == true) {
            $password = substr(md5(uniqid(mt_rand(), true)), 0, 8);
            $user['User']['password'] = $this->Auth->password($password);
        }
        
        if ($this->User->save($user)){
            if ($new_user == true) {
                $this->Email->from = 'Academic <noreply@ulpgc.es>';
                $this->Email->to = $user['User']['username'];
                $this->Email->subject = "Alta en Academic";
                $this->Email->sendAs = 'both';
                $this->Email->template = Configure::read('app.email.user_registered')
                    ? Configure::read('app.email.user_registered')
                    : 'user_registered';
                $this->set('user', $user);
                $this->set('password', $password);
                $this->Email->send();
            }
            
            $imported_subjects++;
        }
        $password = null;
        $user['User']['password'] = null;
                    
        return array_slice($args, 7, count($args) - 7);
    }
    
    function _get_subjects() {
        $course = $this->User->Subject->Course->current();
        $subjects = $this->User->Subject->find('all', array('conditions' => array('Subject.course_id' => $course['id'])));
        $result = array();
        foreach ($subjects as $subject):
            $result[$subject['Subject']['code']] = $subject['Subject']['id'];
        endforeach;
        
        return $result;
    }
    
    function _authorize() {
        parent::_authorize();
            
        $this->set('section', 'users');
        
        $administrator_actions = array('delete', 'import', 'acl_edit');
        $administrative_actions = array('edit_registration', 'delete_subject', 'edit', 'add');
        $stats_actions = array('index', 'teacher_stats', 'student_stats', 'teacher_stats_details', 'student_stats_details', 'get_student_subjects', 'view');
        $student_actions = array('my_subjects');
        $public_actions = array();

        $acl = Configure::read('app.acl');
        $auth_type = $this->Auth->user('type');
        if ($auth_type && !empty($acl[$auth_type]['events.calendar_by_teacher']) || !empty($acl['all']['events.calendar_by_teacher'])) {
            array_push($public_actions, 'find_teachers_by_name');
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
        
        if ((array_search($this->params['action'], $student_actions) !== false) && ($this->Auth->user('type') != "Estudiante")) {
            return false;
        }

        if ((array_search($this->params['action'], $public_actions) !== false)) {
            $this->Auth->allow($this->params['action']);
        }
    
        return true;
    }

}
