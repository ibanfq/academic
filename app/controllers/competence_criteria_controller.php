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

        if (!empty($this->data)) {
            if ($this->CompetenceCriterion->save($this->data)) {
                $this->Session->setFlash('El criterio se ha guardado correctamente');
                $this->redirect(array('controller' => 'competence_goals', 'action' => 'view', $this->data['CompetenceCriterion']['goal_id']));
            }
        }

        $competence = $this->CompetenceCriterion->CompetenceGoal->Competence->find('first', array(
            'recursive' => -1,
            'conditions' => array('Competence.id' => $competence_goal['CompetenceGoal']['competence_id'])
        ));
        $course = $this->CompetenceCriterion->CompetenceGoal->Competence->Course->find('first', array(
            'recursive' => -1,
            'conditions' => array('Course.id' => $competence['Competence']['course_id'])
        ));

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
                'CompetenceCriterionSubject.Subject',
                'CompetenceCriterionTeacher.Teacher'),
            'conditions' => array('CompetenceCriterion.id' => $id)
        ));

        if (!$competence_criterion) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
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

        $this->set('competence_criterion', $competence_criterion);
        $this->set('competence_goal', $competence_goal);
        $this->set('competence', $competence);
        $this->set('course', $course);
    }

    function edit($id = null)
    {
        $id = $id === null ? null : intval($id);
        $this->CompetenceCriterion->id = $id;
        if (empty($this->data)) {
            $this->data = $this->CompetenceCriterion->find('first', array(
                'recursive' => -1,
                'conditions' => array('CompetenceCriterion.id' => $id)
            ));
        } else {
            if ($this->CompetenceCriterion->save($this->data)) {
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

        $this->set('competence_criterion', $this->data);
        $this->set('competence_goal', $competence_goal);
        $this->set('competence', $competence);
        $this->set('course', $course);
    }

    function delete($id = null)
    {
        $id = $id === null ? null : intval($id);
        $this->CompetenceCriterion->id = $id;
        $competenceCriterion = $this->CompetenceCriterion->read();

        if (!$competenceCriterion) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        $this->CompetenceCriterion->delete($id);
        $this->Session->setFlash('El criterio ha sido eliminada correctamente');
        $this->redirect(array('controller' => 'competence_goals', 'action' => 'view', $this->data['CompetenceCriterion']['goal_id']));
    }
  
    function _authorize()
    {
        parent::_authorize();
        $administrator_actions = array('add', 'add_to_goal', 'edit', 'delete');

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
