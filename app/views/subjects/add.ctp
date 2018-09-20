<!-- File: /app/views/subjects/add.ctp -->
<?php $html->addCrumb('Cursos', '/courses'); ?>
<?php $html->addCrumb($course['Course']['name'], "/courses/view/{$course['Course']['id']}"); ?>
<?php $html->addCrumb("Crear asignatura", "/subjects/add/{$course['Course']['id']}"); ?>

<h1>Crear asignatura</h1>
<?php
	echo $form->create('Subject');
?>
	<fieldset>
	<legend>Datos generales</legend>
		<?php echo $form->input('code', array('label' => 'Código', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
		<?php echo $form->input('name', array('label' => 'Nombre', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
		<?php echo $form->input('acronym', array('label' => 'Acrónimo', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
		<?php echo $form->input('level', array('label' => 'Curso', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>', 'options' => Configure::read('app.levels'))); ?>
		<?php echo $form->input('semester', array('label' => 'Semestre', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>', 'options' => Configure::read('app.semesters'))); ?>
		<div class="input select required">
			<dl>
				<dt><label>Tipo</label></dt>
				<dd>
					<select id="SubjectType" name="data[Subject][type]">
						<optgroup label="Grado">
							<option selected="selected" value="Básica de Rama">Básica de Rama</option>
							<option value="Básica">Básica</option>
							<option value="Obligatoria">Obligatoria</option>
							<option value="Optativa">Optativa</option>
						</optgroup>
						<optgroup label="Licencitura">
							<option selected="selected" value="Troncal">Troncal</option>
							<option value="Obligatoria">Obligatoria</option>
							<option value="Optativa">Optativa</option>
						</optgroup>
					</select>
				</dd>
			</dl>
		</div>
		<?php echo $form->input('credits_number', array('label' => 'Nº créditos', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
		<div class="input text">
			<dl>
				<dt><label for="coordinator_name">Coordinador*</label></dt>
				<dd><input type="text" name="coordinator_name" id="coordinator_name" autocomplete="off" /></dd>
				<?php echo $form->input('coordinator_id', array('type' => 'hidden', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
			</dl>
		</div>
		<div class="input text">
			<dl>
				<dt><label for="responsible_name">Responsable de prácticas*</label></dt>
				<dd><input type="text" name="responsible_name" id="responsible_name" autocomplete="off"/></dd>
				<?php echo $form->input('practice_responsible_id', array('type' => 'hidden', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
			</dl>
		</div>
		
		<?php echo $form->input('course_id', array('type' => 'hidden', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>', 'value' => $course_id)); ?>
	</fieldset>
<?php
	echo $form->end('Crear');
?>

<script type ="text/javascript">
	$(document).ready(function() {
		function formatItem(row){
			if (row[1] != null)
				return row[0];
			else
				return 'No existe ningún profesor con este nombre.';
		}
		
	    $("input#coordinator_name").autocomplete("<?php echo PATH ?>/users/find_teachers_by_name", {formatItem: formatItem}).result(function(event, item){ $("input#SubjectCoordinatorId").val(item[1]); });
		$("input#responsible_name").autocomplete("<?php echo PATH ?>/users/find_teachers_by_name", {formatItem: formatItem}).result(function(event, item){ $("input#SubjectPracticeResponsibleId").val(item[1]); });
	});
</script>