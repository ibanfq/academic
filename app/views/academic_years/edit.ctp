<!-- File: /app/views/academic_years/add.ctp -->
<?php $html->addCrumb('Cursos', '/academic_years'); ?>
<?php $html->addCrumb($modelHelper->academic_year_name($academic_year), "/academic_years/view/{$academic_year['AcademicYear']['id']}"); ?>
<?php $html->addCrumb("Modificar curso", "/academic_years/edit/{$academic_year['AcademicYear']['id']}"); ?>

<?php
	echo $form->create('AcademicYear', array('action' => 'edit'));
?>
	<fieldset>
	<legend>Datos generales</legend>
		<?php echo $form->input('initial_date', array('label' => 'Fecha de inicio', 'type' => 'text', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
		<?php echo $form->input('final_date', array('label' => 'Fecha de fin', 'type' => 'text', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
	</fieldset>
	<?php echo $form->input('id', array('type' => 'hidden')); ?>
<?php
	echo $form->end('Modificar');
?>
<script type="text/javascript">
	$(function() {
		<?php 
			echo $dateHelper->datepicker("#AcademicYearInitialDate");
			echo $dateHelper->datepicker("#AcademicYearFinalDate");
		?>
	});
</script>