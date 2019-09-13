<?php $html->addCrumb('Cursos', '/courses'); ?>
<?php $html->addCrumb($course['Degree']['name'], "/courses/view/{$course['Course']['id']}"); ?>
<?php $html->addCrumb($subject['Subject']['name'], "/subjects/view/{$subject['Subject']['id']}"); ?>
<?php $html->addCrumb('E-portfolio', "/competence/by_subject/{$subject['Subject']['id']}"); ?>
<?php $html->addCrumb("Competencia {$competence['Competence']['code']}", "/competence/view_by_subject/{$subject['Subject']['id']}/{$competence['Competence']['id']}"); ?>
<?php $html->addCrumb("Objetivo {$competence_goal['CompetenceGoal']['code']}", "/competence_goals/view_by_subject/{$subject['Subject']['id']}/{$competence_goal['CompetenceGoal']['id']}"); ?>
<?php $html->addCrumb('Modificar criterio', "/competence_criteria/edit_by_subject/{$subject['Subject']['id']}/{$competence_criterion['CompetenceCriterion']['id']}"); ?>

<h1>Modificar criterio</h1>
<?php
	echo $form->create('CompetenceCriterion', array('url' => "/competence_criteria/edit_by_subject/{$subject['Subject']['id']}/{$competence_criterion['CompetenceCriterion']['id']}"));
?>
	<?php require('_form_criterion.ctp') ?>
	<?php require('_form_relations.ctp') ?>
<?php
	echo $form->end('Modificar');
?>

<?php require('_form_script.ctp') ?>