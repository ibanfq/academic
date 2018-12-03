<!-- File: /app/views/subjects/view.ctp -->

<?php $degreeEnabled = Configure::read('app.degrees') !== null; ?>

<?php $html->addCrumb('Cursos', '/courses'); ?>
<?php $html->addCrumb($subject['Course']['name'], "/courses/view/{$subject['Course']['id']}"); ?>
<?php $html->addCrumb($subject['Subject']['name'], "/subjects/view/{$subject['Subject']['id']}"); ?>

<h1><?php
	if ($degreeEnabled) {
		echo "{$subject['Subject']['code']} - {$subject['Subject']['name']} ({$subject['Subject']['degree']} - {$subject['Subject']['level']} - {$subject['Subject']['semester']})";
	} else {
		echo "{$subject['Subject']['code']} - {$subject['Subject']['name']} ({$subject['Subject']['level']} - {$subject['Subject']['semester']})";
	}
?></h1>
<div class="actions">
	<ul>
	  <?php if ($auth->user('type') != "Administrativo"): ?>
      <?php if ($auth->user('type') != "Estudiante"): ?>
        <li><?php echo $html->link('Crear grupo', array('controller' => 'groups', 'action' => 'add', $subject['Subject']['id'])) ?></li>
        <li><?php echo $html->link('Crear actividad', array('controller' => 'activities', 'action' => 'add', $subject['Subject']['id'])) ?></li>
        <li><?php echo $html->link('Ver programación', array('action' => 'getScheduledInfo', $subject['Subject']['id'])) ?></li>
      <?php endif; ?>
  		<li><?php echo $html->link('Estadísticas estudiante', array('action' => 'students_stats', $subject['Subject']['id'])) ?></li>
			<li><?php echo $html->link('Estadísticas asignatura', array('action' => 'statistics', $subject['Subject']['id'])) ?></li>
  		<?php if ($auth->user('type') == "Administrador"): ?>
  			<li><?php echo $html->link('Editar asignatura', array('action' => 'edit', $subject['Subject']['id'])) ?></li>
  			<li><?php echo $html->link('Eliminar asignatura', array('action' => 'delete', $subject['Subject']['id']), null, 'Cuando elimina una asignatura, elimina también los grupos, las actividades y toda la programación. ¿Está seguro que desea borrarla?') ?></li>
			<?php endif; ?>
		<?php endif; ?>
		<?php if (($auth->user('type') == "Administrador") || ($auth->user('id') == $subject['Subject']['coordinator_id']) || ($auth->user('id') == $subject['Subject']['practice_responsible_id'])): ?>
			<li><?php echo $html->link('Programar curso', array('controller' => 'events', 'action' => 'schedule', $subject['Course']['id'])) ?></li>
			<li><?php echo $html->link('Editar estudiantes', array('action' => 'students_edit', $subject['Subject']['id'])) ?></li>
			<li><?php echo $html->link('Alertar estudiantes', array('action' => 'send_alert_students_without_group', $subject['Subject']['id']), null, 'Esta acción enviará un correo electrónico a todos los estudiantes que no hayan elegido grupo para alguna de las actividades de la asignatura. Debido a que es un cálculo complejo, puede tardar algún tiempo. ¿Está seguro de que desea continuar? Una vez decida continuar, no podrá parar la acción.') ?></li>
		<?php endif; ?>
		<?php if (Configure::read('app.competence.enable') && ($auth->user('type') == "Administrador" || $auth->user('type') == "Profesor")): ?>
	      	<li><?php echo $html->link('E-portfolio', array('controller' => 'competence', 'action' => 'by_subject', $subject['Subject']['id'])) ?>
	  	<?php endif; ?>
	</ul>
</div>

<div class="view">
	<fieldset>
	<legend>Datos generales</legend>
		<dl>
			<dt>Nº créditos ECTS</dt>
			<dd><?php echo $subject['Subject']['credits_number']?></dd>
		</dl>
		<dl>
			<dt>Coordinador</dt>
			<dd><?php echo "{$subject['Coordinator']['first_name']} {$subject['Coordinator']['last_name']}"?></dd>
		</dl>
		<dl>
			<dt>Responsable de prácticas</dt>
			<dd><?php echo "{$subject['Responsible']['first_name']} {$subject['Responsible']['last_name']}"?></dd>
		</dl>
		<dl>
			<dt>Nº total de matriculados</dt>
			<dd><?php echo "{$students_registered_on_subject[0][0]['total']}"?></dd>
		</dl>
	</fieldset>

	<?php if (count($subject['Group']) > 0) {?>
		<fieldset>
		<legend>Grupos</legend>
			<table>
				<thead>
					<tr>
						<th>Nombre</th>
						<th>Tipo</th>
						<th>Capacidad</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($subject['Group'] as $group): ?>
						<tr>
							<td><?php echo $html->link($group['name'], array('controller' => 'groups', 'action' => 'view', $group['id'])) ?></td>
							<td><?php echo $group['type'] ?></td>
							<td><?php echo $group['capacity'] ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</fieldset>
	<?php } ?>

	<?php if (count($activities) > 0) { ?>
		<fieldset>
		<legend>Actividades</legend>
			<table>
				<thead>
					<tr>
						<th>Nombre</th>
						<th>Tipo</th>
						<th>Duración</th>
						<th>Horas/grupo</th>
						<th>Estudiantes/grupo</th>
						<th>Total estudiantes</th>
					</tr>
				</thead>
				<tbody>
				  <?php $total_duration = 0.0 ?>
	  			  <?php $total_programmed = 0.0 ?>
					<?php foreach ($activities as $activity): ?>
						<tr>
							<td><?php echo $html->link($activity['Activity']['name'], array('controller' => 'activities', 'action' => 'view', $activity['Activity']['id'])) ?></td>
							<td><?php echo $activity['Activity']['type'] ?></td>
							<td><?php echo $activity['Activity']['duration'] ?></td>
							<td><?php echo round($activity[0]['duration'], 2) ?></td>
							<td><?php echo round($activity[0]['students'], 2) ?></td>
							<td><?php echo $activity['Registration']['activity_total'] ?></td>
							<?php $total_duration += $activity['Activity']['duration'] ?>
							<?php $total_programmed += $activity[0]['duration'] ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
				<tfoot>
				  <tr style="align:right">
					<td></td>
					<td style="text-align:center"><strong>TOTAL:</strong></td>
					<td><?php echo $total_duration ?></td>
					<td><?php echo $total_programmed ?></td>
					<td></td>
					<td></td>
				  </tr>
				  <tr>
					<td colspan="6"><small><strong>Se expone el promedio de horas programadas por grupo y el promedio de estudiantes apuntados por grupo.</strong></small></td>
				  </tr>
				</tfoot>
			</table>
		</fieldset>
	<?php } ?>
</div>
