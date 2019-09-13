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
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        $course = $this->Competence->Course->find('first', array(
            'fields' => array('Course.*', 'Degree.*'),
            'joins' => array(
                array(
                    'table' => 'degrees',
                    'alias' => 'Degree',
                    'type' => 'INNER',
                    'conditions' => 'Degree.id = Course.degree_id'
                )
            ),
            'conditions' => array(
                'Course.id' => $course_id,
                'Course.institution_id' => Environment::institution('id')
            ),
            'recursive' => -1
        ));

        if (!$course) {
            $this->Session->setFlash('No se ha podido acceder al curso.');
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
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
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
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
            $this->Session->setFlash('No se ha podido acceder a la asignatura.');
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        $course = $this->Competence->Course->find('first', array(
            'fields' => array('Course.*', 'Degree.*'),
            'joins' => array(
                array(
                    'table' => 'degrees',
                    'alias' => 'Degree',
                    'type' => 'INNER',
                    'conditions' => 'Degree.id = Course.degree_id'
                )
            ),
            'conditions' => array(
                'Course.id' => $subject['Subject']['course_id'],
                'Course.institution_id' => Environment::institution('id')
            ),
            'recursive' => -1
        ));

        if (!$course) {
            $this->Session->setFlash('No se ha podido acceder al curso.');
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
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
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        $this->loadModel('User');

        $student = $this->User->find('first', array(
            'recursive' => -1,
            'joins' => array(
                array(
                    'table' => 'users_institutions',
                    'alias' => 'UserInstitution',
                    'type' => 'INNER',
                    'conditions' => array(
                        'UserInstitution.user_id = User.id',
                        'UserInstitution.institution_id' => Environment::institution('id'),
                        'UserInstitution.active'
                    )
                )
            ),
            'conditions' => array(
                'User.id' => $student_id,
                'User.type' => 'Estudiante'
            )
        ));

        if (!$student) {
            $this->Session->setFlash('No se ha podido acceder al usuario.');
            $this->redirect(array('controller' => 'users', 'action' => 'index'));
        }

        $courses = $this->Competence->Course->current();

        if (!$courses) {
            $this->Session->setFlash('No se ha podido acceder al curso actual.');
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        $courses_id = Set::extract($courses, '{n}.id');

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
                'Competence.course_id' => $courses_id
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
    }

    function add_to_course($course_id = null)
    {
        $course_id = $course_id === null ? null : intval($course_id);

        if (is_null($course_id)) {
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        $course = $this->Competence->Course->find('first', array(
            'recursive' => -1,
            'conditions' => array(
                'Course.id' => array('Course.id' => $course_id),
                'Course.institution_id' => Environment::institution('id')
            )
        ));

        if (!$course) {
            $this->Session->setFlash('No se ha podido acceder al curso.');
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        if (!empty($this->data)) {
            $this->data['Competence']['course_id'] = $course_id;
            
            if ($this->Competence->save($this->data)) {
                $this->Session->setFlash('La competencia se ha guardado correctamente');
                $this->redirect(array('controller' => 'competence', 'action' => 'by_course', $course_id));
            }
        }
        
        $this->set('course', $course);
    }

    function view($id = null)
    {
        $id = $id === null ? null : intval($id);

        if (is_null($id)) {
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
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
            $this->Session->setFlash('No se ha podido acceder a la competencia.');
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        $competence = array(
            'Competence' => Set::extract($competence_result, '0.Competence'),
            'CompetenceGoal' => Set::filter(Set::extract($competence_result, '{n}.CompetenceGoal'))
        );

        $course = $this->Competence->Course->find('first', array(
            'recursive' => -1,
            'conditions' => array(
                'Course.id' => $competence['Competence']['course_id'],
                'Course.institution_id' => Environment::institution('id')
            )
        ));

        if (!$course) {
            $this->Session->setFlash('No se ha podido acceder al curso de la competencia.');
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        $this->set('competence', $competence);
        $this->set('course', $course);
    }

    function view_by_subject($subject_id = null, $id = null)
    {
        $subject_id = $subject_id === null ? null : intval($subject_id);
        $id = $id === null ? null : intval($id);

        if (is_null($subject_id) || is_null($id)) {
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
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
            $this->Session->setFlash('No se ha podido acceder a la asignatura.');
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        $course = $this->Competence->Course->find('first', array(
            'recursive' => -1,
            'conditions' => array(
                'Course.id' => $subject['Subject']['course_id'],
                'Course.institution_id' => Environment::institution('id')
            )
        ));

        if (!$course) {
            $this->Session->setFlash('No se ha podido acceder al curso.');
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
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
        $this->set('course', $course);
    }

    function view_by_student($student_id = null, $id = null)
    {
        $student_id = $student_id === null ? null : intval($student_id);
        $id = $id === null ? null : intval($id);

        if (is_null($student_id) || is_null($id)) {
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        $this->loadModel('User');

        $student = $this->User->find('first', array(
            'recursive' => -1,
            'joins' => array(
                array(
                    'table' => 'users_institutions',
                    'alias' => 'UserInstitution',
                    'type' => 'INNER',
                    'conditions' => array(
                        'UserInstitution.user_id = User.id',
                        'UserInstitution.institution_id' => Environment::institution('id'),
                        'UserInstitution.active'
                    )
                )
            ),
            'conditions' => array(
                'User.id' => $student_id,
                'User.type' => 'Estudiante'
            )
        ));

        if (!$student) {
            $this->Session->setFlash('No se ha podido acceder al usuario.');
            $this->redirect(array('controller' => 'users', 'action' => 'index'));
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
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        $competence = array(
            'Competence' => Set::extract($competence_result, '0.Competence'),
            'CompetenceGoal' => Set::filter(Set::extract($competence_result, '{n}.CompetenceGoal'))
        );

        $course = $this->Competence->Course->find('first', array(
            'fields' => array('Course.*', 'Degree.*'),
            'joins' => array(
                array(
                    'table' => 'degrees',
                    'alias' => 'Degree',
                    'type' => 'INNER',
                    'conditions' => 'Degree.id = Course.degree_id'
                )
            ),
            'conditions' => array(
                'Course.id' => $competence['Competence']['course_id'],
                'Course.institution_id' => Environment::institution('id')
            ),
            'recursive' => -1
        ));

        if (!$course) {
            $this->Session->setFlash('No se ha podido acceder al curso actual.');
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        $course['Course'] = $course;

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
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        $competence = $this->Competence->find('first', array(
            'recursive' => -1,
            'conditions' => array('Competence.id' => $id)
        ));

        if (!$competence) {
            $this->Session->setFlash('No se ha podido acceder a la competencia.');
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        $course = $this->Competence->Course->find('first', array(
            'recursive' => -1,
            'conditions' => array(
                'Course.id' => $competence['Competence']['course_id'],
                'Course.institution_id' => Environment::institution('id')
            )
        ));

        if (!$course) {
            $this->Session->setFlash('No se ha podido acceder al curso.');
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        if (empty($this->data)) {
            $this->data = $competence;

            $this->set('competence', $competence);
            $this->set('course', $course);
        } else {
            $this->data['Competence']['id'] = $id;
            $this->data['Competence']['course_id'] = $competence['Competence']['course_id'];

            if ($this->Competence->save($this->data)) {
                $this->Session->setFlash('La competencia se ha modificado correctamente.');
                $this->redirect(array('action' => 'view', $id));
            } else {
                $this->set('competence', $competence);
                $this->set('course', $course);
            }
        }
    }

    function delete($id = null)
    {
        $id = $id === null ? null : intval($id);

        if (is_null($id)) {
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }
        
        $competence = $this->Competence->find('first', array(
            'recursive' => -1,
            'conditions' => array('Competence.id' => $id)
        ));

        if (!$competence) {
            $this->Session->setFlash('No se ha podido acceder a la competencia.');
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        $course = $this->Competence->Course->find('first', array(
            'recursive' => -1,
            'conditions' => array(
                'Course.id' => $competence['Competence']['course_id'],
                'Course.institution_id' => Environment::institution('id')
            )
        ));

        if (!$course) {
            $this->Session->setFlash('No se ha podido acceder al curso.');
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        $this->Competence->delete($id);
        $this->Session->setFlash('La competencia ha sido eliminada correctamente');
        $this->redirect(array('action' => 'by_course', $competence['Competence']['course_id']));
    }

    function stats_by_subject($course_id = null, $subject_id = null)
    {
        $course_id = $course_id === null ? null : intval($course_id);
        $subject_id = $subject_id === null ? null : intval($subject_id);

        if (is_null($course_id)) {
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        $course = $this->Competence->Course->find('first', array(
            'recursive' => -1,
            'conditions' => array(
                'Course.id' => $course_id,
                'Course.institution_id' => Environment::institution('id')
            )
        ));

        if (!$course) {
            $this->Session->setFlash('No se ha podido acceder al curso.');
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        if ($subject_id) {
            $subject = $this
                ->Competence
                ->CompetenceGoal
                ->CompetenceCriterion
                ->CompetenceCriterionSubject
                ->Subject->find(
                    'first',
                    array(
                        'recursive' => -1,
                        'conditions' => array(
                            'Subject.id' => $subject_id,
                            'Subject.course_id' => $course_id
                        )
                    )
                );

                if (!$subject) {
                    $this->Session->setFlash('No se ha podido acceder a la asignatura.');
                    $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
                }

            if ($this->Auth->user('type') === "Profesor") {
                $coordinator_id = $subject['Subject']['coordinator_id'];
                $responsible_id = $subject['Subject']['practice_responsible_id'];
        
                if ($coordinator_id != $this->Auth->user('id') && $responsible_id != $this->Auth->user('id')) {
                    $this->Session->setFlash('Para poder ver la evaluación debes ser el coordinador o el responsable de prácticas de la asignatura');
                    $this->redirect(array('controller' => 'competence', 'action' => 'by_course', $course['Course']['id']));
                }
            }
        }

        $stats = $this->_get_subject_stats($course_id, $subject_id);

        $subjects = Set::combine($stats, '{n}.Subject.id', '{n}.Subject');
        $subjects_stats = Set::combine($stats, '{n}.Student.id', '{n}', '{n}.Subject.id');

        $this->set('course', $course);
        $this->set('subject', $subject_id ? $subject : null);
        $this->set('subjects', $subjects);
        $this->set('subjects_stats', $subjects_stats);
    }

    function export_stats_by_subject($course_id = null, $subject_id = null)
    {
        $course_id = $course_id === null ? null : intval($course_id);
        $subject_id = $subject_id === null ? null : intval($subject_id);

        if (is_null($course_id)) {
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        $course = $this->Competence->Course->find('first', array(
            'recursive' => -1,
            'conditions' => array(
                'Course.id' => $course_id,
                'Course.institution_id' => Environment::institution('id')
            )
        ));

        if (!$course) {
            $this->Session->setFlash('No se ha podido acceder al curso.');
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        if ($subject_id) {
            $subject = $this
                ->Competence
                ->CompetenceGoal
                ->CompetenceCriterion
                ->CompetenceCriterionSubject
                ->Subject->find(
                    'first',
                    array(
                        'recursive' => -1,
                        'conditions' => array(
                            'Subject.id' => $subject_id,
                            'Subject.course_id' => $course_id
                        )
                    )
                );

            if (!$subject) {
                $this->Session->setFlash('No se ha podido acceder a la asignatura.');
                $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
            }
        }

        $stats = $this->_get_subject_stats($course_id, $subject_id);

        $response = "Código;Nombre;Estudiante;Dni;Calificación\n";

        foreach ($stats as $row):
            $response .= '"' . str_replace('"', '""', $row['Subject']['code']) . '"';
            $response .= ';"' . str_replace('"', '""', $row['Subject']['name']) . '"';
            $response .= ';"' . str_replace('"', '""', $row['Student']['dni']) . '"';
            $response .= ';"' . str_replace('"', '""', "{$row['Student']['first_name']} {$row['Student']['last_name']}") . '"';
            $response .= ';"' . number_format($row[0]['total'], 2, ',', '') . '"';
            $response .= "\n";
        endforeach;

        $this->set('response', $response);
        $this->set('filename', 'Estadisticas_asignatura.csv');

        $this->render('export_stats_by_subject', 'download');
    }

    function stats_by_student($course_id = null, $student_id = null)
    {
        $course_id = $course_id === null ? null : intval($course_id);
        $student_id = $student_id === null ? null : intval($student_id);

        if (is_null($course_id) || is_null($student_id)) {
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        $course = $this->Competence->Course->find('first', array(
            'recursive' => -1,
            'conditions' => array(
                'Course.id' => $course_id,
                'Course.institution_id' => Environment::institution('id')
            )
        ));

        if (!$course) {
            $this->Session->setFlash('No se ha podido acceder al curso.');
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        $this->loadModel('User');

        $student = $this->User->find('first', array(
            'recursive' => -1,
            'joins' => array(
                array(
                    'table' => 'users_institutions',
                    'alias' => 'UserInstitution',
                    'type' => 'INNER',
                    'conditions' => array(
                        'UserInstitution.user_id = User.id',
                        'UserInstitution.institution_id' => Environment::institution('id'),
                        'UserInstitution.active'
                    )
                )
            ),
            'conditions' => array(
                'User.id' => $student_id,
                'User.type' => 'Estudiante'
            )
        ));

        if (!$student) {
            $this->Session->setFlash('No se ha podido acceder al usuario.');
            $this->redirect(array('controller' => 'users', 'action' => 'index'));
        }

        $stats = $this->_get_student_stats($course_id, $student_id);

        $subjects = Set::combine($stats, '{n}.Subject.id', '{n}.Subject');
        $subjects_stats = Set::combine($stats, '{n}.CompetenceCriterion.id', '{n}', '{n}.Subject.id');

        $this->set('course', $course);
        $this->set('student', $student);
        $this->set('subjects', $subjects);
        $this->set('subjects_stats', $subjects_stats);
    }

    function export_stats_by_student($course_id = null, $student_id = null)
    {
        $course_id = $course_id === null ? null : intval($course_id);
        $student_id = $student_id === null ? null : intval($student_id);

        if (is_null($course_id) || is_null($student_id)) {
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        $course = $this->Competence->Course->find('first', array(
            'recursive' => -1,
            'conditions' => array(
                'Course.id' => $course_id,
                'Course.institution_id' => Environment::institution('id')
            )
        ));

        if (!$course) {
            $this->Session->setFlash('No se ha podido acceder al curso.');
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        $this->loadModel('User');

        $student = $this->User->find('first', array(
            'recursive' => -1,
            'joins' => array(
                array(
                    'table' => 'users_institutions',
                    'alias' => 'UserInstitution',
                    'type' => 'INNER',
                    'conditions' => array(
                        'UserInstitution.user_id = User.id',
                        'UserInstitution.institution_id' => Environment::institution('id'),
                        'UserInstitution.active'
                    )
                )
            ),
            'conditions' => array(
                'User.id' => $student_id,
                'User.type' => 'Estudiante'
            )
        ));

        if (!$student) {
            $this->Session->setFlash('No se ha podido acceder al usuario.');
            $this->redirect(array('controller' => 'users', 'action' => 'index'));
        }

        $stats = $this->_get_student_stats($course_id, $student_id);

        $response = "Código;Asignatura;Criterio;Definición;Calificación,Nivel;Rúbrica\n";

        foreach ($stats as $row):
            $response .= '"' . str_replace('"', '""', $row['Subject']['code']) . '"';
            $response .= ';"' . str_replace('"', '""', $row['Subject']['name']) . '"';
            $response .= ';"' . str_replace('"', '""', $row['CompetenceCriterion']['code']) . '"';
            $response .= ';"' . str_replace('"', '""', $row['CompetenceCriterion']['definition']) . '"';
            $response .= ';"' . number_format($row['CompetenceCriterionRubric']['value'], 2, ',', '') . '"';
            $response .= ';"' . str_replace('"', '""', $row['CompetenceCriterionRubric']['title']) . '"';
            $response .= ';"' . str_replace('"', '""', $row['CompetenceCriterionRubric']['definition']) . '"';
            $response .= "\n";
        endforeach;

        $this->set('response', $response);
        $this->set('filename', "Estadisticas_estudiante_{$student['User']['id']}.csv");

        $this->render('export_stats_by_student', 'download');
    }

    function _get_subject_stats($course_id, $subject_id = null)
    {
        $db = $this->Competence->getDataSource();

        if ($subject_id) {
            $where = "WHERE Subject.id = {$db->value($subject_id)}";
            $group_by = "GROUP BY Student.id";
        } else {
            $where = "WHERE Subject.course_id = {$db->value($course_id)}";
            $group_by = "GROUP BY Subject.id, Student.id";
        }

        if ($this->Auth->user('type') === "Profesor") {
            $user_id = $this->Auth->user('id');
            $where .= " AND (coordinator_id = {$db->value($user_id)} OR practice_responsible_id = {$db->value($user_id)})";
        }

        return $this->Competence->query("
            SELECT Subject.id, Subject.code, Subject.name, Student.id, Student.dni, Student.first_name, Student.last_name, SUM(CompetenceCriterionRubric.value) as total
            FROM competence_criterion_subjects CompetenceCriterionSubject
            INNER JOIN competence_criteria CompetenceCriterion ON CompetenceCriterion.id = CompetenceCriterionSubject.criterion_id
            INNER JOIN subjects Subject ON Subject.id = CompetenceCriterionSubject.subject_id
            LEFT JOIN subjects_users SubjectUser ON SubjectUser.subject_id = CompetenceCriterionSubject.subject_id
            LEFT JOIN users Student ON Student.id = SubjectUser.user_id
            LEFT JOIN competence_criterion_grades CompetenceCriterionGrade ON CompetenceCriterionGrade.criterion_id = CompetenceCriterion.id AND CompetenceCriterionGrade.student_id = SubjectUser.user_id
            LEFT JOIN competence_criterion_rubrics CompetenceCriterionRubric ON CompetenceCriterionRubric.id = CompetenceCriterionGrade.rubric_id
            $where $group_by ORDER BY Subject.name, Student.first_name, Student.last_name
        ");
    }

    function _get_student_stats($course_id, $student_id = null)
    {
        $db = $this->Competence->getDataSource();

        $where = "WHERE SubjectUser.user_id = {$db->value($student_id)} AND Subject.course_id = {$db->value($course_id)}";

        if ($this->Auth->user('type') === "Profesor") {
            $user_id = $this->Auth->user('id');
            $where .= " AND (Subject.coordinator_id = {$db->value($user_id)} OR Subject.practice_responsible_id = {$db->value($user_id)})";
        }

        return $this->Competence->query("
            SELECT Subject.id, Subject.code, Subject.name, CompetenceCriterion.id, CompetenceCriterion.code, CompetenceCriterion.definition,
                CompetenceCriterionRubric.id, CompetenceCriterionRubric.title, CompetenceCriterionRubric.definition, CompetenceCriterionRubric.value
            FROM subjects_users SubjectUser
            INNER JOIN subjects Subject ON Subject.id = SubjectUser.subject_id
            INNER JOIN competence_criterion_subjects CompetenceCriterionSubject ON CompetenceCriterionSubject.subject_id = SubjectUser.subject_id
            INNER JOIN competence_criteria CompetenceCriterion ON CompetenceCriterion.id = CompetenceCriterionSubject.criterion_id
            LEFT JOIN competence_criterion_grades CompetenceCriterionGrade ON CompetenceCriterionGrade.criterion_id = CompetenceCriterion.id AND CompetenceCriterionGrade.student_id = SubjectUser.user_id
            LEFT JOIN competence_criterion_rubrics CompetenceCriterionRubric ON CompetenceCriterionRubric.id = CompetenceCriterionGrade.rubric_id
            $where ORDER BY Subject.name, CompetenceCriterion.code
        ");
    }
  
    function _authorize()
    {
        parent::_authorize();
        $administrator_actions = array(
            'by_course', 'by_subject', 'by_student',
            'view', 'view_by_subject','view_by_student',
            'add_to_course', 'edit', 'delete',
            'stats_by_subject',
            'stats_by_student',
        );
        $teacher_actions = array(
            'by_course', 'by_subject', 'by_student',
            'view', 'view_by_subject', 'view_by_student',
            'stats_by_subject', 'export_stats_by_subject',
            'stats_by_student', 'export_stats_by_student'
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
