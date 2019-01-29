<?php
class CompetenceCriteriaController extends AppController {
    var $name = 'CompetenceCriteria';
    var $uses = array('CompetenceCriterion');

    var $paginate = array(
        'limit' => 10,
        'order' => array('CompetenceCriterion.code' => 'asc'),
    );

    function add_to_goal($goal_id = null)
    {
        $goal_id = $goal_id === null ? null : intval($goal_id);

        if (is_null($goal_id)) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        $competence_goal = $this->CompetenceCriterion->CompetenceGoal->find('first', array(
            'recursive' => -1,
            'conditions' => array('CompetenceGoal.id' => $goal_id)
        ));

        if (!$competence_goal) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        $auth_is_admin = $this->Auth->user('type') === 'Administrador';
        $auth_is_coordinator = false;

        if (empty($this->data)) {
            $this->set('competence_criterion', array());
        } else {
            $filteredData = array('CompetenceCriterion' => $this->data['CompetenceCriterion']);
            if ($this->CompetenceCriterion->save($filteredData)) {
                if ($this->_saveAll($this->CompetenceCriterion->id, $this->data, $auth_is_admin, $auth_is_coordinator)) {
                    $this->Session->setFlash('El criterio se ha guardado correctamente');
                    $this->redirect(array('controller' => 'competence_goals', 'action' => 'view', $this->data['CompetenceCriterion']['goal_id']));
                } else {
                    $this->Session->setFlash('El criterio se ha creado pero se ha producido un error al guardar alguno de sus valores');
                }
            }
            $this->set('competence_criterion', $this->data);
        }

        $competence = $this->CompetenceCriterion->CompetenceGoal->Competence->find('first', array(
            'recursive' => -1,
            'conditions' => array('Competence.id' => $competence_goal['CompetenceGoal']['competence_id'])
        ));
        $course = $this->CompetenceCriterion->CompetenceGoal->Competence->Course->find('first', array(
            'recursive' => -1,
            'conditions' => array('Course.id' => $competence['Competence']['course_id'])
        ));

        $this->set('auth_is_admin', $auth_is_admin);
        $this->set('auth_is_coordinator', $auth_is_coordinator);
        $this->set('competence_goal', $competence_goal);
        $this->set('competence', $competence);
        $this->set('course', $course);
    }

    function view($id = null)
    {
        $id = $id === null ? null : intval($id);

        if (is_null($id)) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        $this->CompetenceCriterion->Behaviors->attach('Containable');
        $competence_criterion = $this->CompetenceCriterion->find('first', array(
            'contain' => array(
                'CompetenceCriterionRubric' => array(
                    'order' => 'CompetenceCriterionRubric.value asc'
                ),
                'CompetenceCriterionSubject.Subject',
                'CompetenceCriterionTeacher.Teacher'
            ),
            'conditions' => array('CompetenceCriterion.id' => $id),
        ));

        if (!$competence_criterion) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        $user_id = $this->Auth->user('id');
        $coordinator_ids = array_merge(
            Set::extract('/CompetenceCriterionSubject/Subject/coordinator_id', $competence_criterion),
            Set::extract('/CompetenceCriterionSubject/Subject/practice_responsible_id', $competence_criterion)
        );
        $teacher_ids = Set::extract('/CompetenceCriterionTeacher/teacher_id', $competence_criterion);
        $subject_ids = Set::extract('/CompetenceCriterionSubject/subject_id', $competence_criterion);

        $auth_is_admin = $this->Auth->user('type') === 'Administrador';
        $auth_is_coordinator = in_array($user_id, $coordinator_ids);
        $auth_is_teacher = in_array($user_id, $teacher_ids);
        $auth_is_student = false;
        
        if ($this->Auth->user('type') === 'Estudiante' && ! empty($subject_ids)) {
            $db = $this->CompetenceCriterion->getDataSource();
            $subject_db_id_values = implode(', ', array_map(array($db, 'value'), $subject_ids));

            $query = "SELECT subject_id FROM subjects_users SubjectUser"
                . " WHERE SubjectUser.user_id = {$db->value($user_id)} "
                . " AND SubjectUser.subject_id in ($subject_db_id_values)"
            ;

            $auth_is_student = (bool) $this->CompetenceCriterion->query($query . ' LIMIT 1');
        }

        if (!$auth_is_admin && !$auth_is_coordinator && !$auth_is_teacher && !$auth_is_student) {
            $this->Session->setFlash('Usted no tiene permisos para realizar esta acción.');
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        // Sort subjects
        $competence_criterion['CompetenceCriterionSubject'] = set::sort(
            $competence_criterion['CompetenceCriterionSubject'],
            '{n}.Subject.name',
            'asc'
        );

        // Sort teachers
        $teachers_first_names = Set::extract('/CompetenceCriterionTeacher/Teacher/first_name', $competence_criterion);
        $teachers_last_names = Set::extract('/CompetenceCriterionTeacher/Teacher/last_name', $competence_criterion);
        array_multisort(
            $teachers_first_names,
            SORT_ASC,
            version_compare(phpversion(), '5.3.0', '>=') ? SORT_LOCALE_STRING : SORT_STRING,
            $teachers_last_names,
            SORT_ASC,
            version_compare(phpversion(), '5.3.0', '>=') ? SORT_LOCALE_STRING : SORT_STRING,
            $competence_criterion['CompetenceCriterionTeacher']
        );

        $competence_goal = $this->CompetenceCriterion->CompetenceGoal->find('first', array(
            'recursive' => -1,
            'conditions' => array('CompetenceGoal.id' => $competence_criterion['CompetenceCriterion']['goal_id'])
        ));
        $competence = $this->CompetenceCriterion->CompetenceGoal->Competence->find('first', array(
            'recursive' => -1,
            'conditions' => array('Competence.id' => $competence_goal['CompetenceGoal']['competence_id'])
        ));
        $course = $this->CompetenceCriterion->CompetenceGoal->Competence->Course->find('first', array(
            'recursive' => -1,
            'conditions' => array('Course.id' => $competence['Competence']['course_id'])
        ));


        $this->set('auth_is_admin', $auth_is_admin);
        $this->set('auth_is_coordinator', $auth_is_coordinator);
        $this->set('competence_criterion', $competence_criterion);
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

        $subject = $this->_getSubjectByCriterion($subject_id, $id);

        if (!$subject) {
            $competence_criterion = $this->CompetenceCriterion->find('first', array(
                'recursive' => -1,
                'conditions' => array('CompetenceCriterion.id' => $id)
            ));
            if ($competence_criterion) {
                $this->redirect(array(
                    'controller' => 'competence_goals',
                    'action' => 'view_by_subject',
                    $subject_id,
                    $competence_criterion['CompetenceCriterion']['goal_id']
                ));
            }
            $subject = $this->CompetenceCriterion->CompetenceCriterionSubject->Subject->find('first', array(
                'recursive' => -1,
                'conditions' => array('Subject.id' => $subject_idid)
            ));
            if ($subject) {
                $this->redirect(array('controller' => 'competence', 'action' => 'by_subject', $subject_id));
            }
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        $this->set('subject', $subject);
        $this->view($id);
    }

    function edit($id = null)
    {
        $id = $id === null ? null : intval($id);

        if (is_null($id)) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        $this->CompetenceCriterion->Behaviors->attach('Containable');
        $competence_criterion = $this->CompetenceCriterion->find('first', array(
            'contain' => array(
                'CompetenceCriterionRubric' => array(
                    'order' => 'CompetenceCriterionRubric.value asc'
                ),
                'CompetenceCriterionSubject.Subject',
                'CompetenceCriterionTeacher.Teacher'
            ),
            'conditions' => array('CompetenceCriterion.id' => $id),
        ));

        if (!$competence_criterion) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        $coordinator_ids = array_merge(
            Set::extract('/CompetenceCriterionSubject/Subject/coordinator_id', $competence_criterion),
            Set::extract('/CompetenceCriterionSubject/Subject/practice_responsible_id', $competence_criterion)
        );
        $auth_is_admin = $this->Auth->user('type') === 'Administrador';
        $auth_is_coordinator = in_array($this->Auth->user('id'), $coordinator_ids);

        if (!$auth_is_admin && !$auth_is_coordinator) {
            $this->Session->setFlash('Usted no tiene permisos para realizar esta acción.');
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        // Sort subjects
        $competence_criterion['CompetenceCriterionSubject'] = set::sort(
            $competence_criterion['CompetenceCriterionSubject'],
            '{n}.Subject.name',
            'asc'
        );

        // Sort teachers
        $teachers_first_names = Set::extract('/CompetenceCriterionTeacher/Teacher/first_name', $competence_criterion);
        $teachers_last_names = Set::extract('/CompetenceCriterionTeacher/Teacher/last_name', $competence_criterion);
        array_multisort(
            $teachers_first_names,
            SORT_ASC,
            version_compare(phpversion(), '5.3.0', '>=') ? SORT_LOCALE_STRING : SORT_STRING,
            $teachers_last_names,
            SORT_ASC,
            version_compare(phpversion(), '5.3.0', '>=') ? SORT_LOCALE_STRING : SORT_STRING,
            $competence_criterion['CompetenceCriterionTeacher']
        );

        if (empty($this->data)) {
            $this->data = $competence_criterion;
        } else {
            if ($this->_saveAll($id, $this->data, $auth_is_admin, $auth_is_coordinator)) {
                if (__FUNCTION__ === $this->action) {
                    $this->Session->setFlash('El criterio se ha modificado correctamente.');
                    $this->redirect(array('action' => 'view', $id));
                }
                return true;
            }
        }

        $competence_goal = $this->CompetenceCriterion->CompetenceGoal->find('first', array(
            'recursive' => -1,
            'conditions' => array('CompetenceGoal.id' => $this->data['CompetenceCriterion']['goal_id'])
        ));
        $competence = $this->CompetenceCriterion->CompetenceGoal->Competence->find('first', array(
            'recursive' => -1,
            'conditions' => array('Competence.id' => $competence_goal['CompetenceGoal']['competence_id'])
        ));
        $course = $this->CompetenceCriterion->CompetenceGoal->Competence->Course->find('first', array(
            'recursive' => -1,
            'conditions' => array('Course.id' => $competence['Competence']['course_id'])
        ));

        $this->set('auth_is_admin', $auth_is_admin);
        $this->set('auth_is_coordinator', $auth_is_coordinator);
        $this->set('competence_criterion', $this->data);
        $this->set('competence_goal', $competence_goal);
        $this->set('competence', $competence);
        $this->set('course', $course);
    }

    function edit_by_subject($subject_id, $id)
    {
        $subject_id = $subject_id === null ? null : intval($subject_id);
        $id = $id === null ? null : intval($id);

        if (is_null($subject_id) || is_null($id)) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        $subject = $this->_getSubjectByCriterion($subject_id, $id);

        if (!$subject) {
            $competence_criterion = $this->CompetenceCriterion->find('first', array(
                'recursive' => -1,
                'conditions' => array('CompetenceCriterion.id' => $id)
            ));
            if ($competence_criterion) {
                $this->redirect(array(
                    'controller' => 'competence_goals',
                    'action' => 'view_by_subject',
                    $subject_id,
                    $competence_criterion['CompetenceCriterion']['goal_id']
                ));
            }
            $subject = $this->CompetenceCriterion->CompetenceCriterionSubject->Subject->find('first', array(
                'recursive' => -1,
                'conditions' => array('Subject.id' => $subject_idid)
            ));
            if ($subject) {
                $this->redirect(array('controller' => 'competence', 'action' => 'by_subject', $subject_id));
            }
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        $this->set('subject', $subject);

        if (empty($this->data)) {
            $this->edit($id);
        } elseif ($this->edit($id)) {
            $this->Session->setFlash('El criterio se ha modificado correctamente.');
            $this->redirect(array('action' => 'view_by_subject', $subject_id, $id));
        }
    }

    function grade($id = null)
    {
        $id = $id === null ? null : intval($id);

        if (is_null($id)) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        $this->CompetenceCriterion->Behaviors->attach('Containable');
        $competence_criterion = $this->CompetenceCriterion->find('first', array(
            'contain' => array(
                'CompetenceCriterionRubric',
                'CompetenceCriterionSubject.Subject',
                'CompetenceCriterionTeacher',
                'CompetenceCriterionGrade'
            ),
            'conditions' => array('CompetenceCriterion.id' => $id)
        ));

        if (!$competence_criterion) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        $coordinator_ids = array_merge(
            Set::extract('/CompetenceCriterionSubject/Subject/coordinator_id', $competence_criterion),
            Set::extract('/CompetenceCriterionSubject/Subject/practice_responsible_id', $competence_criterion)
        );
        $teacher_ids = array_merge(
            Set::extract('/CompetenceCriterionTeacher/teacher_id', $competence_criterion)
        );
        $auth_is_admin = $this->Auth->user('type') === 'Administrador';
        $auth_is_coordinator = in_array($this->Auth->user('id'), $coordinator_ids);
        $auth_is_teacher = in_array($this->Auth->user('id'), $teacher_ids);

        if (!$auth_is_admin && !$auth_is_coordinator && !$auth_is_teacher) {
            $this->Session->setFlash('Usted no tiene permisos para realizar esta acción.');
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        $competence_criterion_rubrics_values = Set::combine(
            $competence_criterion['CompetenceCriterionRubric'],
            '{n}.id',
            '{n}.title'
        );
        $competence_criterion_rubrics_definitions = Set::combine(
            $competence_criterion['CompetenceCriterionRubric'],
            '{n}.id',
            '{n}.definition'
        );

        $dbo = $this->CompetenceCriterion->getDataSource();

        $join_CompetenceCriterionSubject_condition = array(
            'CompetenceCriterionSubject.criterion_id = CompetenceCriterion.id'
        );

        if ($this->action === 'grade_by_subject') {
            $join_CompetenceCriterionSubject_condition['CompetenceCriterionSubject.subject_id'] = $this->params['pass'][0];
        }
        
        $students = $dbo->fetchAll(
            $dbo->buildStatement(
                array(
                    'table' => 'competence_criteria',
                    'alias' => 'CompetenceCriterion',
                    'fields' => array('distinct Student.*, CompetenceCriterionGrade.*'),
                    'recursive' => -1,
                    'joins' => array(
                        array(
                            'table' => 'competence_criterion_subjects',
                            'alias' => 'CompetenceCriterionSubject',
                            'type'  => 'INNER',
                            'conditions' => $join_CompetenceCriterionSubject_condition
                        ),
                        array(
                            'table' => 'subjects_users',
                            'alias' => 'SubjectUser',
                            'type'  => 'INNER',
                            'conditions' => array(
                                'SubjectUser.subject_id = CompetenceCriterionSubject.subject_id'
                            )
                        ),
                        array(
                            'table' => 'users',
                            'alias' => 'Student',
                            'type'  => 'INNER',
                            'conditions' => array(
                                'Student.id = SubjectUser.user_id'
                            )
                        ),
                        array(
                            'table' => 'competence_criterion_grades',
                            'alias' => 'CompetenceCriterionGrade',
                            'type'  => 'LEFT',
                            'conditions' => array(
                                'CompetenceCriterionGrade.student_id = Student.id',
                                'CompetenceCriterionGrade.criterion_id' => $id
                            )
                        ),
                    ),
                    'order' => 'Student.first_name asc, Student.last_name asc',
                    'limit' => null,
                    'group' => null,
                    'conditions' => array('CompetenceCriterion.id' => $id)
                ),
                $this->CompetenceCriterion
            )
        );
        
        if (empty($this->data)) {
            $this->data = array(
                'CompetenceCriterionGrade' => set::combine($students, '{n}.Student.id', '{n}.CompetenceCriterionGrade')
            );
        } else {
            $data_user_rubrics = Set::combine(
                isset($this->data['CompetenceCriterionGrade']) ? $this->data['CompetenceCriterionGrade'] : array(),
                '{n}.student_id',
                '{n}.rubric_id'
            );
            $filteredData = Set::extract('/CompetenceCriterionGrade', $students);
            $deletedGrades = array();

            foreach ($students as $i => $student) {
                $student_id = $student['Student']['id'];

                if (isset($data_user_rubrics[$student_id])) {
                    $rubric_id = trim($data_user_rubrics[$student_id]);

                    if (isset($competence_criterion_rubrics_values[$rubric_id])) {
                        $filteredData[$i]['CompetenceCriterionGrade']['student_id'] = $student_id;
                        $filteredData[$i]['CompetenceCriterionGrade']['criterion_id'] = $id;
                        $filteredData[$i]['CompetenceCriterionGrade']['rubric_id'] = $rubric_id;
                    } elseif (empty($rubric_id)) {
                        // Remove
                        unset($filteredData[$i]);
                        if (isset($student['CompetenceCriterionGrade']['id'])) {
                            $deletedGrades[] = $student['CompetenceCriterionGrade']['id'];
                        }
                    }
                }
            }

            if (empty($filteredData) || $this->CompetenceCriterion->CompetenceCriterionGrade->saveAll($filteredData)) {
                if (!empty($deletedGrades)) {
                    $this->CompetenceCriterion->CompetenceCriterionGrade->deleteAll(
                        array('CompetenceCriterionGrade.id' => $deletedGrades)
                    );
                }
                if (__FUNCTION__ === $this->action) {
                    $this->Session->setFlash('La evaluación se ha modificado correctamente.');
                    if (isset($this->params['named']['ref']) && $this->params['named']['ref'] === 'competence_goals') {
                        $competence_goal_id = $competence_criterion['CompetenceCriterion']['goal_id'];
                        $this->redirect(array('controller' => 'competence_goals', 'action' => 'view', $competence_goal_id));
                    }
                    $this->redirect(array('action' => 'view', $id));
                }
                return true;
            }
        }

        $competence_goal = $this->CompetenceCriterion->CompetenceGoal->find('first', array(
            'recursive' => -1,
            'conditions' => array('CompetenceGoal.id' => $competence_criterion['CompetenceCriterion']['goal_id'])
        ));
        $competence = $this->CompetenceCriterion->CompetenceGoal->Competence->find('first', array(
            'recursive' => -1,
            'conditions' => array('Competence.id' => $competence_goal['CompetenceGoal']['competence_id'])
        ));
        $course = $this->CompetenceCriterion->CompetenceGoal->Competence->Course->find('first', array(
            'recursive' => -1,
            'conditions' => array('Course.id' => $competence['Competence']['course_id'])
        ));

        $this->set('students', $students);
        $this->set('competence_criterion', $competence_criterion);
        $this->set('competence_criterion_rubrics_values', $competence_criterion_rubrics_values);
        $this->set('competence_criterion_rubrics_definitions', $competence_criterion_rubrics_definitions);
        $this->set('competence_goal', $competence_goal);
        $this->set('competence', $competence);
        $this->set('course', $course);
    }

    function grade_by_subject($subject_id = null, $id = null)
    {
        $subject_id = $subject_id === null ? null : intval($subject_id);
        $id = $id === null ? null : intval($id);

        if (is_null($subject_id) || is_null($id)) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        $subject = $this->_getSubjectByCriterion($subject_id, $id);

        if (!$subject) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        $this->set('subject', $subject);

        if (empty($this->data)) {
            $this->grade($id);
        } elseif ($this->grade($id)) {
            $this->Session->setFlash('La evaluación se ha modificado correctamente.');
            if (isset($this->params['named']['ref']) && $this->params['named']['ref'] === 'competence_goals') {
                $competence_criterion = $this->CompetenceCriterion->find('first', array(
                    'fields' => array('goal_id'),
                    'recursive' => -1,
                    'conditions' => array('CompetenceCriterion.id' => $id)
                ));
                $competence_goal_id = $competence_criterion['CompetenceCriterion']['goal_id'];
                $this->redirect(array('controller' => 'competence_goals', 'action' => 'view_by_subject', $subject_id, $competence_goal_id));
            }
            $this->redirect(array('action' => 'view_by_subject', $subject_id, $id));
        }
    }

    function delete($id = null)
    {
        $id = $id === null ? null : intval($id);

        if (is_null($id)) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        $competence_criterion = $this->CompetenceCriterion->find('first', array(
            'recursive' => -1,
            'conditions' => array('CompetenceCriterion.id' => $id)
        ));

        if (!$competence_criterion) {
            $this->Session->setFlash('Usted no tiene permisos para realizar esta acción.');
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        $this->CompetenceCriterion->delete($id);
        $this->Session->setFlash('El criterio ha sido eliminada correctamente');
        $this->redirect(array('controller' => 'competence_goals', 'action' => 'view', $competence_criterion['CompetenceCriterion']['goal_id']));
    }

    function delete_by_subject($subject_id = null, $id = null)
    {
        $subject_id = $subject_id === null ? null : intval($subject_id);
        $id = $id === null ? null : intval($id);

        if (is_null($subject_id) || is_null($id)) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        $subject = $this->_getSubjectByCriterion($subject_id, $id);

        if (!$subject) {
            $this->Session->setFlash('Usted no tiene permisos para realizar esta acción.');
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        $competence_criterion = $this->CompetenceCriterion->find('first', array(
            'recursive' => -1,
            'conditions' => array('CompetenceCriterion.id' => $id)
        ));

        $this->CompetenceCriterion->delete($id);
        $this->Session->setFlash('El criterio ha sido eliminada correctamente');
        $this->redirect(array('controller' => 'competence_goals', 'action' => 'view_by_subject', $subject_id, $competence_criterion['CompetenceCriterion']['goal_id']));

    }

    function _getSubjectByCriterion($subject_id, $criterion_id)
    {
        return $this
            ->CompetenceCriterion
            ->CompetenceCriterionSubject
            ->Subject->find(
                'first',
                array(
                    'fields' => array('Subject.*'),
                    'recursive' => -1,
                    'joins' => array(
                        array(
                            'table' => 'competence_criterion_subjects',
                            'alias' => 'CompetenceCriterionSubject',
                            'type'  => 'INNER',
                            'conditions' => array(
                                'CompetenceCriterionSubject.subject_id = Subject.id',
                                'CompetenceCriterionSubject.criterion_id' => $criterion_id,
                            )
                        )
                    ),
                    'conditions' => array('Subject.id' => $subject_id)
                )
            );
    }

    function _saveAll($id, $data, $auth_is_admin, $auth_is_coordinator)
    {
        $this->CompetenceCriterion->Behaviors->attach('Containable');

        $competence_criterion = $this->CompetenceCriterion->find('first', array(
            'contain' => array(
                'CompetenceCriterionRubric',
                'CompetenceCriterionSubject.Subject',
                'CompetenceCriterionTeacher.Teacher'
            ),
            'conditions' => array('CompetenceCriterion.id' => $id)
        ));

        if (! $competence_criterion) {
            return false;
        }

        $filteredData = $competence_criterion;
        $deletedRubrics = array();
        $deletedSubjects = array();
        $deletedTeachers = array();

        // Criterion
        if ($auth_is_admin) {
            if (isset($this->data['CompetenceCriterion'])) {
                $whiteList = array('code', 'definition');

                $filteredData['CompetenceCriterion']
                    = array_intersect_key($this->data['CompetenceCriterion'], array_flip($whiteList))
                    + $filteredData['CompetenceCriterion'];
            }
        }

        // Rubrics
        if ($auth_is_admin || $auth_is_coordinator) {
            if (isset($this->data['CompetenceCriterionRubric'])) {
                if (!is_array($this->data['CompetenceCriterionRubric'])) {
                    $this->data['CompetenceCriterionRubric'] = array();
                }

                if ($auth_is_admin) {
                    $whiteList = array('title', 'definition', 'value');
                } elseif ($auth_is_coordinator) {
                    $whiteList = array('definition');
                }

                foreach ($filteredData['CompetenceCriterionRubric'] as $i => $rubric) {
                    if (isset($this->data['CompetenceCriterionRubric'][$rubric['id']])) {
                        // Update
                        $this->data['CompetenceCriterionRubric'][$rubric['id']]['id'] = $rubric['id'];
                        $filteredData['CompetenceCriterionRubric'][$i]
                            = array_intersect_key($this->data['CompetenceCriterionRubric'][$rubric['id']], array_flip($whiteList))
                            + $filteredData['CompetenceCriterionRubric'][$i];
                    } else {
                        // Remove
                        unset($filteredData['CompetenceCriterionRubric'][$i]);
                        $deletedRubrics[] = $rubric['id'];
                    }
                }
                foreach ($this->data['CompetenceCriterionRubric'] as $rubric) {
                    if (!isset($rubric['id'])) {
                        // Add
                        $filteredData['CompetenceCriterionRubric'][]
                            = array('criterion_id' => $id)
                            + array_intersect_key($rubric, array_flip($whiteList));
                    }
                }
            } else {
                unset($filteredData['CompetenceCriterionRubric']);
            }
        }

        // Subjects
        if ($auth_is_admin) {
            if (isset($this->data['CompetenceCriterionSubject'])) {
                if (!is_array($this->data['CompetenceCriterionSubject'])) {
                    $this->data['CompetenceCriterionSubject'] = array();
                }

                foreach ($filteredData['CompetenceCriterionSubject'] as $i => $subject) {
                    // Remove relations loaded
                    foreach($subject as $field => $fieldValue) {
                        if (is_array($fieldValue)) {
                            unset($filteredData['CompetenceCriterionSubject'][$i][$field]);
                        }
                    }

                    if (isset($this->data['CompetenceCriterionSubject'][$subject['id']])) {
                        // Update
                        $this->data['CompetenceCriterionSubject'][$subject['id']]['id'] = $subject['id'];
                        // ...nothing to update
                    } else {
                        // Remove
                        unset($filteredData['CompetenceCriterionSubject'][$i]);
                        $deletedSubjects[] = $subject['id'];
                    }
                }
                foreach ($this->data['CompetenceCriterionSubject'] as $subject) {
                    if (!isset($subject['id']) && isset($subject['subject_id'])) {
                        // Add
                        $filteredData['CompetenceCriterionSubject'][] = array(
                            'criterion_id' => $id,
                            'subject_id' => $subject['subject_id']
                        );
                    }
                }
            } else {
                unset($filteredData['CompetenceCriterionSubject']);
            }
        }

        // Teacher
        if ($auth_is_admin || $auth_is_coordinator) {
            if (isset($this->data['CompetenceCriterionTeacher'])) {
                if (!is_array($this->data['CompetenceCriterionTeacher'])) {
                    $this->data['CompetenceCriterionTeacher'] = array();
                }

                foreach ($filteredData['CompetenceCriterionTeacher'] as $i => $teacher) {
                    // Remove relations loaded
                    foreach($teacher as $field => $fieldValue) {
                        if (is_array($fieldValue)) {
                            unset($filteredData['CompetenceCriterionTeacher'][$i][$field]);
                        }
                    }

                    if (isset($this->data['CompetenceCriterionTeacher'][$teacher['id']])) {
                        // Update
                        $this->data['CompetenceCriterionTeacher'][$teacher['id']]['id'] = $teacher['id'];
                        // ...nothing to update
                    } else {
                        // Remove
                        unset($filteredData['CompetenceCriterionTeacher'][$i]);
                        $deletedTeachers[] = $teacher['id'];
                    }
                }
                foreach ($this->data['CompetenceCriterionTeacher'] as $teacher) {
                    if (!isset($teacher['id']) && isset($teacher['teacher_id'])) {
                        // Add
                        $filteredData['CompetenceCriterionTeacher'][] = array(
                            'criterion_id' => $id,
                            'teacher_id' => $teacher['teacher_id']
                        );
                    }
                }
            } else {
                unset($filteredData['CompetenceCriterionTeacher']);
            }
        }

        if ($this->CompetenceCriterion->saveAll($filteredData)) {
            if (!empty($deletedRubrics)) {
                $this->CompetenceCriterion->CompetenceCriterionRubric->deleteAll(
                    array('CompetenceCriterionRubric.id' => $deletedRubrics)
                );
            }
            if (!empty($deletedSubjects)) {
                $this->CompetenceCriterion->CompetenceCriterionSubject->deleteAll(
                    array('CompetenceCriterionSubject.id' => $deletedSubjects)
                );
            }
            if (!empty($deletedTeachers)) {
                $this->CompetenceCriterion->CompetenceCriterionTeacher->deleteAll(
                    array('CompetenceCriterionTeacher.id' => $deletedTeachers)
                );
            }

            return true;
        }

        return false;
    }
  
    function _authorize()
    {
        parent::_authorize();
        $administrator_actions = array(
            'view', 'view_by_subject',
            'grade', 'grade_by_subject',
            'edit', 'edit_by_subject',
            'add_to_goal', 'delete', 'delete_by_suject'
        );
        $teacher_actions = array(
            'view', 'view_by_subject',
            'grade', 'grade_by_subject',
            'edit', 'edit_by_subject'
        );
        $student_actions = array(
            'view', 'view_by_subject'
        );

        $this->set('section', 'competence');

        if ((array_search($this->params['action'], $administrator_actions) !== false) && ($this->Auth->user('type') !== "Administrador")) {
            if ((array_search($this->params['action'], $teacher_actions) !== false) && ($this->Auth->user('type') === "Profesor")) {
                return true;
            }
            if (array_search($this->params['action'], $student_actions) !== false && ($this->Auth->user('type') === "Estudiante")) {
                return true;
            }
            return false;
        }

        if ($this->Auth->user('type') === "Estudiante") {
            return false;
        }

        return true;
    }
}
