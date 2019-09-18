<?php
/**
 * User model
 *
 * @version 2012-06-04
 */
class User extends AppModel {
    /**
     * PHP4 compatibility
     */
    var $name = 'User';

    /**
     * HABTM relationships
     */
    var $hasAndBelongsToMany = array(
        'Subject' => array('unique' => false, 'order' => 'Subject.level ASC, Subject.name ASC')
    );

    /**
     * hasMany relationships
     */
    var $hasMany = array(
        'Registration' => array('foreignKey' => 'student_id')
    );

    /**
     * Validation rules
     */
    var $validate = array(
        'username' => array(
            'isUnique' => array(
                'rule' => 'isUnique',
                'message' => 'Ya existe un usuario con el mismo correo electrónico.'
            ),
            'notEmpty' => array(
                'rule' => 'notEmpty',
                'required' => true,
                'message' => 'El correo electrónico no puede estar vacío'
            )
        ),
        'dni' => array(
            'unique' => array(
                'rule' => array('dniMustBeUnique'),
                'message' => 'Ya existe un usuario del mismo tipo con el mismo DNI.'
            )
        ),
        'password' => array(
            'rule' => array('minLength', '6'),
            'message' => 'Debe tener entre 5 y 10 caracteres'
        ),
        'type' => array(
            'rule' => 'notEmpty',
            'message' => 'Debe especificar el tipo de usuario'
        ),
        'first_name' => array(
            'rule' => 'notEmpty',
            'message' => 'Debe especificar el nombre de pila'
        )
    );

    function __construct($id = false, $table = null, $ds = null) {
        $db = $this->getDataSource();

        if (Environment::institution('id')) {
            $this->hasAndBelongsToMany['Subject']['conditions'][] = array("
                Subject.course_id IN (
                    SELECT Course.id
                    FROM courses Course
                    WHERE Course.institution_id = {$db->value(Environment::institution('id'))}
                )
            ");

            $this->hasMany['Registration']['conditions'][] = array("
                Registration.activity_id IN (
                    SELECT Activity.id FROM activities Activity
                    INNER JOIN subjects Subject ON Subject.id = Activity.subject_id
                    INNER JOIN courses Course ON Course.id = Subject.course_id
                    WHERE Course.institution_id = {$db->value(Environment::institution('id'))}
                )
            ");
        }

        parent::__construct($id, $table, $ds);
    }
    
    function getTypeOptions() {
        return array("Administrador" => "Administrador", "Administrativo" => "Administrativo" , "Conserje" => "Conserje",  "Profesor" => "Profesor", "Estudiante" => "Estudiante", "Becario" => "Becario");
    }

    function getTypes() {
        return array_keys($this->getTypeOptions());
    }

    function getCalendarToken() {
        $security = Security::getInstance();
        $key = Configure::read('Security.calendarTokenSeed');
        $value = $this->id.' ';
        if (empty($this->data['User']['created'])) {
            $value .= $this->data['User']['username'];
        } else {
            $value .= $this->data['User']['created'];
        }
        return rtrim(strtr(base64_encode($security->cipher($value, $key)), '+/', '-_'), '=');
    }

    /**
     * Validates that a combination of dni, type is unique
     */
    function dniMustBeUnique($dni){
        $user = $this->data[$this->alias];
        $dni = trim($user['dni']);

        if (empty($dni)) {
            return true;
        }

        $conditions = array(
            "{$this->alias}.dni" => $dni,
            "{$this->alias}.type" => $user['type'],
        );

        if (!empty($this->id)) {
            $conditions[] = array("{$this->alias}.id !=" => $this->id);
        }

        return $this->find('count', array('conditions' => $conditions)) == 0;
    }
    
    function findByCalendarToken($token) {
        $key = Configure::read('Security.calendarTokenSeed');
        $security = Security::getInstance();
        $value = $security->cipher(base64_decode(strtr($token, '-_', '+/')), $key);
        $value = explode(' ', $value, 2);
        if (count($value) !== 2) {
            return false;
        }
        list($id, $field2) = $value;
        if (!is_numeric($id)) {
            return false;
        }
        return $this->find('first', array(
            'joins' => array(
                array(
                    'table' => 'users_institutions',
                    'alias' => 'UserInstitution',
                    'type' => 'INNER',
                    'conditions' => array(
                        'UserInstitution.user_id = User.id',
                        'UserInstitution.institution_id' => Environment::institution('id'),
                        'UserInstitution.active'
                    )
                )
            ),
            'conditions' => array(
                'User.id' => $id,
                'OR' => array(
                    'User.username' => $field2,
                    'User.created' => $field2
                )
            ),
            'recursive' => 0,
        ));
    }
    
    function getEvents($from_timestamp = null, $to_timestamp = null) {
        $id = intval($this->id);

        $db = $this->getDataSource();

        $select = 'DISTINCT Event.id, Event.parent_id, Event.initial_hour, Event.final_hour, Event.owner_id, Event.activity_id, Activity.name, Activity.type, Event.group_id, `Group`.name, Subject.id, Subject.coordinator_id, Subject.practice_responsible_id, Subject.acronym';
        $from = 'events Event';
        $conditions = array();
        
        if (Environment::institution('id')) {
            $conditions[] = "Event.classroom_id IN (SELECT classroom_id FROM classrooms_institutions ClassroomInstitution WHERE ClassroomInstitution.institution_id = {$db->value(Environment::institution('id'))})";
        }

        switch($this->data['User']['type']){
            case "Estudiante":
                $from = 'registrations Registration';
                $joins = array(
                    'INNER JOIN activities Activity ON Activity.id = Registration.activity_id',
                    'INNER JOIN events Event ON Event.group_id = Registration.group_id AND Event.activity_id = Registration.activity_id',
                    "INNER JOIN subjects Subject ON Subject.id = Activity.subject_id",
                    'INNER JOIN groups `Group` ON `Group`.id = Event.group_id'
                );
                $conditions[] = "Registration.student_id = {$db->value($id)} AND Registration.group_id <> -1";
                break;
            case "Profesor":
            case "Administrador":
                $joins = array(
                    'INNER JOIN activities Activity ON Activity.id = Event.activity_id',
                    'INNER JOIN subjects Subject ON Subject.id = Activity.subject_id',
                    'INNER JOIN groups `Group` ON `Group`.id = Event.group_id'
                );
                $conditions[] = "Event.teacher_id = {$db->value($id)} OR Event.teacher_2_id = {$db->value($id)}";
                break;
            default:
                return array();
        }
        
        if ($from_timestamp) {
            $from_date = date('Y-m-d H:i:s', $from_timestamp);
            $conditions[] = "Event.initial_hour >= '$from_date'";
        }

        if ($to_timestamp) {
            $to_date = date('Y-m-d H:i:s', $to_timestamp);
            $conditions[] = "Event.initial_hour <= '$to_date'";
        }

        $with_joins = implode(' ', $joins);
        $where = implode(' AND ', $conditions);

        return $this->query("SELECT $select FROM $from $with_joins WHERE $where");
    }
  
    function getBookings() {
        $id = intval($this->id);
        $userType = $this->data['User']['type'];

        $db = $this->getDataSource();

        switch($userType) {
            case "Estudiante":
                $whereUserType = "(Booking.user_type = 'Todos' OR Booking.user_type = 'Estudiante')";
                break;
            case "Administrador":
            $whereUserType = "(Booking.user_type = 'Todos' OR Booking.user_type = 'No-estudiante' OR Booking.user_type = 'Administrador' OR Booking.user_type = 'Profesor')";
                break;
            default:
                App::import('Core', 'Sanitize');
                $userType = Sanitize::escape($userType);
                $whereUserType = "(Booking.user_type = 'Todos' OR Booking.user_type = 'No-estudiante' OR Booking.user_type = '$userType')";
        }

        if (Environment::institution('id')) {
            $bookings = $this->query("
                SELECT DISTINCT Booking.id, Booking.initial_hour, Booking.final_hour, Booking.reason
                FROM bookings Booking
                LEFT JOIN users_booking UserBooking ON Booking.id = UserBooking.booking_id
                WHERE
                (
                    (Booking.classroom_id = -1 AND Booking.institution_id = {$db->value(Environment::institution('id'))})
                    OR (Booking.classroom_id IN (SELECT classroom_id FROM classrooms_institutions ClassroomInstitution WHERE ClassroomInstitution.institution_id = {$db->value(Environment::institution('id'))}))
                ) AND ($whereUserType OR UserBooking.user_id = {$id})
            ");
        } else {
            $bookings = $this->query("
                SELECT DISTINCT Booking.id, Booking.initial_hour, Booking.final_hour, Booking.reason
                FROM bookings Booking
                LEFT JOIN users_booking UserBooking ON Booking.id = UserBooking.booking_id
                WHERE $whereUserType OR UserBooking.user_id = {$id}
            ");
        }

        return $bookings;
    }
        
    function can_send_alerts($user_id, $activity_id, $group_id) {
        $user_id = intval($user_id);
        $activity_id = intval($activity_id);
        $group_id = intval($group_id);
        $result = $this->query("SELECT count('') as events_count FROM events WHERE activity_id = {$activity_id} AND group_id = {$group_id} AND (teacher_id = {$user_id} OR teacher_2_id = {$user_id})");
        return $result[0][0]['events_count'] > 0;
    }

    /**
     * Returns the number of teaching hours
     *
     * @param integer $teacher_id ID of a teacher
     * @param integer $course_id ID of a course
     * @param string $type Type of the teaching activity
     * @return float Number of hours teached
     * @version 2012-06-04
     */
    function teachingHours($teacher_id, $course_id, $type = 'all') {
        $teacher_id = intval($teacher_id);
        $course_id = intval($course_id);
        $activityFilter = null;
        if ($type === 'theory') {
            $activityFilter = "AND activities.type IN ('Clase magistral', 'Seminario')";
        } elseif ($type === 'practice') {
            $activityFilter = "AND activities.type IN ('Práctica en aula', 'Práctica de problemas', 'Práctica de informática', 'Práctica de microscopía', 'Práctica de laboratorio', 'Práctica clínica', 'Práctica externa', 'Taller/trabajo en grupo')";
        } else if ($type === 'other') {
            $activityFilter = "AND activities.type IN ('Tutoría', 'Evaluación', 'Otra presencial')";
        }

        return $this->query("
            SELECT SUM(IFNULL(AttendanceRegister.duration, 0)) as total
            FROM attendance_registers AttendanceRegister
            INNER JOIN activities ON activities.id = AttendanceRegister.activity_id
            INNER JOIN subjects ON subjects.id = activities.subject_id
            WHERE (AttendanceRegister.teacher_id = $teacher_id OR AttendanceRegister.teacher_2_id = $teacher_id)
            AND subjects.course_id = $course_id
            $activityFilter
        ");
    }
  
    /**
     * Returns the number of scheduled hours
     *
     * @param integer $teacher_id ID of a teacher
     * @param integer $course_id ID of a course
     * @param string $type Type of the teaching activity
     * @return float Number of hours teached
     */
    function ScheduledHours($teacher_id, $course_id, $type = 'all') {
        $teacher_id = intval($teacher_id);
        $course_id = intval($course_id);
        $activityFilter = null;
        if ($type === 'theory') {
            $activityFilter = "AND activities.type IN ('Clase magistral', 'Seminario')";
        } elseif ($type === 'practice') {
            $activityFilter = "AND activities.type IN ('Práctica en aula', 'Práctica de problemas', 'Práctica de informática', 'Práctica de microscopía', 'Práctica de laboratorio', 'Práctica clínica', 'Práctica externa', 'Taller/trabajo en grupo')";
        } else if ($type === 'other') {
            $activityFilter = "AND activities.type IN ('Tutoría', 'Evaluación', 'Otra presencial')";
        }

        return $this->query("
            SELECT SUM(IFNULL(Event.duration, 0)) as total
            FROM events Event
            INNER JOIN activities ON activities.id = Event.activity_id
            INNER JOIN subjects ON subjects.id = activities.subject_id
            WHERE (Event.teacher_id = $teacher_id OR Event.teacher_2_id = $teacher_id)
            AND subjects.course_id = $course_id
            $activityFilter
        ");
    }
}
