<?php
class DegreesController extends AppController {
    var $name = 'Degrees';
    var $paginate = array('limit' => 10, 'order' => array('Degree.acronym' => 'asc'));
    var $fields_fillable = array('Degree');
    var $fields_guarded = array('Degree' => ['id', 'institution_id', 'created', 'modified']);

    function add($institution_id = null) {
        $institution_id = $institution_id === null ? null : intval($institution_id);

        if (is_null($institution_id) && !empty($this->data['Degree']['institution_id'])) {
            $institution_id = intval($this->data['Degree']['institution_id']);
        }

        if (! $institution_id) {
            $this->Session->setFlash('No se ha podido acceder al centro.');
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        $institution = $this->Degree->Institution->find('first', array(
            'conditions' => array(
                'Institution.id' => $institution_id
            ),
            'recursive' => -1
        ));

        if (!$institution) {
            $this->Session->setFlash('No se ha podido acceder al centro.');
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        if (!empty($this->data)){
            $this->data = $this->Form->filter($this->data);
            $this->data['Degree']['institution_id'] = $institution_id;
            
            if ($this->Degree->save($this->data)){
                $this->Session->setFlash('La titulación se ha guardado correctamente');
                $this->redirect(array('controller' => 'institutions', 'action' => 'view', $institution_id));
            }
        }

        $this->set('institution', $institution);
        $this->set('institution_id', $institution_id);
    }

    function view($id = null) {
        $id = $id === null ? null : intval($id);

        if (! $id) {
            $this->Session->setFlash('No se ha podido acceder a la titulación.');
            $this->redirect(array('action' => 'index'));
        }
        
        $degree = $this->Degree->find('first', array(
            'conditions' => array(
                'Degree.id' => $id
            )
        ));

        if (!$degree) {
            $this->Session->setFlash('No se ha podido acceder a la titualación.');
            $this->redirect(array('action' => 'index'));
        }

        $this->set('degree', $degree);
    }

    function edit($id = null) {
        $id = $id === null ? null : intval($id);

        if (! $id) {
            $this->Session->setFlash('No se ha podido acceder a la titulación.');
            $this->redirect(array('action' => 'index'));
        }
        
        $degree = $this->Degree->find('first', array(
            'conditions' => array(
                'Degree.id' => $id
            )
        ));

        if (!$degree) {
            $this->Session->setFlash('No se ha podido acceder a la titualación.');
            $this->redirect(array('action' => 'index'));
        }

        $this->Degree->set($degree);

        if (empty($this->data)) {
            $this->data = $degree;
        } else {
            $this->data = $this->Form->filter($this->data);
            $this->data['Degree']['id'] = $degree['Degree']['id'];
            $this->data['Degree']['modified'] = null;
            
            if ($this->Degree->save($this->data)) {
                $this->Session->setFlash('La titulación se ha actualizado correctamente.');
                $this->redirect(array('action' => 'view', $id));
            }
        }

        $this->set('degree', $degree);
    }

    function delete($id) {
        $id = $id === null ? null : intval($id);

        if (! $id) {
            $this->Session->setFlash('No se ha podido acceder a la titulación.');
            $this->redirect(array('action' => 'index'));
        }
        
        $degree = $this->Degree->find('first', array(
            'conditions' => array(
                'Degree.id' => $id
            )
        ));

        if (!$degree) {
            $this->Session->setFlash('No se ha podido acceder a la titualación.');
            $this->redirect(array('action' => 'index'));
        }

        $this->Degree->delete($id); // Delete degrees implicitly

        $currentDegreeQuery = "SELECT DISTINCT `Degree`.id FROM degrees `Degree`";
        $this->Degree->query("DELETE FROM `courses` WHERE degree_id NOT IN ($currentDegreeQuery)");

        $currentCoursesQuery = "SELECT DISTINCT `Course`.id FROM courses `Course`";
        $this->Degree->query("DELETE FROM `subjects` WHERE course_id NOT IN ($currentCoursesQuery)");

        $currentSubjectsQuery = "SELECT DISTINCT `Subject`.id FROM subjects `Subject`";
        $this->Degree->query("DELETE FROM `groups` WHERE subject_id NOT IN ($currentSubjectsQuery)");
        $this->Degree->query("DELETE FROM `activities` WHERE subject_id NOT IN ($currentSubjectsQuery)");
        $this->Degree->query("DELETE FROM `subjects_users` WHERE subject_id NOT IN ($currentSubjectsQuery)");

        $currentActivitiesQuery = "SELECT DISTINCT `Activity`.id FROM activities `Activity`";
        $this->Degree->query("DELETE FROM `attendance_registers` WHERE activity_id NOT IN ($currentActivitiesQuery)");
        $this->Degree->query("DELETE FROM `events` WHERE activity_id NOT IN ($currentActivitiesQuery)");
        $this->Degree->query("DELETE FROM `registrations` WHERE activity_id NOT IN ($currentActivitiesQuery)");
        $this->Degree->query("DELETE FROM `group_requests` WHERE activity_id NOT IN ($currentActivitiesQuery)");

        $this->Degree->query("DELETE FROM `users_attendance_register` WHERE attendance_register_id NOT IN (SELECT DISTINCT `AttendanceRegister`.id FROM attendance_registers `AttendanceRegister`)");

        $this->Degree->query("DELETE FROM `competence` WHERE course_id NOT IN (SELECT DISTINCT `Course`.id FROM courses `Course`)");
        $this->Degree->query("DELETE FROM `competence_goals` WHERE competence_id NOT IN (SELECT DISTINCT `Competence`.id FROM competence `Competence`)");

        $currentCompetenceGoalsQuery = "SELECT DISTINCT `CompetenceGoal`.id FROM competence_goals `CompetenceGoal`";
        $this->Degree->query("DELETE FROM `competence_criteria` WHERE goal_id NOT IN ($currentCompetenceGoalsQuery)");
        $this->Degree->query("DELETE FROM `competence_goal_requests` WHERE goal_id NOT IN ($currentCompetenceGoalsQuery)");

        $currentCompetenceCriteriaQuery = "SELECT DISTINCT `CompetenceCriterion`.id FROM competence_criteria `CompetenceCriterion`";
        $this->Degree->query("DELETE FROM `competence_criterion_rubrics` WHERE criterion_id NOT IN ($currentCompetenceCriteriaQuery)");
        $this->Degree->query("DELETE FROM `competence_criterion_subjects` WHERE criterion_id NOT IN ($currentCompetenceCriteriaQuery)");
        $this->Degree->query("DELETE FROM `competence_criterion_teachers` WHERE criterion_id NOT IN ($currentCompetenceCriteriaQuery)");
        $this->Degree->query("DELETE FROM `competence_criterion_grades` WHERE criterion_id NOT IN ($currentCompetenceGoalsQuery)");

        $this->Session->setFlash('La titulación ha sido eliminado correctamente');
        $this->redirect(array('controller' => 'institutions', 'action' => 'view', $degree['Institution']['id']));
    }

    function _authorize() {
        parent::_authorize();

        if (Environment::institution('id')) {
            return false;
        }

        $super_admin_actions = array('add', 'edit', 'delete');
        $administrator_actions = array('index', 'view');

        $this->set('section', 'institutions');

        if ((array_search($this->params['action'], $super_admin_actions) !== false) && (! $this->Auth->user('super_admin'))) {
            return false;
        }

        if ((array_search($this->params['action'], $administrator_actions) !== false) && ($this->Auth->user('type') != "Administrador") && ($this->Auth->user('type') != "Administrativo")) {
            return false;
        }
    
        return true;
    }
}
