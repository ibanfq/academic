<!-- File: /app/views/courses/view.ctp -->

<?php $html->addCrumb('Cursos', '/courses'); ?>
<?php $html->addCrumb("{$course['Course']['name']}", "/courses/view/{$course['Course']['id']}"); ?>
<?php $html->addCrumb("Estadísticas por asignatura", "/courses/stats_by_subject/{$course['Course']['id']}"); ?>

<h1>Estadísticas por asignatura</h1>
<div class="actions">
	<ul>
		<?php if ($auth->user('type') == "Administrador") {?>
			<li><?php echo $html->link('Exportar', array('action' => 'export_stats_by_subject', $course['Course']['id'])) ?>
		<?php } ?>
	</ul>
</div>
<div class="view">
  	<fieldset>
	  	<legend>Asignaturas</legend>
		<div class="horizontal-scrollable-content">
			<table>
				<thead>
					<tr>
						<th>Código</th>
						<th>Nombre</th>
						<th>Nº estudiantes</th>
						<th>Horas planificadas</th>
						<th>Horas programadas</th>
						<th>Horas registradas</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($subjects as $subject): ?>
						<tr>
						<td><?php echo $html->link($subject['subjects']['code'], array('controller' => 'subjects', 'action' => 'view', $subject['subjects']['id'])) ?></td>
						<td><?php echo $subject['subjects']['name']?></td>
						<td><?php echo $subject[0]['students']?></td>
						<td><?php echo round($subject[0]['expected_hours'], 2) ?></td>
						<td><?php echo round($subject[0]['programmed_hours'], 2) ?></td>
						<td><?php echo round($subject[0]['registered_hours'], 2) ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</fieldset>
</div>
					
	