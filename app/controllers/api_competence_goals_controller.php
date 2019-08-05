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
            'by_student', 'grade_by_student'
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
        $db = $this->CompetenceGoal->getDataSource();
        $this->loadModel('User');

        $authIsTeacher = in_array($this->Auth->user('type'), array('Profesor', 'Administrador'));

        if ($authIsTeacher && $teacher_id === 'me') {
            if ($authIsTeacher) {
                $teacher = $this->Auth->user();
                $teacher_id = $this->Auth->user('id');
            }
        } else {
            $teacher_id = $teacher_id === null ? null : intval($teacher_id);
            
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
        }

        if (empty($teacher)) {
            $this->Api->setError('No se ha podido encontrar al profesor.', 404);
            $this->Api->respond($this);
            return;
        }

        
        if ($this->Auth->user('type') === 'Estudiante') {
            $student_id = $this->Auth->user('id');
        } else {
            $student_id = $authIsTeacher ? $this->Api->getParameter('student_id', 'integer') : null;
            if (!empty($student_id)) {
                $student = $this->User->find('first', array(
                    'recursive' => -1,
                    'conditions' => array(
                        'User.id' => $student_id,
                        'User.type' => 'Estudiante'
                    )
                ));
    
                if (empty($student)) {
                    $this->Api->setError('No se ha podido encontrar al estudiante.', 404);
                    $this->Api->respond($this);
                    return;
                }
            }
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

        $fields = array('distinct CompetenceGoal.*');
        $order = array('Competence.code asc', 'CompetenceGoal.code asc', 'CompetenceCriterion.code asc');

        $contain = $this->Api->getParameter('contain', 'string');

        if ($contain === null) {
            $fields[] = 'Competence.*';
        } elseif (trim($contain) !== '') {
            foreach (explode(',', $contain) as $table) {
                switch (trim($table)) {
                    case 'Competence':
                        $fields[] = 'Competence.*';
                        break;
                    case 'CompetenceCriterion':
                        $fields[] = 'CompetenceCriterion.*';
                        break;
                    case 'CompetenceCriterionRubric':
                        $fields[] = 'CompetenceCriterionRubric.*';
                        $order[] = 'CompetenceCriterionRubric.value asc';
                        $competence_goal_joins[] = array(
                            'table' => 'competence_criterion_rubrics',
                            'alias' => 'CompetenceCriterionRubric',
                            'type'  => 'LEFT',
                            'conditions' => array(
                                'CompetenceCriterionRubric.criterion_id = CompetenceCriterion.id'
                            )
                        );
                        break;
                    case 'CompetenceCriterionGrade':
                        if (!$student_id) {
                            $this->Api->setError('CompetenceCriterionGrade contain value requires a student_id parameter value.', 400);
                            $this->Api->respond($this);
                            return;
                        }
                        $fields[] = 'CompetenceCriterionGrade.*';
                        $competence_goal_joins[] = array(
                            'table' => 'competence_criterion_grades',
                            'alias' => 'CompetenceCriterionGrade',
                            'type'  => 'LEFT',
                            'conditions' => array(
                                'CompetenceCriterionGrade.criterion_id = CompetenceCriterion.id',
                                'CompetenceCriterionGrade.student_id' => $student_id
                            )
                        );
                        break;
                    default:
                        $this->Api->setError('Invalid Contain parameter value: ' . trim($table) . '.', 400);
                        $this->Api->respond($this);
                        return;
                }
            }
        }

        if ($student_id) {
            $fields[] = "IFNULL((SELECT 1 FROM competence_goal_requests _request WHERE _request.goal_id = CompetenceGoal.id AND _request.student_id = {$db->value($student_id)} AND _request.completed IS NULL AND _request.canceled IS NULL AND _request.rejected IS NULL limit 1), 0) as has_requests";
            $fields[] = "IFNULL((SELECT 0 FROM competence_goals _goal INNER JOIN competence_criteria _criterion ON _criterion.goal_id = _goal.id LEFT JOIN competence_criterion_grades _grade ON _grade.criterion_id = _criterion.id AND _grade.student_id = {$db->value($student_id)} WHERE _goal.id = CompetenceGoal.id AND _grade.rubric_id IS NULL limit 1), 1) as grade_completed";
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

        $goal_id = $this->Api->getParameter('goal_id', 'integer');
        if (!empty($goal_id)) {
            $conditions['AND'][] = array('CompetenceGoal.id' => $goal_id);
        }

        $groupPath = trim($this->Api->getParameter('group_path', 'string'));
        if (!empty($groupPath) && !preg_match('/^\w+\.\w+$/', $groupPath)) {
            var_dump($groupPath);exit;
            $this->Api->setError('Invalid group_path parameter value.', 400);
            $this->Api->respond($this);
            return;
        }

        $competence_goals = $this->CompetenceGoal->find('all', array(
            'fields' => $fields,
            'recursive' => -1,
            'joins' => $competence_goal_joins,
            'conditions' => $conditions,
            'order' => $order
        ));
        
        if (empty($groupPath)) {
            $this->Api->setData($competence_goals);
        } else {
            $groupIndex = Set::extract("{n}.$groupPath", $competence_goals);
            $out = array();
            foreach ($competence_goals as $i => $val) {
                $group = isset($groupIndex[$i]) ? $groupIndex[$i] : 0;
                if (!isset($out[$group])) {
                    $out[$group] = array();
                }
                $out[$group][] = $val;
            }
            $this->Api->setData(array_values($out));
        }

        $this->Api->respond($this);
    }

    function by_student($student_id = null, $id = null)
    {
        $student_id = $student_id === null ? null : intval($student_id);
        $id = $id === null ? null : intval($id);

        if (is_null($student_id) || is_null($id)) {
            $this->Api->setError('Invalid request.', 400);
            $this->Api->respond($this);
            return;
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
                'CompetenceGoal.id' => $id
            )
        );

        if ($this->Auth->user('type') === "Profesor") {
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
            'fields' => array('distinct CompetenceGoal.*, CompetenceCriterion.*, CompetenceCriterionGrade.*'),
            'joins' => $competence_goal_joins,
            'conditions' => $competence_goal_conditions,
            'order' => array('CompetenceCriterion.code asc')
        ));

        if (!$competence_goal_result) {
            $this->Api->setError('No se ha podido encontrar el objetivo del estudiante', 404);
            $this->Api->respond($this);
            return;
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
                'conditions' => array('CompetenceCriterionRubric.criterion_id' => $competence_criterion['id']),
                'order' => array('CompetenceCriterionRubric.value')
            ));
            $competence_goal['CompetenceCriterion'][$i]['CompetenceCriterionRubric'] = Set::combine(
                $rubrics,
                '{n}.CompetenceCriterionRubric.id',
                '{n}.CompetenceCriterionRubric'
            );
        }

        $this->Api->setData($competence_goal);
        $this->Api->respond($this);
    }

    function grade_by_student($student_id = null, $id = null)
    {
        $student_id = $student_id === null ? null : intval($student_id);
        $id = $id === null ? null : intval($id);
        $data = array('CompetenceCriterionGrade' => $this->Api->getParameter('CompetenceCriterionGrade'));
        $competence_goal_request_id = $this->Api->getParameter('competence_goal_request_id', 'integer');
        $competence_goal_request = null;

        if (is_null($student_id) || is_null($id) || empty($data)) {
            $this->Api->setError('Invalid request.', 400);
            $this->Api->respond($this);
            return;
        }

        $response = $this->Api->call('GET', '/api/competence_goals/by_student/' . urlencode($student_id) . '/' . urlencode($id));
        if ($response['status'] === 'error') {
            $this->Api->setError($response['message'], $response['status']);
            $this->Api->respond($this);
            return;
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
            $this->Api->setError('No se ha podido encontrar al estudiante.', 404);
            $this->Api->respond($this);
            return;
        }

        $competence = $this->CompetenceGoal->Competence->find('first', array(
            'recursive' => -1,
            'conditions' => array('Competence.id' => $competence_goal['CompetenceGoal']['competence_id'])
        ));

        if (!$competence) {
            $this->Api->setError('No se ha podido encontrar la competencia.', 404);
            $this->Api->respond($this);
            return;
        }

        if ($competence_goal_request_id !== null) {
            $competence_goal_request = $this->CompetenceGoal->CompetenceGoalRequest->find('first', array(
                'recursive' => -1,
                'conditions' => array(
                    'id' => $competence_goal_request_id,
                    'teacher_id' => $this->Auth->user('id'),
                    'completed IS NULL AND canceled IS NULL AND rejected IS NULL'
                )
            ));

            if (!$competence_goal_request) {
                $this->Api->setError('La solicitud de evaluación ya no se encuentra disponible.', 404);
                $this->Api->respond($this);
                return;
            }
        }

        // Get valid criterion rubrics
        $competence_criterion_rubric_ids = set::combine(
            $competence_goal['CompetenceCriterion'],
            '{n}.id',
            '{n}.CompetenceCriterionRubric.{n}.id'
        );

        // Get current values
        $competence_grades = array(
            'CompetenceCriterionGrade' => set::combine($competence_goal, 'CompetenceCriterion.{n}.id', 'CompetenceCriterion.{n}.CompetenceCriterionGrade')
        );

        // Read input data
        $data_criterion_rubrics = Set::combine(
            isset($data['CompetenceCriterionGrade']) ? $data['CompetenceCriterionGrade'] : array(),
            '{n}.criterion_id',
            '{n}.rubric_id'
        );
        
        // Process the input data
        // Initialize with current values

        $filteredData = Set::extract('/CompetenceCriterionGrade', $competence_grades);
        $deletedGrades = array();
        // Loop over each criterion to fill $filteredData with the input data
        foreach ($competence_goal['CompetenceCriterion'] as $i => $criterion) {
            $criterion_id = $criterion['id'];

            if (isset($data_criterion_rubrics[$criterion_id])) {
                $rubric_id = trim($data_criterion_rubrics[$criterion_id]);
                
                if ($rubric_id === '') {
                    // If not rubric_id
                    if ($competence_goal_request) {
                        $this->Api->setError('Debe calificar todos los criterios.', 400);
                        $this->Api->respond($this);
                        return;
                    }
                    // Remove it
                    unset($filteredData[$i]);
                    $competence_goal['CompetenceCriterion'][$i]['CompetenceCriterionGrade'] = null;
                    if (isset($criterion['CompetenceCriterionGrade']['id'])) {
                        $deletedGrades[] = $criterion['CompetenceCriterionGrade']['id'];
                    }
                } elseif (in_array($rubric_id, $competence_criterion_rubric_ids[$criterion_id])) {
                    // Save if is valid rubric
                    $filteredData[$i]['CompetenceCriterionGrade']['student_id'] = $student_id;
                    $filteredData[$i]['CompetenceCriterionGrade']['criterion_id'] = $criterion_id;
                    $filteredData[$i]['CompetenceCriterionGrade']['rubric_id'] = $rubric_id;
                    unset($filteredData[$i]['CompetenceCriterionGrade']['modified']);
                    $competence_goal['CompetenceCriterion'][$i]['CompetenceCriterionGrade'] = $filteredData[$i]['CompetenceCriterionGrade'];
                }
            } elseif (!isset($filteredData[$i]['CompetenceCriterionGrade']['criterion_id'])) {
                // If no in form and not persisted yet in database
                if ($competence_goal_request) {
                    $this->Api->setError('Debe calificar todos los criterios.', 400);
                    $this->Api->respond($this);
                    return;
                }
                // Remove from filteredData
                unset($filteredData[$i]);
                $competence_goal['CompetenceCriterion'][$i]['CompetenceCriterionGrade'] = null;
            }
        }

        // Save the data
        if (empty($filteredData) || $this->CompetenceGoal->CompetenceCriterion->CompetenceCriterionGrade->saveAll($filteredData)) {
            if (!empty($deletedGrades)) {
                $this->CompetenceGoal->CompetenceCriterion->CompetenceCriterionGrade->deleteAll(
                    array('CompetenceCriterionGrade.id' => $deletedGrades)
                );
            }

            if ($competence_goal_request) {
                $competence_goal_request['CompetenceGoalRequest']['completed'] = date('Y-m-d H:i:s');
                unset($competence_goal_request['CompetenceGoalRequest']['modified']);
                $this->CompetenceGoal->CompetenceGoalRequest->save($competence_goal_request);
                $this->Email->reset();
                $this->Email->from = 'Academic <noreply@ulpgc.es>';
                $this->Email->to = $student['User']['username'];
                $this->Email->subject = "Objetivo evaluado por el profesor solicitado";
                $this->Email->sendAs = 'both';
                $this->Email->template = Configure::read('app.email.competence_goal_request_completed')
                    ? Configure::read('app.email.competence_goal_request_completed')
                    : 'competence_goal_request_completed';
                $this->set('competence', $competence);
                $this->set('competence_goal', $competence_goal);
                $this->set('teacher', $this->Auth->user());
                $this->Email->send();
            }
        } else {
            $this->Api->setError('No se ha podido guardar la evaluación', 400);
        }
        $this->Api->respond($this);
    }
}
