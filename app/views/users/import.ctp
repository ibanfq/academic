<!-- File: /app/views/users/new.ctp -->
<?php $html->addCrumb('Usuarios', '/users'); ?>
<?php $html->addCrumb('Importar estudiantes', '/users/import'); ?>

<h1>Importar estudiantes</h1>
<?php 
	echo $form->create('User', array('url' => $this->Html->url(null), 'type' => 'file'));
	echo $form->file('User.file');
	echo $form->end('Importar');
?>