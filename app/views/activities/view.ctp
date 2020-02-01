<?php $html->addCrumb('Cursos', '/academic_years'); ?>
<?php $html->addCrumb($modelHelper->academic_year_name($subject), "/academic_years/view/{$subject['Course']['academic_year_id']}"); ?>
<?php $html->addCrumb(Environment::institution('name'), Environment::getBaseUrl() . "/courses/index/{$subject['Course']['academic_year_id']}"); ?>
<?php $html->addCrumb("{$degree['Degree']['name']}", Environment::getBaseUrl() . "/courses/view/{$subject['Course']['id']}"); ?>
<?php $html->addCrumb($subject['Subject']['name'], Environment::getBaseUrl() . "/subjects/view/{$subject['Subject']['id']}"); ?>
<?php $html->addCrumb($activity['Activity']['name'], Environment::getBaseUrl() . "/activities/view/{$activity['Activity']['id']}"); ?>

<?php $flexible_until_days_to_start = Configure::read('app.activity.teacher_can_block_groups_if_days_to_start'); ?>

<h1><?php echo $activity['Activity']['name'] ?></h1>

<?php if ($auth->user('type') != "Estudiante") : ?>
  <div class="actions">
    <ul>
      <li><?php echo $html->link('Estadísticas estudiante', array('action' => 'students_stats', $activity['Activity']['id'])) ?></li>
      <li><?php echo $html->link('Modificar actividad', array('action' => 'edit', $activity['Activity']['id'])) ?></li>
      <li><?php echo $html->link('Eliminar actividad', array('action' => 'delete', $activity['Activity']['id']), null, 'Cuando elimina una actividad, elimina también toda su programación asociada. ¿Está seguro que desea borrarlo?') ?></li>
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
		<?php if ($flexible_until_days_to_start): ?>
			<dl>
				<dt>Bloquear grupos <?php echo $flexible_until_days_to_start === 1 ? "$flexible_until_days_to_start día" : "$flexible_until_days_to_start días" ?> antes</dt>
				<dd><?php echo $activity['Activity']['inflexible_groups']? 'Sí' : 'No' ?></dd>
			</dl>
		<?php endif; ?>
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
					<th>Asignatura vinculada</th>
					<th>Grupo</th>
				</tr>
				<?php foreach($registrations as $registration): ?>
					<tr>
						<td><?php echo rtrim($registration['Student']['last_name']).', '.$registration['Student']['first_name']; ?></td>
						<td><?php
							if (!empty($registration['ChildSubject']['code'])) {
								echo strpos($registration['ChildSubject']['name'], $registration['ChildSubject']['code']) === false
									? "{$registration['ChildSubject']['code']} {$registration['ChildSubject']['name']}"
									: $registration['ChildSubject']['name'];
							}
						?></td>
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
