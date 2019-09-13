<?php
class AttendanceRegistersController extends AppController {
    var $name = 'AttendanceRegisters';

    var $paginate = array(
        'limit' => 10,
        'order' => array('AttendanceRegister.initial_hour' => 'desc'),
        'contain' => array('Event.Activity.*')
    );

    var $helpers = array('Ajax', 'ModelHelper', 'Barcode');

    /**
     * Shows a list of attendance registers
     */
    function index($teacher_id = -1, $activity_id = -1, $date = -1, $id = -1) {
        if (!empty($this->data)) {
            $conditions = $this->_get_search_conditions(
                $this->data['AttendanceRegister']['activity_id'],
                $this->data['AttendanceRegister']['teacher_id'],
                $this->data['AttendanceRegister']['date'],
                $this->data['AttendanceRegister']['id']
            );
            $registers = $this->paginate('AttendanceRegister', $conditions);
        } elseif (($teacher_id != -1) || ($activity_id != -1) || ($date != -1) || ($id != -1)) {
            if ($activity_id == -1) {
                $activity_id = null;
            }

            if ($teacher_id == -1) {
                $teacher_id = null;
            }

            if ($date == -1) {
                $date = null;
            }

            if ($id == -1) {
                $id = null;
            }

            $conditions = $this->_get_search_conditions($activity_id, $teacher_id, $date, $id);
            $registers = $this->paginate('AttendanceRegister', $conditions);
        } else {
            $registers = $this->paginate('AttendanceRegister', $this->_get_search_conditions());
        }

        $this->set('registers', $registers);
    }

    function _get_search_conditions($activity_id = null, $teacher_id = null, $date = null, $id = null){
        $db = $this->AttendanceRegister->getDataSource();
        
        $conditions = "Event.classroom_id IN (SELECT classroom_id FROM classrooms_institutions ClassroomInstitution WHERE ClassroomInstitution.institution_id = {$db->value(Environment::institution('id'))})";

        $conditions .= " AND AttendanceRegister.duration > 0";

        if ($activity_id != null){
            $activity_id = intval($activity_id);
            $activity = $this->AttendanceRegister->Activity->findById($activity_id);
            $this->set('activity', $activity);
            $conditions .= " AND AttendanceRegister.activity_id = {$activity_id}";
        }

        if ($teacher_id != null) {
            $teacher_id = intval($teacher_id);
            $teacher = $this->AttendanceRegister->Teacher->findById($teacher_id);
            $this->set('teacher', $teacher);
            $conditions .= " AND (AttendanceRegister.teacher_id = {$teacher_id} OR AttendanceRegister.teacher_2_id = {$teacher_id})";
        }

        if ($date != null) {
            $date = $this->_parse_date($date, "-");
            $this->set('date', date_create($date));
            $conditions .= " AND DATE_FORMAT(AttendanceRegister.initial_hour, '%Y-%m-%d') = '{$date}'";
        }

        if ($id != null) {
            $id = intval($id);
            $conditions .= " AND AttendanceRegister.id = {$id}";
        }
        return $conditions;
    }

    /**
     * Adds an attendance register using event information
     *
     * @since 2012-09-28
     */
    function add_by_event($event_id = null) {
        $event_id = $event_id === null ? null : intval($event_id);
        $this->AttendanceRegister->Event->id = $event_id;
        $db = $this->AttendanceRegister->getDataSource();
        $event = $this->AttendanceRegister->Event->find('first', array(
            'conditions' => array(
                'Event.id' => $event_id,
                "Event.classroom_id IN (SELECT classroom_id FROM classrooms_institutions ClassroomInstitution WHERE ClassroomInstitution.institution_id = {$db->value(Environment::institution('id'))})"
            )
        ));

        if (!$event) {
            $this->Session->setFlash('No se puede crear un registro de impartición sin un evento válido.');
            $this->redirect($this->referer());
        }

        // Set fixed values
        $subject = $this->AttendanceRegister->Activity->Subject->findById($event['Activity']['subject_id']);
        $this->set(array(
            'activity' => $event['Activity']['name'],
            'subject' => $subject['Subject']['name'],
            )
        );

        if ($this->data) {
            if (empty($this->data['AttendanceRegister'])) {
                $response = $this->Api->call(
                    'POST',
                    '/api/institutions/'.Environment::institution('id').'/attendance_registers',
                    array('Event' => array('id' => $event_id))
                );
                switch ($response['status']) {
                    case 'fail':
                        $this->Session->setFlash('No se pudo crear el registro de impartición. Por favor, revisa que has introducido todos los datos correctamente.');
                        $this->redirect($this->referer());
                        break;
                    case 'error':
                        $this->Session->setFlash($response['message']);
                        $this->redirect($this->referer());
                        break;
                    case 'success':
                        $this->redirect(array('action' => 'view', $response['data']['AttendanceRegister']['id']));
                }
            } else {
                // If teacher is missing, assign the one programmed in event
                if (!(isset($this->data['AttendanceRegister']['teacher_id']))) {
                    $this->data['AttendanceRegister']['teacher_id'] = $event["Event"]["teacher_id"];
                }

                // Cleanup list of students
                if (isset($this->data['AttendanceRegister']['students'])) {
                    $selected_students = array_unique(array_keys($this->data['AttendanceRegister']['students']));
                    $this->data['Student'] = array();
                    foreach ($selected_students as $student_id) {
                        $this->data['Student'][] = array(
                                'UserAttendanceRegister' => array(
                                    'user_id' => $student_id,
                                    'user_gone' => 1
                                )
                        );
                    }
                    $this->data['AttendanceRegister']['num_students'] = count($this->data['Student']);
                    unset($this->data['AttendanceRegister']['students']);
                }
                
                // Cleanup secret code
                $this->data['AttendanceRegister']['secret_code'] = null;

                // Cleanup dates
                $this->data['AttendanceRegister']['initial_hour'] = $this->data['AttendanceRegister']['initial_hour']['hour'].":".$this->data['AttendanceRegister']['initial_hour']['minute'];
                $this->data['AttendanceRegister']['final_hour'] = $this->data['AttendanceRegister']['final_hour']['hour'].":".$this->data['AttendanceRegister']['final_hour']['minute'];

                if ($this->AttendanceRegister->save($this->data)) {
                    $this->Session->setFlash('El registro de impartición se ha creado correctamente.');
                    $this->redirect(array('action' => 'add'));
                }
                else {
                    // Recover information in case of error
                    $teacher = $this->AttendanceRegister->Teacher->findById($this->data['AttendanceRegister']['teacher_id']);
                    $teacher2 = $this->AttendanceRegister->Teacher_2->findById($this->data['AttendanceRegister']['teacher_2_id']);
                    $students = array();
                    foreach ($this->data['Student'] as $student_attendance_register) {
                        $students[] = $this->AttendanceRegister->Student->find('first', array(
                            'conditions' => array('Student.id' => $student_attendance_register['UserAttendanceRegister']['user_id']),
                            'recursive' => -1,
                        ));
                    }
                    $this->set(array(
                        'teacher' => sprintf('%s %s', $teacher['Teacher']['first_name'], $teacher['Teacher']['last_name']),
                        'teacher2' => sprintf('%s %s', $teacher2['Teacher_2']['first_name'], $teacher2['Teacher_2']['last_name']),
                        'students' => $students,
                        )
                    );

                    $initial_hour = date('Y-m-d', strtotime($event['Event']['initial_hour']));
                    $final_hour = date('Y-m-d', strtotime($event['Event']['final_hour']));
                    $this->data['AttendanceRegister']['initial_hour'] = sprintf('%s %s', $initial_hour, $this->data['AttendanceRegister']['initial_hour']);
                    $this->data['AttendanceRegister']['final_hour'] = sprintf('%s %s', $final_hour, $this->data['AttendanceRegister']['final_hour']);

                    $this->Session->setFlash('No se pudo crear el registro de impartición. Por favor, revisa que has introducido todos los datos correctamente.');
                }
            }
        } else {
            $students = $this->AttendanceRegister->Event->findRegisteredStudents($event_id);
            $this->set(array(
                'teacher' => sprintf('%s %s', $event['Teacher']['first_name'], $event['Teacher']['last_name']),
                'teacher2' => sprintf('%s %s', $event['Teacher_2']['first_name'], $event['Teacher_2']['last_name']),
                'students' => $students,
                )
            );

            $this->data['AttendanceRegister'] = array(
                'initial_hour' => $event['Event']['initial_hour'],
                'final_hour' => $event['Event']['final_hour'],
                'teacher_id' => $event['Event']['teacher_id'],
                'teacher_2_id' => $event['Event']['teacher_2_id'],
                'activity_id' => $event['Event']['activity_id'],
                'group_id' => $event['Event']['group_id'],
                'num_students' => count($students),
                'event_id' => $event['Event']['id'],
            );
        }
    }

    /**
     * Adds an attendance register with a possibly modified list of students
     *
     * @since 2012-05-18
     */
    function add() {
        if (!empty($this->data)) {
            $this->data = array('AttendanceRegister' => $this->data['AttendanceRegister']); # Sanitize the data
            list($id) = sscanf($this->data['AttendanceRegister']['id'], "%d");
            if ($id != null) {
                $db = $this->AttendanceRegister->getDataSource();
                $ar = $this->AttendanceRegister->find('first', array(
                    'conditions' => array(
                        'AttendanceRegister.id' => $id,
                        "Event.classroom_id IN (SELECT classroom_id FROM classrooms_institutions ClassroomInstitution WHERE ClassroomInstitution.institution_id = {$db->value(Environment::institution('id'))})"
                    )
                ));

                // Recover information in case of error
                $students = array();
                if (!empty($this->data['AttendanceRegister']['students'])) {
                    foreach ($this->data['AttendanceRegister']['students'] as $student_id => $present) {
                        $students[] = $this->AttendanceRegister->Student->find('first', array(
                            'conditions' => array('id' => $student_id),
                            'recursive' => -1,
                        ));
                    }
                }

                $subject = $this->AttendanceRegister->Activity->Subject->findById($ar['Activity']['subject_id']);
                $this->set(array(
                    'subject' => $subject['Subject']['name'],
                    'activity' => $ar['Activity']['name'],
                    'students' => $students,
                    )
                );

                // Pre-process form data
                $this->data['AttendanceRegister']['initial_hour'] = $this->data['AttendanceRegister']['initial_hour']['hour'].":".$this->data['AttendanceRegister']['initial_hour']['minute'];
                $this->data['AttendanceRegister']['final_hour'] = $this->data['AttendanceRegister']['final_hour']['hour'].":".$this->data['AttendanceRegister']['final_hour']['minute'];

                if (!(isset($this->data['AttendanceRegister']['teacher_id']))) {
                    $this->data['AttendanceRegister']['teacher_id'] = $ar["Event"]["teacher_id"];
                }

                if (isset($this->data['AttendanceRegister']['students'])) {
                    $selected_students = array_unique(array_keys($this->data['AttendanceRegister']['students']));
                    $this->data['Student'] = array();
                    foreach ($selected_students as $student_id) {
                        $this->data['Student'][] = array(
                                'UserAttendanceRegister' => array(
                                    'user_id' => $student_id,
                                    'attendance_register_id' => $id,
                                    'user_gone' => 1
                                )
                        );
                    }
                    $this->data['AttendanceRegister']['num_students'] = count($this->data['Student']);
                    unset($this->data['AttendanceRegister']['students']);
                }
                
                // Clean up secret code
                $this->data['AttendanceRegister']['secret_code'] = null;

                if ($this->AttendanceRegister->saveAll($this->data)) {
                    $this->Session->setFlash('El registro de impartición se ha creado correctamente.');
                    $this->redirect(array('action' => 'add'));
                }
                else {
                    $this->Session->setFlash('No se pudo crear el registro de impartición. Por favor, revisa que has introducido todos los datos correctamente.');
                }
            } else {
                $this->Session->setFlash('No se puede crear un registro de impartición sin especificar su código de barras.');
            }
        }
    }
    
    function finalize($id) {
        $id = $id === null ? null : intval($id);
        $response = $this->Api->call(
            'POST',
            '/api/institutions/'.Environment::institution('id').'/attendance_registers/'.$id,
            array('AttendanceRegister' => array('status' => 'closed'))
        );
        switch ($response['status']) {
            case 'fail':
                $this->Session->setFlash('No se pudo crear el registro de impartición. Por favor, revisa que has introducido todos los datos correctamente.');
                $this->redirect($this->referer());
                break;
            case 'error':
                $this->Session->setFlash($response['message']);
                $this->redirect($this->referer());
                break;
            case 'success':
                $this->Session->setFlash('El registro de impartición se ha creado correctamente.');
                $this->redirect(array('action' => 'view', $id));
        }
    }
    
    function clean_up_day() {
        set_time_limit(0);

        $log_file = dirname(dirname(__FILE__)) . '/tmp/clean_up_day_' . date('l');
        file_put_contents($log_file, 'init: ' . date('c'));

        if (intval(date('H') < 20)) {
            $today_filter = '"' . date('Y-m-d', strtotime('yesterday')) . '" AND "' . date('Y-m-d') . '"';
        } else {
            $today_filter = '"' . date('Y-m-d') . '" AND "' . date('Y-m-d H:i:s') . '"';
        }

        $db = $this->AttendanceRegister->getDataSource();
        
        $events = $this->AttendanceRegister->query("
            SELECT Event.*, AttendanceRegister.*, Activity.*, Teacher.*, Teacher_2.*, count(UserAttendanceRegister.user_id) as total_students
            FROM events Event
            LEFT JOIN attendance_registers AttendanceRegister ON AttendanceRegister.event_id = Event.id
            LEFT JOIN users_attendance_register UserAttendanceRegister ON UserAttendanceRegister.attendance_register_id = AttendanceRegister.id
                AND UserAttendanceRegister.user_gone
            LEFT JOIN activities Activity ON Activity.id = Event.activity_id
            LEFT JOIN users Teacher ON Teacher.id = Event.teacher_id
            LEFT JOIN users Teacher_2 ON Teacher_2.id = Event.teacher_2_id
            WHERE Event.final_hour BETWEEN $today_filter
                AND (
                    AttendanceRegister.id IS NULL
                    OR AttendanceRegister.duration = 0
                    OR AttendanceRegister.secret_code IS NOT NULL
                )
            GROUP BY Event.id
        ");
        
        foreach ($events as $event) {
            if ($event['AttendanceRegister']['secret_code'] && $event[0]['total_students']) {
                $id = $event['AttendanceRegister']['id'];
                $students = $this->AttendanceRegister->getStudentsWithUserGone($id);
                $attendance_register = array('AttendanceRegister' => $event['AttendanceRegister']);
                
                if ($this->AttendanceRegister->close($attendance_register, $students)) {
                    $this->AttendanceRegister->unbindModel(array('hasAndBelongsToMany' => array('Student')));
                    $this->AttendanceRegister->bindModel(array('belongsTo' => array(
                        'Classroom' => array(
                            'foreignKey' => false,
                            'conditions' => array('Classroom.id = Event.classroom_id')
                        )
                    )));
                    $attendance_register = $this->AttendanceRegister->read(null, $id);
                    
                    if ($attendance_register) {
                        $attendance_register['Students'] = $this->AttendanceRegister->getStudentsForApi(
                            $id,
                            $attendance_register['AttendanceRegister']['activity_id'],
                            $attendance_register['AttendanceRegister']['group_id']
                        );
                        $this->AttendanceRegister->notifyAttendanceRegisterClosed($attendance_register, $this);
                        file_put_contents($log_file, "\nAttendance register with id {$event['AttendanceRegister']['id']} closed", FILE_APPEND);
                    }
                }
            } elseif ($event['AttendanceRegister']['secret_code']) {
                $this->AttendanceRegister->query("
                    UPDATE attendance_registers
                    SET duration = 0, secret_code = NULL
                    WHERE id = {$event['AttendanceRegister']['id']}
                ");
                file_put_contents($log_file, "\nAttendance register with id {$event['AttendanceRegister']['id']} closed with duration 0", FILE_APPEND);
            } elseif (empty($event['AttendanceRegister']['id'])) {
                
                $this->Email->reset();
                $this->Email->from = 'Academic <noreply@ulpgc.es>';
                $this->Email->to = $event['Teacher']['username'];
                if (Configure::read('app.audit_email')) {
                    $this->Email->bcc = array(Configure::read('app.audit_email'));
                }
                $this->Email->subject = "Evento no registrado";
                $this->Email->sendAs = 'both';
                $this->Email->template = Configure::read('app.email.attendance_register_forgotten')
                    ? Configure::read('app.email.attendance_register_forgotten')
                    : 'attendance_register_forgotten';
                $this->set('teacher', $event['Teacher']);
                $this->set('event', $event);
                if ($event['Teacher']['notify_all']) {
                    $this->Email->send();
                    file_put_contents($log_file, "\nNotify event with id {$event['Event']['id']} not registed to {$event['Teacher']['username']}", FILE_APPEND);
                }
                if (!empty($event['Teacher_2']['username'])) {
                    $this->Email->to = $event['Teacher_2']['username'];
                    $this->set('teacher', $event['Teacher_2']);
                    if ($event['Teacher_2']['notify_all']) {
                        $this->Email->send();
                        file_put_contents($log_file, "\nNotify event with id {$event['Event']['id']} not registed to {$event['Teacher_2']['username']}", FILE_APPEND);
                    }
                }
            }
        }
        
        file_put_contents($log_file, "\nend: " . date('c'), FILE_APPEND);
        exit;
    }

    /**
     * Shows an attendance register
     */
    function view($id){
        $id = $id === null ? null : intval($id);
        $response = $this->Api->call(
            'GET',
            '/api/institutions/'.Environment::institution('id').'/attendance_registers/'.$id
        );
        switch ($response['status']) {
            case 'fail':
                $this->Session->setFlash('No se pudo abrir el registro de impartición.');
                $this->redirect($this->referer());
                break;
            case 'error':
                $this->Session->setFlash($response['message']);
                $this->redirect($this->referer());
                break;
            case 'success':
                $this->set('ar', $response['data']);
                $this->set('subject', $this->AttendanceRegister->Activity->Subject->findById($response['data']['Activity']['subject_id']));
        }
    }

    /**
     * Returns details of an attendance register via AJAX call
     *
     * @version 2012-05-30
     */
    function get_register_info($attendance_register_id = null){
        list($id) = sscanf($attendance_register_id, "%d");
        $db = $this->AttendanceRegister->getDataSource();
        $ar = $this->AttendanceRegister->find('first', array(
            'conditions' => array(
                'AttendanceRegister.id' => $id,
                "Event.classroom_id IN (SELECT classroom_id FROM classrooms_institutions ClassroomInstitution WHERE ClassroomInstitution.institution_id = {$db->value(Environment::institution('id'))})"
            )
        ));
        $ar["AttendanceRegister"]["teacher_id"] = $ar["Event"]["teacher_id"];
        $ar["AttendanceRegister"]["teacher_2_id"] = $ar["Event"]["teacher_2_id"];

        $teacher = $this->AttendanceRegister->Teacher->findById($ar["Event"]["teacher_id"]);
        $teacher2 = $this->AttendanceRegister->Teacher_2->findById($ar["Event"]["teacher_2_id"]);
        $ar["Teacher"] = $teacher["Teacher"];
        $ar["Teacher_2"] = $teacher2["Teacher_2"];

        if ($ar != null) {
            $students = array();
            if (empty($ar['Student'])) {
                // Load student list from original registration
                $students = $this->AttendanceRegister->Student->query("
                    SELECT Student.*
                    FROM users Student
                    INNER JOIN registrations Registration ON Registration.student_id = Student.id
                    WHERE Registration.group_id = {$ar['AttendanceRegister']['group_id']}
                    AND Registration.activity_id = {$ar['AttendanceRegister']['activity_id']}
                    ORDER BY Student.last_name, Student.first_name
                ");
            } else {
                // Load student list from preloaded attendance registers
                $students = $this->AttendanceRegister->Student->query("SELECT Student.*
                    FROM users Student
                    INNER JOIN users_attendance_register UAR ON UAR.user_id = Student.id
                    WHERE UAR.attendance_register_id = {$ar['AttendanceRegister']['id']}
                    ORDER BY Student.last_name, Student.first_name
                ");
            }

            $subject = $this->AttendanceRegister->Activity->Subject->findById($ar['Activity']['subject_id']);
            $this->set('ar', $ar);
            $this->set('students', $students);
            $this->set('subject', $subject);
        }
    }

    /**
     * Returns a list of attendance registers filtered by barcode
     * @version 2012-09-27
     */
    function find_by_barcode() {
        App::import('Core', 'Sanitize');
        $q = '%'.Sanitize::escape($this->params['url']['q']).'%';
        $attendanceRegisters = $this->AttendanceRegister->find('all', array(
            'fields' => array('AttendanceRegister.*'),
            'joins' => array(
                array(
                    'table' => 'events',
                    'alias' => 'Event',
                    'type' => 'INNER',
                    'conditions' => 'Event.id = AttendanceRegister.event_id'
                ),
                array(
                    'table' => 'classrooms_institutions',
                    'alias' => 'ClassroomInstitution',
                    'type' => 'INNER',
                    'conditions' => array(
                        'ClassroomInstitution.classroom_id = Event.classroom_id',
                        'ClassroomInstitution.institution_id' => Environment::institution('id')
                    )
                )
            ),
            'conditions' => array('AttendanceRegister.id LIKE' => $q),
            'recursive' => -1,
        ));
        $this->set('attendanceRegisters', $attendanceRegisters);
    }

    /**
     * Edits attendance register
     */
    function edit($id = null){
        $id = $id === null ? null : intval($id);

        $db = $this->AttendanceRegister->getDataSource();
        $attendance_register = $this->AttendanceRegister->find('first', array(
            'conditions' => array(
                'AttendanceRegister.id' => $id,
                "Event.classroom_id IN (SELECT classroom_id FROM classrooms_institutions ClassroomInstitution WHERE ClassroomInstitution.institution_id = {$db->value(Environment::institution('id'))})"
            )
        ));

        if (!$attendance_register) {
            $this->Session->setFlash('No se ha podido encontrar el registro de impartición');
            $this->redirect($this->referer());
        }

        if (!empty($this->data)){
            $this->data = array('AttendanceRegister' => $this->data['AttendanceRegister']); # Sanitize the data
            $this->data['AttendanceRegister']['id'] = $id;
            $this->data['AttendanceRegister']['initial_hour'] = $this->data['AttendanceRegister']['initial_hour']['hour'].":".$this->data['AttendanceRegister']['initial_hour']['minute'];
            $this->data['AttendanceRegister']['final_hour'] = $this->data['AttendanceRegister']['final_hour']['hour'].":".$this->data['AttendanceRegister']['final_hour']['minute'];

            if (isset($this->data['AttendanceRegister']['students'])){
                $selected_students = array_unique(array_keys($this->data['AttendanceRegister']['students']));
                $this->data['Student'] = array();
                foreach ($selected_students as $student_id) {
                    $this->data['Student'][] = array(
                            'UserAttendanceRegister' => array(
                                'user_id' => $student_id,
                                'attendance_register_id' => $id,
                                'user_gone' => 1
                            )
                    );
                }
                $this->data['AttendanceRegister']['num_students'] = count($this->data['Student']);
                unset($this->data['AttendanceRegister']['students']);
            }
            
            // Clean up secret code
            $this->data['AttendanceRegister']['secret_code'] = null;

            if ($this->AttendanceRegister->saveAll($this->data)){
                $this->Session->setFlash('El registro de impartición se ha creado correctamente.');
                $this->redirect(array('action' => 'view', $id));
            }
            else {
                $this->Session->setFlash('No se ha podido crear el registro de impartición. Por favor, revise que ha introducido todos los datos correctamente.');
                $this->redirect(array('action' => 'view', $id));
            }

        } else {
            $students = $this->AttendanceRegister->Student->query("SELECT Student.*
                FROM users Student
                INNER JOIN users_attendance_register UAR ON UAR.user_id = Student.id
                WHERE UAR.attendance_register_id = {$id}
                ORDER BY Student.last_name, Student.first_name
            ");
            $this->data = $attendance_register;
            $this->set('subject', $this->AttendanceRegister->Activity->Subject->findById($this->data['Activity']['subject_id']));
            $this->set('ar', $this->data);
            $this->set('students', $students);
        }
    }

    /**
     * Prints PDF with a list of students registered in the activity
     *
     * @param integer $event_id ID of an event
     * @return void
     * @version 2012-05-30
     */
    function print_attendance_file($event_id = null){
        $this->layout = 'print';
        if ($event_id == null) {
            return;
        }
        $event_id = intval($event_id);

        $db = $this->AttendanceRegister->getDataSource();
        $event = $this->AttendanceRegister->Event->find('first', array(
            'conditions' => array(
                'Event.id' => $event_id,
                "Event.classroom_id IN (SELECT classroom_id FROM classrooms_institutions ClassroomInstitution WHERE ClassroomInstitution.institution_id = {$db->value(Environment::institution('id'))})"
            )
        ));

        if (!$event) {
            $this->Session->setFlash('No se ha podido crear el registro de asistencia. Por favor, revise que el evento todavía existe');
            $this->redirect(array('controller' => 'users', 'action' => 'home'));
        } else {
            $event = $this->AttendanceRegister->createFromEvent($event);

            $this->set('event', $event);
            $this->set('students', $event['AttendanceRegister']['Student']);
            $this->set('subject', $this->AttendanceRegister->Event->Activity->Subject->findById($event['Activity']['subject_id']));
        }
    }

    /**
     * Shows a list of activities given by a teacher
     */
    function view_my_registers($course_id = null, $subject_id = null){
        $course_id = $course_id === null ? null : intval($course_id);
        $user_id = $this->Auth->user('id');
        $date = date("Y-m-d");

        $course = $this->AttendanceRegister->Activity->Subject->Course->find(
            'first',
            array(
                'conditions' => array('Course.id' => $course_id, 'Course.institution_id' => Environment::institution('id')),
                'recursive' => -1
            )
        );
        if (!$course) {
            $this->Session->setFlash('El curso no existe.');
            $this->redirect($this->referer());
        }
        
        $course_id = $course['Course']['id'];
        $this->set('course', $course);

        $andWhereSubjectId = '';
        if (!empty($subject_id)) {
            $subject = $this->AttendanceRegister->Activity->Subject->find(
                'first',
                array(
                    'conditions' => array('Subject.id' => $subject_id),
                    'recursive' => -1
                )
            );
            if (!$subject) {
                $this->Session->setFlash('El curso no existe.');
                $this->redirect($this->referer());    
            }

            $this->set('subject', $subject);
            $andWhereSubjectId = "AND (Subject.id = $subject_id)";
        } else {
            $this->set('subject', false);
        }

        $attendance_registers = $this->AttendanceRegister->query("
            SELECT DISTINCT Activity.*, Subject.*, Event.*, AttendanceRegister.*, IFNULL(uar.num_students, 0) AS num_students
            FROM events Event
            INNER JOIN activities Activity ON Activity.id = Event.activity_id
            INNER JOIN subjects Subject ON Subject.id = Activity.subject_id
            LEFT JOIN attendance_registers AttendanceRegister ON AttendanceRegister.event_id = Event.id
            LEFT JOIN (SELECT attendance_register_id, count(*) AS num_students FROM users_attendance_register WHERE user_gone
            GROUP BY attendance_register_id) uar ON uar.attendance_register_id = AttendanceRegister.id
            WHERE (Subject.course_id = {$course_id}) $andWhereSubjectId
            AND (Event.teacher_id = {$user_id} OR Event.teacher_2_id = {$user_id} OR Subject.coordinator_id = {$user_id} OR Subject.practice_responsible_id = {$user_id})
            AND DATE_FORMAT(Event.initial_hour, '%Y-%m-%d') <= '{$date}'
            ORDER BY Event.initial_hour
            ");
        $this->set('course_id', $course_id);
        $this->set('attendance_registers', $attendance_registers);
    }

    /**
     * Edits student attendance to a given activity
     * after the activity took place
     *
     * @param integer $event_id ID of an event
     * @return void
     * @version 2012-05-30
     */
    function edit_student_attendance($event_id = null) {
        $event_id = $event_id === null ? null : intval($event_id);

        $event = $this->AttendanceRegister->Event->findById($event_id);
        
        if (!$event) {
            $this->Session->setFlash('No se ha podido acceder al evento.');
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        $subject = $this->AttendanceRegister->Activity->Subject->findById($event["Activity"]["subject_id"]);

        if (!$subject) {
            $this->Session->setFlash('No se ha podido acceder a la asignatura.');
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        // Preload student attendance by saving an attendance register
        if (!empty($this->data)) {
            $this->data = array('AttendanceRegister' => $this->data['AttendanceRegister']); # Sanitize the data
            list($id) = sscanf($this->data['AttendanceRegister']['id'], "%d");

            $db = $this->AttendanceRegister->getDataSource();
            $ar = $this->AttendanceRegister->find('first', array(
                'conditions' => array(
                    'AttendanceRegister.id' => $id,
                    "Event.classroom_id IN (SELECT classroom_id FROM classrooms_institutions ClassroomInstitution WHERE ClassroomInstitution.institution_id = {$db->value(Environment::institution('id'))})"
                )
            ));
            if ($this->Auth->user('type') !== "Profesor" || ($ar && floatval($ar['AttendanceRegister']['duration']))) {
                if (isset($this->data['AttendanceRegister']['students'])) {
                    $selected_students = array_unique(array_keys($this->data['AttendanceRegister']['students']));
                    $this->data['Student'] = array();
                    foreach ($selected_students as $student_id) {
                        $this->data['Student'][] = array(
                                'UserAttendanceRegister' => array(
                                    'user_id' => $student_id,
                                    'attendance_register_id' => $id,
                                    'user_gone' => 1
                                )
                        );
                    }
                    $this->data['AttendanceRegister']['num_students'] = count($this->data['Student']);
                    unset($this->data['AttendanceRegister']['students']);
                }

                if ($this->AttendanceRegister->saveAll($this->data)) {
                    $this->Session->setFlash('El registro de asistencia se ha creado correctamente.');
                    $this->redirect(array('action' => 'view_my_registers', $subject['Subject']['course_id']));
                } else {
                    $this->Session->setFlash('No se ha podido crear el registro de asistencia. Por favor, revise que ha introducido todos los datos correctamente.');
                    $this->redirect(array('action' => 'index'));
                }
            } else {
                $this->Session->setFlash('No es posible editar el registro de asistencia hasta que el evento haya sido marcado como impartido.');
                $this->redirect(array('action' => 'view_my_registers', $subject['Subject']['course_id']));
            }
        } else {
            /**
             * Block edition until attendance sheet has been printed or if is a teahcer until attendance sheet has been registered
             */
            $ar = $this->AttendanceRegister->findByEventId($event_id);
            if (!$ar || ($this->Auth->user('type') === "Profesor" && !floatval($ar['AttendanceRegister']['duration']))) {
                $this->set('students', false);
                $this->set('subject', $subject);
                $this->set('event', $event);
            } else {
                if (!isset($ar['AttendanceRegister']['id'])) {
                    $this->data['AttendanceRegister'] = array('id' => null,
                        'event_id' => $event['Event']['id'],
                        'initial_hour' => $event['Event']['initial_hour'],
                        'final_hour' => $event['Event']['final_hour'],
                        'activity_id' => $event['Event']['activity_id'],
                        'group_id' => $event['Event']['group_id'],
                        'teacher_id' => $event['Event']['teacher_id'],
                        'teacher_2_id' => $event['Event']['teacher_2_id']);

                    $this->AttendanceRegister->save($this->data);
                    $ar = $this->AttendanceRegister->findByEventId($event_id);
                }

                if ((isset($ar['Student'])) && (count($ar['Student']))) {
                    $students = $this->AttendanceRegister->query("
                        SELECT Student.*
                        FROM users Student
                        INNER JOIN users_attendance_register UAR ON UAR.user_id = Student.id
                        WHERE UAR.attendance_register_id = {$ar['AttendanceRegister']['id']}
                        ORDER BY Student.last_name, Student.first_name
                        ");
                } else {
                    $students = $this->AttendanceRegister->query("
                        SELECT Student.*
                        FROM users Student
                        INNER JOIN registrations ON registrations.student_id = Student.id
                        WHERE registrations.activity_id = {$event['Event']['activity_id']}
                        AND registrations.group_id = {$event['Event']['group_id']}
                        ORDER BY Student.last_name, Student.first_name
                        ");
                }

                $students_raw = array();
                foreach ($students as $student) {
                    array_push($students_raw, $student['Student']);
                }

                $this->set('students', $students_raw);
                $this->set('subject', $this->AttendanceRegister->Activity->Subject->findById($ar["Activity"]["subject_id"]));
                $this->set('ar', $ar);
            }
        }
    }

    /**
     * Deletes an attendance register
     *
     * @param integer $id ID of an attendance register
     * @return void
     * @since 2012-05-17
     */
    function delete($id) {
        $id = $id === null ? null : intval($id);

        if (!$id) {
            $this->Session->setFlash('El registro de asistencia que intentas eliminar no existe.');
            $this->redirect(array('action' => 'index'));
        }
        
        $attendance_register = $this->AttendanceRegister->find('all', array(
            'fields' => array('AttendanceRegister.*'),
            'joins' => array(
                array(
                    'table' => 'events',
                    'alias' => 'Event',
                    'type' => 'INNER',
                    'conditions' => 'Event.id = AttendanceRegister.event_id'
                ),
                array(
                    'table' => 'classrooms_institutions',
                    'alias' => 'ClassroomInstitution',
                    'type' => 'INNER',
                    'conditions' => array(
                        'ClassroomInstitution.classroom_id = Event.classroom_id',
                        'ClassroomInstitution.institution_id' => Environment::institution('id')
                    )
                )
            ),
            'conditions' => array('AttendanceRegister.id' => $id),
            'recursive' => -1,
        ));

        if (!$attendance_register) {
            $this->Session->setFlash('El registro de asistencia que intentas eliminar no existe.');
            $this->redirect(array('action' => 'index'));
        }

        $this->AttendanceRegister->query("DELETE FROM users_attendance_register WHERE attendance_register_id = $id");
        $updated = $this->AttendanceRegister->updateAll(
            array(
                'AttendanceRegister.duration' => 0.0,
                'AttendanceRegister.num_students' => 0,
            ),
            array('AttendanceRegister.id' => $id)
        );
        if ($updated) {
            $this->Session->setFlash('El registro de asistencia se eliminó correctamente.');
            $this->redirect(array('action' => 'index'));
        }

        $this->Session->setFlash('El registro de asistencia no se pudo eliminar. Si el error continúa contacta con el administrador.');
        $this->redirect($this->referer());
    }

    function _authorize(){
        parent::_authorize();
        
        $no_institution_actions = array("clean_up_day");
        $public_actions = array("clean_up_day");
        $private_actions = array("index", "add", "edit", "get_register_info");

        if (array_search($this->params['action'], $no_institution_actions) === false && ! Environment::institution('id')) {
            return false;
        }
        
        if (array_search($this->params['action'], $public_actions) !== false) {
            return true;
        }

        if (($this->Auth->user('type') != "Profesor") && ($this->Auth->user('type') != "Administrador") && ($this->Auth->user('type') != "Administrativo") && ($this->Auth->user('type') != "Becario"))
            return false;

        if (($this->Auth->user('type') != "Administrador") && ($this->Auth->user('type') != "Becario") && ($this->Auth->user('type') != "Administrativo") && (array_search($this->params['action'], $private_actions) !== false))
            return false;

        $this->set('section', 'attendance_registers');
        return true;
    }
}
