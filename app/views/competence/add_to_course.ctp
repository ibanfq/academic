<?php $html->addCrumb('Cursos', '/academic_years'); ?>
<?php $html->addCrumb($modelHelper->academic_year_name($course), "/academic_years/view/{$course['Course']['academic_year_id']}"); ?>
<?php $html->addCrumb(Environment::institution('name'), Environment::getBaseUrl() . "/courses/index/{$course['Course']['academic_year_id']}"); ?>
<?php $html->addCrumb("{$course['Degree']['name']}", Environment::getBaseUrl() . "/courses/view/{$course['Course']['id']}"); ?>
<?php $html->addCrumb('E-portfolio', Environment::getBaseUrl() . "/competence/by_course/{$course['Course']['id']}"); ?>
<?php $html->addCrumb('Crear', Environment::getBaseUrl() . "/competence/add_to_course/{$course['Course']['id']}"); ?>

<h1>Crear competencia</h1>
<?php
	echo $form->create('Competence', array('url' => $this->Html->url(null, true)));
?>
	<fieldset>
	<legend>Datos generales</legend>
		<?php echo $form->input('code', array('label' => 'Código', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
		<?php echo $form->input('definition', array('label' => 'Definition', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
		<?php echo $form->input('course_id', array('type' => 'hidden', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>', 'value' => $course['Course']['id'])); ?>
	</fieldset>
<?php
	echo $form->end('Crear');
?>
