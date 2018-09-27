<?php if (isset($eventExceedDuration)): ?>
	$('#notice').removeClass('success');
	$('#notice').addClass('error');
	$('#notice').html("Los eventos no han podido programarse porque exceden la duración de la actividad");
<?php elseif (isset($events)): ?>
	if (currentEvent != null) {
		$('#calendar').fullCalendar('removeEventSource', currentEvent);
		$('#calendar').fullCalendar('refetchEvents');
	}
	events = [<?php 
		$events_array = array();
		foreach($events as $event):
			$parent_id = json_encode($event['Event']['parent_id'] ? (string) $event['Event']['parent_id'] : null);
			$initial_date = date_create($event['Event']['initial_hour']);
			$final_date = date_create($event['Event']['final_hour']);
			$title = json_encode("{$event['Activity']['name']} ({$subject['Subject']['acronym']})");
			$className = json_encode($activityHelper->getActivityClassName($event['Activity']['type']));
			$deletable = json_encode($authorizeDelete($event));

			array_push($events_array,"{id: '{$event['Event']['id']}', parent_id: {$parent_id}, start: '{$initial_date->format('Y-m-d H:i:s')}', end: '{$final_date->format('Y-m-d H:i:s')}', title: {$title}, allDay: false, className: {$className}, deletable: {$deletable}}");
		endforeach;
		echo implode($events_array, ",");
	?>];
	$('#form_container').hide();
	$('#notice').removeClass('error');
	$('#notice').html('Los eventos se han añadido correctamente');
	$('#notice').addClass('success');
	$('#calendar').fullCalendar('addEventSource', events);
	$('#calendar').fullCalendar('refetchEvents');
	$('#calendar').fullCalendar('render');
<?php else: ?>
	$('#notice').removeClass('success');
	$('#notice').addClass('error');
	<?php if (isset($invalidFields['initial_hour']) && $invalidFields['initial_hour'] === 'eventDontOverlap'): ?>
		<?php $initial_date = date_create($event['Event']['initial_hour']); ?>
		$('#notice').html("<?php echo "No ha sido posible crear el evento en la fecha señalada porque coincide el día <strong>{$initial_date->format('d-m-Y')}</strong> con la actividad <strong>{$activity['Activity']['name']}</strong> de la asignatura <strong>{$activity['Subject']['name']}</strong> del aula <strong>{$event['Classroom']['name']}</strong>" ?>");
	<?php else: ?>
		$('#notice').html("<?php echo h(current(array_values($invalidFields))) ?>");
	<?php endif; ?>
<?php endif; ?>
