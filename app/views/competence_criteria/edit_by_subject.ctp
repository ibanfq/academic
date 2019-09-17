<?php $html->addCrumb('Cursos', '/academic_years'); ?>
<?php $html->addCrumb($modelHelper->academic_year_name($course), "/academic_years/view/{$course['Course']['academic_year_id']}"); ?>
<?php $html->addCrumb(Environment::institution('name'), Environment::getBaseUrl() . "/courses/index/{$course['Course']['academic_year_id']}"); ?>
<?php $html->addCrumb("{$course['Degree']['name']}", Environment::getBaseUrl() . "/courses/view/{$course['Course']['id']}"); ?>
<?php $html->addCrumb($subject['Subject']['name'], Environment::getBaseUrl() . "/subjects/view/{$subject['Subject']['id']}"); ?>
<?php $html->addCrumb('E-portfolio', Environment::getBaseUrl() . "/competence/by_subject/{$subject['Subject']['id']}"); ?>
<?php $html->addCrumb("Competencia {$competence['Competence']['code']}", Environment::getBaseUrl() . "/competence/view_by_subject/{$subject['Subject']['id']}/{$competence['Competence']['id']}"); ?>
<?php $html->addCrumb("Objetivo {$competence_goal['CompetenceGoal']['code']}", Environment::getBaseUrl() . "/competence_goals/view_by_subject/{$subject['Subject']['id']}/{$competence_goal['CompetenceGoal']['id']}"); ?>
<?php $html->addCrumb("Criterio {$competence_criterion['CompetenceCriterion']['code']}", Environment::getBaseUrl() . "/competence_criteria/view_by_subject/{$subject['Subject']['id']}/{$competence_criterion['CompetenceCriterion']['id']}"); ?>
<?php $html->addCrumb('Modificar criterio', Environment::getBaseUrl() . "/competence_criteria/edit_by_subject/{$subject['Subject']['id']}/{$competence_criterion['CompetenceCriterion']['id']}"); ?>

<h1>Modificar criterio</h1>
<?php
	echo $form->create('CompetenceCriterion', array('url' => $this->Html->url(null, true)));
?>
	<?php require('_form_criterion.ctp') ?>
	<?php require('_form_relations.ctp') ?>
<?php
	echo $form->end('Modificar');
?>

<?php require('_form_script.ctp') ?>