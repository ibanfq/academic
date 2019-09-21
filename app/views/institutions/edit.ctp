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
		<?php echo $form->input('code', array('label' => 'Código', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
		<?php echo $form->input('name', array('label' => 'Nombre', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
		<?php
			$help_text = '<span class="help-text">Correo electrónico al que se enviará una copia de los mensajes más importantes</span>';
			echo $form->input('audit_email', array('label' => 'Correo de auditoría', 'autocorrect' => 'off', 'autocapitalize' => 'none', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => "</dd></dl>$help_text"));
		?>
	</fieldset>
	<?php echo $form->input('id', array('type' => 'hidden')); ?>
<?php
	echo $form->end('Modificar');
?>
