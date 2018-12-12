<?php $html->addCrumb('Cursos', '/courses'); ?>
<?php $html->addCrumb($course['Course']['name'], "/courses/view/{$course['Course']['id']}"); ?>
<?php $html->addCrumb('E-portfolio', "/competence/by_course/{$course['Course']['id']}"); ?>
<?php $html->addCrumb("Competencia {$competence['Competence']['code']}", "/competence/view/{$competence['Competence']['id']}"); ?>
<?php $html->addCrumb("Objetivo {$competence_goal['CompetenceGoal']['code']}", "/competence_goals/view/{$competence_goal['CompetenceGoal']['id']}"); ?>
<?php $html->addCrumb('Evaluar criterio', "/competence_criteria/grade/{$competence_criterion['CompetenceCriterion']['id']}"); ?>

<h1>Evaluar criterio</h1>
<?php require('_view_resume.ctp') ?>
<?php echo $form->create('CompetenceCriterionGrade', array('url' => $this->Html->url(null, true)));?>
	<?php require('_form_students.ctp') ?>
<?php echo $form->end('Modificar'); ?>

<?php require('_form_script.ctp') ?>