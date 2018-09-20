var events = [
<?php 
	
	$events_array = array();
	foreach($events as $event):
		$initial_date = date_create($event['events']['initial_hour']);
		$final_date = date_create($event['events']['final_hour']);
		array_push($events_array,"{id: '{$event['events']['id']}', start: '{$initial_date->format('Y-m-d H:i:s')}', end: '{$final_date->format('Y-m-d H:i:s')}', title: '{$event['activities']['activity']} ({$event['subjects']['acronym']}) ', allDay: false, className: '{$activityHelper->getActivityClassName($event['activities']['type'])}'}");
	endforeach;
	echo implode($events_array, ",");
?>
];

$('#calendar').fullCalendar('addEventSource', events);
$('#calendar').fullCalendar('refetchEvents');
