<?php 
	if (count($subjects) == 0) {
		echo "No existe ninguna asignatura con el nombre especificado";
	} else {
		foreach ($subjects as $subject) {
			if ($course_id) {
				echo "{$subject['Subject']['code']} - {$subject['Subject']['name']}|{$subject['Subject']['id']}\n" ;
			} else {
				echo "{$subject['Subject']['code']} - {$subject['Subject']['name']} ({$subject['Degree']['name']})|{$subject['Subject']['id']}\n" ;
			}
		}
	}
?>

