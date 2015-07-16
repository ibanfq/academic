<?php if (empty($ok)) { ?>
	$('#notice').removeClass('success');
	$('#notice').addClass('error');
	<?php if (isset($activity)) { ?>
		$('#notice').html("<?php
			$initial_date = date_create($event['Event']['initial_hour']);
			echo "No ha sido posible crear el evento en la fecha señalada porque coincide el día <strong>{$initial_date->format('d-m-Y')}</strong> con la actividad <strong>{$activity['Activity']['name']}</strong> de la asignatura <strong>{$activity['Subject']['name']}</strong>";
		?>");
	<?php } else { ?>
		$('#notice').html("Ha ocurrido algún error y el evento no ha podido actualizarse correctamente");
	<?php } ?>
<?php } else {?>
	$('#notice').removeClass('error');
	$('#notice').html('El evento se ha actualizado correctamente.');
	$('#notice').addClass('success');
<?php } ?>
