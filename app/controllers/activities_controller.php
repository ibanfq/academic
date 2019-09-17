<?php
class ActivitiesController extends AppController {
    var $name = 'Activities';
    var $paginate = array('limit' => 10, 'order' => array('activity.initial_date' => 'asc'));
    var $helpers = array('Ajax', 'ModelHelper');
    var $fields_fillable = array('Activity');
    var $fields_guarded = array('Activity' => ['id', 'course_id', 'created', 'modified']);

    function add($subject_id = null){
        $subject_id = $subject_id === null ? null : intval($subject_id);

        if (is_null($subject_id) && !empty($this->data['Activity']['subject_id'])) {
            $subject_id = intval($this->data['Activity']['subject_id']);
        }

        $subject = $this->Activity->Subject->find('first', array(
            'conditions' => array(
                'Subject.id' => $subject_id,
                'Course.institution_id' => Environment::institution('id')
            )
        ));

        if (!$subject) {
            $this->Session->setFlash('No se ha podido acceder a la asignatura.');
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }
        
        if (!empty($this->data)) {
            $this->data = $this->Form->filter($this->data);
            $this->data['Activity']['subject_id'] = $subject_id;

            if ($this->Activity->save($this->data)){
                $this->Session->setFlash('La actividad se ha guardado correctamente');
                $this->redirect(array('controller' => 'subjects', 'action' => 'view', intval($this->data['Activity']['subject_id'])));
            } else{
                $subject = $this->Activity->Subject->find('first', array('conditions' => array('Subject.id' => $this->data['Activity']['subject_id'])));
            }
        }

        $degree = $this->Activity->Subject->Course->Degree->find('first', array(
            'conditions' => array(
                'Degree.id' => $subject['Course']['degree_id'],
            ),
            'recursive' => -1
        ));

        $this->set('subject', $subject);
        $this->set('subject_id', $subject_id);
        $this->set('degree', $degree);
    }

    function view($id = null) {
        $id = $id === null ? null : intval($id);

        if (! $id) {
            $this->Session->setFlash('No se ha podido acceder a la actividad.');
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }
        
        $activity = $this->Activity->find('first', array(
            'conditions' => array('Activity.id' => $id),
            'recursive' => -1
        ));

        if (!$activity) {
            $this->Session->setFlash('No se ha podido acceder a la actividad.');
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        $subject = $this->Activity->Subject->find('first', array(
            'conditions' => array(
                'Subject.id' => $activity['Activity']['subject_id'],
                'Course.institution_id' => Environment::institution('id')
            )
        ));

        if (!$subject) {
            $this->Session->setFlash('No se ha podido acceder a la asignatura.');
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        $degree = $this->Activity->Subject->Course->Degree->find('first', array(
            'conditions' => array(
                'Degree.id' => $subject['Course']['degree_id'],
            ),
            'recursive' => -1
        ));

        $isEvaluation = $activity['Activity']['type'] === 'Evaluación';
        $this->set('activity', $activity);
        $this->set('isEvaluation', $isEvaluation);
        $this->set('subject', $subject);
        $this->set('degree', $degree);
        $groups = $this->Activity->query("SELECT `Group`.*, count(DISTINCT Registration.id) AS students FROM groups `Group` INNER JOIN registrations Registration ON Registration.group_id = `Group`.id WHERE Registration.activity_id = {$id} GROUP BY `Group`.id ORDER BY `Group`.name");
        $this->set('groups', set::combine($groups, '{n}.Group.id', '{n}'));
        $this->set('registrations', $this->Activity->query("SELECT Registration.*, Student.* FROM subjects_users INNER JOIN users Student ON subjects_users.user_id = Student.id LEFT JOIN registrations Registration ON Registration.student_id = subjects_users.user_id AND Registration.activity_id = {$id} WHERE subjects_users.subject_id = {$subject['Subject']['id']} ORDER BY Student.last_name, Student.first_name"));
    }

    function edit($id = null) {
        $id = $id === null ? null : intval($id);
        $administrator = $this->Auth->user('type') === "Administrador";
        $profesor = $this->Auth->user('type') === "Profesor";
        $canChangeGroup = $administrator || $profesor && Configure::read('app.activity.teacher_can_change_groups');
        
        if (! $id) {
            $this->Session->setFlash('No se ha podido acceder a la actividad.');
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }
        
        $activity = $this->Activity->find('first', array(
            'conditions' => array('Activity.id' => $id),
            'recursive' => -1
        ));

        if (!$activity) {
            $this->Session->setFlash('No se ha podido acceder a la actividad.');
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        $subject = $this->Activity->Subject->find('first', array(
            'conditions' => array(
                'Subject.id' => $activity['Activity']['subject_id'],
                'Course.institution_id' => Environment::institution('id')
            )
        ));

        if (!$subject) {
            $this->Session->setFlash('No se ha podido acceder a la asignatura.');
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        $this->Activity->set($activity);
        
        if (empty($this->data)) {
            $this->data = $activity;
        } else {
            $registrations = $this->Activity->query("SELECT Registration.* FROM registrations Registration WHERE Registration.activity_id = {$id}");
            $registrations_deleted = array();
            foreach ($registrations as $i => &$registration) {
                $student_id = $registration['Registration']['student_id'];
                $group_id = isset($this->data['Students'][$student_id]['group_id'])? $this->data['Students'][$student_id]['group_id'] : false;
                if ($group_id) {
                    if ($canChangeGroup || $group_id == -1) {
                        $registration['Registration']['group_id'] = $group_id;
                    }
                } else {
                    if ($canChangeGroup || $registration['Registration']['group_id'] == -1) {
                        array_push($registrations_deleted, $registration['Registration']['id']);
                        unset($registrations[$i]);
                    }
                }
                unset($this->data['Students'][$student_id]);
            }
            if (isset($this->data['Students'])) {
                foreach ($this->data['Students'] as $student_id => $student) {
                    if ($student['group_id'] && ($canChangeGroup || $student['group_id'] == -1)) {
                        array_push($registrations, array('group_id' => $student['group_id'], 'activity_id' => $id, 'student_id' => $student_id));
                    }
                }
            }
            
            $this->data = $this->Form->filter($this->data);
            $this->data['Activity']['id'] = $activity['Activity']['id'];
            $this->data['Activity']['modified'] = null;

            $this->Activity->Registration->unbindModel(array('hasOne' => array('User', 'Activity', 'Group')), false);

            if ($this->Activity->save($this->data) && (empty($registrations) || $this->Activity->Registration->saveAll($registrations)) && (empty($registrations_deleted) || $this->Activity->Registration->deleteAll(array('Registration.id' => $registrations_deleted)))) {
                $this->loadModel('AttendanceRegister');
                $attendanceRegisters = $this->AttendanceRegister->find("all", array(
                    'fields' => array('AttendanceRegister.*'),
                    'conditions' => "AttendanceRegister.activity_id = {$id} and AttendanceRegister.initial_hour > now()",
                    'recursive' => 0
                ));
                foreach ($attendanceRegisters as $attendanceRegister) {
                    $this->AttendanceRegister->data = $attendanceRegister;
                    $this->AttendanceRegister->id = $attendanceRegister['AttendanceRegister']['id'];
                    $this->AttendanceRegister->updateStudents();
                }
                
                $this->Session->setFlash('La actividad se ha modificado correctamente.');
                $this->redirect(array('action' => 'view', $id));
            }
        }

        $degree = $this->Activity->Subject->Course->Degree->find('first', array(
            'conditions' => array(
                'Degree.id' => $subject['Course']['degree_id'],
            ),
            'recursive' => -1
        ));

        $isEvaluation = $activity['Activity']['type'] === 'Evaluación';
        $subject = $this->Activity->Subject->find('first', array('conditions' => array('Subject.id' => $activity['Activity']['subject_id'])));
        $this->set('activity', $activity);
        $this->set('isEvaluation', $isEvaluation);
        $this->set('subject', $subject);
        $this->set('degree', $degree);
        $groups = $this->Activity->query("SELECT distinct(`Group`.id), `Group`.name FROM events Event INNER JOIN groups `Group` on `Group`.id = Event.group_id WHERE Event.activity_id = {$id}");
        $this->set('groups', array(-1 => $isEvaluation? 'No se puede presentar' : 'Actividad aprobada') + set::combine($groups, '{n}.Group.id', '{n}.Group.name'));
        $this->set('registrations', $this->Activity->query("SELECT Registration.*, Student.* FROM subjects_users INNER JOIN users Student ON subjects_users.user_id = Student.id LEFT JOIN registrations Registration ON Registration.student_id = subjects_users.user_id AND Registration.activity_id = {$id} WHERE subjects_users.subject_id = {$subject['Subject']['id']} ORDER BY Student.last_name, Student.first_name"));

    }

    function get($subject_id = null) {
        $subject_id = $subject_id === null ? null : intval($subject_id);

        $activities = array();

        if ($subject_id) {
            $subject = $this->Activity->Subject->find('first', array(
                'conditions' => array(
                    'Subject.id' => $subject_id,
                    'Course.institution_id' => Environment::institution('id')
                )
            ));
    
            if ($subject) {
                $query = "SELECT DISTINCT Activity.* FROM activities Activity INNER JOIN subjects Subject ON Subject.id = Activity.subject_id WHERE Activity.subject_id = {$subject_id}";
        
                if ($this->Auth->user('type') != "Administrador") {
                    $query .= " AND (Subject.coordinator_id = {$this->Auth->user('id')} OR Subject.practice_responsible_id = {$this->Auth->user('id')})";
                }
                $activities = $this->Activity->query($query);    
            }
        }

        $this->set('activities', $activities);
    }

    function delete($id = null) {
        $id = $id === null ? null : intval($id);
        
        if (! $id) {
            $this->Session->setFlash('No se ha podido acceder a la actividad.');
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }
        
        $activity = $this->Activity->find('first', array(
            'fields' => array('Activity.*', 'Subject.*'),
            'joins' => array(
                array(
                    'table' => 'subjects',
                    'alias' => 'Subject',
                    'type' => 'INNER',
                    'conditions' => 'Subject.id = Activity.subject_id'
                ),
                array(
                    'table' => 'courses',
                    'alias' => 'Course',
                    'type' => 'INNER',
                    'conditions' => 'Course.id = Subject.course_id'
                )
            ),
            'conditions' => array(
                'Activity.id' => $id,
                'Course.institution_id' => Environment::institution('id'),
            ),
            'recursive' => -1
        ));

        if (!$activity) {
            $this->Session->setFlash('No se ha podido acceder a la actividad.');
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        $subject_id = $activity['Subject']['id'];

        $this->Activity->query("DELETE FROM events WHERE activity_id = {$id}");
        $this->Activity->delete($id);
        $this->Session->setFlash('La actividad ha sido eliminada correctamente');
        $this->redirect(array('controller' => 'subjects', 'action' => 'view', $subject_id));
    }
    
    function find_activities_by_name(){
        App::import('Core', 'Sanitize');
        $q = '%'.Sanitize::escape($this->params['url']['q']).'%';
        $activities = $this->Activity->find(
            'all',
            array(
                'fields' => array('Activity.id', 'Activity.name', 'Subject.code', 'Subject.name', 'Course.name'), 'recursive' => 0,
                'recursive' => -1,
                'joins' => array(
                    array(
                        'table' => 'subjects',
                        'alias' => 'Subject',
                        'type' => 'INNER',
                        'conditions' => 'Subject.id = Activity.subject_id'
                    ),
                    array(
                        'table' => 'courses',
                        'alias' => 'Course',
                        'type' => 'INNER',
                        'conditions' => 'Course.id = Subject.course_id'
                    )
                ),
                'conditions' => array(
                    'Course.institution_id' => Environment::institution('id'),
                    'OR' => array(
                        'Activity.name LIKE' => $q,
                        'Subject.code LIKE' => $q,
                        'Subject.name LIKE' => $q
                    )
                ),
                'order' => array('Course.initial_date' => 'desc', 'Activity.name' => 'asc', 'Subject.name' => 'asc')
            )
        );
        $this->set('activities', $activities);
    }

    function students_stats($activity_id = null) {
        $activity_id = $activity_id === null ? null : intval($activity_id);
        $activity = $this->Activity->find('first', array(
            'conditions' => array('Activity.id' => $activity_id),
            'recursive' => -1
        ));
  
        if (!$activity) {
            $this->Session->setFlash('No se ha podido acceder a la actividad.');
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        $subject = $this->Activity->Subject->find('first', array(
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
                'Subject.id' => $activity['Activity']['subject_id'],
                'Course.institution_id' => Environment::institution('id')
            ),
            'recursive' => -1
        ));

        $registers = $this->Activity->query("SELECT Student.id, Student.first_name, Student.last_name, RegistrationGroup.name, UserAttendanceRegister.user_gone, Event.initial_hour, `Group`.name, Teacher.first_name, Teacher.last_name, Teacher2.first_name, Teacher2.last_name FROM activities Activity INNER JOIN subjects_users SubjectUser ON SubjectUser.subject_id = Activity.subject_id INNER JOIN users Student ON Student.id = SubjectUser.user_id LEFT JOIN registrations Registration on Registration.activity_id = Activity.id AND Registration.student_id = Student.id LEFT JOIN groups RegistrationGroup ON RegistrationGroup.id = Registration.group_id LEFT JOIN attendance_registers AttendanceRegister ON AttendanceRegister.activity_id = Activity.id LEFT JOIN users_attendance_register UserAttendanceRegister ON UserAttendanceRegister.attendance_register_id = AttendanceRegister.id AND UserAttendanceRegister.user_id = Student.id AND UserAttendanceRegister.user_gone LEFT JOIN users Teacher ON Teacher.id = AttendanceRegister.teacher_id LEFT JOIN users Teacher2 ON Teacher2.id = AttendanceRegister.teacher_2_id LEFT JOIN groups `Group` ON `Group`.id = AttendanceRegister.group_id LEFT JOIN events Event ON Event.id = AttendanceRegister.event_id WHERE Activity.id = {$activity['Activity']['id']} GROUP BY Student.id, UserAttendanceRegister.attendance_register_id ORDER BY Student.first_name ASC, Student.last_name ASC, UserAttendanceRegister.user_gone is null, Event.initial_hour DESC");

        $this->set('subject', $subject);
        $this->set('activity', $activity);
        $this->set('registers', $registers);
    }
    
    function delete_student($activity_id = null, $group_id = null, $student_id = null){
        $activity_id = $activity_id === null ? null : intval($activity_id);
        $group_id = $group_id === null ? null : intval($group_id);
        $student_id = $student_id === null ? null : intval($student_id);

        $activity = false;

        if (isset($activity_id, $group_id, $student_id)) {
            $activity = $this->Activity->find('first', array(
                'fields' => array('Activity.*', 'Subject.*'),
                'joins' => array(
                    array(
                        'table' => 'subjects',
                        'alias' => 'Subject',
                        'type' => 'INNER',
                        'conditions' => 'Subject.id = Activity.subject_id'
                    ),
                    array(
                        'table' => 'courses',
                        'alias' => 'Course',
                        'type' => 'INNER',
                        'conditions' => 'Course.id = Subject.course_id'
                    )
                ),
                'conditions' => array(
                    'Activity.id' => $activity_id,
                    'Course.institution_id' => Environment::institution('id'),
                ),
                'recursive' => -1
            ));
        }

        if ($activity) {
            $this->Activity->query("DELETE FROM registrations WHERE group_id = {$group_id} AND activity_id = {$activity_id} AND student_id = {$student_id}");
            $this->set('activity_id', $activity_id);
            $this->set('group_id', $group_id);
            $this->set('student_id', $student_id);
            $this->set('success', true);
        }
        
    }
    
    function send_alert($activity_id=null, $group_id=null){
        $activity_id = $activity_id === null ? null : intval($activity_id);
        $group_id = $group_id === null ? null : intval($group_id);
        
        $activity = false;

        if ($activity_id && $group_id) {
            $activity = $this->Activity->find('first', array(
                'fields' => array('Activity.*', 'Subject.*'),
                'joins' => array(
                    array(
                        'table' => 'subjects',
                        'alias' => 'Subject',
                        'type' => 'INNER',
                        'conditions' => 'Subject.id = Activity.subject_id'
                    ),
                    array(
                        'table' => 'courses',
                        'alias' => 'Course',
                        'type' => 'INNER',
                        'conditions' => 'Course.id = Subject.course_id'
                    )
                ),
                'conditions' => array(
                    'Activity.id' => $activity_id,
                    'Course.institution_id' => Environment::institution('id'),
                ),
                'recursive' => -1
            ));
        }

        if ($activity) {
            $coordinator_id = $activity['Subject']['coordinator_id'];
            $responsible_id = $activity['Subject']['practice_responsible_id'];
            
            $user_can_send_alerts = $this->Activity->Subject->Coordinator->can_send_alerts($this->Auth->user('id'), $activity_id, $group_id);
        }
            
        if ($activity && ($coordinator_id == $this->Auth->user('id') || $responsible_id == $this->Auth->user('id') || $this->Auth->user('type') == "Administrador" || $user_can_send_alerts == true)) {
            App::import('Core', 'Sanitize');
            $message = Sanitize::escape(file_get_contents("php://input"));
            $this->Email->from = 'Academic <noreply@ulpgc.es>';

            $students = $this->Activity->query("SELECT Student.* FROM users Student INNER JOIN registrations Registration ON Registration.student_id = Student.id WHERE Registration.activity_id = {$activity_id} AND Registration.group_id = {$group_id}");

            $emails = array();
            foreach($students as $student):
                if ($student['Student']['username'] != null)
                    array_push($emails, $student['Student']['username']);
            endforeach;

            $this->Email->to = implode(",", array_unique($emails));
            $this->Email->subject = "Alta en Academic";
            $this->set('success', $this->Email->send($message));
        } else {
            $this->Session->setFlash('No tiene permisos para realizar esta acción.');
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }
    }
    
    function _get_subject() {
        if ($this->params['action'] == 'add') {
            if (!empty($this->data) && isset($this->data['Activity'])) {
                $subject_id = $this->data['Activity']['subject_id'];
            } elseif (isset($this->params['pass']['0'])) {
                $subject_id = $this->params['pass']['0'];
            }

            return $this->Activity->Subject->find('first', array(
                'conditions' => array('Subject.id' => $subject_id),
                'recursive' => -1
            ));
        } else {
            if (!empty($this->data) && isset($this->data['Activity'])) {
                $activity_id = $this->data['Activity']['id'];
            } else {
                $activity_id = $this->params['pass']['0'];
            }
            
            return $this->Activity->find('first', array(
                'fields' => 'Subject.*',
                'joins' => array(
                    array(
                        'table' => 'subjects',
                        'alias' => 'Subject',
                        'type' => 'INNER',
                        'conditions' => 'Subject.id = Activity.subject_id'
                    )
                ),
                'conditions' => array(
                    'Activity.id' => $activity_id
                ),
                'recursive' => -1
            ));
        }
    }
    
    function view_students($activity_id = null, $group_id = null){
        $activity_id = $activity_id === null ? null : intval($activity_id);
        $group_id = $group_id === null ? null : intval($group_id);

        $activity = false;

        if (!isset($activity_id, $group_id)) {
            $this->Session->setFlash('No se ha podido acceder a la actividad.');
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        $activity = $this->Activity->find('first', array(
            'fields' => array('Activity.*', 'Subject.*'),
            'joins' => array(
                array(
                    'table' => 'subjects',
                    'alias' => 'Subject',
                    'type' => 'INNER',
                    'conditions' => 'Subject.id = Activity.subject_id'
                ),
                array(
                    'table' => 'courses',
                    'alias' => 'Course',
                    'type' => 'INNER',
                    'conditions' => 'Course.id = Subject.course_id'
                )
            ),
            'conditions' => array(
                'Activity.id' => $activity_id,
                'Course.institution_id' => Environment::institution('id'),
            ),
            'recursive' => -1
        ));

        if (!$activity) {
            $this->Session->setFlash('No se ha podido acceder a la actividad.');
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        $subject = $this->Activity->Subject->find('first', array(
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
                'Subject.id' => $activity['Activity']['subject_id'],
                'Course.institution_id' => Environment::institution('id')
            ),
            'recursive' => -1
        ));

        $this->set('activity', $activity);
        $this->set('subject', $subject);
        $this->set('group', $this->Activity->Subject->Group->findById($group_id));
        $this->set('students', $this->Activity->query("SELECT Student.* FROM users Student INNER JOIN registrations Registration ON Registration.student_id = Student.id WHERE Registration.activity_id = {$activity_id} AND Registration.group_id = {$group_id}"));
        $this->set('user_can_send_alerts', $this->Activity->Subject->Coordinator->can_send_alerts($this->Auth->user('id'), $activity_id, $group_id));
    }

    function _authorize() {
        parent::_authorize();

        if (! Environment::institution('id')) {
            return false;
        }
        
        $administrator_actions = array('add', 'edit', 'delete', 'delete_student');
        
        $this->set('section', 'courses');
        if ((array_search($this->params['action'], $administrator_actions) !== false) && ($this->Auth->user('type') != "Administrador")) {
            $user_id = $this->Auth->user('id');
            $subject = $this->_get_subject();
            
            if ($subject && ($subject['Subject']['coordinator_id'] != $user_id) && ($subject['Subject']['practice_responsible_id'] != $user_id)) {
                 return false;
            }
        }
            
        return true;
    }
}
