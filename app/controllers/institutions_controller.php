<?php
class InstitutionsController extends AppController {
    var $name = 'Institutions';
    var $paginate = array('limit' => 10, 'order' => array('Institution.acronym' => 'asc'));
    var $fields_fillable = array('Institution');
    var $fields_guarded = array('Institution' => ['id', 'created', 'modified']);
    var $refs_sections = array('classrooms' => 'classrooms', 'users' => 'users', 'attendance_registers' => 'attendance_registers', 'bookings' => 'bookings');

    function index() {
        $ref = isset($this->params['named']['ref']) ? $this->params['named']['ref'] : null;

        if (! empty($this->params['named']['academic_year'])) {
            $academic_year_id = intval($this->params['named']['academic_year']);
            
            if (is_null($academic_year_id)) {
                $this->Session->setFlash('No se ha podido acceder al curso.');
                $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
            }
            
            $this->loadModel('AcademicYear');

            $academic_year = $this->AcademicYear->find('first', array(
                'recursive' => -1,
                'conditions' => array(
                    'AcademicYear.id' => $academic_year_id
                )
            ));

            if (!$academic_year) {
                $this->Session->setFlash('No se ha podido acceder al curso.');
                $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
            }
        } elseif (! $this->Auth->user('super_admin') && (!$ref || ! array_key_exists($ref, $this->refs_sections))) {
            $this->Session->setFlash('No se ha podido acceder al curso.');
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        App::import('Core', 'Sanitize');

        $scope = array();
        $db = $this->Institution->getDataSource();
        
        if (isset($this->params['url']['q'])) {
            $q = Sanitize::escape($this->params['url']['q']);
        } elseif (isset($this->passedArgs['q'])) {
            $q = Sanitize::escape($this->passedArgs['q']);
        } else {
            $q = '';
        }

        if (!empty($q)) {
            $scope['OR'] = array(
                'Institution.acronym LIKE' => "%$q%",
                'Institution.name LIKE' => "%$q%",
            );
        }

        if (! $this->Auth->user('super_admin')) {
            $scope = array(
                "Institution.id IN (
                    SELECT UserInstitution.institution_id
                    FROM users_institutions UserInstitution
                    WHERE UserInstitution.user_id = {$db->value($this->Auth->user('id'))}
                )",
                $scope
            );
        }

        $institutions = $this->paginate('Institution', $scope);

        $this->set('institutions', $institutions);
        $this->set('q', $q);
        $this->set('academic_year', isset($academic_year) ? $academic_year : null);
        $this->set('ref', $ref);
    }

    function add() {
        if (!empty($this->data)){
            $this->data = $this->Form->filter($this->data);
            
            if ($this->Institution->save($this->data)){
                $this->Session->setFlash('El centro se ha guardado correctamente');
                $this->redirect(array('action' => 'index'));
            }
        }
    }

    function view($id = null) {
        $id = $id === null ? null : intval($id);

        if (! $id) {
            $this->Session->setFlash('No se ha podido acceder al centro.');
            $this->redirect(array('action' => 'index'));
        }
        
        $institution = $this->Institution->find('first', array(
            'conditions' => array(
                'Institution.id' => $id
            )
        ));

        if (!$institution) {
            $this->Session->setFlash('No se ha podido acceder al centro.');
            $this->redirect(array('action' => 'index'));
        }

        $this->loadModel('User');

        $administrators = $student = $this->User->find('all', array(
            'recursive' => -1,
            'joins' => array(
                array(
                    'table' => 'users_institutions',
                    'alias' => 'UserInstitution',
                    'type' => 'INNER',
                    'conditions' => array(
                        'UserInstitution.user_id = User.id',
                        'UserInstitution.institution_id' => $id,
                        'UserInstitution.active'
                    )
                )
            ),
            'conditions' => array(
                'User.type' => 'Administrador'
            )
        ));

        $this->set('institution', $institution);
        $this->set('administrators', $administrators);
    }

    function edit($id = null) {
        $id = $id === null ? null : intval($id);

        if (! $id) {
            $this->Session->setFlash('No se ha podido acceder al centro.');
            $this->redirect(array('action' => 'index'));
        }
        
        $institution = $this->Institution->find('first', array(
            'conditions' => array(
                'Institution.id' => $id
            )
        ));

        if (!$institution) {
            $this->Session->setFlash('No se ha podido acceder al centro.');
            $this->redirect(array('action' => 'index'));
        }

        $this->Institution->set($institution);

        if (empty($this->data)) {
            $this->data = $institution;
        } else {
            $this->data = $this->Form->filter($this->data);
            $this->data['Institution']['id'] = $institution['Institution']['id'];
            $this->data['Institution']['modified'] = null;
            
            if ($this->Institution->save($this->data)) {
                $this->Session->setFlash('El centro se ha actualizado correctamente.');
                $this->redirect(array('action' => 'view', $id));
            }
        }

        $this->set('institution', $institution);
    }

    function delete($id) {
        $id = $id === null ? null : intval($id);

        if (! $id) {
            $this->Session->setFlash('No se ha podido acceder al centro.');
            $this->redirect(array('action' => 'index'));
        }
        
        $institution = $this->Institution->find('first', array(
            'conditions' => array(
                'Institution.id' => $id
            )
        ));

        if (!$institution) {
            $this->Session->setFlash('No se ha podido acceder al centro.');
            $this->redirect(array('action' => 'index'));
        }

        $this->Institution->delete($id); // Delete degrees implicitly

        $currentInstitutionsQuery = "SELECT DISTINCT `Institution`.id FROM institutions `Institution`";
        $this->Institution->query("DELETE FROM `courses` WHERE institution_id NOT IN ($currentInstitutionsQuery)");
        $this->Institution->query("DELETE FROM `classrooms` WHERE institution_id NOT IN ($currentInstitutionsQuery)");
        $this->Institution->query("DELETE FROM `monitors` WHERE institution_id NOT IN ($currentInstitutionsQuery)");
        $this->Institution->query("DELETE FROM `users_institutions` WHERE institution_id NOT IN ($currentInstitutionsQuery)");
        $this->Institution->query("DELETE FROM `bookings` WHERE institution_id NOT IN ($currentInstitutionsQuery)");

        $currentClassroomsQuery = "SELECT DISTINCT `Course`.id FROM courses `Course`";
        $this->Institution->query("DELETE FROM `bookings` WHERE classroom_id IS NOT NULL AND classroom_id NOT IN ($currentClassroomsQuery)");

        $currentCoursesQuery = "SELECT DISTINCT `Course`.id FROM courses `Course`";
        $this->Institution->query("DELETE FROM `subjects` WHERE course_id NOT IN ($currentCoursesQuery)");

        $currentSubjectsQuery = "SELECT DISTINCT `Subject`.id FROM subjects `Subject`";
        $this->Institution->query("DELETE FROM `groups` WHERE subject_id NOT IN ($currentSubjectsQuery)");
        $this->Institution->query("DELETE FROM `activities` WHERE subject_id NOT IN ($currentSubjectsQuery)");
        $this->Institution->query("DELETE FROM `subjects_users` WHERE subject_id NOT IN ($currentSubjectsQuery)");

        $currentActivitiesQuery = "SELECT DISTINCT `Activity`.id FROM activities `Activity`";
        $this->Institution->query("DELETE FROM `attendance_registers` WHERE activity_id NOT IN ($currentActivitiesQuery)");
        $this->Institution->query("DELETE FROM `events` WHERE activity_id NOT IN ($currentActivitiesQuery)");
        $this->Institution->query("DELETE FROM `registrations` WHERE activity_id NOT IN ($currentActivitiesQuery)");
        $this->Institution->query("DELETE FROM `group_requests` WHERE activity_id NOT IN ($currentActivitiesQuery)");

        $this->Institution->query("DELETE FROM `users_attendance_register` WHERE attendance_register_id NOT IN (SELECT DISTINCT `AttendanceRegister`.id FROM attendance_registers `AttendanceRegister`)");

        $this->Institution->query("DELETE FROM `competence` WHERE course_id NOT IN (SELECT DISTINCT `Course`.id FROM courses `Course`)");
        $this->Institution->query("DELETE FROM `competence_goals` WHERE competence_id NOT IN (SELECT DISTINCT `Competence`.id FROM competence `Competence`)");

        $currentCompetenceGoalsQuery = "SELECT DISTINCT `CompetenceGoal`.id FROM competence_goals `CompetenceGoal`";
        $this->Institution->query("DELETE FROM `competence_criteria` WHERE goal_id NOT IN ($currentCompetenceGoalsQuery)");
        $this->Institution->query("DELETE FROM `competence_goal_requests` WHERE goal_id NOT IN ($currentCompetenceGoalsQuery)");

        $currentCompetenceCriteriaQuery = "SELECT DISTINCT `CompetenceCriterion`.id FROM competence_criteria `CompetenceCriterion`";
        $this->Institution->query("DELETE FROM `competence_criterion_rubrics` WHERE criterion_id NOT IN ($currentCompetenceCriteriaQuery)");
        $this->Institution->query("DELETE FROM `competence_criterion_subjects` WHERE criterion_id NOT IN ($currentCompetenceCriteriaQuery)");
        $this->Institution->query("DELETE FROM `competence_criterion_teachers` WHERE criterion_id NOT IN ($currentCompetenceCriteriaQuery)");
        $this->Institution->query("DELETE FROM `competence_criterion_grades` WHERE criterion_id NOT IN ($currentCompetenceGoalsQuery)");

        $this->Session->setFlash('El curso ha sido eliminado correctamente');
        $this->redirect(array('action' => 'index'));
    }

    function _authorize() {
        parent::_authorize();

        if (Environment::institution('id')) {
            return false;
        }

        $ref = isset($this->params['named']['ref']) ? $this->params['named']['ref'] : null;

        if ($ref && array_key_exists($ref, $this->refs_sections)) {
            $this->set('section', $this->refs_sections[$ref]);
        } else {
            $this->set('section', 'institutions');
        }

        $super_admin_actions = array('add', 'edit', 'view', 'delete');

        if (! $this->Auth->user('super_admin')) {
            if (array_search($this->params['action'], $super_admin_actions) !== false) {
                return false;
            }

            if ($this->params['action'] == 'index' && !$ref) {
                return false;
            }        
        }

        return true;
    }
}
