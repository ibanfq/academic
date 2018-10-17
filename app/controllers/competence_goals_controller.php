<?php
class CompetenceGoalsController extends AppController {
    var $name = 'CompetenceGoals';
    var $uses = array('CompetenceGoal');

    var $paginate = array(
        'limit' => 10,
        'order' => array('CompetenceGoal.code' => 'asc'),
    );

    function add_to_competence($competence_id)
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
        
        $this->CompetenceGoal->Behaviors->attach('Containable');
        $competence_goal = $this->CompetenceGoal->find('first', array(
            'contain' => array('CompetenceCriterion'),
            'conditions' => array('CompetenceGoal.id' => $id)
        ));

        if (!$competence_goal) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

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

    function edit($id = null)
    {
        $id = $id === null ? null : intval($id);

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
        $administrator_actions = array('add', 'add_to_competence', 'edit', 'delete');

        $this->set('section', 'courses');

        if ((array_search($this->params['action'], $administrator_actions) !== false) && ($this->Auth->user('type') != "Administrador")) {
            return false;
        }

        if ($this->Auth->user('type') == "Estudiante") {
            return false;
        }

        return true;
    }
}
