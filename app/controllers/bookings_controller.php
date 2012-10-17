<?php
	class BookingsController extends AppController {
		var $name = 'Bookings';
		var $paginate = array('limit' => 10, 'order' => array('Bookings.initial_hour' => 'asc'));
		var $helpers = array('Ajax', 'activityHelper');
		
		function index(){
			$this->set('section', 'bookings');
			$classrooms = array();
			foreach($this->Booking->Classroom->find('all', array('order' => array('Classroom.name'))) as $classroom):
				$classrooms["{$classroom['Classroom']['id']}"] = $classroom['Classroom']['name'];
			endforeach;
			
			$this->set('classrooms', $classrooms);
		}
		
		function add($finished_at = null, $frequency = null) {
			if (($finished_at != null) && ($frequency != null)) {
				$initial_hour = new DateTime($this->data['Booking']['initial_hour']);
				$final_hour = new DateTime($this->data['Booking']['final_hour']);
				$finished_at = new DateTime($this->_parse_date($finished_at, "-"));
				
				$this->data['Booking']['user_id'] = $this->Auth->user('id');
				$bookings = array();
				
				while ($finished_at->format('Ymd') >= $initial_hour->format('Ymd')) {
					if ($this->Booking->save($this->data)){
						
						$current_booking = $this->Booking->read();
						
						if (!isset($this->data['Booking']['parent_id']))
							$this->data['Booking']['parent_id'] = $current_booking['Booking']['id'];
						array_push($bookings, $current_booking);
						$this->_add_days($initial_hour, $frequency);
						$this->_add_days($final_hour, $frequency);
						$this->data['Booking']['initial_hour'] = $initial_hour->format('Y-m-d H:i:s');
						$this->data['Booking']['final_hour'] = $final_hour->format('Y-m-d H:i:s');
						$this->Booking->id = null;
						$this->data['Booking']['id'] = null;
					}
					else 
					{
						if ($this->data['Booking']['parent_id'] != null)
							$this->Booking->query("DELETE FROM bookings WHERE id = {$this->data['Booking']['parent_id']} OR parent_id = {$this->data['Booking']['parent_id']}");
						
						unset($bookings);
						
						break;
					}
				}
				
				if (isset($bookings)) {
					$this->set('bookings', $bookings);
				} 
			}
			else {
				$this->data['Booking']['user_id'] = $this->Auth->user('id');
				if ($this->Booking->save($this->data)){
					$this->set('success', true);
					$booking = $this->Booking->read();
					
					$this->set('bookings', array($booking));
				}
			}
		}
		
		function get($classroom_id = null){
			$bookings = $this->Booking->query("SELECT DISTINCT Booking.id, Booking.initial_hour, Booking.final_hour, Booking.reason FROM bookings Booking WHERE Booking.classroom_id = {$classroom_id}");
			
			$this->set('bookings', $bookings);
		}
		
		function view($id = null){
			$this->set('booking', $this->Booking->findById($id));
		}
		
		function delete($id=null) {
			$this->Booking->id = $id;
			$booking = $this->Booking->read();
			
			$ids = $this->Booking->query("SELECT Booking.id FROM bookings Booking where Booking.id = {$id} OR Booking.parent_id = {$id}");
			$this->Booking->query("DELETE FROM bookings WHERE id = {$id} OR parent_id = {$id}");
			$this->set('bookings', $ids);
		}
		
		function update($id, $deltaDays, $deltaMinutes, $resize = null) {
			$this->Booking->id = $id;
			$booking = $this->Booking->read();
			$uid = $this->Auth->user('id');
			if (($booking['Booking']['user_id'] == $uid) || ($this->Auth->user('type') == "Administrador") || ($this->Auth->user('type') == "Administrativo") ) {
				
				if ($resize == null) {
					$initial_hour = date_create($booking['Booking']['initial_hour']);
					$this->_add_days($initial_hour, $deltaDays, $deltaMinutes);
					$booking['Booking']['initial_hour'] = $initial_hour->format('Y-m-d H:i:s');
				}
			
				$final_hour = date_create($booking['Booking']['final_hour']);
				$this->_add_days($final_hour, $deltaDays, $deltaMinutes);
				$booking['Booking']['final_hour'] = $final_hour->format('Y-m-d H:i:s');

				if (!($this->Booking->save($booking))){
					$booking = $this->Booking->read();
					$this->set('booking', $booking);
				}
			} else
				$this->set('notAllowed', true);
		}
		
		function _add_days(&$date, $ndays, $nminutes = 0){
			$date_components = split("-", $date->format('Y-m-d-H-i-s'));
			$timestamp = mktime($date_components[3],$date_components[4],$date_components[5], $date_components[1], $date_components[2] + $ndays, $date_components[0]);
			$timestamp += ($nminutes * 60);
			$date_string = date('Y-m-d H:i:s', $timestamp);
			$date = new DateTime($date_string);
		}
		
		function _parse_date($date, $separator = "/"){
			$date_components = split($separator, $date);
			
			return count($date_components) != 3 ? false : date("Y-m-d", mktime(0,0,0, $date_components[1], $date_components[0], $date_components[2]));
		}
		
		function _authorize() {
			parent::_authorize();
			
			$this->set('bookings_schedule', true);
		
			if (($this->params['action'] == "get") || ($this->params['action'] == "view"))
				return true;
				
			if (($this->Auth->user('type') != "Administrador") && ($this->Auth->user('type') != "Conserje")) 
				return false;
			
			return true;
		}
	}
?>
