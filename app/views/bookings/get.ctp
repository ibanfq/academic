var events = [
<?php 
	
	$bookings_array = array();
	foreach($bookings as $booking):
		$parent_id = json_encode($booking['Booking']['parent_id'] ? "booking_{$booking['Booking']['parent_id']}" : null);
		$initial_date = date_create($booking['Booking']['initial_hour']);
		$final_date = date_create($booking['Booking']['final_hour']);
		$title = json_encode($booking['Booking']['reason']);
		$deletable = json_encode($authorizeDelete($booking));

		array_push($bookings_array,"{id: 'booking_{$booking['Booking']['id']}', parent_id: {$parent_id}, start: '{$initial_date->format('Y-m-d H:i:s')}', end: '{$final_date->format('Y-m-d H:i:s')}', title: {$title}, allDay: false, className: 'booking', deletable: {$deletable}}");
	endforeach;
	echo implode($bookings_array, ",");
?>
];
$('#calendar').fullCalendar('addEventSource', events);
$('#calendar').fullCalendar('refetchEvents');