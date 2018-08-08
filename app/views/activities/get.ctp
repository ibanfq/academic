<?php
	if (count($activities) > 0){
		echo "<option value=''>Seleccione una actividad</option>";
		foreach ($activities as $activity):
			echo "<option value='{$activity['Activity']['id']}' data-type='{$activity['Activity']['type']}'>{$activity['Activity']['name']}</option>";
		endforeach;
	}
	else
		echo "<option value=''>No existen actividades</option>"
?>