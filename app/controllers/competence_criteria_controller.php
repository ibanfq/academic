<?php
class CompetenceCriteriaController extends AppController {
    var $name = 'CompetenceCriteria';
    var $uses = array('CompetenceCriterion');

    var $paginate = array(
        'limit' => 10,
        'order' => array('CompetenceCriterion.code' => 'asc'),
    );

    function add_to_goal($goal_id)
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

        $this->CompetenceCriterion->Behaviors->attach('Containable');
        $competence_criterion = $this->CompetenceCriterion->find('first', array(
            'contain' => array(
                'CompetenceCriterionRubric',
                'CompetenceCriterionSubject.Subject.Coordinator.id',
                'CompetenceCriterionSubject.Subject.Responsible.id',
                'CompetenceCriterionTeacher.Teacher'
            ),
            'conditions' => array('CompetenceCriterion.id' => $id)
        ));

        if (!$competence_criterion) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        $coordinator_ids = array_merge(
            Set::extract('/CompetenceCriterionSubject/Subject/Coordinator/id', $competence_criterion),
            Set::extract('/CompetenceCriterionSubject/Subject/Responsible/id', $competence_criterion)
        );
        $auth_is_admin = $this->Auth->user('type') === 'Administrador';
        $auth_is_coordinator = in_array($this->Auth->user('id'), $coordinator_ids);

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

    function edit($id = null)
    {
        $id = $id === null ? null : intval($id);

        $this->CompetenceCriterion->Behaviors->attach('Containable');
        $competence_criterion = $this->CompetenceCriterion->find('first', array(
            'contain' => array(
                'CompetenceCriterionRubric',
                'CompetenceCriterionSubject.Subject.Coordinator.id',
                'CompetenceCriterionSubject.Subject.Responsible.id',
                'CompetenceCriterionTeacher.Teacher'
            ),
            'conditions' => array('CompetenceCriterion.id' => $id)
        ));

        $coordinator_ids = array_merge(
            Set::extract('/CompetenceCriterionSubject/Subject/Coordinator/id', $competence_criterion),
            Set::extract('/CompetenceCriterionSubject/Subject/Responsible/id', $competence_criterion)
        );
        $auth_is_admin = $this->Auth->user('type') === 'Administrador';
        $auth_is_coordinator = in_array($this->Auth->user('id'), $coordinator_ids);

        if (!$auth_is_admin && !$auth_is_coordinator) {
            $this->Session->setFlash('Usted no tiene permisos para realizar esta acción.');
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        if (empty($this->data)) {
            $this->data = $competence_criterion;
        } else {
            if ($this->_saveAll($id, $this->data, $auth_is_admin, $auth_is_coordinator)) {
                $this->Session->setFlash('El criterio se ha modificado correctamente.');
                $this->redirect(array('action' => 'view', $id));
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
        $this->set('auth_is_coordinator', in_array($this->Auth->user('id'), $coordinator_ids));
        $this->set('competence_criterion', $this->data);
        $this->set('competence_goal', $competence_goal);
        $this->set('competence', $competence);
        $this->set('course', $course);
    }

    function delete($id = null)
    {
        $id = $id === null ? null : intval($id);
        $this->CompetenceCriterion->id = $id;
        $competence_criterion = $this->CompetenceCriterion->read();

        if (!$competence_criterion) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        $this->CompetenceCriterion->delete($id);
        $this->Session->setFlash('El criterio ha sido eliminada correctamente');
        $this->redirect(array('controller' => 'competence_goals', 'action' => 'view', $competence_criterion['goal_id']));
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
        $deletedRubrics = [];
        $deletedSubjects = [];
        $deletedTeachers = [];

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
                    $this->data['CompetenceCriterionRubric'] = [];
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
                    $this->data['CompetenceCriterionSubject'] = [];
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
                    $this->data['CompetenceCriterionTeacher'] = [];
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
        $administrator_actions = array('add', 'add_to_goal', 'edit', 'delete');
        $teacher_actions = array('edit');

        $this->set('section', 'courses');

        if ((array_search($this->params['action'], $administrator_actions) !== false) && ($this->Auth->user('type') !== "Administrador")) {
            if ((array_search($this->params['action'], $teacher_actions) !== false) && ($this->Auth->user('type') === "Profesor")) {
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
