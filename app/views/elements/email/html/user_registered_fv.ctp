<p>Hola</p>
<p>Usted ha sido dado de alta en el gestor académico de la Facultad de Veterinaria (Academic) con los siguientes datos de acceso:</p>
<p>
	Nombre de usuario: <?php echo $user['User']['username'] ?><br />
	<?php if (isset($password)): ?>Contraseña: <?php echo $password ?><br /><?php endif; ?>
</p>
<p>Un saludo,<br />El equipo de Academic.</p>