<!-- File: /app/views/activites/edit.ctp -->
<?php $html->addCrumb('Cursos', '/courses'); ?>
<?php $html->addCrumb($subject['Course']['name'], "/courses/view/{$subject['Course']['id']}"); ?>
<?php $html->addCrumb($subject['Subject']['name'], "/subjects/view/{$subject['Subject']['id']}"); ?>
<?php $html->addCrumb($activity['Activity']['name'], "/activities/view/{$activity['Activity']['id']}"); ?>
<?php $html->addCrumb('Modificar actividad', "/activities/edit/{$activity['Activity']['id']}"); ?>

<?php $flexible_until_days_to_start = Configure::read('app.activity.teacher_can_block_groups_if_days_to_start'); ?>
<?php $teacher_can_change_groups = Configure::read('app.activity.teacher_can_change_groups'); ?>

<h1>Modificar actividad</h1>
<?php
	echo $form->create('Activity', array('action' => 'edit'));
?>
	<fieldset>
	<legend>Datos generales</legend>
		<?php echo $form->input('name', array('label' => 'Nombre', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
		<?php echo $form->input('type', array('label' => 'Tipo', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>', 'options' => array("Clase magistral" => "Clase magistral", "Seminario" => "Seminario", "Taller/trabajo en grupo" => "Taller/trabajo en grupo", "Práctica en aula" => "Práctica en aula", "Práctica de problemas" => "Práctica de problemas", "Práctica de informática" => "Práctica de informática", "Práctica de microscopía" => "Práctica de microscopía", "Práctica de laboratorio" => "Práctica de laboratorio", "Práctica clínica" => "Práctica clínica", "Práctica externa" => "Práctica externa", "Tutoría" => "Tutoría", "Evaluación" => "Evaluación", "Otra presencial" => "Otra presencial"))); ?>
		<?php echo $form->input('duration', array('label' => 'Duración', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
		<?php echo $form->input('notes', array('label' => 'Observaciones', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
		<?php if (is_int($flexible_until_days_to_start)): ?>
			<?php echo $form->input('inflexible_groups', array('label' => 'Impedir que los usuarios cambien de grupo ' . ($flexible_until_days_to_start === 1 ? "$flexible_until_days_to_start día" : "$flexible_until_days_to_start días") . ' antes de empezar')); ?>
		<?php endif; ?>
	</fieldset>
		<?php echo $form->input('subject_id', array('type' => 'hidden')); ?>
		<?php echo $form->input('id', array('type' => 'hidden')); ?>
		
	<fieldset>
	<legend>Estudiantes</legend>
		<table>
			<thead>
				<tr>
					<th style="width:80%">Estudiante</th>
					<?php if ($auth->user('type') == "Administrador" || $teacher_can_change_groups): ?>
						<th>Grupo / <?php echo $isEvaluation? 'No se puede presentar' : 'Actividad aprobada' ?></th>
					<?php else: ?>
						<th><?php echo $isEvaluation? 'No se puede presentar' : 'Actividad aprobada' ?></th>
					<?php endif; ?>
				</th>
			</thead>
			<tbody>
				<?php foreach ($registrations as $registration): ?>
					<tr>
						<td><?php echo rtrim($registration['Student']['last_name']).', '.$registration['Student']['first_name']; ?></td>
						<?php if ($auth->user('type') == "Administrador" || $teacher_can_change_groups): ?>
							<td><?php echo $form->select("Students.{$registration['Student']['id']}.group_id", $groups, $registration['Registration']['group_id']); ?></td>
						<?php else: ?>
							<td><?php echo $form->checkbox("Students.{$registration['Student']['id']}.group_id", array('value' => '-1', 'checked' => $registration['Registration']['group_id'] == -1)); ?></td>
						<?php endif; ?>
					</tr>
				<?php endforeach;?>
			</tbody>
		</table>		
	</fieldset>
<?php
	echo $form->end('Modificar');
?>
