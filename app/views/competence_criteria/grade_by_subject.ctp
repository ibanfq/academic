<?php $html->addCrumb('Cursos', '/courses'); ?>
<?php $html->addCrumb($course['Course']['name'], "/courses/view/{$course['Course']['id']}"); ?>
<?php $html->addCrumb($subject['Subject']['name'], "/subjects/view/{$subject['Subject']['id']}"); ?>
<?php $html->addCrumb('E-portfolio', "/competence/by_subject/{$subject['Subject']['id']}"); ?>
<?php $html->addCrumb("Competencia {$competence['Competence']['code']}", "/competence/view_by_subject/{$subject['Subject']['id']}/{$competence['Competence']['id']}"); ?>
<?php $html->addCrumb("Objetivo {$competence_goal['CompetenceGoal']['code']}", "/competence_goals/view_by_subject/{$subject['Subject']['id']}/{$competence_goal['CompetenceGoal']['id']}"); ?>
<?php $html->addCrumb('Evaluar criterio', "/competence_criteria/grade_by_subject/{$subject['Subject']['id']}/{$competence_criterion['CompetenceCriterion']['id']}"); ?>

<h1>Evaluar criterio</h1>
<?php echo $form->create('CompetenceCriterionGrade', array('url' => $this->Html->url(null, true))); ?>
	<?php require('_view_resume.ctp') ?>
	<?php require('_form_students.ctp') ?>
<?php echo $form->end('Modificar'); ?>

<?php require('_form_script.ctp') ?>