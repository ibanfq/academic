<?php
	$html->addCrumb('Registros de impartición', '/attendance_registers');

	if (isset($activity))
	  $activity_id = $activity['Activity']['id'];
	else
	  $activity_id = -1;

	if (isset($teacher))
	  $teacher_id = $teacher['Teacher']['id'];
	else
	  $teacher_id = -1;

	if (isset($date))
	  $date_id = $date->format('d-m-Y');
	else
	  $date_id = -1;

	if (!isset($id))
	  $id = -1;

  $paginator->options(array('url' => array($teacher_id, $activity_id, $date_id, $id)));
?>

<h1>Registro de impartición</h1>
<div class="actions">
	<ul>
		<li><?php echo $html->link('Crear registro', array('action' => 'add')) ?></li>
	</ul>
</div>

<div class="view">
	<?php echo $form->create('AttendanceRegister', array('action' => 'index')); ?>
	<fieldset class="search">
	<legend>Buscar registro</legend>
		<div class="input">
			<dl>
				<dt><label for="code_id">Código de barras</label></dt>
				<dd>
					<?php echo $form->input('id', array('div' => false, 'label' => false, 'type' => 'text')); ?>
				</dd>
			</dl>
		</div>

		<div class="input">
			<dl>
				<dt><label for="date">Fecha</label></dt>
				<dd><input type="text" id="date" name="data[AttendanceRegister][date]" value='<?php echo (isset($date) ? $date->format('d-m-Y') : "") ?>' /></dd>
			</dl>
		</div>

		<div class="input">
			<dl>
				<dt><label for="teacher_name">Profesor</label></dt>
				<dd><input type="text" id="teacher_name" value='<?php echo (isset($teacher) ? "{$teacher['Teacher']['first_name']} {$teacher['Teacher']['last_name']}" : "") ?>'/></dd>
			</dl>
		</div>

		<div class="input">
			<dl>
				<dt><label for="activity_name">Actividad</label></dt>
				<dd><input type="text" id="activity_name" value='<?php echo (isset($activity) ? $activity['Activity']['name'] : "") ?>' /></dd>
			</dl>
		</div>

		<?php echo $form->input('teacher_id', array('type' => 'hidden')); ?>
		<?php echo $form->input('student_id', array('type' => 'hidden')); ?>
		<?php echo $form->input('activity_id', array('type' => 'hidden')); ?>
	</fieldset>
		<div class="submit">
			<input type="submit" value="Buscar"/> o <?php echo $html->link('Ver todos', array('action' => 'index')); ?>
		</div>
	</form>
	<table>
		<thead>
			<tr>
				<th>Fecha</th>
				<th>Actividad</th>
				<th>Profesor</th>
				<th>Nº de horas</th>
				<th>Nº de estudiantes</th>
			</tr>
		</thead>
		<tfoot>
			<?php
				echo $paginator->prev('« Anterior ', null, null, array('class' => 'disabled'));
				echo "&nbsp";
				echo $paginator->numbers();
				echo "&nbsp";
				echo $paginator->next(' Siguiente »', null, null, array('class' => 'disabled'));
			?>
		</tfoot>
		<tbody>
			<?php
				foreach ($registers as $register):
					$date = date_create($register['AttendanceRegister']['initial_hour']);
			?>
				<tr>
					<td><?php echo $html->link($date->format('d-m-Y'), array('action' => 'view', $register['AttendanceRegister']['id'])) ?></th>
					<td><?php echo $register['Activity']['name'] ?></th>
					<td><?php echo "{$register['Teacher']['first_name']} {$register['Teacher']['last_name']}" ?></th>
					<td><?php echo $register['AttendanceRegister']['duration'] ?></th>
					<td><?php echo $register['AttendanceRegister']['num_students'] ?></th>
				</tr>
			<?php endforeach; ?>

		</tbody>
	</table>
</div>

<script type="text/javascript">
	$(function() {
		<?php echo $dateHelper->datepicker("#date"); ?>
	});

	$(document).ready(function() {
		function makeFormatItem(noItemString) {
		    return function formatItem(row){
				if (row[1] != null)
					return row[0];
				else
					return noItemString;
			}
		}

		//$('#AttendanceRegisterId').autocomplete("<?php echo Environment::getBaseUrl() ?>/attendance_registers/find_by_code", {formatItem: formatItem}).result(function(event, item){ $("#AttendanceRegisterId").val(item[1]); });

		$('#activity_name').autocomplete("<?php echo Environment::getBaseUrl() ?>/activities/find_activities_by_name", {formatItem: makeFormatItem('No existe ninguna actividad con este nombre.')}).result(function(event, item){ $("#AttendanceRegisterActivityId").val(item[1]); });

	  $("#teacher_name").autocomplete("<?php echo Environment::getBaseUrl() ?>/users/find_teachers_by_name", {formatItem: makeFormatItem('No existe ningún profesor con este nombre.')}).result(function(event, item){ $("#AttendanceRegisterTeacherId").val(item[1]); });

		//$("#student_name").autocomplete("<?php echo Environment::getBaseUrl() ?>/users/find_students_by_name", {formatItem: makeFormatItem('No existe ningún alumno con este nombre.')}).result(function(event, item){ $("#AttendanceRegisterStudentId").val(item[1]); });
	});
</script>
