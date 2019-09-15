<?php $html->addCrumb('Cursos', '/courses'); ?>
<?php $html->addCrumb($course['Degree']['name'], "/courses/view/{$course['Course']['id']}"); ?>
<?php $html->addCrumb('E-portfolio', "/competence/by_course/{$course['Course']['id']}"); ?>
<?php $html->addCrumb("Competencia {$competence['Competence']['code']}", "/competence/view/{$competence['Competence']['id']}"); ?>
<?php $html->addCrumb("Objetivo {$competence_goal['CompetenceGoal']['code']}", "/competence_goals/view/{$competence_goal['CompetenceGoal']['id']}"); ?>
<?php $html->addCrumb('Crear criterio', "/competence_criteria/add_to_goal/{$competence_goal['CompetenceGoal']['id']}"); ?>

<h1>Crear criterio</h1>
<?php
	echo $form->create('CompetenceCriterion', array('url' => "/competence_criteria/add_to_goal/{$competence_goal['CompetenceGoal']['id']}"));
?>
	<fieldset>
	<legend>Datos generales</legend>
		<?php echo $form->input('code', array('label' => 'Código', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
		<?php echo $form->input('definition', array('label' => 'Definition', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
		<?php echo $form->input('goal_id', array('type' => 'hidden', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>', 'value' => $competence_goal['CompetenceGoal']['id'])); ?>
	</fieldset>

	<?php require('_form_relations.ctp') ?>
<?php
	echo $form->end('Crear');
?>

<?php require('_form_script.ctp') ?>