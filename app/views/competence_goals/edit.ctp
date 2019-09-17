<?php $html->addCrumb('Cursos', '/academic_years'); ?>
<?php $html->addCrumb($modelHelper->academic_year_name($course), "/academic_years/view/{$course['Course']['academic_year_id']}"); ?>
<?php $html->addCrumb(Environment::institution('name'), Environment::getBaseUrl() . "/courses/index/{$course['Course']['academic_year_id']}"); ?>
<?php $html->addCrumb("{$course['Degree']['name']}", Environment::getBaseUrl() . "/courses/view/{$course['Course']['id']}"); ?>
<?php $html->addCrumb('E-portfolio', Environment::getBaseUrl() . "/competence/by_course/{$course['Course']['id']}"); ?>
<?php $html->addCrumb("Competencia {$competence['Competence']['code']}", Environment::getBaseUrl() . "/competence/view/{$competence['Competence']['id']}"); ?>
<?php $html->addCrumb("Objetivo {$competence_goal['CompetenceGoal']['code']}", Environment::getBaseUrl() . "/competence_goals/view/{$competence_goal['CompetenceGoal']['id']}"); ?>
<?php $html->addCrumb('Modificar objetivo', Environment::getBaseUrl() . "/competence_goals/edit/{$competence_goal['CompetenceGoal']['id']}"); ?>

<h1>Modificar objetivo</h1>
<?php
	echo $form->create('CompetenceGoal', array('url' => $this->Html->url(null, true)));
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
