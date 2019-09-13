<!-- File: /app/views/academic_years/add.ctp -->
<?php $html->addCrumb('Cursos', '/academic_years'); ?>
<?php $html->addCrumb('Crear curso', "/academic_year/add"); ?>

<?php
	echo $form->create('AcademicYear');
?>
	<fieldset>
	<legend>Datos generales</legend>
		<?php echo $form->input('initial_date', array('label' => 'Fecha de inicio', 'type' => 'text', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
		<?php echo $form->input('final_date', array('label' => 'Fecha de fin', 'type' => 'text', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
	</fieldset>
<?php
	echo $form->end('Crear');
?>
<script type="text/javascript">
	$(function() {
		<?php 
			echo $dateHelper->datepicker("#AcademicYearInitialDate");
			echo $dateHelper->datepicker("#AcademicYearFinalDate");
		?>
	});
</script>