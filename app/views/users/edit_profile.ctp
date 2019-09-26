<!-- File: /app/views/users/edit.ctp -->
<?php $html->addCrumb("Mi perfil", "/editProfile"); ?>

<h1>Modificar usuario</h1>
<?php
	echo $form->create('User', array('action' => 'editProfile'));
?>
	<fieldset>
	<legend>Datos generales</legend>
		<?php
			if ($auth->user('type') == "Estudiante" || $auth->user('type') == "Profesor") {
				echo "<dl><dt><label>Nombre</label></dt><dd>{$user['User']['first_name']}</dd></dl>";
				echo "<dl><dt><label>Apellidos</label></dt><dd>{$user['User']['last_name']}</dd></dl>";
				echo "<dl><dt><label>DNI sin letra</label></dt><dd>{$user['User']['dni']}</dd></dl>";
				echo "<dl><dt><label>Teléfono</label></dt><dd>{$user['User']['phone']}</dd></dl>";
			} else {
				if ($auth->user('__LOGGED_WITH_CAS__')) {
					echo "<div><dl><dt><label>Nombre</label></dt><dd>{$user['User']['first_name']}</dd></dl></div>";
					echo "<div><dl><dt><label>Apellidos</label></dt><dd>{$user['User']['last_name']}</dd></dl></div>";
				} else {
					echo $form->input('first_name', array('label' => 'Nombre', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); 
					echo $form->input('last_name', array('label' => 'Apellidos', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>'));
				}
				echo $form->input('dni', array('label' => 'DNI sin letra', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>'));
				echo $form->input('phone', array('label' => 'Teléfono', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>'));
			}
			if ($auth->user('type') == "Profesor" || $auth->user('type') == "Administrador") {
				echo $form->input('notify_all', array('label' => 'Activar el envío de correos automáticos si olvida pasar la asistencia'));
			}
		?>
	</fieldset>
	
	<fieldset>
	<p>Para modificar su contraseña, debe introducir su contraseña actual y, a continuación, introducir la nueva contraseña, la cual debe repetir en el campo "Confirmación".</p> 
	<legend>Contraseña</legend>
		<?php
			
			echo $form->input("old_password", array('type' => 'password', 'label' => 'Contraseña actual', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>'));
			echo $form->input("new_password", array('type' => 'password', 'label' => 'Nueva contraseña', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>'));
			echo $form->input("password_confirmation", array('type' => 'password', 'label' => 'Confirmación', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>'));
		?>
	</fieldset>
<?php
	echo $form->end('Actualizar');
?>