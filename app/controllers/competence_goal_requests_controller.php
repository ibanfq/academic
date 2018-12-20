<?php
class CompetenceGoalRequestsController extends AppController {
    var $name = 'CompetenceGoalRequests';
    var $uses = array('CompetenceGoalRequest');

    var $paginate = array(
        'limit' => 10,
        'order' => array('CompetenceGoalRequest.id' => 'asc'),
    );

    function index()
    {
        $course = $this->CompetenceGoalRequest->CompetenceGoal->Competence->Course->current();

        if (!$course) {
            $this->Session->setFlash('No hay ningún curso activo actualmente.');
            $this->redirect(array('controller' => 'users', 'action' => 'home'));
        }

        $response = $this->Api->call('GET', '/api/competence_goal_requests/by_course/' . urlencode($course['id']));

        if ($response['status'] === 'error') {
            if ($response['code'] !== 404) {
                $this->Session->setFlash($response['message']);
            }
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        $this->set('competence_goal_requests', $response['data']);
        $this->set('course', array('Course' => $course));
    }

    function by_course($course_id = null)
    {
        $course_id = $course_id === null ? null : intval($course_id);

        if (is_null($course_id)) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        $course = $this->CompetenceGoalRequest->CompetenceGoal->Competence->Course->find('first', array(
            'recursive' => -1,
            'conditions' => array('Course.id' => $course_id)
        ));

        if (!$course) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        $response = $this->Api->call('GET', '/api/competence_goal_requests/by_course/' . urlencode($course_id));

        if ($response['status'] === 'error') {
            if ($response['code'] !== 404) {
                $this->Session->setFlash($response['message']);
            }
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        $this->set('competence_goal_requests', $response['data']);
        $this->set('course', $course);
    }

    function add()
    {
        if (!isset($this->data['CompetenceGoalRequest']['goal_id'], $this->data['CompetenceGoalRequest']['teacher_id'])) {
            $this->redirect(array('action' => 'index'));
        }

        $response = $this->Api->call('POST', '/api/competence_goal_requests', $this->data);

        switch ($response['status']) {
            case 'fail':
                $this->Session->setFlash('No se ha podido crear la solicitud de evaluación');
                $this->redirect(array('action' => 'index'));
                break;
            case 'error':
                if ($response['code'] !== 404) {
                    $this->Session->setFlash($response['message']);
                }
                $this->redirect(array('action' => 'index'));
                break;
            case 'success':
                $this->Session->setFlash('La solicitud se ha realizado correctamente');
        }

        $this->index();
    }

    function reject($id = null)
    {
        $id = $id === null ? null : intval($id);

        if (is_null($id)) {
            $this->redirect(array('action' => 'index'));
        }

        $response = $this->Api->call('DELETE', '/api/competence_goal_requests/'.urlencode($id));

        switch ($response['status']) {
            case 'error':
                if ($response['code'] !== 404) {
                    $this->Session->setFlash($response['message']);
                }
                break;
            case 'success':
                if ($this->Auth->user('type') === "Estudiante") {
                    $this->Session->setFlash('La solicitud de evaluación se ha cancelado correctamente');
                } else {
                    $this->Session->setFlash('La solicitud de evaluación se ha rechazado correctamente');
                }
        }

        $this->redirect(array('action' => 'index'));
    }

    function reject_by_course($course_id = null, $id = null)
    {
        $course_id = $course_id === null ? null : intval($course_id);
        $id = $id === null ? null : intval($id);

        if (is_null($course_id) || is_null($id)) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        $course = $this->CompetenceGoalRequest->CompetenceGoal->Competence->Course->find('first', array(
            'recursive' => -1,
            'conditions' => array('Course.id' => $course_id)
        ));

        if (!$course) {
            $this->redirect(array('controller' => 'courses', 'action' => 'index'));
        }

        $response = $this->Api->call('DELETE', '/api/competence_goal_requests/'.urlencode($id));

        switch ($response['status']) {
            case 'error':
                if ($response['code'] !== 404) {
                    $this->Session->setFlash($response['message']);
                }
                break;
            case 'success':
                if ($this->Auth->user('type') === "Estudiante") {
                    $this->Session->setFlash('La solicitud de evaluación se ha cancelado correctamente');
                } else {
                    $this->Session->setFlash('La solicitud de evaluación se ha rechazado correctamente');
                }
        }
        
        $this->redirect(array('action' => 'by_course', $course_id));
    }

    function _authorize()
    {
        parent::_authorize();
        $administrator_actions = array(
            'by_course'
        );
        $teacher_actions = array(
            'by_course'
        );
        $student_actions = array(
            'index', 'reject', 'add'
        );
        $only_student_actions = array(
            'add'
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

        if ($this->Auth->user('type') !== "Estudiante" && array_search($this->params['action'], $only_student_actions) === true) {
            return false;
        }

        return true;
    }
}
