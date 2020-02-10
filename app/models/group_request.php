<?php

App::import('model', 'academicModel');

class GroupRequest extends AcademicModel {
    var $name = "GroupRequest";
    var $belongsTo = array(
        'Activity', 
        'Student' => array(
            'className' => 'User',
            'foreignKey' => 'student_id'
        ),
        'Group',
        'Student_2' => array(
            'className' => 'User',
            'foreignKey' => 'student_2_id'
        ),
        'Group_2' => array(
            'className' => 'Group',
            'foreignKey' => 'group_2_id'
        )
    );
    
    
    function getUserRequests($user_id, $subject_id = null, $activity_id = null, $group_id = null) {
        $user_id = $user_id === null ? null : intval($user_id);
        $subject_id = $subject_id === null ? null : intval($subject_id);
        $activity_id = $activity_id === null ? null : intval($activity_id);
        $group_id = $group_id === null ? null : intval($group_id);

        $db = $this->getDataSource();

        if (empty($group_id)) {
            $where = "(student_id = $user_id OR student_2_id = $user_id)";
        } else {
            $group_id = intval($group_id);
            $where = "(student_id = $user_id AND group_2_id = $group_id OR student_2_id = $user_id AND group_id = $group_id)";
        }

        if (!empty($subject_id)) {
            $subject_id = intval($subject_id);
            $where .= " AND subject_id = $subject_id";
        }

        if (!empty($activity_id)) {
            $activity_id = intval($activity_id);
            $where .= " AND activity_id = $activity_id";
        }

        return $this->query("
            SELECT group_requests.* FROM group_requests
            INNER JOIN activities ON activities.id = activity_id
            INNER JOIN subjects ON subjects.id = subject_id
            INNER JOIN courses ON courses.id = course_id
            WHERE $where AND courses.institution_id = {$db->value(Environment::institution('id'))}
        ");
    }
}
