<!-- File: /app/views/degrees/add.ctp -->

<?php $html->addCrumb('Centros', '/institutions'); ?>
<?php $html->addCrumb($degree['Institution']['name'], "/institutions/view/{$degree['Institution']['id']}"); ?>
<?php $html->addCrumb($degree['Degree']['name'], "/degrees/view/{$degree['Degree']['id']}"); ?>
<?php $html->addCrumb("Modificar titulación", "/degrees/edit/{$degree['Degree']['id']}"); ?>

<h1>Modificar titulación</h1>
<?php
	echo $form->create('Degree', array('action' => 'edit'));
?>
	<fieldset>
	<legend>Datos generales</legend>
		<?php echo $form->input('acronym', array('label' => 'Acrónimo', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
		<?php echo $form->input('code', array('label' => 'Código', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
		<?php echo $form->input('name', array('label' => 'Nombre', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
	</fieldset>
	<?php echo $form->input('id', array('type' => 'hidden')); ?>
<?php
	echo $form->end('Modificar');
?>
