<?php if (isset($notAllowed)): ?>
	$('#notice').removeClass('success');
	$('#notice').addClass('error');
	$('#notice').html("Usted no tiene permisos para modificar esta reserva. Solo su dueño, un conserje, un administrativo o un administrador pueden hacerlo.");
<?php else: ?>
	<?php foreach($bookings as $booking): ?>
		$('#calendar').fullCalendar('removeEvents', '<?php echo "booking_{$booking['Booking']['id']}"; ?>');
	<?php endforeach; ?>

	$('#calendar').fullCalendar('refetchEvents');
<?php endif; ?>
$('#edit_form').dialog('close');
