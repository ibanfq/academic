<?php $html->addCrumb('Cursos', '/courses'); ?>
<?php $html->addCrumb($course['Degree']['name'], "/courses/view/{$course['Course']['id']}"); ?>
<?php $html->addCrumb('E-portfolio', "/competence/by_course/{$course['Course']['id']}"); ?>
<?php $html->addCrumb("Competencia {$competence['Competence']['code']}", "/competence/view/{$competence['Competence']['id']}"); ?>
<?php $html->addCrumb('Modificar objetivo', "/competence_goals/edit/{$competence_goal['CompetenceGoal']['id']}"); ?>

<h1>Modificar objetivo</h1>
<?php
	echo $form->create('CompetenceGoal', array('url' => "/competence_goals/edit/{$competence_goal['CompetenceGoal']['id']}"));
?>
	<fieldset>
	<legend>Datos generales</legend>
		<?php echo $form->input('code', array('label' => 'Código', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
		<?php echo $form->input('definition', array('label' => 'Definition', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
		<?php echo $form->input('competence_id', array('type' => 'hidden', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>', 'value' => $competence['Competence']['id'])); ?>
		<?php echo $form->input('id', array('type' => 'hidden')); ?>
	</fieldset>
<?php
	echo $form->end('Modificar');
?>
