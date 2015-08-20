<?php
	if (count($groups) > 0){
		echo "<option value=''>Seleccione un grupo</option>";
		foreach ($groups as $group):
			echo "<option value='{$group['Group']['id']}'>{$group['Group']['name']} ({$group['Event']['no_scheduled']} horas pendiente)</option>";
		endforeach;
	}
	else
		echo "<option value=''>No quedan grupos por programar</option>"
?>