<!-- File: /app/views/courses/view.ctp -->

<?php $degreeEnabled = !empty(Configure::read('app.degrees')); ?>

<?php $html->addCrumb('Cursos', '/courses'); ?>
<?php $html->addCrumb("{$course['Course']['name']}", "/courses/view/{$course['Course']['id']}"); ?>

<h1><?php echo $friendly_name ?></h1>
<?php if ($auth->user('type') != "Estudiante"): ?>
  <div class="actions">
    <ul>
      <?php if ($auth->user('type') == "Administrador"): ?>
        <li><?php echo $html->link('Crear asignatura', array('controller' => 'subjects', 'action' => 'add', $course['Course']['id'])) ?>
        <li><?php echo $html->link('Editar curso', array('action' => 'edit', $course['Course']['id'])) ?></li>
      <?php endif; ?>
      <li><?php echo $html->link('Programar curso', array('controller' => 'events', 'action' => 'schedule', $course['Course']['id'])) ?></li>
      <?php if (($auth->user('type') == "Administrador") || ($auth->user('type') == "Administrativo")): ?>
        <li><?php echo $html->link('Registro impartición masivo', array('action' => 'add', 'controller' => 'massive_attendance_registers', $course['Course']['id'])) ?></li>
      <?php endif; ?>

      <?php if (($auth->user('type') == "Profesor") || ($auth->user('type') == "Administrador")): ?>
        <li><?php echo $html->link('Editar asistencia estudiantes', array('action' => 'view_my_registers', 'controller' => 'attendance_registers', $course['Course']['id'])) ?></li>
      <?php endif; ?>

      <li><?php echo $html->link('Estadísticas asignatura', array('action' => 'stats_by_subject', $course['Course']['id'])) ?></li>
      <li><?php echo $html->link('Estadísticas profesor', array('action' => 'stats_by_teacher', 'controller' => 'courses', $course['Course']['id'])) ?></li>
      <li><?php echo $html->link('Estadísticas por aula', array('action' => 'stats', 'controller' => 'classrooms', $course['Course']['id'])) ?></li>

      <?php if (Configure::read('app.competence.enable') && ($auth->user('type') == "Administrador" || $auth->user('type') == "Profesor")): ?>
      	<li><?php echo $html->link('E-portfolio', array('controller' => 'competence', 'action' => 'by_course', $course['Course']['id'])) ?>
  	  <?php endif; ?>

      <?php if ($auth->user('type') == "Administrador"): ?>
        <li><?php echo $html->link('Copiar curso', array('action' => 'copy', $course['Course']['id']), null, 'Cuando copia un curso, copia también las asignaturas, las actividades, los grupos y las competencias. Esta operación puede durar hasta dos minutos, ¿está seguro que desea copiar el curso?') ?></li>
        <li><?php echo $html->link('Eliminar curso', array('action' => 'delete', $course['Course']['id']), null, 'Cuando elimina un curso, elimina también los grupos, las asignaturas, las actividades y toda la programación. ¿Está seguro que desea borrarlo?') ?></li>
      <?php endif; ?>
    </ul>
  </div>
<?php endif; ?>
<div class="<?php if ($auth->user('type') != "Estudiante"): ?>view<?php endif; ?>">
	<fieldset>
	<legend>Asignaturas</legend>
		<table>
			<thead>
				<tr>
					<th>Código</th>
					<th>Nombre</th>
					<th>Acrónimo</th>
					<?php if ($degreeEnabled): ?>
						<th>Titulación</th>
					<?php endif; ?>
					<th>Curso</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($course['Subject'] as $subject): ?>
					<tr>
						<td><?php echo $html->link($subject['code'], array('controller' => 'subjects', 'action' => 'view', $subject['id'])) ?></td>
						<td><?php echo $subject['name'] ?></td>
						<td><?php echo $subject['acronym'] ?></td>
						<?php if ($degreeEnabled): ?>
							<td><?php echo $subject['degree'] ?></td>
						<?php endif; ?>
						<td><?php echo $subject['level'] ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</fieldset>
</div>
