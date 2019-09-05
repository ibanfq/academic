<?php

App::import('model', 'academicModel');

class Activity extends AcademicModel {
    var $name = "Activity";

    var $belongsTo = array('Subject');

    var $hasMany = array('Registration');

    var $validate = array(
        'name' => array(
            'notEmpty' => array(
                'rule' => 'notEmpty',
                'required' => true,
                'message' => 'Debe especificar un nombre para la actividad'
            ),
            'unique' => array(
                'rule' => array('nameMustBeUnique'),
                'on' => 'create',
                'message' => 'Ya existe una actividad con este nombre en la asignatura'
            )
        ),
        'type' => array(
            'rule' => 'notEmpty',
            'required' => true,
            'message' => 'Debe especificar un tipo para la actividad'
            ),
        'duration' => array(
            'notEmpty' => array(
                'rule' => 'notEmpty',
                'message' => 'Debe especificar una duración para la actividad'
                ),
            'isNumeric' => array(
                'rule' => 'numeric',
                'message' => 'La duración debe ser un número (p.ej 10)'
                ),
            'greter_than_0' => array(
                'rule' => array('comparison', ">", 0),
                'message' => 'La duración debe ser mayor que 0'
                )
            )
        );

    function _exists($id){
        $activity = $this->find('first', array(
            'conditions' => array(
                'Activity.id' => $id
            ),
            'recursive' => -1
        ));

        return ($activity != null);
    }
    
    function _existsAndGroupOpened($activity_id, $group_id) {
        $activity_id = intval($activity_id);
        $group_id = intval($group_id);
        $activity = $this->query("SELECT Activity.id, Activity.inflexible_groups, DATEDIFF(MIN(Event.initial_hour), CURDATE()) as days_to_start, UNIX_TIMESTAMP(MAX(Event.final_hour)) - UNIX_TIMESTAMP() as time_to_end FROM events Event INNER JOIN activities Activity ON Activity.id = Event.activity_id WHERE Event.activity_id = $activity_id AND Event.group_id = $group_id");
        if ($activity && $activity[0]['Activity']['id']) {
            if (Configure::read('app.registration.flexible_groups')) {
                $ended = $activity[0][0]['time_to_end'] < 0;
                $until_days_to_start = Configure::read('app.activity.teacher_can_block_groups_if_days_to_start');
                return !$ended && (!is_int($until_days_to_start) || !$activity[0]['Activity']['inflexible_groups'] || $until_days_to_start < $activity[0][0]['days_to_start']);
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
    
    function _existsAndGroupNotEnded($activity_id, $group_id) {
        $activity_id = intval($activity_id);
        $group_id = intval($group_id);
        $activity = $this->query("SELECT Activity.id, Activity.inflexible_groups, DATEDIFF(MIN(Event.initial_hour), CURDATE()) as days_to_start, UNIX_TIMESTAMP(MAX(Event.final_hour)) - UNIX_TIMESTAMP() as time_to_end FROM events Event INNER JOIN activities Activity ON Activity.id = Event.activity_id WHERE Event.activity_id = $activity_id AND Event.group_id = $group_id");
        if ($activity && $activity[0]['Activity']['id']) {
            return $activity[0][0]['time_to_end'] >= 0;
        } else {
            return false;
        }
    }

    /**
     * Validates that a combination of name, subject is unique
     */
    function nameMustBeUnique($name){
        $activity = $this->data[$this->alias];
        return $this->find('count', array('conditions' => array(
            'Activity.name' => $name,
            'Activity.subject_id' => $activity['subject_id'],
        ))) == 0;
    }
    
    function afterSave($created) {
        if ($created && $this->typeIsPractice($this->data['Activity']['type'])) {
            $subject_id = intval($this->data['Activity']['subject_id']);
            $users = $this->query("Select SubjectStudent.user_id FROM subjects_users SubjectStudent WHERE SubjectStudent.subject_id = {$subject_id} AND practices_approved");
            $new_registrations = array();
            foreach ($users as $user) {
                $user_id = $user['SubjectStudent']['user_id'];
                $new_registrations[]= array('Registration' => array(
                    'activity_id' => $this->id,
                    'student_id' => $user_id,
                    'group_id' => -1
                ));
            }
            if (!empty($new_registrations)) {
                $this->Registration->unbindModel(array('hasOne' => array('User', 'Activity', 'Group')));
                $this->Registration->saveAll($new_registrations);
            }
        }
    }
    
    function typeIsPractice($type) {
        $prefix = 'Práctica';
        return substr($type, 0, strlen($prefix)) === $prefix;
    }
}
