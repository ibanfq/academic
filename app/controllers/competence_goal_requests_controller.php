<?php
class CompetenceGoalRequestsController extends AppController {
    var $name = 'CompetenceGoalRequests';
    var $uses = array('CompetenceGoalRequest');

    var $paginate = array(
        'limit' => 10,
        'order' => array('CompetenceGoalRequest.id' => 'asc'),
    );

    function index()
    {
        $course = $this->CompetenceGoalRequest->CompetenceGoal->Competence->Course->current();

        if (!$course) {
            $this->Session->setFlash('No hay ningún curso activo actualmente.');
            $this->redirect(array('controller' => 'users', 'action' => 'home'));
        }

        $competence_goal_requests = $this->_get_from_course_id($course['id']);

        $this->set('competence_goal_requests', $competence_goal_requests);
        $this->set('course', array('Course' => $course));
    }

    function by_course($course_id = null)
    {
        $course_id = $course_id === null ? null : intval($course_id);

        if (is_null($course_id)) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        $course = $this->CompetenceGoalRequest->CompetenceGoal->Competence->Course->find('first', array(
            'recursive' => -1,
            'conditions' => array('Course.id' => $course_id)
        ));

        if (!$course) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        $competence_goal_requests = $this->_get_from_course_id($course_id);

        $this->set('competence_goal_requests', $competence_goal_requests);
        $this->set('course', $course);
    }

    function add()
    {
        if ($this->Auth->user('type') !== "Estudiante") {
            $this->redirect(array('action' => 'index'));
        }

        if (!isset($this->data['CompetenceGoalRequest']['goal_id'], $this->data['CompetenceGoalRequest']['teacher_id'])) {
            $this->redirect(array('action' => 'index'));
        }

        $goal_id = $this->data['CompetenceGoalRequest']['goal_id'];
        $teacher_id = $this->data['CompetenceGoalRequest']['teacher_id'];

        $this->CompetenceGoalRequest->CompetenceGoal->Behaviors->attach('Containable');
        $competence_goal = $this->CompetenceGoalRequest->CompetenceGoal->find('first', array(
            'contain' => array(
                'Competence'
            ),
            'conditions' => array(
                'CompetenceGoal.id' => $goal_id
            )
        ));

        if (!$competence_goal) {
            $this->redirect(array('action' => 'index'));
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
            $this->Session->setFlash('No se ha podido encontrar al profesor');
            $this->redirect(array('action' => 'index'));
        }

        $response = $this->Api->call(
            'GET',
            '/api/competence_goals/by_teacher/'.urlencode($teacher_id).'?goal_id='.urlencode($goal_id)
        );

        if (empty($response['data'])) {
            $this->Session->setFlash('El profesor elegido no puede evaluarte ese objetivo');
            $this->redirect(array('action' => 'index'));
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
            $this->Session->setFlash('La solicitud se ha realizado correctamente');
            $this->redirect(array('action' => 'index'));
        }

        $this->index();
    }

    function reject($id = null)
    {
        $id = $id === null ? null : intval($id);

        if (is_null($id)) {
            if (__FUNCTION__ === $this->action) {
                $this->redirect(array('action' => 'index'));
            } else {
                return false;
            }
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
                'CompetenceGoalRequest.canceled is null',
                'CompetenceGoalRequest.rejected is null'
            )
        ));

        if (!$competence_goal_request) {
            if (__FUNCTION__ === $this->action) {
                $this->redirect(array('action' => 'index'));
            } else {
                return false;
            }
        }

        if ($this->Auth->user('type') === "Estudiante") {
            $competence_goal_request['CompetenceGoalRequest']['canceled'] = date('Y-m-d H:i:s');
        } else {
            $competence_goal_request['CompetenceGoalRequest']['rejected'] = date('Y-m-d H:i:s');
        }

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
            if (__FUNCTION__ === $this->action) {
                if ($this->Auth->user('type') === "Estudiante") {
                    $this->Session->setFlash('La solicitud de evaluación se ha cancelado correctamente');
                } else {
                    $this->Session->setFlash('La solicitud de evaluación se ha rechazado correctamente');
                }
                $this->redirect(array('action' => 'index'));
            }
            return true;
        }

        if (__FUNCTION__ === $this->action) {
            if ($this->Auth->user('type') === "Estudiante") {
                $this->Session->setFlash('No se ha podido cancelar la solicitud de evaluación');
            } else {
                $this->Session->setFlash('No se ha podido rechazar la solicitud de evaluación');
            }
            $this->redirect(array('action' => 'index'));
        }
        return false;
    }

    function reject_by_course($course_id = null, $id = null)
    {
        $course_id = $course_id === null ? null : intval($course_id);
        $id = $id === null ? null : intval($id);

        if (is_null($course_id) || is_null($id)) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        $course = $this->CompetenceGoalRequest->CompetenceGoal->Competence->Course->find('first', array(
            'recursive' => -1,
            'conditions' => array('Course.id' => $course_id)
        ));

        if (!$course) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        if ($this->reject($id)) {
            if ($this->Auth->user('type') === "Estudiante") {
                $this->Session->setFlash('La solicitud de evaluación se ha cancelado correctamente');
            } else {
                $this->Session->setFlash('La solicitud de evaluación se ha rechazado correctamente');
            }
        } else {
            if ($this->Auth->user('type') === "Estudiante") {
                $this->Session->setFlash('No se ha podido cancelar la solicitud de evaluación');
            } else {
                $this->Session->setFlash('No se ha podido rechazar la solicitud de evaluación');
            }
        }
        
        $this->redirect(array('action' => 'by_course', $course_id));
    }

    function _get_from_course_id($course_id)
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
                'conditions' => array(
                    'Competence.id = CompetenceGoal.competence_id',
                    'Competence.course_id' => $course_id
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

    function _authorize()
    {
        parent::_authorize();
        $administrator_actions = array(
            'by_course'
        );
        $teacher_actions = array(
            'by_course'
        );
        $student_actions = array(
            'index', 'reject', 'add'
        );

        $this->set('section', 'competence');

        if ((array_search($this->params['action'], $administrator_actions) !== false) && ($this->Auth->user('type') !== "Administrador")) {
            if ((array_search($this->params['action'], $teacher_actions) !== false) && ($this->Auth->user('type') === "Profesor")) {
                return true;
            }
            return false;
        }

        if ($this->Auth->user('type') === "Estudiante" && array_search($this->params['action'], $student_actions) === false) {
            return false;
        }

        return true;
    }
}
