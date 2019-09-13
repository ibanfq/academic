<!-- File: /app/views/courses/add.ctp -->
<?php $html->addCrumb('Cursos', '/academic_years'); ?>
<?php $html->addCrumb($modelHelper->academic_year_name($academic_year), "/academic_years/view/{$academic_year['AcademicYear']['id']}"); ?>
<?php $html->addCrumb(Environment::institution('name'), Environment::getBaseUrl() . "/courses/index/{$academic_year['AcademicYear']['id']}"); ?>
<?php $html->addCrumb('Añadir titulación', Environment::getBaseUrl() . "/courses/add/{$academic_year['AcademicYear']['id']}"); ?>

<?php
	echo $form->create('Course');
?>
	<fieldset>
	<legend>Datos generales</legend>
		<dl>
			<dt>Titulación</dt>
			<dd>
				<select id="titulaciones" name="data[Course][degree_id]">
					<?php foreach ($degrees as $degree): ?>
						<option <?php if ($disabled_degrees[$degree['id']]): ?>disabled="disabled"<?php endif; ?> value="<?php echo h($degree['id']) ?>"><?php echo h($degree['name']) ?></option>
					<?php endforeach; ?>
				</select>
			</dd>
		</dl>
		<dl>
			<dt>Copiar curso</dt>
			<dd id="courses">
				<?php foreach ($degrees as $degree): ?>
					<select data-degree-id="<?php echo h($degree['id']) ?>" name="data[Course][course_template_id]" disabled style="display:none;">
						<?php if (!$disabled_degrees[$degree['id']] && isset($degree['Course'])): ?>
							<option value=""></option>
							<?php foreach ($degree['Course'] as $course): ?>
								<option value="<?php echo h($course['id']) ?>"><?php echo h($modelHelper->academic_year_name($course)) ?></option>
							<?php endforeach; ?>
						<?php endif; ?>
					</select>
				<?php endforeach; ?>
			</dd>
		</dl>
		<?php echo $form->input('academic_year_id', array('type' => 'hidden', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>', 'value' => $academic_year_id)); ?>
	</fieldset>
<?php
	echo $form->end('Añadir');
?>
<script type="text/javascript">
	$(function() {
		$('#titulaciones').on('change', function() {
			var value = $(this).val();
			$('#courses select')
				.prop('disabled', true).css('display', 'none')
				.filter(function () {
					return $(this).data('degree-id') == value;
				})
				.prop('disabled', false).css('display', 'block')
				.val('');
		}).change();
	});
</script>