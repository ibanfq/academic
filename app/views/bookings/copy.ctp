<?php 
if (isset($notAllowed)) { ?>
	$('#notice').removeClass('success');
  $('#notice').addClass('error');
  $('#notice').html("Usted no tiene permisos para crear ese tipo de reserva. Solo un administrador pueden hacerlo.");
<?php } elseif (isset($bookings)) { ?>
	if (currentEvent != null){
		$('#calendar').fullCalendar('removeEventSource', currentEvent);
		$('#calendar').fullCalendar('refetchEvents');
	}
	events = [<?php 
		$bookings_array = array();
		foreach($bookings as $booking):
			$parent_id = json_encode($booking['Booking']['parent_id'] ? "booking_{$booking['Booking']['parent_id']}" : null);
			$initial_date = date_create($booking['Booking']['initial_hour']);
			$final_date = date_create($booking['Booking']['final_hour']);
			$title = json_encode($booking['Booking']['reason']);
			$deletable = json_encode(call_user_func($authorizeDelete, $booking));

			array_push($bookings_array,"{id: 'booking_{$booking['Booking']['id']}', parent_id: {$parent_id}, start: '{$initial_date->format('Y-m-d H:i:s')}', end: '{$final_date->format('Y-m-d H:i:s')}', title: {$title}, allDay: false, className: 'booking', deletable: {$deletable}}");
		endforeach;
		echo implode($bookings_array, ",");
	?>];
	$('#form_container').hide();
	$('#notice').removeClass('error');
	$('#notice').html('Las reservas se han añadido correctamente');
	$('#notice').addClass('success');
	$('#calendar').fullCalendar('addEventSource', events);
	$('#calendar').fullCalendar('refetchEvents');
	$('#calendar').fullCalendar('render');

<?php } elseif (isset($booking_overlaped)) { ?>
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
<?php } elseif (isset($event_overlaped)) { ?>
    <?php 
    $initial_date = date_create($event_overlaped['Event']['initial_hour']);
		$message = "No ha sido posible crear la/s reserva/s en la fecha señalada porque coincide el día <strong>{$initial_date->format('d-m-Y')}</strong> con la actividad <strong>{$activity_overlaped['Activity']['name']}</strong> de la asignatura <strong>{$activity_overlaped['Subject']['name']}</strong> del aula <strong>{$event_overlaped['Classroom']['name']}</strong>";
    ?>
		$('#notice').removeClass('success');
		$('#notice').addClass('error');
		$('#notice').html("<?php echo addslashes($message) ?>");
<?php } else { ?>
		$('#notice').removeClass('success');
		$('#notice').addClass('error');
		$('#notice').html("<?php 
		echo "No ha sido posible crear la/s reserva/s debido a que coinciden con otra reserva u otra actividad académica";?>");
<?php } ?>
