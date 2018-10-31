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

        $competence_goal = $this->_getCompetenceGoalByStudent($student_id, $id, $this->Auth->user());

        $competence = $this->CompetenceGoal->Competence->find('first', array(
            'recursive' => -1,
            'conditions' => array('Competence.id' => $competence_goal['CompetenceGoal']['competence_id'])
        ));

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

        $competence_goal = $this->_getCompetenceGoalByStudent($student_id, $id, $this->Auth->user());

        if (!$competence_goal) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

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
        }

        $competence_grades = array(
            'CompetenceCriterionGrade' => set::combine($competence_goal, 'CompetenceCriterion.{n}.id', 'CompetenceCriterion.{n}.CompetenceCriterionGrade')
        );

        if (empty($this->data)) {
            $this->data = $competence_grades;
        } else {
            $data_criterion_rubrics = Set::combine(
                isset($this->data['CompetenceCriterionGrade']) ? $this->data['CompetenceCriterionGrade'] : array(),
                '{n}.criterion_id',
                '{n}.rubric_id'
            );
            $competence_criterion_rubric_ids = set::combine(
                $competence_goal['CompetenceCriterion'],
                '{n}.id',
                '{n}.CompetenceCriterionRubric.{n}.id'
            );
            $filteredData = Set::extract('/CompetenceCriterionGrade', $competence_grades);
            $deletedGrades = [];

            foreach ($competence_goal['CompetenceCriterion'] as $i => $criterion) {
                $criterion_id = $criterion['id'];

                if (isset($data_criterion_rubrics[$criterion_id])) {
                    $rubric_id = $data_criterion_rubrics[$criterion_id];
                    
                    if (in_array($rubric_id, $competence_criterion_rubric_ids[$criterion_id])) {
                        $filteredData[$i]['CompetenceCriterionGrade']['student_id'] = $student_id;
                        $filteredData[$i]['CompetenceCriterionGrade']['criterion_id'] = $criterion_id;
                        $filteredData[$i]['CompetenceCriterionGrade']['rubric_id'] = $rubric_id;
                    } elseif (empty(trim($rubric_id))) {
                        // Remove
                        unset($filteredData[$i]);
                        if (isset($criterion['CompetenceCriterionGrade']['id'])) {
                            $deletedGrades[] = $criterion['CompetenceCriterionGrade']['id'];
                        }
                    }
                }
            }

            if (empty($filteredData) || $this->CompetenceGoal->CompetenceCriterion->CompetenceCriterionGrade->saveAll($filteredData)) {
                if (!empty($deletedGrades)) {
                    $this->CompetenceGoal->CompetenceCriterion->CompetenceCriterionGrade->deleteAll(
                        array('CompetenceCriterionGrade.id' => $deletedGrades)
                    );
                }
                $this->Session->setFlash('La evaluación se ha modificado correctamente.');

                if ($competence_goal_request) {
                    $competence_goal_request['CompetenceGoalRequest']['completed'] = date('Y-m-d H:i:s');
                    $this->CompetenceGoal->CompetenceGoalRequest->save($competence_goal_request);
                    $this->Email->reset();
                    $this->Email->from = 'Academic <noreply@ulpgc.es>';
                    $this->Email->to = $student['User']['username'];
                    $this->Email->subject = "Objetivo evaluado por el profesor solicitado";
                    $this->Email->sendAs = 'both';
                    $this->Email->template = Configure::read('app.email.competence_goal_request_completed') ?: 'competence_goal_request_completed';
                    $this->set('competence', $competence);
                    $this->set('competence_goal', $competence_goal);
                    $this->set('teacher', $this->Auth->user());
                    $this->Email->send();

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

    function _getCompetenceGoalByStudent($student_id, $goal_id, $auth_user = null)
    {
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
                    'CompetenceCriterionSubject.criterion_id = CompetenceCriterion.id'
                )
            ),
            array(
                'table' => 'subjects_users',
                'alias' => 'SubjectUser',
                'type'  => 'INNER',
                'conditions' => array(
                    'SubjectUser.subject_id = CompetenceCriterionSubject.subject_id',
                    'SubjectUser.user_id' => $student_id
                )
            ),
            array(
                'table' => 'competence_criterion_grades',
                'alias' => 'CompetenceCriterionGrade',
                'type'  => 'LEFT',
                'conditions' => array(
                    'CompetenceCriterionGrade.criterion_id = CompetenceCriterion.id',
                    'CompetenceCriterionGrade.student_id' => $student_id
                )
            )
        );

        $competence_goal_conditions = array(
            'AND' => array(
                'CompetenceGoal.id' => $goal_id
            )
        );

        if ($auth_user && $auth_user['User']['type'] === "Profesor") {
            $user_id = $auth_user['User']['id'];

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
            'fields' => array('distinct CompetenceGoal.*, CompetenceCriterion.*, CompetenceCriterionGrade.*'),
            'joins' => $competence_goal_joins,
            'conditions' => $competence_goal_conditions,
            'order' => array('CompetenceCriterion.code asc')
        ));

        if (!$competence_goal_result) {
            return false;
        }

        $competence_goal = array(
            'CompetenceGoal' => Set::extract($competence_goal_result, '0.CompetenceGoal'),
            'CompetenceCriterion' => Set::filter(Set::merge(
                Set::extract($competence_goal_result, '{n}.CompetenceCriterion'),
                Set::extract($competence_goal_result, '/CompetenceCriterionGrade')
            )
        ));

        foreach ($competence_goal['CompetenceCriterion'] as $i => $competence_criterion) {
            $rubrics = $this->CompetenceGoal->CompetenceCriterion->CompetenceCriterionRubric->find('all', array(
                'recursive' => -1,
                'conditions' => array('CompetenceCriterionRubric.criterion_id' => $competence_criterion['id'])
            ));
            $competence_goal['CompetenceCriterion'][$i]['CompetenceCriterionRubric'] = Set::combine(
                $rubrics,
                '{n}.CompetenceCriterionRubric.id',
                '{n}.CompetenceCriterionRubric'
            );
        }

        return $competence_goal;
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

        $this->set('section', 'courses');

        if ((array_search($this->params['action'], $administrator_actions) !== false) && ($this->Auth->user('type') != "Administrador")) {
            if ((array_search($this->params['action'], $teacher_actions) !== false) && ($this->Auth->user('type') === "Profesor")) {
                return true;
            }
            return false;
        }

        if ($this->Auth->user('type') == "Estudiante") {
            return false;
        }

        return true;
    }
}
