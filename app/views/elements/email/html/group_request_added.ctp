<p>Hola</p>
<p>Desde Academic te informarmos que el estudiante "<?php echo $user_2['first_name'] ?> <?php echo $user_2['last_name'] ?>" te solicita que le cambies el grupo.</p>
<p>
	Asignatura: <?php echo $subject['name'] ?><br />
	Nombre actividad: <?php echo $activity['name'] ?><br />
	Tu grupo elegido: <?php echo $group['name'] ?><br />
	El grupo propuesto: <?php echo $group_2['name'] ?>
</p>
<p>
	Para proceder a confirmar o cancelar el cambio accede al siguiente enlace:<br />
	<a href="<?php echo $this->Html->url('/registrations/view_students_registered/'.$subject['id'].'/'.$group_2['id'], true)."#user_{$user_2['id']}" ?>">MOSTRAR LA SOLICITUD</a>
</p>
<p>Un saludo,<br />El equipo de Academic.</p>