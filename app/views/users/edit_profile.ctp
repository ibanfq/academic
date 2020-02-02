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
					echo "<div><dl><dt><label>DNI sin letra</label></dt><dd>{$user['User']['dni']}</dd></dl></div>";
				} else {
					echo $form->input('first_name', array('label' => 'Nombre', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); 
					echo $form->input('last_name', array('label' => 'Apellidos', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>'));
					echo $form->input('dni', array('label' => 'DNI sin letra', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>'));
				}
				echo $form->input('phone', array('label' => 'Teléfono', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>'));
			}
			if ($auth->user('type') == "Profesor" || $auth->user('type') == "Administrador") {
				echo $form->input('notify_all', array('label' => 'Activar el envío de correos automáticos si olvida pasar la asistencia'));
			}
		?>
	</fieldset>
	
	<?php /*if ($this->data['User']['password'] !== null): ?>
		<fieldset>
		<p>Para modificar su contraseña, debe introducir su contraseña actual y, a continuación, introducir la nueva contraseña, la cual debe repetir en el campo "Confirmación".</p> 
		<legend>Contraseña</legend>
			<?php
				
				echo $form->input("old_password", array('type' => 'password', 'label' => 'Contraseña actual', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>'));
				echo $form->input("new_password", array('type' => 'password', 'label' => 'Nueva contraseña', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>'));
				echo $form->input("password_confirmation", array('type' => 'password', 'label' => 'Confirmación', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>'));
			?>
		</fieldset>
	<?php endif; */ ?>
<?php
	echo $form->end('Actualizar');
?>

<fieldset style="margin-top: 3em">
	<legend>Dispositivos vinculados</legend>
	<?php if (!empty($auth_tokens)): ?>
		<ul id="UserLogoutDevice">
			<?php foreach ($auth_tokens as $auth_token): ?>
				<li style="margin-top:0.5em;"><?php echo h($auth_token['AuthToken']['device']) ?> (<a href="#" data-device="<?php echo htmlspecialchars($auth_token['AuthToken']['id']) ?>">Desconectar</a>)</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
	<p style="margin-top: 1em">Para vincular un nuevo dispositivo escanea el siguiente código QR con la app de Academic:</p>
	<div style="margin: 1em 0;"><img src="<?php echo htmlspecialchars($qr_image) ?>" alt="qr code"></div>
	<p><strong>¿Tienes problemas para leer el código?</strong> Prueba <a href="<?php htmlspecialchars($this->Html->url(null, true)) ?>" onclick="window.location.reload(true); return false;">recargando la página</a> para generar uno nuevo.</p>
</fieldset>

<script type="text/javascript">
	$(function() {
		$('#UserLogoutDevice').on('click', 'a[data-device]', function (e) {
			e.preventDefault(); // avoid to execute the actual submit of the form.
			var a = $(this);
			var device = a.attr('data-device');

			if (confirm('¿Está seguro de que desea desconectar el dispositivo?')) {
				$.ajax({
					type: "GET",
					url: '/users/logout/'+device,
					success: function(data)
					{
						a.closest('li').remove();
					}
				});
			}
		})
	});
</script>