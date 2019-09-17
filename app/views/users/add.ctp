<!-- File: /app/views/users/new.ctp -->
<?php $html->addCrumb('Usuarios', '/institutions/ref:users'); ?>
<?php if (Environment::institution('id')): ?>
	<?php $html->addCrumb(Environment::institution('name'), Environment::getBaseUrl() . '/users'); ?>
	<?php $html->addCrumb('Crear usuario', Environment::getBaseUrl() . '/users/add'); ?>
<?php else: ?>
	<?php $html->addCrumb('Todos los centros', '/users'); ?>
	<?php $html->addCrumb('Crear usuario', '/users/add'); ?>
<?php endif; ?>

<h1>Crear usuario</h1>
<?php
	echo $form->create('User');
?>
	<fieldset>
	<legend>Datos generales</legend>
		<?php
			echo $form->input('first_name', array('label' => 'Nombre', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>'));
			echo $form->input('last_name', array('label' => 'Apellidos', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>'));
			echo $form->input('dni', array('label' => 'DNI (sin letra)', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>'));
			echo $form->input('username', array('label' => 'Correo electrónico', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>'));
			echo $form->input('phone', array('label' => 'Teléfono', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>'));

			$types = array("Administrador" => "Administrador", "Administrativo" => "Administrativo" , "Conserje" => "Conserje",  "Profesor" => "Profesor", "Estudiante" => "Estudiante", "Becario" => "Becario");
			if (! Environment::institution('id') && $auth->user('super_admin')) {
				$types = array_merge(array('Super administrador' => 'Super administrador'), $types);
			}

			echo $form->input('type', array('label' => 'Tipo', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>', 'options' => $types, 'default' => 'Administrador'));
		?>
	</fieldset>
<?php
	echo $form->end('Crear');
?>