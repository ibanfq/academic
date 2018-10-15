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
        if (is_null($competence_id)) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        $competence = $this->CompetenceGoal->Competence->find('first', array(
            'recursive' => 2,
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

        $this->set('competence', $competence);
        $this->set('course', array('Course' => $competence['Course']));
    }

    function view($id = null)
    {
        $competence_goal = $this->CompetenceGoal->find('first', array(
            'recursive' => 2,
            'conditions' => array('CompetenceGoal.id' => $id)
        ));

        if (!$competence_goal) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        $this->set('competence_goal', $competence_goal);
        $this->set('competence', array('Competence' => $competence_goal['Competence']));
        $this->set('course', array('Course' => $competence_goal['Competence']['Course']));
    }

    function edit($id = null)
    {
        $this->CompetenceGoal->id = $id;
        if (empty($this->data)) {
            $this->data = $this->CompetenceGoal->find('first', array(
                'recursive' => 2,
                'conditions' => array('CompetenceGoal.id' => $id)
            ));
            $this->set('competence_goal', $this->data);
            $this->set('competence', array('Competence' => $this->data['Competence']));
            $this->set('course', array('Course' => $this->data['Competence']['Course']));
        } else {
            if ($this->CompetenceGoal->save($this->data)) {
                $this->Session->setFlash('La competencia se ha modificado correctamente.');
                $this->redirect(array('action' => 'view', $id));
            } else {
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
        }
    }

    function delete($id = null)
    {
        $this->Competence->id = $id;
        $competence = $this->Competence->read();

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
        $administrator_actions = array('add', 'edit', 'delete');

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
