<!-- File: /app/views/users/edit.ctp -->
<?php $html->addCrumb('Usuarios', '/institutions/ref:users'); ?>
<?php if (Environment::institution('id')): ?>
	<?php $html->addCrumb(Environment::institution('name'), Environment::getBaseUrl() . '/users'); ?>
	<?php $html->addCrumb("{$user['User']['first_name']} {$user['User']['last_name']}", Environment::getBaseUrl() . "/users/view/{$user['User']['id']}"); ?>
	<?php $html->addCrumb("Modificar usuario", Environment::getBaseUrl() . "/users/edit/{$user['User']['id']}"); ?>
<?php else: ?>
	<?php $html->addCrumb('Todos los centros', '/users'); ?>
	<?php $html->addCrumb("{$user['User']['first_name']} {$user['User']['last_name']}", "/users/view/{$user['User']['id']}"); ?>
	<?php $html->addCrumb("Modificar usuario", "/users/edit/{$user['User']['id']}"); ?>
<?php endif; ?>

<h1>Modificar usuario</h1>
<?php
	echo $form->create('User', array('action' => 'edit'));
?>
	<fieldset>
	<legend>Datos generales</legend>
		<?php 
			if ($auth->user('super_admin')) {
				echo $form->input('first_name', array('label' => 'Nombre', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); 
				echo $form->input('last_name', array('label' => 'Apellidos', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>'));
			} else {
				echo "<div><dl><dt><label>Nombre</label></dt><dd>{$user['User']['first_name']}</dd></dl></div>";
				echo "<div><dl><dt><label>Apellidos</label></dt><dd>{$user['User']['last_name']}</dd></dl></div>";
			}

			if ($dni_editable) {
				echo $form->input('dni', array('label' => 'DNI (sin letra)', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>'));
			} else {
				echo "<div><dl><dt><label>DNI (sin letra)</label></dt><dd>{$user['User']['dni']}</dd></dl></div>";
			}

			if ($auth->user('super_admin')) {
				echo $form->input('username', array('label' => 'Correo electrónico', 'autocorrect' => 'off', 'autocapitalize' => 'none', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>'));
			} else {
				echo "<div><dl><dt><label>Correo electrónico</label></dt><dd>{$user['User']['username']}</dd></dl></div>";
			}

			echo $form->input('phone', array('label' => 'Teléfono', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>'));

			if ($type_editable):
				if ($auth->user('super_admin')):
					$help_text = '<span class="help-text">El tipo de usuario se actualizará en todas los centros</span>';
				else:
					$help_text = '';
				endif;
				$types = array("Administrador" => "Administrador", "Administrativo" => "Administrativo" , "Conserje" => "Conserje",  "Profesor" => "Profesor", "Estudiante" => "Estudiante", "Becario" => "Becario");
				if ($auth->user('super_admin')) {
					$types = array_merge(array('Super administrador' => 'Super administrador'), $types);
				}
				echo $form->input('type', array('label' => 'Tipo', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => "</dd></dl>$help_text", 'onchange' => 'userTypeChanged()', 'options' => $types));
			else:
				echo "
					<div class=\"input\">
						<dl>
							<dt><label>Tipo</label></dt>
							<dd>{$user['User']['type']}</dd>
						</dl>
					</div>
				";
			endif;
			
			echo $form->input('notify_all', array('label' => 'Activar el envío de correos automáticos si olvida pasar la asistencia', 'div' => array('id' => 'UserNotifyAllWrap')));
		?>
	</fieldset>

	<?php if (Environment::institution('id') && ($auth->user('type') === 'Administrador' || $auth->user('type') === 'Administrativo')): ?>
		<fieldset>
		<legend>Datos específicos del centro</legend>
			<?php echo $form->input('active', array('label' => 'Usuario activo', 'type' => 'select', 'value' => $this->data['User']['active'], 'options' => array('No', 'Si'), 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
			<?php if (($auth->user('type') === 'Administrador')): ?>
				<?php echo $form->input('beta_tester', array('label' => 'Activar opciones beta', 'type' => 'select', 'value' => $this->data['User']['beta_tester'], 'options' => array('No', 'Si'), 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
			<?php endif; ?>
		</fieldset>
	<?php endif; ?>

	<?php echo $form->input('id', array('type' => 'hidden')); ?>
<?php
	echo $form->end('Modificar');
?>

<script>
  function userTypeChanged() {
    $('#UserNotifyAllWrap').toggle($('#UserType').val() === 'Profesor');
  }
  $(function() {
		userTypeChanged();
	});
</script>