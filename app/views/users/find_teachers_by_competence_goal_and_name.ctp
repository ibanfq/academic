<?php 
	if (count($users) == 0)
		echo "No existe ningún profesor con el nombre especificado";
	else {
		foreach ($users as $user):	
			echo "{$user['User']['first_name']} {$user['User']['last_name']}|{$user['User']['id']}\n" ;
		endforeach;
	} 
?>

