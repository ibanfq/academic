<h1>Recordar contraseña</h1>
<p>Introduzca su dirección de correo electrónico y haga clic en Recordar. Se generará una nueva contraseña de forma automática que será enviada a su dirección de correo.</p>
<?php
	echo $form->create('User', array('action' => 'rememberPassword'));
	echo $form->input('username', array('label' => 'Correo electrónico', 'autocorrect' => 'off', 'autocapitalize' => 'none'));
	echo $form->end('Recordar');
?>