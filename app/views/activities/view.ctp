<?php 
	$html->addCrumb('Cursos', '/courses'); 
	$html->addCrumb($subject['Course']['name'], "/courses/view/{$subject['Course']['id']}"); 
	$html->addCrumb($subject['Subject']['name'], "/subjects/view/{$subject['Subject']['id']}"); 
	$html->addCrumb($activity['Activity']['name'], "/activities/view/{$activity['Activity']['id']}");
?>

<h1><?php echo $activity['Activity']['name'] ?></h1>

<?php if ($auth->user('type') != "Estudiante") : ?>
  <div class="actions">
    <ul>
      <li><?php echo $html->link('Estadísticas estudiante', array('action' => 'students_stats', $activity['Activity']['id'])) ?></li>
      <li><?php echo $html->link('Modificar actividad', array('action' => 'edit', $activity['Activity']['id'])) ?></li>
      <li><?php echo $html->link('Eliminar actividad', array('action' => 'delete', $activity['Activity']['id']), null, 'Cuando elimina una actividad toda su programación asociada. ¿Está seguro que desea borrarlo?') ?></li>
    </ul>
  </div>
<?php endif; ?>

<div class="<?php if ($auth->user('type') != "Estudiante"): ?>view<?php endif; ?>">
	<fieldset>
	<legend>Datos generales</legend>
		<dl>
			<dt>Nombre</dt>
			<dd><?php echo $activity['Activity']['name']?></dd>
		</dl>
		<dl>
			<dt>Tipo</dt>
			<dd><?php echo $activity['Activity']['type'] ?></dd>
		</dl>
		<dl>
			<dt>Duración</dt>
			<dd><?php echo $activity['Activity']['duration'] ?></dd>
		</dl>
		<dl>
			<dt>Observaciones</dt>
			<dd><?php echo $activity['Activity']['notes'] ?></dd>
		</dl>
		<dl>
			<dt>Bloquear grupos 7 días antes</dt>
			<dd><?php echo $activity['Activity']['inflexible_groups']? 'Sí' : 'No' ?></dd>
		</dl>
	</fieldset>
	
	<?php if (count($groups) > 0): ?>
		<fieldset>
		<legend>Grupos con estudiantes</legend>
			<table>
				<tr>
					<th>Grupo</th>
					<th>Nº de estudiantes</th>
				</tr>
				<?php foreach($groups as $group): ?>
					<tr>
						<td><?php echo $html->link($group['Group']['name'], array('action' => 'view_students', $activity['Activity']['id'], $group['Group']['id']))?></td>
						<td><?php echo $group[0]['students']?></td>
					</tr>
				<?php endforeach;?>
			</table>
		</fieldset>
	<?php endif; ?>
	
	<?php if (count($registrations) > 0): ?>
		<fieldset>
		<legend>Estudiantes</legend>
			<table>
				<tr>
					<th>Estudiante</th>
					<th>Grupo</th>
				</tr>
				<?php foreach($registrations as $registration): ?>
					<tr>
						<td><?php echo rtrim($registration['Student']['last_name']).', '.$registration['Student']['first_name']; ?></td>
						<td><?php
							if ($registration['Registration']['group_id'] == -1) {
								echo $isEvaluation? 'No se puede presentar' : 'Actividad aprobada';
							} else if ($registration['Registration']['group_id'] !== null) {
								echo $groups[$registration['Registration']['group_id']]['Group']['name'];
							}
						?></td>
					</tr>
				<?php endforeach; ?>
			</table>
		</fieldset>
	<?php endif; ?>
</div>
