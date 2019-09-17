<!-- File: /app/views/users/new.ctp -->
<?php $html->addCrumb('Cursos', '/academic_years'); ?>
<?php $html->addCrumb($modelHelper->academic_year_name($course), "/academic_years/view/{$course['Course']['academic_year_id']}"); ?>
<?php $html->addCrumb(Environment::institution('name'), Environment::getBaseUrl() . "/courses/index/{$course['Course']['academic_year_id']}"); ?>
<?php $html->addCrumb("{$course['Degree']['name']}", Environment::getBaseUrl() . "/courses/view/{$course['Course']['id']}"); ?>
<?php $html->addCrumb('Importar estudiantes', '/users/import'); ?>

<h1>Importar estudiantes</h1>
<?php 
	echo $form->create('User', array('url' => $this->Html->url(null), 'type' => 'file'));
	echo $form->file('User.file');
	echo $form->end('Importar');
?>