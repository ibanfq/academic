<?php
class AcademicYearsController extends AppController {
    var $name = 'AcademicYears';
    var $paginate = array(
        'AcademicYear' => array('limit' => 10, 'order' => array('AcademicYear.initial_date' => 'asc')),
        'Institution' => array('limit' => 10, 'order' => array('Institution.acronym' => 'asc')),
    );
    var $fields_fillable = array('AcademicYear');
    var $fields_guarded = array('AcademicYear' => ['id', 'created', 'modified']);

    function index() {
        $academic_years = $this->AcademicYear->find('all', array(
            'order' => array('AcademicYear.initial_date desc'))
        );

        $this->set('academic_years', $academic_years);
    }

    function add() {
        if (!empty($this->data)) {
            $this->data = $this->Form->filter($this->data);
            
            if ($this->AcademicYear->save($this->data)){
                $this->Session->setFlash('El curso se ha guardado correctamente');
                $this->redirect(array('action' => 'index'));
            }
        }
    }

    function view($id = null) {
        $id = $id === null ? null : intval($id);

        if (! $id) {
            $this->Session->setFlash('No se ha podido acceder al curso.');
            $this->redirect(array('action' => 'index'));
        }
        
        $academic_year = $this->AcademicYear->find('first', array(
            'conditions' => array(
                'AcademicYear.id' => $id
            )
        ));

        if (!$academic_year) {
            $this->Session->setFlash('No se ha podido acceder al curso.');
            $this->redirect(array('action' => 'index'));
        }

        $this->AcademicYear->set($academic_year);

        App::import('Core', 'Sanitize');
        $db = $this->AcademicYear->getDataSource();

        $scope = array();

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

        $this->loadModel('Institution');

        $institutions = $this->paginate('Institution', $scope);

        $this->set('academic_year', $this->AcademicYear->data);
        $this->set('institutions', $institutions);
        $this->set('q', $q);
    }

    function edit($id = null) {
        $id = $id === null ? null : intval($id);

        if (! $id) {
            $this->Session->setFlash('No se ha podido acceder al curso.');
            $this->redirect(array('action' => 'index'));
        }
        
        $academic_year = $this->AcademicYear->find('first', array(
            'conditions' => array(
                'AcademicYear.id' => $id
            )
        ));

        if (!$academic_year) {
            $this->Session->setFlash('No se ha podido acceder al curso.');
            $this->redirect(array('action' => 'index'));
        }

        $this->AcademicYear->set($academic_year);

        if (empty($this->data)) {
            $this->data = $academic_year;
        } else {
            $this->data = $this->Form->filter($this->data);
            $this->data['AcademicYear']['id'] = $academic_year['AcademicYear']['id'];
            $this->data['AcademicYear']['modified'] = null;
            
            if ($this->AcademicYear->save($this->data)) {
                $initial_date = $this->AcademicYear->dateFormatInternal($this->data['AcademicYear']['initial_date']);
                $final_date = $this->AcademicYear->dateFormatInternal($this->data['AcademicYear']['final_date']);
                $this->AcademicYear->query("UPDATE courses SET initial_date = '{$initial_date}', final_date = '{$final_date}' WHERE academic_year_id = {$id}");
                $this->Session->setFlash('El curso se ha actualizado correctamente.');
                $this->redirect(array('action' => 'view', $id));
            }
        }

        $this->set('academic_year', $this->AcademicYear->data);
    }

    function delete($id) {
        $id = $id === null ? null : intval($id);

        if (!$id) {
            $this->Session->setFlash('No se ha podido acceder al curso.');
            $this->redirect(array('action' => 'index'));
        }

        $academic_year = $this->AcademicYear->find('first', array(
            'conditions' => array(
                'AcademicYear.id' => $id,
            ),
            'recursive' => -1
        ));

        if (!$academic_year) {
            $this->Session->setFlash('No se ha podido acceder al curso.');
            $this->redirect(array('action' => 'index'));
        }

        $this->AcademicYear->delete($id);

        $currentAcademicYearsQuery = "SELECT DISTINCT `AcademicYear`.id FROM academic_years `AcademicYear`";
        $this->AcademicYear->query("DELETE FROM `courses` WHERE academic_year_id NOT IN ($currentAcademicYearsQuery)");

        $currentCoursesQuery = "SELECT DISTINCT `Course`.id FROM courses `Course`";
        $this->AcademicYear->query("DELETE FROM `subjects` WHERE course_id NOT IN ($currentCoursesQuery)");

        $currentSubjectsQuery = "SELECT DISTINCT `Subject`.id FROM subjects `Subject`";
        $this->AcademicYear->query("DELETE FROM `groups` WHERE subject_id NOT IN ($currentSubjectsQuery)");
        $this->AcademicYear->query("DELETE FROM `activities` WHERE subject_id NOT IN ($currentSubjectsQuery)");
        $this->AcademicYear->query("DELETE FROM `subjects_users` WHERE subject_id NOT IN ($currentSubjectsQuery)");

        $currentActivitiesQuery = "SELECT DISTINCT `Activity`.id FROM activities `Activity`";
        $this->AcademicYear->query("DELETE FROM `attendance_registers` WHERE activity_id NOT IN ($currentActivitiesQuery)");
        $this->AcademicYear->query("DELETE FROM `events` WHERE activity_id NOT IN ($currentActivitiesQuery)");
        $this->AcademicYear->query("DELETE FROM `registrations` WHERE activity_id NOT IN ($currentActivitiesQuery)");
        $this->AcademicYear->query("DELETE FROM `group_requests` WHERE activity_id NOT IN ($currentActivitiesQuery)");

        $this->AcademicYear->query("DELETE FROM `users_attendance_register` WHERE attendance_register_id NOT IN (SELECT DISTINCT `AttendanceRegister`.id FROM attendance_registers `AttendanceRegister`)");

        $this->AcademicYear->query("DELETE FROM `competence` WHERE course_id NOT IN (SELECT DISTINCT `Course`.id FROM courses `Course`)");
        $this->AcademicYear->query("DELETE FROM `competence_goals` WHERE competence_id NOT IN (SELECT DISTINCT `Competence`.id FROM competence `Competence`)");

        $currentCompetenceGoalsQuery = "SELECT DISTINCT `CompetenceGoal`.id FROM competence_goals `CompetenceGoal`";
        $this->AcademicYear->query("DELETE FROM `competence_criteria` WHERE goal_id NOT IN ($currentCompetenceGoalsQuery)");
        $this->AcademicYear->query("DELETE FROM `competence_goal_requests` WHERE goal_id NOT IN ($currentCompetenceGoalsQuery)");

        $currentCompetenceCriteriaQuery = "SELECT DISTINCT `CompetenceCriterion`.id FROM competence_criteria `CompetenceCriterion`";
        $this->AcademicYear->query("DELETE FROM `competence_criterion_rubrics` WHERE criterion_id NOT IN ($currentCompetenceCriteriaQuery)");
        $this->AcademicYear->query("DELETE FROM `competence_criterion_subjects` WHERE criterion_id NOT IN ($currentCompetenceCriteriaQuery)");
        $this->AcademicYear->query("DELETE FROM `competence_criterion_teachers` WHERE criterion_id NOT IN ($currentCompetenceCriteriaQuery)");
        $this->AcademicYear->query("DELETE FROM `competence_criterion_grades` WHERE criterion_id NOT IN ($currentCompetenceGoalsQuery)");

        $this->Session->setFlash('El curso ha sido eliminado correctamente');
        $this->redirect(array('action' => 'index'));
    }

    function _authorize() {
        parent::_authorize();

        $super_admin_actions = array('add', 'edit', 'delete');
        $administrator_actions = array();

        $this->set('section', 'courses');

        if ((array_search($this->params['action'], $super_admin_actions) !== false) && (! $this->Auth->user('super_admin'))) {
            return false;
        }

        if ((array_search($this->params['action'], $administrator_actions) !== false) && ($this->Auth->user('type') != "Administrador") && ($this->Auth->user('type') != "Administrativo")) {
            return false;
        }
    
        return true;
    }
}
