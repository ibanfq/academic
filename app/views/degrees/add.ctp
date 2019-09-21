<!-- File: /app/views/degrees/add.ctp -->

<?php $html->addCrumb('Centros', '/institutions'); ?>
<?php $html->addCrumb($institution['Institution']['name'], "/institutions/view/{$institution['Institution']['id']}"); ?>
<?php $html->addCrumb("Crear titulación", "/degrees/add/{$institution['Institution']['id']}"); ?>

<h1>Crear titulación</h1>
<?php
	echo $form->create('Degree');
?>
	<fieldset>
	<legend>Datos generales</legend>
		<?php echo $form->input('acronym', array('label' => 'Acrónimo', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
		<?php echo $form->input('code', array('label' => 'Código', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
		<?php echo $form->input('name', array('label' => 'Nombre', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
		<?php echo $form->input('institution_id', array('type' => 'hidden', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>', 'value' => $institution_id)); ?>
	</fieldset>
<?php
	echo $form->end('Crear');
?>
