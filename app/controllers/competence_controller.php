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

        $this->set('competence', $this->Competence->find('all', array(
            'recursive' => -1,
            'conditions' => array('Competence.course_id' => $course_id),
            'order' => array('Competence.code asc')
        )));
        $this->set('course', $course);
    }

    function add_to_course($course_id)
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
        $this->Competence->id = $id;
        $competence = $this->Competence->read();

        if (!$competence) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        $this->set('competence', $competence);
        $this->set('course', array('Course' => $competence['Course']));
    }

    function edit($id = null)
    {
        $id = $id === null ? null : intval($id);
        $this->Competence->id = $id;
        if (empty($this->data)) {
            $this->data = $this->Competence->read();
            $this->set('competence', $this->data);
            $this->set('course', array('Course' => $this->data['Course']));
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
        $administrator_actions = array('add', 'add_to_course', 'edit', 'delete');

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
