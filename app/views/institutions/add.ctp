<!-- File: /app/views/institutions/add.ctp -->
<?php $html->addCrumb('Centros', '/institutions'); ?>
<?php $html->addCrumb('Crear centro', "/institutions/add"); ?>

<?php
	echo $form->create('Institution');
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
<?php
	echo $form->end('Crear');
?>
