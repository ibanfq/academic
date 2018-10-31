<?php
class CompetenceGoalRequestsController extends AppController {
    var $name = 'CompetenceGoalRequests';
    var $uses = array('CompetenceGoalRequest');

    var $paginate = array(
        'limit' => 10,
        'order' => array('CompetenceGoalRequest.id' => 'asc'),
    );

    function by_course($course_id)
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

        if ($this->Auth->user('type') === "Estudiante") {
            $competence_goal_request_conditions = array(
                'AND' => array(
                    'CompetenceGoalRequest.student_id' => $user_id
                )
            );
        } else {
            $competence_goal_request_conditions = array(
                'AND' => array(
                    'CompetenceGoalRequest.teacher_id' => $user_id
                )
            );
        }

        if ($this->Auth->user('type') === "Profesor") {
            $competence_joins[] = array(
                'table' => 'subjects',
                'alias' => 'Subject',
                'type'  => 'LEFT',
                'conditions' => array(
                    'Subject.id = CompetenceCriterionSubject.subject_id'
                )
            );

            $competence_joins[] = array(
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
