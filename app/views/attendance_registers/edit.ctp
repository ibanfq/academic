<?php 
	$html->addCrumb('Registros de impartición', '/attendance_registers');
	$html->addCrumb('Ver registro de impartición', "/attendance_registers/view/{$ar['AttendanceRegister']['id']}");
	$html->addCrumb('Editar registro', "/attendance_registers/edit/{$ar['AttendanceRegister']['id']}");
	
	$initial_hour = date_create($ar['AttendanceRegister']['initial_hour']);
	$final_hour = date_create($ar['AttendanceRegister']['final_hour']);
?>
<h1>Editar registro de impartición</h1>

<?php
	echo $form->create('AttendanceRegister', array('action' => 'edit'));
?>
	<fieldset>
	<legend>Datos generales</legend>
		<div class="input">
			<dl>
				<dt><label for="subect">Asignatura</label></dt>
				<dd><input type="input" id="subject" readonly class="disabled" value="<?php echo $subject['Subject']['name'] ?>" /></dd>
			</dl>
		</div>
		
		<div class="input">
			<dl>
				<dt><label for="activity">Actividad</label></dt>
				<dd><input type="input" id="activity" readonly class="disabled" value="<?php echo $ar['Activity']['name'] ?>"/></dd>
			</dl>
		</div>
		
		<div class="input">
			<dl>
				<dt><label for="teacher">Profesor</label></dt>
				<dd><input type="input" id="teacher" value="<?php echo "{$ar['Teacher']['first_name']} {$ar['Teacher']['last_name']}" ?>"/></dd>
				<input type="hidden" id="AttendanceRegisterTeacherId" name="data[AttendanceRegister][teacher_id]" value="<?php echo "{$ar['AttendanceRegister']['teacher_id']}" ?>"/>
			</dl>
		</div>
		
		<div class="input">
			<dl>
				<dt><label for="teacher_2">2º Profesor</label></dt>
				<dd>
				  <input type="input" id="teacher_2" value="<?php 
				    if (isset($ar['Teacher_2']['id'])) 
				      echo "{$ar['Teacher_2']['first_name']} {$ar['Teacher_2']['last_name']}" 
				    ?>"
				  />
				</dd>
				<input type="hidden" id="AttendanceRegisterTeacher_2Id" name="data[AttendanceRegister][teacher_2_id]" value="<?php echo "{$ar['AttendanceRegister']['teacher_2_id']}" ?>"/>
			</dl>
		</div>
		
		<?php echo $form->input('date', array('label' => 'Fecha', 'type' => 'text', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>', 'value' => $initial_hour->format('d-m-Y'))); ?>
		<div class="input">
			<dl>
				<dt>
					<label for="AttendanceRegisterInitialHour" style="display:inline">Hora de incio</label>
				</dt>
				<dd>
					<?php echo $form->hour('initial_hour', true, "07", array('timeFormat' => '24')); ?>
					:
          <select id="AttendanceRegisterInitialHourMin" name="data[AttendanceRegister][initial_hour][minute]">
						<option value="00">00</option>
						<option value="30">30</option>
					</select>
				</dd>
			</dl>
		</div>
		
		<div class="input">
			<dl>
				<dt>
					<label for="AttendanceRegisterFinalHour" style="display:inline">Hora de fin</label>
				</dt>
				
				<dd>
					<?php echo $form->hour('final_hour', true, "07", array('timeFormat' => '24')); ?>
					:
          <select id="AttendanceRegisterFinalHourMin" name="data[AttendanceRegister][final_hour][minute]">
						<option value="00">00</option>
						<option value="30">30</option>
					</select>
				</dd>
			</dl>
		</div>
		
		<div class="input">
			<dl>
				<dt><label for="AttendanceRegisterNumStudents">Nº de asistentes</label></dt>
				<dd><input type="input" id="AttendanceRegisterNumStudents" name="data[AttendanceRegister][num_students]" value="<?php 
				    if ($ar['AttendanceRegister']['num_students'] == 0)
				      echo count($students);
				    else
				      echo $ar['AttendanceRegister']['num_students'];
			    ?>" readonly class="disabled"/></dd>
			</dl>
		</div>
		
		<?php echo $form->input('id', array('type' => 'hidden')); ?>
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
					<tr id="row_<?php echo $i?>">
						<td><?php echo "{$student['Student']['first_name']} {$student['Student']['last_name']}"?></td>
						<td><input type="checkbox" name="data[AttendanceRegister][students][<?php echo $student['Student']['id'] ?>]" value="1" id="students_<?php echo $student['Student']['id'] ?>" checked /></td>
					</tr>
					<?php $i++; ?>
				<?php endforeach;?>
			</tbody>
		</table>		
	</fieldset>
<?php
	echo $form->end('Crear');
?>
<script type="text/javascript">
	$(function() {
		<?php 
			echo $dateHelper->datepicker("#AttendanceRegisterDate");
		?>
		$('#AttendanceRegisterInitialHourHour').val('<?php echo $initial_hour->format('H') ?>');
		$('#AttendanceRegisterInitialHourMin').val('<?php echo $initial_hour->format('i') ?>');
		$('#AttendanceRegisterFinalHourHour').val('<?php echo $final_hour->format('H') ?>');
		$('#AttendanceRegisterFinalHourMin').val('<?php echo $final_hour->format('i') ?>');
	});
	
	function getRegisterInfo(){
		$.ajax({
			type: "GET", 
			asynchronous: false,
			url: "<?php echo PATH ?>/attendance_registers/get_register_info/" + $('#AttendanceRegisterId').val(),
			dataType: 'script'
		});
	}
	
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
  	    $("input#AttendanceRegisterTeacher_2Id").val("");
  	}

  	$("input#teacher").autocomplete("<?php echo PATH ?>/users/find_teachers_by_name", {formatItem: formatItem}).result(function(event, item){ $("input#AttendanceRegisterTeacherId").val(item[1]); });
  	  
  	$("input#teacher_2").autocomplete("<?php echo PATH ?>/users/find_teachers_by_name", {formatItem: formatItem}).result(function(event, item){ $("input#AttendanceRegisterTeacher_2Id").val(item[1]); });
    
    $('form').bind('submit', check_2nd_teacher);
  });
</script>