<?php
class CompetenceGoalRequestsController extends AppController {
    var $name = 'CompetenceGoalRequests';
    var $uses = array('CompetenceGoalRequest');

    var $paginate = array(
        'limit' => 10,
        'order' => array('CompetenceGoalRequest.id' => 'asc'),
    );

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

        $competence_goal_requests = $this->CompetenceGoalRequest->find('all', array(
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

        $this->set('competence_goal_requests', $competence_goal_requests);
        $this->set('course', $course);
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

        $this->CompetenceGoalRequest->Behaviors->attach('Containable');
        $competence_goal_request = $this->CompetenceGoalRequest->find('first', array(
            'contain' => array(
                'Student',
                'CompetenceGoal.Competence'
            ),
            'conditions' => array(
                'CompetenceGoalRequest.id' => $id,
                'CompetenceGoalRequest.completed is null',
                'CompetenceGoalRequest.canceled is null',
                'CompetenceGoalRequest.rejected is null'
            )
        ));

        if (!$competence_goal_request) {
            $this->redirect(array('action' => 'by_course', $course_id));
        }

        $competence_goal_request['CompetenceGoalRequest']['rejected'] = date('Y-m-d H:i:s');

        if ($this->CompetenceGoalRequest->save(array('CompetenceGoalRequest' => $competence_goal_request['CompetenceGoalRequest']))) {
            $this->Email->reset();
            $this->Email->from = 'Academic <noreply@ulpgc.es>';
            $this->Email->to = $competence_goal_request['Student']['username'];
            $this->Email->subject = "Petición de evaluación rechazada por el profesor";
            $this->Email->sendAs = 'both';
            $this->Email->template = Configure::read('app.email.competence_goal_request_rejected') ?: 'competence_goal_request_rejected';
            $this->set('competence_goal_request', $competence_goal_request);
            $this->set('teacher', $this->Auth->user());
            $this->Email->send();
            $this->Session->setFlash('La solicitud de evaluación se ha rechazado correctamente');
        } else {
            $this->Session->setFlash('No se ha podido rechazar la solicitud de evaluación');
        }
        
        $this->redirect(array('action' => 'by_course', $course_id));
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
        );

        $this->set('section', 'courses');

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
