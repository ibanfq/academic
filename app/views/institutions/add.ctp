<!-- File: /app/views/institutions/add.ctp -->
<?php $html->addCrumb('Centros', '/institutions'); ?>
<?php $html->addCrumb('Crear centro', "/institutions/add"); ?>

<?php
	echo $form->create('Institution');
?>
	<fieldset>
	<legend>Datos generales</legend>
		<?php echo $form->input('acronym', array('label' => 'Acrónimo', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
		<?php echo $form->input('name', array('label' => 'Nombre', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
	</fieldset>
<?php
	echo $form->end('Crear');
?>
