<!-- File: /app/views/classrooms/new.ctp -->
<?php $html->addCrumb('Aulas', '/classrooms'); ?>
<?php $html->addCrumb($classroom['Classroom']['name'], "/classrooms/edit/{$classroom['Classroom']['id']}"); ?>

<h1>Modificar aula</h1>
<?php
	echo $form->create('Classroom', array('action' => 'edit'));
?>
	<fieldset>
	<legend>Datos generales</legend>
		<?php echo $form->input('name', array('label' => 'Nombre', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
		<?php echo $form->input('type', array('label' => 'Tipo', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>', 'options' => array("Aula" => "Aula", "Clínica" => "Clínica" , "Laboratorio" => "Laboratorio"))); ?>
		<?php echo $form->input('capacity', array('label' => 'Capacidad', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
                <?php echo $form->input('show_tv', array('label' => 'Mostrar los eventos de este aula en TV')); ?>
	</fieldset>
<?php
	echo $form->end('Modificar');
?>