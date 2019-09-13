<?php
class ApiCompetenceGoalRequestsController extends AppController {
    var $name = 'CompetenceGoalRequests';
    var $uses = array('CompetenceGoalRequest');
    var $isApi = true;

    function _authorize(){
        if (!parent::_authorize()) {
            return false;
        }

        $administrator_actions = array(
        );
        $teacher_actions = array(
        );
        $student_actions = array(
            'index', 'by_course', 'by_goal', 'delete', 'add'
        );
        $only_student_actions = array(
            'add'
        );

        if ((array_search($this->params['action'], $administrator_actions) !== false) && ($this->Auth->user('type') !== "Administrador")) {
            if ((array_search($this->params['action'], $teacher_actions) !== false) && ($this->Auth->user('type') === "Profesor")) {
                return true;
            }
            return false;
        }

        if ($this->Auth->user('type') === "Estudiante" && array_search($this->params['action'], $student_actions) === false) {
            return false;
        }

        if ($this->Auth->user('type') !== "Estudiante" && array_search($this->params['action'], $only_student_actions) === true) {
            return false;
        }

        return true;
    }
    
    function index()
    {
        if (! Environment::institution('id')) {
            $this->Api->setError('No se ha especificado la institución en la url de la petición.', 400);
            $this->Api->respond($this);
            return;
        }

        $courses = $this->CompetenceGoalRequest->CompetenceGoal->Competence->Course->current();

        if (!$courses) {
            $this->Api->setError('No hay ningún curso activo actualmente.', 404);
            $this->Api->respond($this);
            return;
        }

        $competence_goal_requests = $this->_get_from(Set::extract($courses, '{n}.id'));

        $this->Api->setData($competence_goal_requests);
        $this->Api->respond($this);
    }

    function by_goal($goal_id)
    {
        if (! Environment::institution('id')) {
            $this->Api->setError('No se ha especificado la institución en la url de la petición.', 400);
            $this->Api->respond($this);
            return;
        }

        $goal_id = intval($goal_id);

        $goal = $this->CompetenceGoalRequest->CompetenceGoal->find('first', array(
            'recursive' => -1,
            'joins' => array(
                array(
                    'table' => 'competence',
                    'alias' => 'Competence',
                    'type'  => 'INNER',
                    'conditions' => isset($course_id)
                        ? array(
                            'Competence.id = CompetenceGoal.competence_id',
                            'Competence.course_id' => $course_id
                        )
                        : array(
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
            'conditions' => array('CompetenceGoal.id' => $goal_id)
        ));

        if (!$goal) {
            $this->Api->setError('No se ha encontrado el objetivo.', 404);
            $this->Api->respond($this);
            return;
        }

        $competence_goal_requests = $this->_get_from(null, $goal['CompetenceGoal']['id']);

        $this->Api->setData($competence_goal_requests);
        $this->Api->respond($this);
    }

    function by_course($course_id)
    {
        if (! Environment::institution('id')) {
            $this->Api->setError('No se ha especificado la institución en la url de la petición.', 400);
            $this->Api->respond($this);
            return;
        }

        $course_id = intval($course_id);

        $course = $this->CompetenceGoalRequest->CompetenceGoal->Competence->Course->find('first', array(
            'recursive' => -1,
            'conditions' => array('Course.id' => $course_id, 'Course.institution_id' => Environment::institution('id'))
        ));

        if (!$course) {
            $this->Api->setError('No se ha encontrado el curso.', 404);
            $this->Api->respond($this);
            return;
        }

        $competence_goal_requests = $this->_get_from($course['Course']['id']);

        $this->Api->setData($competence_goal_requests);
        $this->Api->respond($this);
    }

    function add()
    {
        if (! Environment::institution('id')) {
            $this->Api->setError('No se ha especificado la institución en la url de la petición.', 400);
            $this->Api->respond($this);
            return;
        }

        $goal_id = $this->Api->getParameter('CompetenceGoalRequest.goal_id', array('required', 'integer'));
        $teacher_id = $this->Api->getParameter('CompetenceGoalRequest.teacher_id', array('required', 'integer'));

        if ($this->Api->getStatus() !== 'success') {
            $this->Api->respond($this);
            return;
        }

        $db = $this->CompetenceGoalRequest->getDataSource();
        $this->CompetenceGoalRequest->CompetenceGoal->Behaviors->attach('Containable');
        $competence_goal = $this->CompetenceGoalRequest->CompetenceGoal->find('first', array(
            'contain' => array(
                'Competence'
            ),
            'conditions' => array(
                'CompetenceGoal.id' => $goal_id,
                "Competence.course_id IN (SELECT id FROM courses Course WHERE Course.institution_id = {$db->value(Environment::institution('id'))})"
            )
        ));

        if (!$competence_goal) {
            $this->Api->setError('No se ha podido encontrar el objetivo');
            $this->Api->respond($this);
            return;
        }

        $this->loadModel('User');

        $teacher = $this->User->find('first', array(
            'recursive' => -1,
            'conditions' => array(
                'User.id' => $teacher_id,
                'OR' => array(
                    array('User.type' => 'Profesor'),
                    array('User.type' => 'Administrador')
                ),
            )
        ));

        if (!$teacher) {
            $this->Api->setError('No se ha podido encontrar al profesor');
            $this->Api->respond($this);
            return;
        }

        $response = $this->Api->call(
            'GET',
            '/api/institutions/'.Environment::institution('id').'/competence_goals/by_teacher/'.urlencode($teacher_id),
            array('goal_id' => $goal_id, 'contain' => '')
        );

        if (empty($response['data'])) {
            $this->Api->setError('El profesor elegido no puede evaluarte ese objetivo');
            $this->Api->respond($this);
            return;
        }

        $clean_data = array(
            'CompetenceGoalRequest' => array(
                'goal_id' => $goal_id,
                'teacher_id' => $teacher_id,
                'student_id' => $this->Auth->user('id')
            )
        );

        if ($this->CompetenceGoalRequest->save($clean_data)) {
            $this->Email->reset();
            $this->Email->from = 'Academic <noreply@ulpgc.es>';
            $this->Email->to = $teacher['User']['username'];
            $this->Email->subject = "Nueva solicitud de evaluación";
            $this->Email->sendAs = 'both';
            $this->Email->template = Configure::read('app.email.competence_goal_request_added')
                ? Configure::read('app.email.competence_goal_request_added')
                : 'competence_goal_request_added';
            $this->set('competence', array('Competence' => $competence_goal['Competence']));
            $this->set('competence_goal', array('CompetenceGoal' => $competence_goal['CompetenceGoal']));
            $this->set('student', $this->Auth->user());
            $this->Email->send();
            $this->Api->respond($this);
            return;
        }

        if (!empty($this->CompetenceGoalRequest->validationErrors['goal_id'])) {
            $this->Api->setError($this->CompetenceGoalRequest->validationErrors['goal_id']);
        } elseif (!empty($this->CompetenceGoalRequest->validationErrors['teacher_id'])) {
            $this->Api->setError($this->CompetenceGoalRequest->validationErrors['teacher_id']);
        } else {
            $this->Api->setError('No se ha podido crear la solicitud de evaluación');
        }

        $this->Api->respond($this);
    }

    function delete($id)
    {
        if (! Environment::institution('id')) {
            $this->Api->setError('No se ha especificado la institución en la url de la petición.', 400);
            $this->Api->respond($this);
            return;
        }

        $id = $id === null ? null : intval($id);

        if (is_null($id)) {
            $this->Api->setError('Identificador de solicitud de evaluación inválido.', 400);
            $this->Api->respond($this);
            return;
        }

        $user_id = $this->Auth->user('id');

        $this->CompetenceGoalRequest->Behaviors->attach('Containable');
        $competence_goal_request = $this->CompetenceGoalRequest->find('first', array(
            'contain' => array(
                'Teacher',
                'Student',
                'CompetenceGoal.Competence'
            ),
            'conditions' => array(
                (
                    $this->Auth->user('type') === "Estudiante"
                        ? 'CompetenceGoalRequest.student_id'
                        : 'CompetenceGoalRequest.teacher_id'
                ) => $user_id,
                'CompetenceGoalRequest.id' => $id,
                'CompetenceGoalRequest.completed is null',
            )
        ));

        if (!$competence_goal_request) {
            $this->Api->setError('No se ha podido encontrar la solicitud de evaluación.', 404);
            $this->Api->respond($this);
            return;
        }

        if (!empty($competence_goal_request['CompetenceGoalRequest']['canceled']) || !empty($competence_goal_request['CompetenceGoalRequest']['rejected'])) {
            $this->Api->setData($competence_goal_request);
            $this->Api->respond($this);
            return;
        }

        if ($this->Auth->user('type') === "Estudiante") {
            $competence_goal_request['CompetenceGoalRequest']['canceled'] = date('Y-m-d H:i:s');
        } else {
            $competence_goal_request['CompetenceGoalRequest']['rejected'] = date('Y-m-d H:i:s');
        }
        unset($competence_goal_request['CompetenceGoalRequest']['modified']);

        if ($this->CompetenceGoalRequest->save(array('CompetenceGoalRequest' => $competence_goal_request['CompetenceGoalRequest']))) {
            $this->Email->reset();
            $this->Email->from = 'Academic <noreply@ulpgc.es>';
            if ($this->Auth->user('type') === 'Estudiante') {
                $this->Email->to = $competence_goal_request['Teacher']['username'];
                $this->Email->subject = "Petición de evaluación cancelada por el alumno";
                $this->Email->sendAs = 'both';
                $this->Email->template = Configure::read('app.email.competence_goal_request_canceled')
                    ? Configure::read('app.email.competence_goal_request_canceled')
                    : 'competence_goal_request_canceled';
            } else {
                $this->Email->to = $competence_goal_request['Student']['username'];
                $this->Email->subject = "Petición de evaluación rechazada por el profesor";
                $this->Email->sendAs = 'both';
                $this->Email->template = Configure::read('app.email.competence_goal_request_rejected')
                    ? Configure::read('app.email.competence_goal_request_rejected')
                    : 'competence_goal_request_rejected';
            }
            $this->set('competence_goal_request', $competence_goal_request);
            $this->Email->send();

            $this->Api->setData($competence_goal_request);
            $this->Api->respond($this);
            return;
        }

        if ($this->Auth->user('type') === "Estudiante") {
            $this->Api->setError('No se ha podido cancelar la solicitud de evaluación');
        } else {
            $this->Api->setError('No se ha podido rechazar la solicitud de evaluación');
        }
        $this->Api->respond($this);
    }

    function _get_from($courses_id = null, $goal_id = null)
    {
        $user_id = $this->Auth->user('id');

        $competence_goal_request_joins = array(
            array(
                'table' => 'users',
                'alias' => 'Student',
                'type'  => 'INNER',
                'conditions' => array(
                    'Student.id = CompetenceGoalRequest.student_id'
                )
            ),
            array(
                'table' => 'users',
                'alias' => 'Teacher',
                'type'  => 'INNER',
                'conditions' => array(
                    'Teacher.id = CompetenceGoalRequest.teacher_id'
                )
            ),
            array(
                'table' => 'competence_goals',
                'alias' => 'CompetenceGoal',
                'type'  => 'INNER',
                'conditions' => array(
                    'CompetenceGoal.id = CompetenceGoalRequest.goal_id'
                )
            ),
            array(
                'table' => 'competence',
                'alias' => 'Competence',
                'type'  => 'INNER',
                'conditions' => isset($courses_id)
                    ? array(
                        'Competence.id = CompetenceGoal.competence_id',
                        'Competence.course_id' => $courses_id
                    )
                    : array(
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
            ),
            array(
                'table' => 'competence_criteria',
                'alias' => 'CompetenceCriterion',
                'type'  => 'INNER',
                'conditions' => array(
                    'CompetenceCriterion.goal_id = CompetenceGoal.id'
                )
            ),
            array(
                'table' => 'competence_criterion_subjects',
                'alias' => 'CompetenceCriterionSubject',
                'type'  => 'INNER',
                'conditions' => array(
                    'CompetenceCriterionSubject.criterion_id = CompetenceCriterion.id'
                )
            ),
            array(
                'table' => 'subjects_users',
                'alias' => 'SubjectUser',
                'type'  => 'INNER',
                'conditions' => array(
                    'SubjectUser.subject_id = CompetenceCriterionSubject.subject_id',
                    'SubjectUser.user_id = CompetenceGoalRequest.student_id'
                )
            )
        );

        $competence_goal_request_conditions = array(
            'AND' => array(
                'CompetenceGoalRequest.completed is null',
                'CompetenceGoalRequest.canceled is null',
                'CompetenceGoalRequest.rejected is null',
            )
        );

        if (isset($goal_id)) {
            $competence_goal_request_conditions['AND']['CompetenceGoalRequest.goal_id'] = $goal_id;
        }

        if ($this->Auth->user('type') === "Estudiante") {
            $competence_goal_request_conditions['AND']['CompetenceGoalRequest.student_id'] = $user_id;
        } else {
            $competence_goal_request_conditions['AND']['CompetenceGoalRequest.teacher_id'] = $user_id;
        }

        if ($this->Auth->user('type') === "Profesor") {
            $competence_goal_request_joins[] = array(
                'table' => 'subjects',
                'alias' => 'Subject',
                'type'  => 'LEFT',
                'conditions' => array(
                    'Subject.id = CompetenceCriterionSubject.subject_id'
                )
            );

            $competence_goal_request_joins[] = array(
                'table' => 'competence_criterion_teachers',
                'alias' => 'CompetenceCriterionTeacher',
                'type'  => 'LEFT',
                'conditions' => array(
                    'CompetenceCriterionTeacher.criterion_id = CompetenceCriterion.id'
                )
            );

            $competence_goal_request_conditions['AND'][] = array(
                'OR' => array(
                    array('Subject.coordinator_id' => $user_id),
                    array('Subject.practice_responsible_id' => $user_id),
                    array('CompetenceCriterionTeacher.teacher_id' => $user_id)
                )
            );
        }

        return $this->CompetenceGoalRequest->find('all', array(
            'fields' => array('distinct CompetenceGoalRequest.*, Competence.*, CompetenceGoal.*, Student.*, Teacher.*'),
            'recursive' => -1,
            'joins' => $competence_goal_request_joins,
            'conditions' => $competence_goal_request_conditions,
            'order' => array(
                $this->Auth->user('type') === "Estudiante"
                    ? 'Teacher.last_name asc, Teacher.first_name asc, Competence.code asc, CompetenceGoal.code asc'
                    : 'Student.last_name asc, Student.first_name asc, Competence.code asc, CompetenceGoal.code asc'
            )
        ));
    }
}
