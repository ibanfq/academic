<!-- File: /app/views/activities/add.ctp -->
<?php $html->addCrumb('Cursos', '/courses'); ?>
<?php $html->addCrumb($subject['Course']['name'], "/courses/view/{$subject['Course']['id']}"); ?>
<?php $html->addCrumb($subject['Subject']['name'], "/subjects/view/{$subject['Subject']['id']}"); ?>
<?php $html->addCrumb('Crear actividad', "/activities/add/{$subject['Subject']['id']}"); ?>

<?php $flexible_until_days_to_start = Configure::read('app.activity.teacher_can_block_groups_if_days_to_start'); ?>
<?php $flexible_until_default_value = Configure::read('app.activity.teacher_can_block_groups_default_value'); ?>

<h1>Crear actividad</h1>
<?php
	echo $form->create('Activity');
?>
	<fieldset>
	<legend>Datos generales</legend>
		<?php echo $form->input('name', array('label' => 'Nombre', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
		<?php echo $form->input('type', array('label' => 'Tipo', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>', 'options' => array("Clase magistral" => "Clase magistral", "Seminario" => "Seminario", "Taller/trabajo en grupo" => "Taller/trabajo en grupo", "Práctica en aula" => "Práctica en aula", "Práctica de problemas" => "Práctica de problemas", "Práctica de informática" => "Práctica de informática", "Práctica de microscopía" => "Práctica de microscopía", "Práctica de laboratorio" => "Práctica de laboratorio", "Práctica clínica" => "Práctica clínica", "Práctica externa" => "Práctica externa", "Tutoría" => "Tutoría", "Evaluación" => "Evaluación", "Otra presencial" => "Otra presencial"))); ?>
		<?php echo $form->input('duration', array('label' => 'Duración', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl><span class="help-text">Si tiene que poner decimales utilice el punto (p.ej. 10.5)</span>')); ?>
		<?php echo $form->input('notes', array('label' => 'Observaciones', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
		<?php if (is_int($flexible_until_days_to_start)): ?>
			<?php echo $form->input('inflexible_groups', array('checked' => $flexible_until_default_value, 'label' => 'Impedir que los usuarios cambien de grupo ' . ($flexible_until_days_to_start === 1 ? "$flexible_until_days_to_start día" : "$flexible_until_days_to_start días") . ' antes de empezar')); ?>
		<?php endif; ?>
	</fieldset>
		<?php echo $form->input('subject_id', array('type' => 'hidden', 'value' => $subject_id)); ?>
<?php
	echo $form->end('Crear');
?>
