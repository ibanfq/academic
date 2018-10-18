<?php $html->addCrumb('Cursos', '/courses'); ?>
<?php $html->addCrumb($course['Course']['name'], "/courses/view/{$course['Course']['id']}"); ?>
<?php $html->addCrumb($subject['Subject']['name'], "/subjects/view/{$subject['Subject']['id']}"); ?>
<?php $html->addCrumb('E-portfolio', "/competence/by_subject/{$subject['Subject']['id']}"); ?>
<?php $html->addCrumb("Competencia {$competence['Competence']['code']}", "/competence/view_by_subject/{$subject['Subject']['id']}/{$competence['Competence']['id']}"); ?>
<?php $html->addCrumb("Objetivo {$competence_goal['CompetenceGoal']['code']}", "/competence_goals/view_by_subject/{$subject['Subject']['id']}/{$competence_goal['CompetenceGoal']['id']}"); ?>
<?php $html->addCrumb('Modificar criterio', "/competence_criteria/edit_by_subject/{$subject['Subject']['id']}/{$competence_criterion['CompetenceCriterion']['id']}"); ?>

<h1>Modificar criterio</h1>
<?php
	echo $form->create('CompetenceCriterion', array('url' => "/competence_criteria/edit_by_subject/{$subject['Subject']['id']}/{$competence_criterion['CompetenceCriterion']['id']}"));
?>
	<fieldset>
	<legend>Datos generales</legend>
		<?php echo $form->input('code', array('label' => 'Código', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>', 'disabled' => $auth_is_admin ? false : true, 'readonly' => $auth_is_admin ? false : true, 'class' => $auth_is_admin ? '' : 'disabled')); ?>
		<?php echo $form->input('definition', array('label' => 'Definition', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>', 'disabled' => $auth_is_admin ? false : true, 'readonly' => $auth_is_admin ? false : true, 'class' => $auth_is_admin ? '' : 'disabled')); ?>
		<?php echo $form->input('goal_id', array('type' => 'hidden', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>', 'value' => $competence_goal['CompetenceGoal']['id'])); ?>
		<?php echo $form->input('id', array('type' => 'hidden')); ?>
	</fieldset>

	<?php require('_form_relations.ctp') ?>
<?php
	echo $form->end('Modificar');
?>

<?php require('_form_script.ctp') ?>