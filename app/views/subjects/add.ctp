<!-- File: /app/views/subjects/add.ctp -->

<?php $html->addCrumb('Cursos', '/academic_years'); ?>
<?php $html->addCrumb($modelHelper->academic_year_name($course), "/academic_years/view/{$course['Course']['academic_year_id']}"); ?>
<?php $html->addCrumb(Environment::institution('name'), Environment::getBaseUrl() . "/courses/index/{$course['Course']['academic_year_id']}"); ?>
<?php $html->addCrumb("{$course['Degree']['name']}", Environment::getBaseUrl() . "/courses/view/{$course['Course']['id']}"); ?>
<?php $html->addCrumb("Crear asignatura", Environment::getBaseUrl() . "/subjects/add/{$course['Course']['id']}"); ?>

<h1>Crear asignatura</h1>
<?php
	echo $form->create('Subject');
?>
	<fieldset>
	<legend>Datos generales</legend>
		<?php echo $form->input('code', array('label' => 'Código', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
		<?php echo $form->input('name', array('label' => 'Nombre', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
		<?php echo $form->input('acronym', array('label' => 'Acrónimo', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
		<?php echo $form->input('level', array('label' => 'Curso', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>', 'options' => Configure::read('app.subject.levels'))); ?>
		<?php echo $form->input('semester', array('label' => 'Semestre', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>', 'options' => Configure::read('app.subject.semesters'))); ?>
		<?php echo $form->input('type', array('label' => 'Tipo', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>', 'options' => Configure::read('app.subject.types'), 'default' => Configure::read('app.subject.default_type'))); ?>
		<?php echo $form->input('credits_number', array('label' => 'Nº créditos', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
		<div class="input text">
			<dl>
				<dt><label for="coordinator_name">Coordinador*</label></dt>
				<dd><input type="text" name="coordinator_name" id="coordinator_name" autocomplete="off" <?php if (isset($this->data['Coordinator']['first_name'])): ?>value="<?php echo "{$this->data['Coordinator']['first_name']} {$this->data['Coordinator']['last_name']}" ?>"<?php endif ?>/></dd>
				<?php echo $form->input('coordinator_id', array('type' => 'hidden', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
			</dl>
			<?php echo $form->error('coordinator_id'); ?>
		</div>
		<div class="input text">
			<dl>
				<dt><label for="responsible_name">Responsable de prácticas</label></dt>
				<dd><input type="text" name="responsible_name" id="responsible_name" autocomplete="off" <?php if (isset($this->data['Responsible']['first_name'])): ?>value="<?php echo "{$this->data['Responsible']['first_name']} {$this->data['Responsible']['last_name']}" ?>"<?php endif ?> /></dd>
				<?php echo $form->input('practice_responsible_id', array('type' => 'hidden', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
			</dl>
			<?php echo $form->error('practice_responsible_id'); ?>
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
		
	    $("input#coordinator_name").autocomplete("<?php echo Environment::getBaseUrl() ?>/users/find_teachers_by_name", {formatItem: formatItem}).result(function(event, item){ $("input#SubjectCoordinatorId").val(item[1]); });
		$("input#responsible_name").autocomplete("<?php echo Environment::getBaseUrl() ?>/users/find_teachers_by_name", {formatItem: formatItem}).result(function(event, item){ $("input#SubjectPracticeResponsibleId").val(item[1]); });
	});
</script>