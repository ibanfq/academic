<?php

App::import('model', 'academicModel');

class Booking extends AcademicModel {
    var $name = "Booking";
    var $booking_id_overlaped = null;
    var $event_id_overlaped = null;

    /**
     * belongsTo associations
     */
    var $belongsTo = array('Classroom');
    var $hasAndBelongsToMany = array(
        'Attendee' =>
            array(
                    'className'             => 'User',
                    'joinTable'             => 'users_booking',
                    'foreignKey'            => 'booking_id',
                    'associationForeignKey' => 'user_id',
                    'order'                 => array('Attendee.last_name' => 'ASC', 'Attendee.first_name' => 'ASC'),
                    'unique'                => true
                )
        );
    
    var $validate = array(
        'classroom_id' => array(
            'notEmpty' => array(
                'rule' => 'notEmpty',
                'required' => true,
                'message' => 'Debe especificar un aula para esta reserva'
                )
            ),
        'initial_hour' => array(
            'notEmpty' => array(
                'rule' => 'notEmpty',
                'required' => true,
                'message' => 'Debe especificar una fecha de inicio para esta reserva'
                ),
            'bookingDontOverlap' => array(
                'message' => array('La reserva coincide con una actividad académica u otra reserva'),
                'rule' => array('bookingDontOverlap')
                )
            ),
        'final_hour' => array(
            'notEmpty' => array(
                'rule' => 'notEmpty',
                'required' => true,
                'message' => 'Debe especificar una fecha de inicio para este evento'
                ),
            )
        );
    
    function bookingDontOverlap($initial_hour) {
        App::import('Core', 'Sanitize');

        $db = $this->getDataSource();

        $this->booking_id_overlaped = null;
        $this->event_id_overlaped = null;
        
        $initial_hour = Sanitize::escape($this->data['Booking']['initial_hour']);
        $final_hour = Sanitize::escape($this->data['Booking']['final_hour']);
        $classroom_id = intval($this->data['Booking']['classroom_id']);

        $users = array_map(
            array($db, 'value'),
            (array) Set::extract("Attendee.{n}.UserBooking.user_id", $this->data)
        );

        $user_list = empty($users) ? false : implode(',', $users);

        $conditions = array();
        $conditions[] = "
            (Booking.initial_hour <= '{$initial_hour}' AND Booking.final_hour > '{$initial_hour}')
            OR (Booking.initial_hour < '{$final_hour}' AND Booking.final_hour >= '{$final_hour}')
            OR (Booking.initial_hour >= '{$initial_hour}' AND Booking.final_hour <= '{$final_hour}')
        ";

        if ($user_list) {
            $user_list_condition = "OR EXISTS (SELECT '' FROM users_booking UserBooking WHERE UserBooking.booking_id = Booking.id AND UserBooking.user_id IN ($user_list))";
        } else {
            $user_list_condition = '';
        }

        if ($classroom_id == -1) {
            $conditions[] = "
                (Booking.classroom_id = -1 AND Booking.institution_id = {$db->value(Environment::institution('id'))})
                OR (Booking.classroom_id IN (SELECT classroom_id FROM classrooms_institutions ClassroomInstitution WHERE ClassroomInstitution.institution_id = {$db->value(Environment::institution('id'))}))
                $user_list_condition
            ";
        } else {
            $conditions[]= "
                (Booking.classroom_id = {$classroom_id})
                OR (Booking.classroom_id = -1 AND Booking.institution_id = {$db->value(Environment::institution('id'))})
                $user_list_condition
            ";
        }

        if ((isset($this->data['Booking']['id'])) && ($this->data['Booking']['id'] > 0)) {
            $id = intval($this->data['Booking']['id']);
            $conditions[] = "Booking.id <> {$id}";
        }

        $where = '(' . implode(') AND (', $conditions) . ')';
        
        $query = "SELECT Booking.id AS id FROM bookings Booking WHERE $where LIMIT 1";

        $bookings_count = $this->query($query);

        if (count($bookings_count) > 0) {
            $this->booking_id_overlaped = $bookings_count[0]['Booking']['id'];
            return false;
        }

        $conditions = array();
        $conditions[] = "
            (Event.initial_hour <= '{$initial_hour}' AND Event.final_hour > '{$initial_hour}')
            OR (Event.initial_hour < '{$final_hour}' AND Event.final_hour >= '{$final_hour}')
            OR (Event.initial_hour >= '{$initial_hour}' AND Event.final_hour <= '{$final_hour}')
        ";

        if ($user_list) {
            $user_list_condition = "OR Event.teacher_id IN ($user_list) OR Event.teacher_2_id IN ($user_list)";
        } else {
            $user_list_condition = '';
        }

        if ($classroom_id == -1) {
            $conditions[] = "
                (Event.classroom_id IN (SELECT classroom_id FROM classrooms_institutions ClassroomInstitution WHERE ClassroomInstitution.institution_id = {$db->value(Environment::institution('id'))}))
                $user_list_condition
            ";
        } else {
            $conditions[] = "
                Event.classroom_id = {$classroom_id}
                $user_list_condition
            ";
        }

        $where = '(' . implode(') AND (', $conditions) . ')';
        
        $query = "SELECT Event.id AS id FROM events Event WHERE $where LIMIT 1";
        
        $events_count = $this->query($query);

        if (count($events_count) > 0) {
            $this->event_id_overlaped= $events_count[0]['Event']['id'];
            return false;
        }
        
        return true;
    } 
    
    function beforeValidate(){
        if (!empty($this->data['Booking']['initial_hour'])) {
            $initial_hour = date_create($this->data['Booking']['initial_hour']);
            $this->data['Booking']['initial_hour'] = $initial_hour->format('Y-m-d H:i:s');
        }
        if (!empty($this->data['Booking']['final_hour'])){
            $final_hour = date_create($this->data['Booking']['final_hour']);
            $this->data['Booking']['final_hour'] = $final_hour->format('Y-m-d H:i:s');
        }
        
        if ((!empty($this->data['Booking']['initial_hour'])) && (!empty($this->data['Booking']['final_hour'])))
            $this->data['Booking']['duration'] = $this->_get_booking_duration($initial_hour, $final_hour);
        
        return true;
    }
    
    
    function _get_booking_duration($initial_hour, $final_hour) {
        // Hour, minute, second, month, day, year
        $initial_timestamp = $this->_get_timestamp($initial_hour);
        $final_timestamp = $this->_get_timestamp($final_hour);
        return ($final_timestamp - $initial_timestamp) / 3600.0;
    }
    
    function _get_timestamp($date){
        $date_components = explode('-', $date->format('Y-m-d-H-i-s'));
        return mktime($date_components[3],$date_components[4],$date_components[5], $date_components[1], $date_components[2], $date_components[0]);
    }

    /**
     * Finds all bookings on a given date
     *
     * @param date Date when bookings are active
     * @return Array of bookings
     * @since 2013-03-12
     */
    function findAllByDate($date = '') {
        if (empty($date)) {
            return array();
        }

        $db = $this->getDataSource();

        $this->Behaviors->attach('Containable');
        $this->bindModel(array(
            'belongsTo' => array(
                'Teacher' => array(
                    'className' => 'User',
                    'foreignKey' => false,
                    'conditions' => array("(Teacher.id = Booking.user_id)"),
                ),
            ),
        ));

        return $this->find('all', array(
            'fields' => array(
                'Booking.initial_hour', 'Booking.final_hour', 'Booking.reason',
                'Teacher.first_name', 'Teacher.last_name',
                'Classroom.name',
            ),
            'contain' => array('Teacher', 'Classroom'),
            'conditions' => array(
                'Booking.initial_hour >= ' => $date . ' 00:00:00',
                'Booking.final_hour <=' => $date . ' 23:59:59',
                'OR' => array(
                    "Booking.classroom_id = -1 AND Booking.institution_id = {$db->value(Environment::institution('id'))}",
                    "Booking.classroom_id IN (SELECT classroom_id FROM classrooms_institutions ClassroomInstitution WHERE ClassroomInstitution.institution_id = {$db->value(Environment::institution('id'))})"
                )
            ),
            'order' => array('Booking.initial_hour'),
        ));
    }
}
