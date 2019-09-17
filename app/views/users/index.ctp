<?php if ($ref === 'competence_student_stats'): ?>
	<?php $html->addCrumb('Cursos', '/academic_years'); ?>
	<?php $html->addCrumb($modelHelper->academic_year_name($course), "/academic_years/view/{$course['Course']['academic_year_id']}"); ?>
	<?php $html->addCrumb(Environment::institution('name'), Environment::getBaseUrl() . "/courses/index/{$course['Course']['academic_year_id']}"); ?>
	<?php $html->addCrumb("{$course['Degree']['name']}", Environment::getBaseUrl() . "/courses/view/{$course['Course']['id']}"); ?>
	<?php $html->addCrumb('E-portfolio', Environment::getBaseUrl() . "/competence/by_course/{$course['Course']['id']}"); ?>
	<?php $html->addCrumb('Estudiantes', Environment::getBaseUrl() . "/users/index/type:Estudiante/course:{$course['Course']['id']}/ref:competence"); ?>
	<?php $html->addCrumb('Usuarios', '/institutions/ref:users'); ?>
<?php elseif (Environment::institution('id')): ?>
	<?php $html->addCrumb(Environment::institution('name'), Environment::getBaseUrl() . '/users'); ?>
<?php else: ?>
	<?php $html->addCrumb('Todos los centros', '/users'); ?>
<?php endif; ?>

<h1><?php echo $type === 'Estudiante' ? 'Estudiantes' : 'Usuarios' ?></h1>

<?php if (!$ref): ?>
	<div class="actions">
		<ul>
			<?php if (($auth->user('type') == "Administrador") || ($auth->user('type') == "Administrativo")): ?>
				<li><?php echo $html->link('Crear usuario', array('action' => 'add')) ?></li>
			<?php endif; ?>

			<?php if (($auth->user('type') == "Administrador") || ($auth->user('type') == "Administrativo")): ?>
				<?php if (Environment::institution('id')): ?>
					<li><?php echo $html->link('Modificar permisos', array('action' => 'acl_edit')) ?></li>
				<?php else: ?>
					<li><?php echo $html->link('Filtrar por centro', '/institutions/ref:users') ?></li>
				<?php endif; ?>
			<?php endif; ?>

			<?php if ($acl->check('events.calendar_by_teacher')): ?>
				<?php if (Environment::institution('id')): ?>
					<li><?php echo $html->link('Ver agenda del profesorado', array('controller' => 'events', 'action' => 'calendar_by_teacher')) ?></li>
				<?php endif; ?>
			<?php endif; ?>
		</ul>
	</div>
<?php endif; ?>

<div class="<?php echo !$ref ? 'view' : '' ?>">
	<?php
		echo $form->create('User', array('url' => $this->Html->url(null, true), 'type' => 'get'))
	?>
		<fieldset>
		<legend><?php echo $type === 'Estudiante' ? 'Buscar estudiantes' : 'Buscar usuarios' ?></legend>
			<?php
				echo $form->text('q', array('value' => $q));
			?>
		</fieldset>
	<?php
		echo $form->end('Buscar');
	?>
	<div class="horizontal-scrollable-content">
		<table>
			<thead>
				<tr>
					<th>Nombre completo</th>
					<th>Tipo</th>
					<th>DNI</th>
					<th>Correo electrónico</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<!-- Shows the next and previous links -->
					<?php
						$paginator->options(array('url' => array('course' => isset($course) ? $course['Course']['id'] : null, 'type' => $type, 'ref' => $ref, 'q'=>$q)));
						echo $paginator->prev('« Anterior ', null, null, array('class' => 'disabled'));
						echo "&nbsp";
						echo $paginator->numbers();
						echo "&nbsp";
						echo $paginator->next(' Siguiente »', null, null, array('class' => 'disabled'));
					?>
				</tr>
			</tfoot>
			<tbody>
				<?php foreach ($users as $user): ?>
				<tr>
					<td><?php
						if ($ref === 'competence_student_stats') {
							echo $html->link("{$user['User']['last_name']}, {$user['User']['first_name']}", array('controller' => 'competence', 'action' => 'stats_by_student', $course['Course']['id'], $user['User']['id']));
						} else {
							echo $html->link("{$user['User']['last_name']}, {$user['User']['first_name']}", array('controller' => 'users', 'action' => 'view', $user['User']['id']));
						}
					?></td>
					<td><?php echo $user['User']['type'] ?></td>
					<td><?php echo $user['User']['dni'] ?></td>
					<td><?php echo $user['User']['username'] ?></td>
				</tr>
				<?php endforeach; ?>
				
			</tbody>
		</table>
	</div>
</div>