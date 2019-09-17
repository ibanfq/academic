<?php $html->addCrumb('Cursos', '/academic_years'); ?>
<?php $html->addCrumb($modelHelper->academic_year_name($course), "/academic_years/view/{$course['Course']['academic_year_id']}"); ?>
<?php $html->addCrumb(Environment::institution('name'), Environment::getBaseUrl() . "/courses/index/{$course['Course']['academic_year_id']}"); ?>
<?php $html->addCrumb("{$course['Degree']['name']}", Environment::getBaseUrl() . "/courses/view/{$course['Course']['id']}"); ?>
<?php $html->addCrumb('E-portfolio', Environment::getBaseUrl() . "/competence/by_course/{$course['Course']['id']}"); ?>
<?php $html->addCrumb("Competencia {$competence['Competence']['code']}", Environment::getBaseUrl() . "/competence/view/{$competence['Competence']['id']}"); ?>
<?php $html->addCrumb("Objetivo {$competence_goal['CompetenceGoal']['code']}", Environment::getBaseUrl() . "/competence_goals/view/{$competence_goal['CompetenceGoal']['id']}"); ?>
<?php $html->addCrumb('Evaluar criterio', Environment::getBaseUrl() . "/competence_criteria/grade/{$competence_criterion['CompetenceCriterion']['id']}"); ?>

<h1>Evaluar criterio</h1>
<?php echo $form->create('CompetenceCriterionGrade', array('url' => $this->Html->url(null, true)));?>
	<?php require('_view_resume.ctp') ?>
	<?php require('_form_students.ctp') ?>
<?php echo $form->end('Modificar'); ?>

<?php require('_form_script.ctp') ?>