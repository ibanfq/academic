<!-- File: /app/views/institutions/add.ctp -->
<?php $html->addCrumb('Centros', '/institutions'); ?>
<?php $html->addCrumb($institution['Institution']['name'], "/institutions/view/{$institution['Institution']['id']}"); ?>
<?php $html->addCrumb("Modificar centro", "/institutions/edit/{$institution['Institution']['id']}"); ?>

<?php
	echo $form->create('Institution', array('action' => 'edit'));
?>
	<fieldset>
	<legend>Datos generales</legend>
		<?php echo $form->input('acronym', array('label' => 'Acrónimo', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
		<?php echo $form->input('name', array('label' => 'Nombre', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
	</fieldset>
	<?php echo $form->input('id', array('type' => 'hidden')); ?>
<?php
	echo $form->end('Modificar');
?>
