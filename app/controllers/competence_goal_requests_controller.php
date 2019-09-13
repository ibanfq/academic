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
        $response = $this->Api->call(
            'GET',
            '/api/institutions/'.Environment::institution('id').'/competence_goal_requests/'
        );

        if ($response['status'] === 'error') {
            if ($response['code'] !== 404) {
                $this->Session->setFlash($response['message']);
            }
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        $this->set('competence_goal_requests', $response['data']);
    }

    function by_course($course_id = null)
    {
        $course_id = $course_id === null ? null : intval($course_id);

        if (is_null($course_id)) {
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        $course = $this->CompetenceGoalRequest->CompetenceGoal->Competence->Course->find('first', array(
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

        $response = $this->Api->call(
            'GET',
            '/api/institutions/'.Environment::institution('id').'/competence_goal_requests/by_course/' . urlencode($course_id)
        );

        if ($response['status'] === 'error') {
            if ($response['code'] !== 404) {
                $this->Session->setFlash($response['message']);
            }
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        $this->set('competence_goal_requests', $response['data']);
        $this->set('course', $course);
    }

    function add()
    {
        $redirect = array('action' => 'index');

        if (!isset($this->data['CompetenceGoalRequest']['goal_id'], $this->data['CompetenceGoalRequest']['teacher_id'])) {
            $this->redirect($redirect);
        }

        if (!empty($this->params['url']['referer'])) {
            $referer_parts = explode(':', $this->params['url']['referer']);
            $referer_view = implode(':', array_slice($referer_parts, 0, 2));
            switch ($referer_view) {
                case 'competence_goals:view':
                    $redirect = array(
                        'controller' => 'competence_goals',
                        'action' => 'view',
                        $this->data['CompetenceGoalRequest']['goal_id']
                    );
                    break;
                case 'competence_goals:view_by_subject':
                    $subject_id = isset($referer_parts[2]) ? intval($referer_parts[2]) : $referer_parts[2];
                    if ($subject_id) {
                        $redirect = array(
                            'controller' => 'competence_goals',
                            'action' => 'view_by_subject',
                            $subject_id,
                            $this->data['CompetenceGoalRequest']['goal_id']
                        );
                    }
                    break;
            }
        }

        $response = $this->Api->call(
            'POST',
            '/api/institutions/'.Environment::institution('id').'/competence_goal_requests',
            $this->data
        );

        switch ($response['status']) {
            case 'fail':
                $this->Session->setFlash('No se ha podido crear la solicitud de evaluación');
                break;
            case 'error':
                if ($response['code'] !== 404) {
                    $this->Session->setFlash($response['message']);
                }
                break;
            case 'success':
                $this->Session->setFlash('La solicitud se ha realizado correctamente');
        }

        $this->redirect($redirect);
    }

    function add_by_course($course_id = null)
    {
        $course_id = $course_id === null ? null : intval($course_id);
        
        if (!isset($course_id)) {
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        $redirect = array('action' => 'by_course', $course_id);
        
        if (!isset($this->data['CompetenceGoalRequest']['goal_id'], $this->data['CompetenceGoalRequest']['teacher_id'])) {
            $this->redirect($redirect);
        }

        $course = $this->CompetenceGoalRequest->CompetenceGoal->Competence->Course->find('first', array(
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

        $response = $this->Api->call(
            'POST',
            '/api/institutions/'.Environment::institution('id').'/competence_goal_requests',
            $this->data
        );

        switch ($response['status']) {
            case 'fail':
                $this->Session->setFlash('No se ha podido crear la solicitud de evaluación');
                break;
            case 'error':
                if ($response['code'] !== 404) {
                    $this->Session->setFlash($response['message']);
                }
                break;
            case 'success':
                $this->Session->setFlash('La solicitud se ha realizado correctamente');
        }

        $this->redirect($redirect);
    }

    function reject($id = null)
    {
        $redirect = array('action' => 'index');

        $id = $id === null ? null : intval($id);

        if (is_null($id)) {
            $this->redirect($redirect);
        }

        $goal_request = $this->CompetenceGoalRequest->find('first', array(
            'recursive' => -1,
            'conditions' => array('CompetenceGoalRequest.id' => $id)
        ));

        if (!$goal_request) {
            $this->redirect($redirect);
        }

        if (!empty($this->params['url']['referer'])) {
            $referer_parts = explode(':', $this->params['url']['referer']);
            $referer_view = implode(':', array_slice($referer_parts, 0, 2));
            switch ($referer_view) {
                case 'competence_goals:view':
                    $redirect = array(
                        'controller' => 'competence_goals',
                        'action' => 'view',
                        $goal_request['CompetenceGoalRequest']['goal_id']
                    );
                    break;
                case 'competence_goals:view_by_subject':
                    $subject_id = isset($referer_parts[2]) ? intval($referer_parts[2]) : $referer_parts[2];
                    if ($subject_id) {
                        $redirect = array(
                            'controller' => 'competence_goals',
                            'action' => 'view_by_subject',
                            $subject_id,
                            $goal_request['CompetenceGoalRequest']['goal_id']
                        );
                    }
                    break;
            }
        }

        $response = $this->Api->call(
            'DELETE',
            '/api/institutions/'.Environment::institution('id').'/competence_goal_requests/'.urlencode($id)
        );

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

        $this->redirect($redirect);
    }

    function reject_by_course($course_id = null, $id = null)
    {
        $course_id = $course_id === null ? null : intval($course_id);
        $id = $id === null ? null : intval($id);

        if (is_null($course_id) || is_null($id)) {
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        $course = $this->CompetenceGoalRequest->CompetenceGoal->Competence->Course->find('first', array(
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

        $response = $this->Api->call(
            'DELETE',
            '/api/institutions/'.Environment::institution('id').'/competence_goal_requests/'.urlencode($id)
        );

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
            'index', 'by_course', 'reject', 'reject_by_course', 'add', 'add_by_course'
        );
        $only_student_actions = array(
            'add', 'add_by_course'
        );

        $this->set('section', 'competence');

        if ((array_search($this->params['action'], $administrator_actions) !== false) && ($this->Auth->user('type') !== "Administrador")) {
            if ((array_search($this->params['action'], $teacher_actions) !== false) && ($this->Auth->user('type') === "Profesor")) {
                return true;
            }
            if ((array_search($this->params['action'], $student_actions) !== false) && ($this->Auth->user('type') === "Estudiante")) {
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
