<?php if (empty($ok)) { ?>

	$('#notice').removeClass('success');
	$('#notice').addClass('error');
	
	<?php if (isset($notAllowed)): ?>
		$('#notice').html("No tienes permisos para realizar esta acción");
	<?php elseif (isset($booking_overlaped)): ?>
		$('#notice').html("<?php
			$initial_date = date_create($booking_overlaped['Booking']['initial_hour']);
			$message = "No ha sido posible actualizar el evento porque coincide el día <strong>{$initial_date->format('d-m-Y')}</strong> con la reserva <strong>{$booking_overlaped['Booking']['reason']}</strong>";
			if ($booking_overlaped['Classroom']['name']) {
				$message .= " del aula <strong>{$booking_overlaped['Classroom']['name']}</strong>";
			}
			echo ($message);
		?>");
	<?php elseif (isset($event_overlaped)): ?>
		$('#notice').html("<?php
			$initial_date = date_create($event_overlaped['Event']['initial_hour']);
			$message = "No ha sido posible actualizar el evento porque coincide el día <strong>{$initial_date->format('d-m-Y')}</strong> con la actividad <strong>{$activity_overlaped['Activity']['name']}</strong> de la asignatura <strong>{$activity_overlaped['Subject']['name']}</strong> del aula <strong>{$event_overlaped['Classroom']['name']}</strong>";
			echo ($message);
		?>");
	<?php else: ?>
		$('#notice').html("Ha ocurrido algún error y el evento no ha podido actualizarse correctamente");
	<?php endif ?>
	
<?php } else {?>
	
	$('#notice').removeClass('error');
	$('#notice').html('El evento se ha actualizado correctamente.');
	$('#notice').addClass('success');
	
<?php } ?>