<?php if (isset($notAllowed)): ?>
	$('#notice').removeClass('success');
	$('#notice').addClass('error');
	$('#notice').html("No tienes permisos para realizar esta acción");
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
	$('#notice').html('El evento se han añadido correctamente');
	$('#notice').addClass('success');
	$('#calendar').fullCalendar('addEventSource', events);
	$('#calendar').fullCalendar('refetchEvents');
	$('#calendar').fullCalendar('render');
<?php elseif (isset($booking_overlaped)): ?>
	<?php 
    	$initial_date = date_create($booking_overlaped['Booking']['initial_hour']);
		$message = "No ha sido posible crear la/s reserva/s en la fecha señalada porque coincide el día <strong>{$initial_date->format('d-m-Y')}</strong> con la reserva <strong>{$booking_overlaped['Booking']['reason']}</strong>";
	    if ($booking_overlaped['Classroom']['name']) {
	      $message .= " del aula <strong>{$booking_overlaped['Classroom']['name']}</strong>";
	    }
    ?>
	$('#notice').removeClass('success');
	$('#notice').addClass('error');
	$('#notice').html("<?php echo addslashes($message) ?>");
<?php elseif (isset($event_overlaped)): ?>
    <?php 
    	$initial_date = date_create($event_overlaped['Event']['initial_hour']);
		$message = "No ha sido posible crear la/s reserva/s en la fecha señalada porque coincide el día <strong>{$initial_date->format('d-m-Y')}</strong> con la actividad <strong>{$activity_overlaped['Activity']['name']}</strong> de la asignatura <strong>{$activity_overlaped['Subject']['name']}</strong> del aula <strong>{$event_overlaped['Classroom']['name']}</strong>";
    ?>
	$('#notice').removeClass('success');
	$('#notice').addClass('error');
	$('#notice').html("<?php echo addslashes($message) ?>");
<?php elseif (empty($invalidFields) || isset($invalidFields['initial_hour']) && $invalidFields['initial_hour'] === 'eventDurationDontExceedActivityDuration'): ?>
	$('#notice').removeClass('success');
	$('#notice').addClass('error');
	$('#notice').html("El evento no ha podido programarse porque exceden la duración de la actividad");
<?php else: ?>
	$('#notice').removeClass('success');
	$('#notice').addClass('error');
	$('#notice').html("<?php echo h(current(array_values($invalidFields))) ?>");
<?php endif; ?>
