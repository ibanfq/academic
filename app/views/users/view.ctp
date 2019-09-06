<!-- File: /app/views/users/view.ctp -->

<?php $html->addCrumb('Usuarios', '/institutions/ref:users'); ?>
<?php if (Environment::institution('id')): ?>
	<?php $html->addCrumb(Environment::institution('name'), Environment::getBaseUrl() . '/users'); ?>
	<?php $html->addCrumb("{$user['User']['first_name']} {$user['User']['last_name']}", Environment::getBaseUrl() . "/users/view/{$user['User']['id']}"); ?>
<?php else: ?>
	<?php $html->addCrumb('Todos los centros', '/users'); ?>
	<?php $html->addCrumb("{$user['User']['first_name']} {$user['User']['last_name']}", "/users/view/{$user['User']['id']}"); ?>
<?php endif; ?>

<h1>
	<?php echo $modelHelper->full_name($user)?> - <?php echo $user['User']['type'] ?></h1>
<div class="actions">	
	<ul>
	    <?php if (($auth->user('type') == "Administrador") ||  ($auth->user('type') == "Administrativo")): ?>
		    <li><?php echo $html->link('Modificar usuario', array('action' => 'edit', $user['User']['id'])) ?></li>
	    <?php endif; ?>
		
		<?php if (Environment::institution('id')): ?>
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
			<dd><?php echo TextUtils::maskdni($user['User']['dni'])?></dd>
		</dl>
		<dl>
			<dt>Correo electrónico</dt>
			<dd><?php echo $user['User']['username']?></dd>
		</dl>
		<dl>
			<dt>Teléfono</dt>
			<dd><?php echo $user['User']['phone']?></dd>
		</dl>
		<?php if ($auth->user('type') == "Administrador" || $auth->user('type') == "Administrativo"): ?>
			<dl>
				<dt>Tipo</dt>
				<dd><?php echo $user['User']['super_admin'] ? 'Super administrador' : $user['User']['type'] ?></dd>
			</dl>
		<?php endif; ?>
		<?php if (Environment::institution('id')): ?>
			<dl>
				<dt>Usuario activo</dt>
				<dd><?php echo $user['UserInstitution']['active'] ? 'Si' : 'No' ?></dd>
			</dl>
		<?php endif; ?>
	</fieldset>

	<?php if (! Environment::institution('id') && $auth->user('super_admin')): ?>
		<fieldset>
		<legend>Centros</legend>
			<div class="horizontal-scrollable-content">
				<table>
					<thead>
						<tr>
							<th>Acrónimo</th>
							<th>Nombre</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($user_institutions as $institution): ?>
							<?php $url = array('controller' => 'users', 'action' => 'index', 'institution' => $institution['Institution']['id']); ?>
							<tr>
								<td><?php echo $html->link($modelHelper->format_acronym($institution['Institution']['acronym']), $url) ?></td>
								<td><?php echo $html->link($institution['Institution']['name'], $url); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</fieldset>
	<?php endif; ?>
</div>
