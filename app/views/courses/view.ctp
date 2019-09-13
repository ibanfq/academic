<!-- File: /app/views/courses/view.ctp -->

<?php $html->addCrumb('Cursos', '/academic_years'); ?>
<?php $html->addCrumb($modelHelper->academic_year_name($course), "/academic_years/view/{$course['Course']['academic_year_id']}"); ?>
<?php $html->addCrumb(Environment::institution('name'), Environment::getBaseUrl() . "/courses/index/{$course['Course']['academic_year_id']}"); ?>
<?php $html->addCrumb("{$course['Degree']['name']}", Environment::getBaseUrl() . "/courses/view/{$course['Course']['id']}"); ?>
<?php if ($ref === 'competence'): ?>
	<?php $html->addCrumb('E-portfolio', Environment::getBaseUrl() . "/competence/by_course/{$course['Course']['id']}"); ?>
	<?php $html->addCrumb('Asignaturas', Environment::getBaseUrl() . "/courses/view/{$course['Course']['id']}/ref:competence"); ?>
<?php endif; ?>

<h1><?php echo "{$course['Degree']['name']} ({$course['Course']['initial_date']} - {$course['Course']['final_date']})" ?></h1>

<?php if ($ref !== 'competence'): ?>
	<div class="actions">
		<ul>
			<?php if ($auth->user('type') == "Administrador"): ?>
				<li><?php echo $html->link('Crear asignatura', array('controller' => 'subjects', 'action' => 'add', $course['Course']['id'])) ?></li>
			<?php endif; ?>

			<?php if ($auth->user('type') != "Estudiante"): ?>
				<li><?php echo $html->link('Programar curso', array('controller' => 'events', 'action' => 'schedule', $course['Course']['id'])) ?></li>
			<?php endif; ?>

			<?php if (($auth->user('type') == "Administrador") || ($auth->user('type') == "Administrativo")): ?>
				<li><?php echo $html->link('Registro impartición masivo', array('action' => 'add', 'controller' => 'massive_attendance_registers', $course['Course']['id'])) ?></li>
			<?php endif; ?>

			<?php if (($auth->user('type') == "Profesor") || ($auth->user('type') == "Administrador")): ?>
				<li><?php echo $html->link('Editar asistencia estudiantes', array('action' => 'view_my_registers', 'controller' => 'attendance_registers', $course['Course']['id'])) ?></li>
			<?php endif; ?>

			<?php if ($auth->user('type') != "Estudiante"): ?>
				<li><?php echo $html->link('Estadísticas asignatura', array('action' => 'stats_by_subject', $course['Course']['id'])) ?></li>
				<li><?php echo $html->link('Estadísticas profesor', array('action' => 'stats_by_teacher', 'controller' => 'courses', $course['Course']['id'])) ?></li>
				<li><?php echo $html->link('Estadísticas por aula', array('action' => 'stats', 'controller' => 'classrooms', $course['Course']['id'])) ?></li>
			<?php endif; ?>

			<?php if (Configure::read('app.competence.enable') && in_array($auth->user('type'), array("Administrador", "Profesor", "Estudiante"))): ?>
				<li><?php echo $html->link('E-portfolio', array('controller' => 'competence', 'action' => 'by_course', $course['Course']['id'])) ?></li>
			<?php endif; ?>

			<?php if (($auth->user('type') == "Administrador") || ($auth->user('type') == "Administrativo")): ?>
				<li><?php echo $html->link('Importar usuarios', array('controller' => 'users', 'action' => 'import', $course['Course']['id'])) ?></li>
			<?php endif; ?>

			<?php if ($auth->user('type') == "Administrador"): ?>
				<li><?php echo $html->link('Eliminar titulación del curso', array('action' => 'delete', $course['Course']['id']), null, 'Cuando elimina la titulación curso, elimina también los grupos, las asignaturas, las actividades y toda la programación. ¿Está seguro que desea borrarlo?') ?></li>
			<?php endif; ?>
		</ul>
	</div>
<?php endif; ?>

<div class="<?php echo $ref !== 'competence' ? 'view' : '' ?>">
	<fieldset>
	<legend>Asignaturas</legend>
		<div class="horizontal-scrollable-content">
			<table>
				<thead>
					<tr>
						<th>Código</th>
						<th>Nombre</th>
						<th>Acrónimo</th>
						<th>Curso</th>
					</tr>
				</thead>
				<tbody>
					<?php if (isset($course['Subject'])): ?>
						<?php foreach ($course['Subject'] as $subject): ?>
							<tr>
								<td><?php
									if ($ref === 'competence') {
										echo $html->link($subject['code'], array('controller' => 'competence', 'action' => 'stats_by_subject', $course['Course']['id'], $subject['id']));
									} else {
										echo $html->link($subject['code'], array('controller' => 'subjects', 'action' => 'view', $subject['id']));
									}
								?></td>
								<td><?php echo $subject['name'] ?></td>
								<td><?php echo $subject['acronym'] ?></td>
								<td><?php echo $subject['level'] ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</fieldset>
</div>
