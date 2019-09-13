<?php
class GroupsController extends AppController {
    var $name = 'Groups';
    var $paginate = array('limit' => 10, 'order' => array('group.initial_date' => 'asc'));
    var $helpers = array('Ajax', 'ModelHelper');
    var $fields_fillable = array('Group');
    var $fields_guarded = array('Group' => ['id', 'course_id', 'created', 'modified']);

    function add($subject_id = null){
        $subject_id = $subject_id === null ? null : intval($subject_id);

        if (is_null($subject_id) && !empty($this->data['Group']['subject_id'])) {
            $subject_id = intval($this->data['Group']['subject_id']);
        }

        $subject = $this->Group->Subject->find('first', array(
            'conditions' => array(
                'Subject.id' => $subject_id,
                'Course.institution_id' => Environment::institution('id')
            )
        ));

        if (!$subject) {
            $this->Session->setFlash('No se ha podido acceder a la asignatura.');
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        if (!empty($this->data)){
            $this->data = $this->Form->filter($this->data);
            $this->data['Group']['subject_id'] = $subject_id;

            if ($this->Group->save($this->data)){
                $this->Session->setFlash('El grupo se ha guardado correctamente');
                $this->redirect(array('controller' => 'subjects', 'action' => 'view', $this->data['Group']['subject_id']));
            } else{
                $subject = $this->Group->Subject->find('first', array('conditions' => array('Subject.id' => $this->data['Group']['subject_id'])));
            }
        }

        $degree = $this->Group->Subject->Course->Degree->find('first', array(
            'conditions' => array(
                'Degree.id' => $subject['Course']['degree_id'],
            ),
            'recursive' => -1
        ));

        $this->set('subject', $subject);
        $this->set('subject_id', $subject_id);
        $this->set('degree', $degree);
    }

    function view($id = null) {
        $id = $id === null ? null : intval($id);

        if (! $id) {
            $this->Session->setFlash('No se ha podido acceder al grupo.');
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }
        
        $group = $this->Group->find('first', array(
            'conditions' => array('Group.id' => $id),
            'recursive' => -1
        ));

        if (!$group) {
            $this->Session->setFlash('No se ha podido acceder al grupo.');
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        $subject = $this->Group->Subject->find('first', array(
            'conditions' => array(
                'Subject.id' => $group['Group']['subject_id'],
                'Course.institution_id' => Environment::institution('id')
            )
        ));

        if (!$subject) {
            $this->Session->setFlash('No se ha podido acceder a la asignatura.');
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        $degree = $this->Group->Subject->Course->Degree->find('first', array(
            'conditions' => array(
                'Degree.id' => $subject['Course']['degree_id'],
            ),
            'recursive' => -1
        ));

        $this->set('group', $group);
        $this->set('subject', $subject);
        $this->set('degree', $degree);
    }

    function edit($id = null) {
        $id = $id === null ? null : intval($id);

        if (! $id) {
            $this->Session->setFlash('No se ha podido acceder al grupo.');
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }
        
        $group = $this->Group->find('first', array(
            'conditions' => array('Group.id' => $id),
            'recursive' => -1
        ));

        if (!$group) {
            $this->Session->setFlash('No se ha podido acceder al grupo.');
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        $subject = $this->Group->Subject->find('first', array(
            'conditions' => array(
                'Subject.id' => $group['Group']['subject_id'],
                'Course.institution_id' => Environment::institution('id')
            )
        ));

        $this->Group->set($group);

        if (empty($this->data)) {
            $this->data = $group;
        } else {
            $this->data = $this->Form->filter($this->data);
            $this->data['Group']['id'] = $group['Group']['id'];
            $this->data['Group']['modified'] = null;

            if ($this->Group->save($this->data)) {
                $this->Session->setFlash('El grupo se ha modificado correctamente.');
                $this->redirect(array('action' => 'view', $id));
            }
        }

        $degree = $this->Group->Subject->Course->Degree->find('first', array(
            'conditions' => array(
                'Degree.id' => $subject['Course']['degree_id'],
            ),
            'recursive' => -1
        ));

        $this->set('subject', $subject);
        $this->set('degree', $degree);
        $this->set('group', $this->data);
}

    function delete($id = null) {
        $id = $id === null ? null : intval($id);

        if (! $id) {
            $this->Session->setFlash('No se ha podido acceder al grupo.');
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }
        
        $group = $this->Group->find('first', array(
            'fields' => array('Group.*', 'Subject.*'),
            'joins' => array(
                array(
                    'table' => 'subjects',
                    'alias' => 'Subject',
                    'type' => 'INNER',
                    'conditions' => 'Subject.id = Group.subject_id'
                ),
                array(
                    'table' => 'courses',
                    'alias' => 'Course',
                    'type' => 'INNER',
                    'conditions' => 'Course.id = Subject.course_id'
                )
            ),
            'conditions' => array(
                'Group.id' => $id,
                'Course.institution_id' => Environment::institution('id'),
            ),
            'recursive' => -1
        ));

        if (!$group) {
            $this->Session->setFlash('No se ha podido acceder al grupo.');
            $this->redirect(array('controller' => 'academic_years', 'action' => 'index', 'base' => false));
        }

        $subject_id = $group['Subject']['id'];

        $this->Group->query("DELETE FROM group_requests WHERE group_id = {$id} OR group_2_id = {$id}");
        $this->Group->query("DELETE FROM events WHERE group_id = {$id}");
        $this->Group->delete($id);
        $this->Session->setFlash('El grupo ha sido eliminado correctamente');
        $this->redirect(array('controller' => 'subjects', 'action' => 'view', $subject_id));
    }

    function get($activity_id = null){
        $activity_id = $activity_id === null ? null : intval($activity_id);

        $groups = array();

        if ($activity_id) {
            $activity = $this->Group->Subject->Activity->find('first', array(
                'fields' => array('Activity.*'),
                'joins' => array(
                    array(
                        'table' => 'subjects',
                        'alias' => 'Subject',
                        'type' => 'INNER',
                        'conditions' => 'Subject.id = Activity.subject_id'
                    ),
                    array(
                        'table' => 'courses',
                        'alias' => 'Course',
                        'type' => 'INNER',
                        'conditions' => 'Course.id = Subject.course_id'
                    )
                ),
                'conditions' => array(
                    'Activity.id' => $activity_id,
                    'Course.institution_id' => Environment::institution('id'),
                ),
                'recursive' => -1
            ));

            if ($activity) {
                $groups = $this->Group->query("SELECT DISTINCT `Group`.*, scheduled FROM `groups` `Group` LEFT JOIN (SELECT group_id, sum(duration) as scheduled from events WHERE activity_id = {$activity_id} group by group_id) Event ON `Group`.id = Event.group_id WHERE `Group`.subject_id = {$activity['Activity']['subject_id']} AND `Group`.type = '{$activity['Activity']['type']}' AND (scheduled IS NULL or scheduled < {$activity['Activity']['duration']}) ORDER BY `Group`.name");

                $duration = floatval($activity['Activity']['duration']);
                foreach ($groups as $key => $group) {
                    $scheduled = floatval($group['Event']['scheduled']);
                    $groups[$key]['Event']['no_scheduled'] = number_format(max(0, $duration - $scheduled), 2);
                }
            }
        }

        $this->set('groups', $groups);
    }

    function _get_subject() {
        if ($this->params['action'] == 'add') {
            if (!empty($this->data) && isset($this->data['Group'])) {
                $subject_id = $this->data['Group']['subject_id'];
            } elseif (isset($this->params['pass']['0'])) {
                $subject_id = $this->params['pass']['0'];
            }

            return $this->Group->Subject->find('first', array(
                'conditions' => array('Subject.id' => $subject_id),
                'recursive' => -1
            ));
        } else {
            if (!empty($this->data) && isset($this->data['Group'])) {
                $group_id = $this->data['Group']['id'];
            } else {
                $group_id = $this->params['pass']['0'];
            }
            
            return $this->Group->find('first', array(
                'fields' => 'Subject.*',
                'joins' => array(
                    array(
                        'table' => 'subjects',
                        'alias' => 'Subject',
                        'type' => 'INNER',
                        'conditions' => 'Subject.id = Group.subject_id'
                    )
                ),
                'conditions' => array(
                    'Group.id' => $group_id
                ),
                'recursive' => -1
            ));
        }
    }

    function _authorize() {
        parent::_authorize();
        $administrator_actions = array('add', 'edit', 'delete');
        
        $this->set('section', 'courses');
    
        if ((array_search($this->params['action'], $administrator_actions) !== false) && ($this->Auth->user('type') != "Administrador")) {
            
            if (($this->params['action'] == 'add') || ($this->params['action'] == 'delete')) {
                return false;
            }

            $user_id = $this->Auth->user('id');
            $subject = $this->_get_subject();
            
            if ($subject && ($subject['Subject']['coordinator_id'] != $user_id) && ($subject['Subject']['practice_responsible_id'] != $user_id)) {
                return false;
            }
        }
        
        return true;
    }
}
