<?php 
	if (count($activities) == 0)
		echo "No existe ninguna actividad con el nombre especificado";
	else {
		foreach ($activities as $activity):	
			$activity_name = str_replace('|', '/', $activity['Activity']['name']);
			$subject_name = str_replace('|', '/', $activity['Subject']['name']);
			$course_name = str_replace('|', '/', $activity['Course']['name']);
			echo "$activity_name - {$activity['Subject']['code']} $subject_name - $course_name|{$activity['Activity']['id']}\n" ;
		endforeach;
	} 
?>

