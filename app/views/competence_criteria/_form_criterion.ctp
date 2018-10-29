<fieldset>
<legend>Datos generales</legend>
	<?php echo $form->input('code', array('label' => 'Código', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>', 'disabled' => $auth_is_admin ? false : true, 'readonly' => $auth_is_admin ? false : true, 'class' => $auth_is_admin ? '' : 'disabled')); ?>
	<?php echo $form->input('definition', array('label' => 'Definition', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>', 'disabled' => $auth_is_admin ? false : true, 'readonly' => $auth_is_admin ? false : true, 'class' => $auth_is_admin ? '' : 'disabled')); ?>
	<?php echo $form->input('goal_id', array('type' => 'hidden', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>', 'value' => $competence_goal['CompetenceGoal']['id'])); ?>
	<?php echo $form->input('id', array('type' => 'hidden')); ?>
</fieldset>