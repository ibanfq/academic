<?php 
	$html->addCrumb('Registros de asistencia', "/attendance_registers/view_my_registers/{$subject['Subject']['course_id']}"); 
  if ($students === false) {
    $html->addCrumb($event['Activity']['name'], "/attendance_registers/edit_student_attendance/{$event['Event']['id']}"); 
  } else {
    $html->addCrumb($ar['Activity']['name'], "/attendance_registers/edit_student_attendance/{$ar['Event']['id']}"); 
  }
?>

<?php if ($students === false): // Edition is blocked, show subject information ?>
<?php echo $this->Html->css('attendance_registers', null, array('inline' => false)) ?>
<?php $initial_hour = date_create($event['Event']['initial_hour']) ?>
<?php $final_hour = date_create($event['Event']['final_hour']) ?>
<h1>Editar registro de asistencia</h1>
<?php echo $form->create('AttendanceRegister', array('action' => 'edit_student_attendance')); ?>
<fieldset>
<legend>Datos generales</legend>
	<div class="input">
		<dl>
			<dt><label for="subect">Asignatura</label></dt>
			<dd><input type="input" id="subject" readonly disabled class="disabled" value="<?php echo $subject['Subject']['name'] ?>" /></dd>
		</dl>
	</div>

	<div class="input">
		<dl>
			<dt><label for="activity">Actividad</label></dt>
			<dd><input type="input" id="activity" readonly disabled class="disabled" value="<?php echo $event['Activity']['name'] ?>"/></dd>
		</dl>
	</div>

	<div class="input">
		<dl>
			<dt><label for="teacher">Profesor</label></dt>
			<dd><input type="input" id="teacher" readonly disabled class="disabled" value="<?php echo "{$event['Teacher']['first_name']} {$event['Teacher']['last_name']}" ?>"/></dd>
		</dl>
	</div>

	<div class="input">
		<dl>
			<dt><label for="teacher_2">2º Profesor</label></dt>
			<dd>
			  <input type="input" id="teacher_2" readonly disabled class="disabled" value="<?php
			    if (isset($event['Teacher_2']['id']))
			      echo "{$event['Teacher_2']['first_name']} {$event['Teacher_2']['last_name']}"
			    ?>"
			  />
			</dd>
		</dl>
	</div>

	<div class="input">
		<dl>
			<dt><label for="teacher">Fecha y hora</label></dt>
			<dd><input type="input" readonly disabled class="disabled" value="<?php echo $initial_hour->format('d/m/Y H:i')."-".$final_hour->format('H:i') ?>"/></dd>
		</dl>
	</div>
  <?php if ($auth->user('type') != "Profesor"): ?>
    <p class="info-message">No es posible editar el registro de asistencia hasta que la hoja de firmas no se haya imprimido.</p>
  <?php else: ?>
    <p class="info-message">No es posible editar el registro de asistencia hasta que el evento haya sido marcado como impartido.</p>
  <?php endif; ?>
</fieldset>
<?php echo $form->end(); ?>
<?php else: ?>

<?php $initial_hour = date_create($ar['Event']['initial_hour']) ?>
<?php $final_hour = date_create($ar['Event']['final_hour']) ?>
<h1>Editar registro de asistencia</h1>

<?php echo $form->create('AttendanceRegister', array('action' => 'edit_student_attendance')); ?>
	<fieldset>
	<legend>Datos generales</legend>
		<div class="input">
			<dl>
				<dt><label for="subect">Asignatura</label></dt>
				<dd><input type="input" id="subject" readonly disabled class="disabled" value="<?php echo $subject['Subject']['name'] ?>" /></dd>
			</dl>
		</div>

		<div class="input">
			<dl>
				<dt><label for="activity">Actividad</label></dt>
				<dd><input type="input" id="activity" readonly disabled class="disabled" value="<?php echo $ar['Activity']['name'] ?>"/></dd>
			</dl>
		</div>

		<div class="input">
			<dl>
				<dt><label for="teacher">Profesor</label></dt>
				<dd><input type="input" id="teacher" readonly disabled class="disabled" value="<?php echo "{$ar['Teacher']['first_name']} {$ar['Teacher']['last_name']}" ?>"/></dd>
			</dl>
		</div>

		<div class="input">
			<dl>
				<dt><label for="teacher_2">2º Profesor</label></dt>
				<dd>
				  <input type="input" id="teacher_2" readonly disabled class="disabled" value="<?php
				    if (isset($ar['Teacher_2']['id']))
				      echo "{$ar['Teacher_2']['first_name']} {$ar['Teacher_2']['last_name']}"
				    ?>"
				  />
				</dd>
			</dl>
		</div>

		<div class="input">
			<dl>
				<dt><label for="teacher">Fecha y hora</label></dt>
				<dd><input type="input" readonly disabled class="disabled" value="<?php echo $initial_hour->format('d/m/Y H:i')."-".$final_hour->format('H:i') ?>"/></dd>
			</dl>
		</div>

		<?php echo $form->input('id', array('type' => 'hidden', 'value' => $ar['AttendanceRegister']['id'])); ?>
	</fieldset>

	<fieldset>
	<legend>Estudiantes</legend>
		<table>
			<thead>
				<tr>
					<th style="width:80%">Estudiante</th>
					<th>Asistió</th>
				</th>
			</thead>
			<tfoot>
				<tr><td colspan=2 ><a href="javascript:;" onclick="addRow()" title="Haga click para añadir un estudiante">Añadir estudiante</a></td></tr>
			</tfoot>
			<tbody id="students">
				<?php $i = 0 ?>
				<?php foreach ($students as $student): ?>
          <?php $checked = empty($ar['AttendanceRegister']['secret_code'])? 'checked' : ''; ?>
					<tr id="row_<?php echo $i?>">
						<td onclick="toogleCheckBox(<?php echo $student['id'] ?>)"><?php echo "{$student['first_name']} {$student['last_name']}"?></td>
						<td><input type="checkbox" name="data[AttendanceRegister][students][<?php echo $student['id'] ?>]" value="1" id="students_<?php echo $student['id'] ?>" <?php echo $checked ?> /></td>
					</tr>
					<?php $i++; ?>
				<?php endforeach;?>
			</tbody>
		</table>
	</fieldset>
<?php echo $form->end('Guardar'); ?>
<script type="text/javascript">
	$(function() {
		<?php echo $dateHelper->datepicker("#AttendanceRegisterDate") ?>
		$('#AttendanceRegisterInitialHourHour').val('<?php echo $initial_hour->format('H') ?>');
		$('#AttendanceRegisterInitialHourMin').val('<?php echo $initial_hour->format('i') ?>');
		$('#AttendanceRegisterFinalHourHour').val('<?php echo $final_hour->format('H') ?>');
		$('#AttendanceRegisterFinalHourMin').val('<?php echo $final_hour->format('i') ?>');
	});

	function toogleCheckBox(id){
		$('#students_' + id).attr('checked', !($('#students_' + id).attr('checked')));
	}

	function addRow(){
		index = $('#students > tr').length;
		if (index == 0)
			$('#students').html("<tr id='row_" + index + "'><td><input type='text' id='new_student_" + index + "' class='student_autocomplete' /></td><td style='vertical-align:middle'><input type='checkbox' id='new_student_"+ index + "_checkbox' value='1' checked onclick='deleteRow(" + index + ")' /></td><script type='text\/javascript'>$('#new_student_" + index + "').autocomplete('<?php echo PATH ?>\/users\/find_students_by_name', {formatItem: 	function (row){if (row[1] != null) return row[0];else return 'No existe ningún estudiante con este nombre.'; }}).result(function(event, item){ $('#new_student_" + index + "_checkbox').attr('name', 'data[AttendanceRegister][students][' + item[1] + ']'); });<\/script></tr>");
	else
		$('#row_' + (index - 1)).after("<tr id='row_" + index + "'><td><input type='text' id='new_student_" + index + "' class='student_autocomplete' /></td><td style='vertical-align:middle'><input type='checkbox' id='new_student_"+ index + "_checkbox' value='1' checked onclick='deleteRow(" + index + ")' /></td><script type='text\/javascript'>$('#new_student_" + index + "').autocomplete('<?php echo PATH ?>\/users\/find_students_by_name', {formatItem: 	function (row){if (row[1] != null) return row[0];else return 'No existe ningún estudiante con este nombre.'; }}).result(function(event, item){ $('#new_student_" + index + "_checkbox').attr('name', 'data[AttendanceRegister][students][' + item[1] + ']'); });<\/script></tr>");
	}

	function deleteRow(index) {
		$('#row_' + index).remove();
	}

	$(document).ready(function() {
		function formatItem(row){
  		if (row[1] != null)
  			return row[0];
  		else
  			return 'No existe ningún profesor con este nombre.';
  	}

  	function check_2nd_teacher(){
  	  if ($("input#teacher_2").val() == "")
  	    $("input#AttendanceRegisterTeacher2Id").val("");
  	}

  	$("input#teacher").autocomplete("<?php echo PATH ?>/users/find_teachers_by_name", {formatItem: formatItem}).result(function(event, item){ $("input#AttendanceRegisterTeacherId").val(item[1]); });

  	$("input#teacher_2").autocomplete("<?php echo PATH ?>/users/find_teachers_by_name", {formatItem: formatItem}).result(function(event, item){ $("input#AttendanceRegisterTeacher2Id").val(item[1]); });

    $('form').bind('submit', check_2nd_teacher);
  });
</script>

<?php endif; ?>