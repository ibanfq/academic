<?php 
	if (count($classrooms) == 0)
		echo "No existe ningún aula con el nombre especificado";
	else {
		foreach ($classrooms as $classroom):	
			echo "{$classroom['Classroom']['name']}|{$classroom['Classroom']['id']}\n" ;
		endforeach;
	} 
?>

