<!-- File: /app/views/users/new.ctp -->
<?php $html->addCrumb('Usuarios', '/users'); ?>
<?php $html->addCrumb('Importar estudiantes', '/users/import'); ?>

<h1>Importar estudiantes</h1>
<?php 
	echo $form->create('User', array('action' => 'import', 'type' => 'file'));
	echo $form->file('User.file');
	echo $form->end('Importar');
?>