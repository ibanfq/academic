<!-- File: /app/views/users/view.ctp -->
<?php $html->addCrumb('Usuarios', '/users'); ?>
<?php $html->addCrumb("{$user['User']['first_name']} {$user['User']['last_name']}", "/users/view/{$user['User']['id']}"); ?>

<h1>
	<?php echo $userModel->full_name($user)?> - <?php echo $user['User']['type'] ?></h1>
<div class="actions">	
	<ul>
	    <?php if (($auth->user('type') == "Administrador") ||  ($auth->user('type') == "Administrativo")): ?>
		    <li><?php echo $html->link('Modificar usuario', array('action' => 'edit', $user['User']['id'])) ?></li>
	    <?php endif; ?>
		
		<?php if (($auth->user('type') != "Estudiante") && ($auth->user('type') != "Conserje")): ?>
        <?php if (($user['User']['type'] == "Profesor") || ($user['User']['type'] == "Administrador") || ($user['User']['type'] == "Administrativo")): ?>
		    	<li><?php echo $html->link('Ver ejecución', array('action' => 'teacher_stats', $user['User']['id'])) ?></li>
					<li><?php echo $html->link('Ver planificación', array('action' => 'teacher_schedule', $user['User']['id'])) ?></li>
					<li><?php echo $html->link('Ver agenda', array('controller' => 'events', 'action' => 'calendar_by_teacher', $user['User']['id'])) ?></li>
		    <?php endif; ?>
		
			<?php if (($user['User']['type'] == "Estudiante")): ?>
			    <li><?php echo $html->link('Ver asistencia', array('action' => 'student_stats', $user['User']['id'])) ?></li>

			    <?php if (($auth->user('type') == "Administrador") ||  ($auth->user('type') == "Profesor")): ?>
			    	<li><?php echo $html->link('E-portfolio', array('controller' => 'competence', 'action' => 'by_student', $user['User']['id'])) ?></li>
		    	<?php endif; ?>
			<?php endif; ?>
		<?php endif; ?>
		
		<?php if ((($auth->user('type') == "Administrador") || ($auth->user('type') == "Administrativo")) && ($user['User']['type'] == "Estudiante")): ?>
			<li><?php echo $html->link('Modificar matrícula', array('action' => 'edit_registration', $user['User']['id'])) ?></li>
		<?php endif; ?>
		
		<?php if (($auth->user('type') == "Administrador")): ?>
		  	<li><?php echo $html->link('Eliminar usuario', array('action' => 'delete', $user['User']['id']), null, '¿Está seguro que desea eliminar este usuario?') ?></li>
		<?php endif; ?>
	</ul>
</div>

<div class="view">
	<fieldset>
	<legend>Datos generales</legend>
		<dl>
			<dt>Nombre</dt>
			<dd><?php echo $user['User']['first_name']?></dd>
		</dl>
		<dl>
			<dt>Apellidos</dt>
			<dd><?php echo $user['User']['last_name']?></dd>
		</dl>
		<dl>
			<dt>DNI (sin letra)</dt>
			<dd><?php echo $user['User']['dni']?></dd>
		</dl>
		<dl>
			<dt>Correo electrónico</dt>
			<dd><?php echo $user['User']['username']?></dd>
		</dl>
		<dl>
			<dt>Teléfono</dt>
			<dd><?php echo $user['User']['phone']?></dd>
		</dl>
		<dl>
			<dt>Usuario activo</dt>
			<dd><?php echo $user['InstitutionUser']['active'] ? 'Si' : 'No' ?></dd>
		</dl>
	</fieldset>
</div>
