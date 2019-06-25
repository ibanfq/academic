<?php
class CompetenceController extends AppController {
    var $name = 'Competence';
    var $uses = array('Competence');

    var $paginate = array(
        'limit' => 10,
        'order' => array('Competence.code' => 'asc'),
    );

    function by_course($course_id)
    {
        $course_id = $course_id === null ? null : intval($course_id);

        if (is_null($course_id)) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        $course = $this->Competence->Course->find('first', array(
            'recursive' => -1,
            'conditions' => array('Course.id' => $course_id)
        ));

        if (!$course) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        $competence_joins = array();

        $competence_conditions = array(
            'AND' => array(
                'Competence.course_id' => $course_id
            )
        );

        $user_id = $this->Auth->user('id');

        if ($this->Auth->user('type') === "Profesor" || $this->Auth->user('type') === "Estudiante") {
            $competence_joins[] = array(
                'table' => 'competence_goals',
                'alias' => 'CompetenceGoal',
                'type'  => 'INNER',
                'conditions' => array(
                    'CompetenceGoal.competence_id = Competence.id'
                )
            );

            $competence_joins[] = array(
                'table' => 'competence_criteria',
                'alias' => 'CompetenceCriterion',
                'type'  => 'INNER',
                'conditions' => array(
                    'CompetenceCriterion.goal_id = CompetenceGoal.id'
                )
            );

            $competence_joins[] = array(
                'table' => 'competence_criterion_subjects',
                'alias' => 'CompetenceCriterionSubject',
                'type'  => 'LEFT',
                'conditions' => array(
                    'CompetenceCriterionSubject.criterion_id = CompetenceCriterion.id'
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

            $competence_conditions['AND'][] = array(
                'OR' => array(
                    array('Subject.coordinator_id' => $user_id),
                    array('Subject.practice_responsible_id' => $user_id),
                    array('CompetenceCriterionTeacher.teacher_id' => $user_id)
                )
            );
        } else if ($this->Auth->user('type') === "Estudiante") {
            $competence_joins[] = array(
                'table' => 'subjects_users',
                'alias' => 'SubjectUser',
                'type'  => 'INNER',
                'conditions' => array(
                    'SubjectUser.subject_id = CompetenceCriterionSubject.subject_id',
                    'SubjectUser.user_id' => $user_id
                )
            );
        }

        $competence = $this->Competence->find('all', array(
            'fields' => array('distinct Competence.*'),
            'recursive' => -1,
            'joins' => $competence_joins,
            'conditions' => $competence_conditions,
            'order' => array('Competence.code asc')
        ));

        $this->set('competence', $competence);
        $this->set('course', $course);
    }

    function by_subject($subject_id)
    {
        $subject_id = $subject_id === null ? null : intval($subject_id);

        if (is_null($subject_id)) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        $subject = $this
            ->Competence
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

        $course = $this->Competence->Course->find('first', array(
            'recursive' => -1,
            'conditions' => array('Course.id' => $subject['Subject']['course_id'])
        ));

        $competence_joins = array(
            array(
                'table' => 'competence_goals',
                'alias' => 'CompetenceGoal',
                'type'  => 'INNER',
                'conditions' => array(
                    'CompetenceGoal.competence_id = Competence.id'
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
                    'CompetenceCriterionSubject.criterion_id = CompetenceCriterion.id',
                    'CompetenceCriterionSubject.subject_id' => $subject_id
                )
            )
        );

        $competence_conditions = array();

        $user_id = $this->Auth->user('id');

        if ($this->Auth->user('type') === "Profesor")
        {
            if ($user_id !== $subject['Subject']['coordinator_id']
                && $user_id !== $subject['Subject']['practice_responsible_id']
            ) {
                $competence_joins[] = array(
                    'table' => 'competence_criterion_teachers',
                    'alias' => 'CompetenceCriterionTeacher',
                    'type'  => 'INNER',
                    'conditions' => array(
                        'CompetenceCriterionTeacher.criterion_id = CompetenceCriterion.id',
                        'CompetenceCriterionTeacher.teacher_id' => $user_id
                    )
                );
            }
        } else if ($this->Auth->user('type') === "Estudiante") {
            $competence_joins[] = array(
                'table' => 'subjects_users',
                'alias' => 'SubjectUser',
                'type'  => 'INNER',
                'conditions' => array(
                    'SubjectUser.subject_id = CompetenceCriterionSubject.subject_id',
                    'SubjectUser.user_id' => $user_id
                )
            );
        }

        $competence = $this->Competence->find('all', array(
            'fields' => array('distinct Competence.*'),
            'recursive' => -1,
            'joins' => $competence_joins,
            'conditions' => $competence_conditions,
            'order' => array('Competence.code asc')
        ));

        $this->set('competence', $competence);
        $this->set('course', $course);
        $this->set('subject', $subject);
    }

    function by_student($student_id)
    {
        $student_id = $student_id === null ? null : intval($student_id);

        if (is_null($student_id)) {
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

        $course = $this->Competence->Course->current();

        if (!$course) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        $course['Course'] = $course;

        $competence_joins = array(
            array(
                'table' => 'competence_goals',
                'alias' => 'CompetenceGoal',
                'type'  => 'INNER',
                'conditions' => array(
                    'CompetenceGoal.competence_id = Competence.id'
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
                    'SubjectUser.user_id' => $student_id
                )
            )
        );

        $competence_conditions = array(
            'AND' => array(
                'Competence.course_id' => $course['Course']['id']
            )
        );

        if ($this->Auth->user('type') === "Profesor")
        {
            $user_id = $this->Auth->user('id');

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

            $competence_conditions['AND'][] = array(
                'OR' => array(
                    array('Subject.coordinator_id' => $user_id),
                    array('Subject.practice_responsible_id' => $user_id),
                    array('CompetenceCriterionTeacher.teacher_id' => $user_id)
                )
            );
        }

        $competence = $this->Competence->find('all', array(
            'fields' => array('distinct Competence.*'),
            'recursive' => -1,
            'joins' => $competence_joins,
            'conditions' => $competence_conditions,
            'order' => array('Competence.code asc')
        ));

        $this->set('student', $student);
        $this->set('competence', $competence);
        $this->set('course', $course);
    }

    function add_to_course($course_id = null)
    {
        $course_id = $course_id === null ? null : intval($course_id);

        if (is_null($course_id)) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        $course = $this->Competence->Course->find('first', array(
            'recursive' => -1,
            'conditions' => array('Course.id' => $course_id)
        ));

        if (!$course) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        if (!empty($this->data)) {
            if ($this->Competence->save($this->data)) {
                $this->Session->setFlash('La competencia se ha guardado correctamente');
                $this->redirect(array('controller' => 'competence', 'action' => 'by_course', $this->data['Competence']['course_id']));
            }
        }
        
        $this->set('course', $course);
    }

    function view($id = null)
    {
        $id = $id === null ? null : intval($id);

        if (is_null($id)) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        $competence_joins = array(
            array(
                'table' => 'competence_goals',
                'alias' => 'CompetenceGoal',
                'type'  => 'LEFT',
                'conditions' => array(
                    'CompetenceGoal.competence_id = Competence.id'
                )
            )
        );

        $competence_conditions = array(
            'AND' => array(
                'Competence.id' => $id
            )
        );

        $user_id = $this->Auth->user('id');

        if ($this->Auth->user('type') === "Profesor" || $this->Auth->user('type') === "Estudiante") {
            $competence_joins[] = array(
                'table' => 'competence_criteria',
                'alias' => 'CompetenceCriterion',
                'type'  => 'INNER',
                'conditions' => array(
                    'CompetenceCriterion.goal_id = CompetenceGoal.id'
                )
            );

            $competence_joins[] = array(
                'table' => 'competence_criterion_subjects',
                'alias' => 'CompetenceCriterionSubject',
                'type'  => 'LEFT',
                'conditions' => array(
                    'CompetenceCriterionSubject.criterion_id = CompetenceCriterion.id'
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

            $competence_conditions['AND'][] = array(
                'OR' => array(
                    array('Subject.coordinator_id' => $user_id),
                    array('Subject.practice_responsible_id' => $user_id),
                    array('CompetenceCriterionTeacher.teacher_id' => $user_id)
                )
            );
        } else if ($this->Auth->user('type') === "Estudiante") {
            $competence_joins[] = array(
                'table' => 'subjects_users',
                'alias' => 'SubjectUser',
                'type'  => 'INNER',
                'conditions' => array(
                    'SubjectUser.subject_id = CompetenceCriterionSubject.subject_id',
                    'SubjectUser.user_id' => $user_id
                )
            );
        }

        $competence_result = $this->Competence->find('all', array(
            'recursive' => -1,
            'fields' => array('distinct Competence.*, CompetenceGoal.*'),
            'joins' => $competence_joins,
            'conditions' => $competence_conditions,
            'order' => array('CompetenceGoal.code asc')
        ));

        if (!$competence_result) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        $competence = array(
            'Competence' => Set::extract($competence_result, '0.Competence'),
            'CompetenceGoal' => Set::filter(Set::extract($competence_result, '{n}.CompetenceGoal'))
        );

        $this->set('competence', $competence);
        $this->set('course', $this->Competence->Course->find('first', array(
            'recursive' => -1,
            'conditions' => array('Course.id' => $competence['Competence']['course_id'])
        )));
    }

    function view_by_subject($subject_id = null, $id = null)
    {
        $subject_id = $subject_id === null ? null : intval($subject_id);
        $id = $id === null ? null : intval($id);

        if (is_null($subject_id) || is_null($id)) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        $subject = $this
            ->Competence
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

        $competence_joins = array(
            array(
                'table' => 'competence_goals',
                'alias' => 'CompetenceGoal',
                'type'  => 'INNER',
                'conditions' => array(
                    'CompetenceGoal.competence_id = Competence.id'
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
                    'CompetenceCriterionSubject.criterion_id = CompetenceCriterion.id',
                    'CompetenceCriterionSubject.subject_id' => $subject_id
                )
            )
        );

        $competence_conditions = array(
            'AND' => array(
                'Competence.id' => $id
            )
        );

        $user_id = $this->Auth->user('id');

        if ($this->Auth->user('type') === "Profesor")
        {
            if ($user_id !== $subject['Subject']['coordinator_id']
                && $user_id !== $subject['Subject']['practice_responsible_id']
            ) {
                $competence_joins[] = array(
                    'table' => 'competence_criterion_teachers',
                    'alias' => 'CompetenceCriterionTeacher',
                    'type'  => 'INNER',
                    'conditions' => array(
                        'CompetenceCriterionTeacher.criterion_id = CompetenceCriterion.id',
                        'CompetenceCriterionTeacher.teacher_id' => $user_id
                    )
                );
            }
        } else if ($this->Auth->user('type') === "Estudiante") {
            $competence_joins[] = array(
                'table' => 'subjects_users',
                'alias' => 'SubjectUser',
                'type'  => 'INNER',
                'conditions' => array(
                    'SubjectUser.subject_id = CompetenceCriterionSubject.subject_id',
                    'SubjectUser.user_id' => $user_id
                )
            );
        }

        $competence_result = $this->Competence->find('all', array(
            'recursive' => -1,
            'fields' => array('distinct Competence.*, CompetenceGoal.*'),
            'joins' => $competence_joins,
            'conditions' => $competence_conditions,
            'order' => array('CompetenceGoal.code asc')
        ));

        if (!$competence_result) {
            $this->redirect(array('controller' => 'competence', 'action' => 'by_subject', $subject_id));
        }

        $competence = array(
            'Competence' => Set::extract($competence_result, '0.Competence'),
            'CompetenceGoal' => Set::filter(Set::extract($competence_result, '{n}.CompetenceGoal'))
        );

        $this->set('competence', $competence);
        $this->set('subject', $subject);
        $this->set('course', $this->Competence->Course->find('first', array(
            'recursive' => -1,
            'conditions' => array('Course.id' => $competence['Competence']['course_id'])
        )));
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

        $course = $this->Competence->Course->current();

        if (!$course) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        $course['Course'] = $course;

        $competence_joins = array(
            array(
                'table' => 'competence_goals',
                'alias' => 'CompetenceGoal',
                'type'  => 'INNER',
                'conditions' => array(
                    'CompetenceGoal.competence_id = Competence.id'
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
                    'SubjectUser.user_id' => $student_id
                )
            )
        );

        $competence_conditions = array(
            'AND' => array(
                'Competence.id' => $id
            )
        );

        if ($this->Auth->user('type') === "Profesor")
        {
            $user_id = $this->Auth->user('id');

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

            $competence_conditions['AND'][] = array(
                'OR' => array(
                    array('Subject.coordinator_id' => $user_id),
                    array('Subject.practice_responsible_id' => $user_id),
                    array('CompetenceCriterionTeacher.teacher_id' => $user_id)
                )
            );
        }

        $competence_result = $this->Competence->find('all', array(
            'recursive' => -1,
            'fields' => array('distinct Competence.*, CompetenceGoal.*'),
            'joins' => $competence_joins,
            'conditions' => $competence_conditions,
            'order' => array('CompetenceGoal.code asc')
        ));

        if (!$competence_result) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        $competence = array(
            'Competence' => Set::extract($competence_result, '0.Competence'),
            'CompetenceGoal' => Set::filter(Set::extract($competence_result, '{n}.CompetenceGoal'))
        );

        $this->set('student', $student);
        $this->set('competence', $competence);
        $this->set('course', $this->Competence->Course->find('first', array(
            'recursive' => -1,
            'conditions' => array('Course.id' => $competence['Competence']['course_id'])
        )));
    }

    function edit($id = null)
    {
        $id = $id === null ? null : intval($id);

        if (is_null($id)) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        if (empty($this->data)) {
            $this->data = $this->Competence->find('first', array(
                'recursive' => -1,
                'conditions' => array('Competence.id' => $id)
            ));
            $this->set('competence', $this->data);
            $this->set('course', $this->Competence->Course->find('first', array(
                'recursive' => -1,
                'conditions' => array('Course.id' => $this->data['Competence']['course_id'])
            )));
        } else {
            if ($this->Competence->save($this->data)) {
                $this->Session->setFlash('La competencia se ha modificado correctamente.');
                $this->redirect(array('action' => 'view', $id));
            } else {
                $this->set('competence', $this->data);
                $this->set('course', $this->Competence->Course->find('first', array(
                    'recursive' => -1,
                    'conditions' => array('Course.id' => $this->data['Competence']['course_id'])
                )));
            }
        }
    }

    function delete($id = null)
    {
        $id = $id === null ? null : intval($id);

        if (is_null($id)) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }
        
        $competence = $this->Competence->find('first', array(
            'recursive' => -1,
            'conditions' => array('Competence.id' => $id)
        ));

        if (!$competence) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        $this->Competence->delete($id);
        $this->Session->setFlash('La competencia ha sido eliminada correctamente');
        $this->redirect(array('action' => 'by_course', $competence['Competence']['course_id']));
    }
  
    function _authorize()
    {
        parent::_authorize();
        $administrator_actions = array(
            'by_course', 'by_subject', 'by_student',
            'view', 'view_by_subject','view_by_student',
            'add_to_course', 'edit', 'delete'
        );
        $teacher_actions = array(
            'by_course', 'by_subject', 'by_student',
            'view', 'view_by_subject', 'view_by_student'
        );
        $student_actions = array(
            'by_course', 'by_subject', 'view', 'view_by_subject'
        );

        $this->set('section', 'competence');

        if ((array_search($this->params['action'], $administrator_actions) !== false) && ($this->Auth->user('type') != "Administrador")) {
            if ((array_search($this->params['action'], $teacher_actions) !== false) && ($this->Auth->user('type') === "Profesor")) {
                return true;
            }
            if (array_search($this->params['action'], $student_actions) !== false && ($this->Auth->user('type') === "Estudiante")) {
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
