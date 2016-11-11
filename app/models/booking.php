<?php
require_once('models/academic_model.php');

class Booking extends AcademicModel {
	var $name = "Booking";
  var $booking_id_overlaped = null;
  var $event_id_overlaped = null;

	/**
	 * belongsTo associations
	 */
	var $belongsTo = array('Classroom');
	
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
	
	function bookingDontOverlap($initial_hour){
    $this->booking_id_overlaped = null;
    $this->event_id_overlaped = null;
    
		$initial_hour = $this->data['Booking']['initial_hour'];
		$final_hour = $this->data['Booking']['final_hour'];
		$classroom_id = $this->data['Booking']['classroom_id'];
            
		$query = "SELECT Booking.id AS id FROM bookings Booking WHERE ((Booking.initial_hour <= '{$initial_hour}' AND Booking.final_hour > '{$initial_hour}') OR (Booking.initial_hour < '{$final_hour}' AND Booking.final_hour >= '{$final_hour}') OR (Booking.initial_hour >= '{$initial_hour}' AND Booking.final_hour <= '{$final_hour}'))";
		
    if ($classroom_id != -1) {
      $query .= " AND (Booking.classroom_id = {$classroom_id} OR Booking.classroom_id = -1)";
    }
		if ((isset($this->data['Booking']['id'])) && ($this->data['Booking']['id'] > 0)) {
			$query .= " AND Booking.id <> {$this->data['Booking']['id']}";
    }
    
    $bookings_count = $this->query($query);
    if (count($bookings_count) > 0) {
      $this->booking_id_overlaped = $bookings_count[0]['Booking']['id'];
			return false;
		}

		$query = "SELECT Event.id AS id FROM events Event WHERE ((Event.initial_hour <= '{$initial_hour}' AND Event.final_hour > '{$initial_hour}') OR (Event.initial_hour < '{$final_hour}' AND Event.final_hour >= '{$final_hour}') OR (Event.initial_hour >= '{$initial_hour}' AND Event.final_hour <= '{$final_hour}'))";
		if ($classroom_id != -1) {
      $query .= "  AND Event.classroom_id = {$classroom_id}";
    }
    
		$events_count = $this->query($query);
    if (count($events_count) > 0) {
      $this->event_id_overlaped= $events_count[0]['Event']['id'];
			return false;
		} else {
			return true;
    }
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
		$date_components = split("-", $date->format('Y-m-d-H-i-s'));
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
			'conditions' => array('Booking.initial_hour >= ' => $date . ' 00:00:00', 'Booking.final_hour <=' => $date . ' 23:59:59'),
			'order' => array('Booking.initial_hour'),
		));
	}
}
?>
