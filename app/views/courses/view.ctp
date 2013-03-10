<!-- File: /app/views/courses/view.ctp -->

<?php $html->addCrumb('Cursos', '/courses'); ?>
<?php $html->addCrumb("{$course['Course']['name']}", "/courses/view/{$course['Course']['id']}"); ?>

<h1><?php echo $friendly_name ?></h1>
<div class="actions">
	<ul>
		<?php if ($auth->user('type') == "Administrador") {?>
			<li><?php echo $html->link('Crear asignatura', array('controller' => 'subjects', 'action' => 'add', $course['Course']['id'])) ?>
			<li><?php echo $html->link('Editar curso', array('action' => 'edit', $course['Course']['id'])) ?></li>
		<?php } ?>
		<?php if ($auth->user('type') != "Estudiante") {?>
				<li><?php echo $html->link('Programar curso', array('controller' => 'events', 'action' => 'schedule', $course['Course']['id'])) ?></li>
		<?php } ?>
		<?php if (($auth->user('type') == "Administrador") || ($auth->user('type') == "Administrativo")) {?>
		  <li><?php echo $html->link('Registro impartición masivo', array('action' => 'add', 'controller' => 'massive_attendance_registers', $course['Course']['id'])) ?></li>
		<?php } ?>
		
		<?php if (($auth->user('type') == "Profesor") || ($auth->user('type') == "Administrador")) { ?>
		  <li><?php echo $html->link('Editar asistencia estudiantes', array('action' => 'view_my_registers', 'controller' => 'attendance_registers', $course['Course']['id'])) ?></li>
		<?php } ?>
		
		<li><?php echo $html->link('Estadísticas asignatura', array('action' => 'stats_by_subject', $course['Course']['id'])) ?></li>
	  <li><?php echo $html->link('Estadísticas profesor', array('action' => 'stats_by_teacher', 'controller' => 'courses', $course['Course']['id'])) ?></li>
	  <li><?php echo $html->link('Estadísticas por aula', array('action' => 'stats', 'controller' => 'classrooms', $course['Course']['id'])) ?></li>
	  
		
		<?php if ($auth->user('type') == "Administrador") {?>
			<li><?php echo $html->link('Copiar curso', array('action' => 'copy', $course['Course']['id']), null, 'Cuando copia un curso, copia también las asignaturas, las actividades y los grupos. Esta operación puede durar hasta dos minutos, ¿está seguro que desea copiar el curso?') ?></li>
			<li><?php echo $html->link('Eliminar curso', array('action' => 'delete', $course['Course']['id']), null, 'Cuando elmina un curso, elimina también los grupos, las asignaturas, las actividades y toda la programación. ¿Está seguro que desea borrarlo?') ?></li>
		<?php } ?>
	</ul>
</div>
<div class="view">
	<fieldset>
	<legend>Asignaturas</legend>
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
				<?php foreach ($course['Subject'] as $subject): ?>
					<tr>
						<td><?php echo $html->link($subject['code'], array('controller' => 'subjects', 'action' => 'view', $subject['id'])) ?></td>
						<td><?php echo $subject['name'] ?></td>
						<td><?php echo $subject['acronym'] ?></td>
						<td><?php echo $subject['level'] ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</fieldset>
</div>
