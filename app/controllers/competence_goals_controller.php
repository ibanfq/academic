<?php
class CompetenceGoalsController extends AppController {
    var $name = 'CompetenceGoals';
    var $uses = array('CompetenceGoal');

    var $paginate = array(
        'limit' => 10,
        'order' => array('CompetenceGoal.code' => 'asc'),
    );

    function add_to_competence($competence_id = null)
    {
        $competence_id = $competence_id === null ? null : intval($competence_id);
        
        if (is_null($competence_id)) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        $competence = $this->CompetenceGoal->Competence->find('first', array(
            'recursive' => -1,
            'conditions' => array('Competence.id' => $competence_id)
        ));

        if (!$competence) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        if (!empty($this->data)) {
            if ($this->CompetenceGoal->save($this->data)) {
                $this->Session->setFlash('El objetivo se ha guardado correctamente');
                $this->redirect(array('controller' => 'competence', 'action' => 'view', $this->data['CompetenceGoal']['competence_id']));
            }
        }

        $course = $this->CompetenceGoal->Competence->Course->find('first', array(
            'recursive' => -1,
            'conditions' => array('Course.id' => $competence['Competence']['course_id'])
        ));

        $this->set('competence', $competence);
        $this->set('course', $course);
    }

    function view($id = null)
    {
        $id = $id === null ? null : intval($id);

        if (is_null($id)) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }
        
        $competence_goal_joins = array(
        );

        $competence_goal_conditions = array(
            'AND' => array(
                'CompetenceGoal.id' => $id
            )
        );

        if ($this->Auth->user('type') === "Administrador") {
            $competence_goal_joins[] = array(
                'table' => 'competence_criteria',
                'alias' => 'CompetenceCriterion',
                'type'  => 'LEFT',
                'conditions' => array(
                    'CompetenceCriterion.goal_id = CompetenceGoal.id'
                )
            );
        } else {
            $user_id = $this->Auth->user('id');

            $competence_goal_joins[] = array(
                'table' => 'competence_criteria',
                'alias' => 'CompetenceCriterion',
                'type'  => 'INNER',
                'conditions' => array(
                    'CompetenceCriterion.goal_id = CompetenceGoal.id'
                )
            );

            $competence_goal_joins[] = array(
                'table' => 'competence_criterion_subjects',
                'alias' => 'CompetenceCriterionSubject',
                'type'  => 'LEFT',
                'conditions' => array(
                    'CompetenceCriterionSubject.criterion_id = CompetenceCriterion.id'
                )
            );

            $competence_goal_joins[] = array(
                'table' => 'subjects',
                'alias' => 'Subject',
                'type'  => 'LEFT',
                'conditions' => array(
                    'Subject.id = CompetenceCriterionSubject.subject_id'
                )
            );

            $competence_goal_joins[] = array(
                'table' => 'competence_criterion_teachers',
                'alias' => 'CompetenceCriterionTeacher',
                'type'  => 'LEFT',
                'conditions' => array(
                    'CompetenceCriterionTeacher.criterion_id = CompetenceCriterion.id'
                )
            );

            $competence_goal_conditions['AND'][] = array(
                'OR' => array(
                    array('Subject.coordinator_id' => $user_id),
                    array('Subject.practice_responsible_id' => $user_id),
                    array('CompetenceCriterionTeacher.teacher_id' => $user_id)
                )
            );
        }

        $competence_goal_result = $this->CompetenceGoal->find('all', array(
            'recursive' => -1,
            'fields' => array('distinct CompetenceGoal.*, CompetenceCriterion.*'),
            'joins' => $competence_goal_joins,
            'conditions' => $competence_goal_conditions,
            'order' => array('CompetenceCriterion.code asc')
        ));

        if (!$competence_goal_result) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        $competence_goal = array(
            'CompetenceGoal' => Set::extract($competence_goal_result, '0.CompetenceGoal'),
            'CompetenceCriterion' => Set::filter(Set::extract($competence_goal_result, '{n}.CompetenceCriterion'))
        );

        $competence = $this->CompetenceGoal->Competence->find('first', array(
            'recursive' => -1,
            'conditions' => array('Competence.id' => $competence_goal['CompetenceGoal']['competence_id'])
        ));

        $course = $this->CompetenceGoal->Competence->Course->find('first', array(
            'recursive' => -1,
            'conditions' => array('Course.id' => $competence['Competence']['course_id'])
        ));

        $this->set('competence_goal', $competence_goal);
        $this->set('competence', $competence);
        $this->set('course', $course);
    }

    function view_by_subject($subject_id = null, $id = null)
    {
        $subject_id = $subject_id === null ? null : intval($subject_id);
        $id = $id === null ? null : intval($id);

        if (is_null($subject_id) || is_null($id)) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }
        
        $subject = $this
            ->CompetenceGoal
            ->CompetenceCriterion
            ->CompetenceCriterionSubject
            ->Subject->find(
                'first',
                array(
                    'recursive' => -1,
                    'conditions' => array('Subject.id' => $subject_id)
                )
            );

        if (!$subject) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        $competence_goal_joins = array(
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
                    'CompetenceCriterionSubject.criterion_id = CompetenceCriterion.id',
                    'CompetenceCriterionSubject.subject_id' => $subject_id
                )
            )
        );

        $competence_goal_conditions = array(
            'AND' => array(
                'CompetenceGoal.id' => $id
            )
        );

        if ($this->Auth->user('type') === "Profesor")
        {
            $user_id = $this->Auth->user('id');

            $competence_goal_joins[] = array(
                'table' => 'subjects',
                'alias' => 'Subject',
                'type'  => 'LEFT',
                'conditions' => array(
                    'Subject.id = CompetenceCriterionSubject.subject_id'
                )
            );

            $competence_goal_joins[] = array(
                'table' => 'competence_criterion_teachers',
                'alias' => 'CompetenceCriterionTeacher',
                'type'  => 'LEFT',
                'conditions' => array(
                    'CompetenceCriterionTeacher.criterion_id = CompetenceCriterion.id'
                )
            );

            $competence_goal_conditions['AND'][] = array(
                'OR' => array(
                    array('Subject.coordinator_id' => $user_id),
                    array('Subject.practice_responsible_id' => $user_id),
                    array('CompetenceCriterionTeacher.teacher_id' => $user_id)
                )
            );
        }

        $competence_goal_result = $this->CompetenceGoal->find('all', array(
            'recursive' => -1,
            'fields' => array('distinct CompetenceGoal.*, CompetenceCriterion.*'),
            'joins' => $competence_goal_joins,
            'conditions' => $competence_goal_conditions,
            'order' => array('CompetenceCriterion.code asc')
        ));

        if (!$competence_goal_result) {
            $competence_goal = $this->CompetenceGoal->find('first', array(
                'recursive' => -1,
                'conditions' => array('CompetenceGoal.id' => $id)
            ));
            if ($competence_goal) {
                 $this->redirect(array(
                    'controller' => 'competence',
                    'action' => 'view_by_subject',
                    $subject_id,
                    $competence_goal['CompetenceGoal']['competence_id']
                ));
            }
            $this->redirect(array('controller' => 'competence', 'action' => 'by_subject', $subject_id));
        }

        $competence_goal = array(
            'CompetenceGoal' => Set::extract($competence_goal_result, '0.CompetenceGoal'),
            'CompetenceCriterion' => Set::filter(Set::extract($competence_goal_result, '{n}.CompetenceCriterion'))
        );

        $competence = $this->CompetenceGoal->Competence->find('first', array(
            'recursive' => -1,
            'conditions' => array('Competence.id' => $competence_goal['CompetenceGoal']['competence_id'])
        ));

        $course = $this->CompetenceGoal->Competence->Course->find('first', array(
            'recursive' => -1,
            'conditions' => array('Course.id' => $competence['Competence']['course_id'])
        ));

        $this->set('competence_goal', $competence_goal);
        $this->set('competence', $competence);
        $this->set('course', $course);
        $this->set('subject', $subject);
    }

    function view_by_student($student_id = null, $id = null)
    {
        $student_id = $student_id === null ? null : intval($student_id);
        $id = $id === null ? null : intval($id);

        if (is_null($student_id) || is_null($id)) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        $response = $this->Api->call('GET', '/api/competence_goals/by_student/' . urlencode($student_id) . '/' . urlencode($id));
        if ($response['status'] === 'error') {
            $this->Session->setFlash($response['message']);
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }
        $competence_goal = $response['data'];

        $this->loadModel('User');
        $student = $this->User->find('first', array(
            'recursive' => -1,
            'conditions' => array(
                'User.id' => $student_id,
                'User.type' => 'Estudiante'
            )
        ));
        if (!$student) {
            $this->redirect(array('controller' => 'users', 'action' => 'index'));
        }

        $competence = $this->CompetenceGoal->Competence->find('first', array(
            'recursive' => -1,
            'conditions' => array('Competence.id' => $competence_goal['CompetenceGoal']['competence_id'])
        ));
        if (!$competence) {
            $this->redirect(array('controller' => 'users', 'action' => 'index'));
        }

        $course = $this->CompetenceGoal->Competence->Course->find('first', array(
            'recursive' => -1,
            'conditions' => array('Course.id' => $competence['Competence']['course_id'])
        ));

        $this->set('student', $student);
        $this->set('competence_goal', $competence_goal);
        $this->set('competence', $competence);
        $this->set('course', $course);
    }

    function grade_by_student($student_id = null, $id = null)
    {
        $student_id = $student_id === null ? null : intval($student_id);
        $id = $id === null ? null : intval($id);
        $competence_goal_request = null;

        if (is_null($student_id) || is_null($id)) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        $response = $this->Api->call('GET', '/api/competence_goals/by_student/' . urlencode($student_id) . '/' . urlencode($id));
        if ($response['status'] === 'error') {
            $this->Session->setFlash($response['message']);
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }
        $competence_goal = $response['data'];

        $competence = $this->CompetenceGoal->Competence->find('first', array(
            'recursive' => -1,
            'conditions' => array('Competence.id' => $competence_goal['CompetenceGoal']['competence_id'])
        ));
        if (!$competence) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        if (!empty($this->params['named']['request_id'])) {
            $competence_goal_request = $this->CompetenceGoal->CompetenceGoalRequest->find('first', array(
                'recursive' => -1,
                'conditions' => array(
                    'id' => $this->params['named']['request_id'],
                    'teacher_id' => $this->Auth->user('id'),
                    'completed is null AND canceled is null AND rejected is null'
                )
            ));

            if (!$competence_goal_request) {
                $this->Session->setFlash('La solicitud de evaluación ya no se encuentra disponible.');
                $this->redirect(array(
                    'controller' => 'competence_goal_requests',
                    'action' => 'by_course',
                    $competence['Competence']['course_id']
                ));
            }
            
            if (!empty($this->data)) {
                $this->data['competence_goal_request_id'] = $this->params['named']['request_id'];
            }
        }

        $competence_grades = array(
            'CompetenceCriterionGrade' => set::combine($competence_goal, 'CompetenceCriterion.{n}.id', 'CompetenceCriterion.{n}.CompetenceCriterionGrade')
        );

        if (empty($this->data)) {
            $this->data = $competence_grades;
        } else {
            $response = $this->Api->call(
                'POST',
                '/api/competence_goals/grade_by_student/' . urlencode($student_id) . '/' . urlencode($id),
                $this->data
            );
            switch ($response['status']) {
                case 'fail':
                    $this->Session->setFlash('No se pudo guardar la evaluación. Por favor, revisa que has introducido todos los datos correctamente.');
                    break;
                case 'error':
                    $this->Session->setFlash($response['message']);
                    break;
                case 'success':
                    $this->Session->setFlash('La evaluación se ha modificado correctamente.');

                    if ($competence_goal_request) {
                        $this->redirect(array(
                            'controller' => 'competence_goal_requests',
                            'action' => 'by_course',
                            $competence['Competence']['course_id']
                        ));
                    }

                    if (isset($this->params['named']['ref']) && $this->params['named']['ref'] === 'competence') {
                        $competence_id = $competence_goal['CompetenceGoal']['competence_id'];
                        $this->redirect(array('controller' => 'competence', 'action' => 'view_by_student', $student_id, $competence_id));
                    }

                    $this->redirect(array('action' => 'view_by_student', $student_id, $id));
            }
        }

        $this->loadModel('User');
        $student = $this->User->find('first', array(
            'recursive' => -1,
            'conditions' => array(
                'User.id' => $student_id,
                'User.type' => 'Estudiante'
            )
        ));

        $course = $this->CompetenceGoal->Competence->Course->find('first', array(
            'recursive' => -1,
            'conditions' => array('Course.id' => $competence['Competence']['course_id'])
        ));

        $this->set('student', $student);
        $this->set('competence_goal', $competence_goal);
        $this->set('competence_goal_request', $competence_goal_request);
        $this->set('competence', $competence);
        $this->set('course', $course);
    }

    function edit($id = null)
    {
        $id = $id === null ? null : intval($id);

        if (is_null($id)) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        if (empty($this->data)) {
            $this->data = $this->CompetenceGoal->find('first', array(
                'recursive' => -1,
                'conditions' => array('CompetenceGoal.id' => $id)
            ));
        } else {
            if ($this->CompetenceGoal->save($this->data)) {
                $this->Session->setFlash('La objetivo se ha modificado correctamente.');
                $this->redirect(array('action' => 'view', $id));
            }
        }

        $competence = $this->CompetenceGoal->Competence->find('first', array(
            'recursive' => -1,
            'conditions' => array('Competence.id' => $this->data['CompetenceGoal']['competence_id'])
        ));
        $course = $this->CompetenceGoal->Competence->Course->find('first', array(
            'recursive' => -1,
            'conditions' => array('Course.id' => $competence['Competence']['course_id'])
        ));

        $this->set('competence_goal', $this->data);
        $this->set('competence', $competence);
        $this->set('course', $course);
    }

    function delete($id = null)
    {
        $id = $id === null ? null : intval($id);

        if (is_null($id)) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        $competence_goal = $this->CompetenceGoal->find('first', array(
            'recursive' => -1,
            'conditions' => array('CompetenceGoal.id' => $id)
        ));

        if (!$competence_goal) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        $this->CompetenceGoal->delete($id);
        $this->Session->setFlash('El objetivo ha sido eliminada correctamente');
        $this->redirect(array('controller' => 'competence', 'action' => 'view', $competence_goal['CompetenceGoal']['competence_id']));
    }

    function _authorize()
    {
        parent::_authorize();
        $administrator_actions = array(
            'view', 'view_by_subject', 'view_by_student',
            'grade_by_student',
            'add_to_competence', 'edit', 'delete'
        );
        $teacher_actions = array(
            'view', 'view_by_subject', 'view_by_student',
            'grade_by_student'
        );
        $student_actions = array(
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
