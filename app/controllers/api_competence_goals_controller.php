<?php
class ApiCompetenceGoalsController extends AppController {
    var $name = 'CompetenceGoals';
    var $isApi = true;
    var $uses = array('CompetenceGoal');

    function _authorize(){
        parent::_authorize();
        $administrator_actions = array(
        );
        $teacher_actions = array(
        );
        $student_actions = array(
            'by_teacher'
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

    function by_teacher($teacher_id = null)
    {
        $teacher_id = $teacher_id === null ? null : intval($teacher_id);

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
            $this->Api->setError('No se ha podido encontrar al profesor.', 404);
            $this->Api->respond($this);
            return;
        }

        $competence_goal_joins = array(
            array(
                'table' => 'competence',
                'alias' => 'Competence',
                'type'  => 'INNER',
                'conditions' => array(
                    'Competence.id = CompetenceGoal.competence_id'
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
                    'Subject.id = CompetenceCriterionSubject.subject_id'
                )
            ),
            array(
                'table' => 'competence_criterion_teachers',
                'alias' => 'CompetenceCriterionTeacher',
                'type'  => 'LEFT',
                'conditions' => array(
                    'CompetenceCriterionTeacher.criterion_id = CompetenceCriterion.id'
                )
            ),
        );

        $fields = array('distinct CompetenceGoal.*, Competence.*');

        if ($this->Auth->user('type') === 'Estudiante') {
            $student_id = $this->Auth->user('id');
            $fields[] = "IFNULL((SELECT 1 FROM competence_goal_requests where goal_id = CompetenceGoal.id AND student_id = $student_id AND rejected is null AND canceled is null limit 1), 0) as has_requests";
            $competence_goal_joins[] = array(
                'table' => 'subjects_users',
                'alias' => 'SubjectUser',
                'type'  => 'INNER',
                'conditions' => array(
                    'SubjectUser.subject_id = CompetenceCriterionSubject.subject_id',
                    'SubjectUser.user_id' => $student_id
                )
            );
        }

        $conditions = array(
            'AND' => array(
                'OR' => array(
                    array('Subject.coordinator_id' => $teacher_id),
                    array('Subject.practice_responsible_id' => $teacher_id),
                    array('CompetenceCriterionTeacher.teacher_id' => $teacher_id)
                )
            )
        );

        if (!empty($this->params['url']['goal_id'])) {
            $conditions['AND'][] = array('CompetenceGoal.id' => $this->params['url']['goal_id']);
        }

        $competence_goals = $this->CompetenceGoal->find('all', array(
            'fields' => $fields,
            'recursive' => -1,
            'joins' => $competence_goal_joins,
            'conditions' => $conditions,
            'order' => array('Competence.code asc', 'CompetenceGoal.code asc')
        ));

        $competence_goals = Set::combine($competence_goals, '{n}.CompetenceGoal.id', '{n}', '{n}.CompetenceGoal.competence_id');

        $this->Api->setData($competence_goals);
        $this->Api->respond($this);
    }
}
