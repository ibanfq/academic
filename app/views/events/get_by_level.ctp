var events = [
<?php 
	
	$events_array = array();
	foreach($events as $event):
		$parent_id = json_encode($event['Event']['parent_id'] ? (string) $event['Event']['parent_id'] : null);
		$initial_date = date_create($event['Event']['initial_hour']);
		$final_date = date_create($event['Event']['final_hour']);
		$title = json_encode("{$event['Activity']['name']} ({$event['Subject']['acronym']})");
		$className = json_encode($activityHelper->getActivityClassName($event['Activity']['type']));
		$deletable = json_encode(call_user_func($authorizeDelete, $event));

		array_push($events_array,"{id: '{$event['Event']['id']}', parent_id: {$parent_id}, start: '{$initial_date->format('Y-m-d H:i:s')}', end: '{$final_date->format('Y-m-d H:i:s')}', title: {$title}, allDay: false, className: {$className}, deletable: {$deletable}}");
	endforeach;
	foreach($bookings as $booking):
		$parent_id = json_encode(null);
        $initial_date = date_create($booking['Booking']['initial_hour']);
		$final_date = date_create($booking['Booking']['final_hour']);
		$title = json_encode($booking['Booking']['reason']);
		$className = json_encode('booking');
		$deletable = json_encode(false);

        array_push($events_array, "{id: 'booking_{$booking['Booking']['id']}', parent_id: {$parent_id}, start: '{$initial_date->format('Y-m-d H:i:s')}', end: '{$final_date->format('Y-m-d H:i:s')}', title: {$title}, allDay: false, className: {$className}, deletable: {$deletable}}");
	endforeach;
	echo implode($events_array, ",");
?>
];
$('#calendar').fullCalendar('addEventSource', events);
$('#calendar').fullCalendar('refetchEvents');