<!-- File: /app/views/subjects/add.ctp -->

<?php $html->addCrumb('Cursos', '/academic_years'); ?>
<?php $html->addCrumb($modelHelper->academic_year_name($subject), "/academic_years/view/{$subject['Course']['academic_year_id']}"); ?>
<?php $html->addCrumb(Environment::institution('name'), Environment::getBaseUrl() . "/courses/index/{$subject['Course']['academic_year_id']}"); ?>
<?php $html->addCrumb("{$degree['Degree']['name']}", Environment::getBaseUrl() . "/courses/view/{$subject['Course']['id']}"); ?>
<?php $html->addCrumb($subject['Subject']['name'], Environment::getBaseUrl() . "/subjects/view/{$subject['Subject']['id']}"); ?>
<?php $html->addCrumb("Modificar asignatura", Environment::getBaseUrl() . "/subjects/edit/{$subject['Subject']['id']}"); ?>

<h1>Modificar asignatura</h1>
<?php
	echo $form->create('Subject', array('action' => 'edit'));
?>
	<fieldset>
	<legend>Datos generales</legend>
		<?php echo $form->input('code', array('label' => 'Código', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
		<?php echo $form->input('name', array('label' => 'Nombre', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
		<?php echo $form->input('acronym', array('label' => 'Acrónimo', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
		<?php echo $form->input('level', array('label' => 'Curso', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>', 'options' => Configure::read('app.subject.levels'))); ?>
		<?php echo $form->input('semester', array('label' => 'Semestre', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>', 'options' => Configure::read('app.subject.semesters'))); ?>
		<?php echo $form->input('type', array('label' => 'Tipo', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>', 'options' => Configure::read('app.subject.types'))); ?>
		<?php echo $form->input('credits_number', array('label' => 'Nº créditos', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
		<?php echo $form->input('credits_number', array('label' => 'Nº créditos', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
		<?php if ($subject['Subject']['parent_id']): ?>
			<div class="input">
				<dl>
					<dt><label>Asignatura maestra</label></dt>
					<dd><?php
						echo $html->link(
							strpos($subject['Parent']['name'], $subject['Parent']['code']) === false ? "{$subject['Parent']['code']} {$subject['Parent']['name']}" : $subject['Parent']['name'],
							array('controller' => 'subjects', 'action' => 'view', $subject['Parent']['id'])
						)
					?></dd>
				</dl>
			</div>
		<?php else: ?>
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
			<?php echo $form->input('closed_attendance_groups', array('label' => 'Grupos de asistencias cerrados (los alumnos solo pueden registrar la asistencia en el grupo apuntado)')); ?>
		<?php endif; ?>
		
		<?php echo $form->input('course_id', array('type' => 'hidden', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>

		<?php
			if ($auth->user('type') == "Administrador") {
				echo '<div class="submit">';
				echo $html->link('Eliminar asignatura', array('action' => 'delete', $subject['Subject']['id']), null, 'Cuando elimina una asignatura, elimina también los grupos, las actividades y toda la programación. ¿Está seguro que desea borrarla?');
				echo '</div>';
			}
		?>
	</fieldset>
	<?php echo $form->input('id', array('type' => 'hidden')); ?>
<?php
	echo $form->end('Modificar');
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