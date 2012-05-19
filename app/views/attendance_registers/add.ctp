<?php $html->addCrumb('Registros de impartición', '/attendance_registers'); ?>
<?php $html->addCrumb('Crear registro de impartición', '/attendance_registers/add'); ?>

<h1>Crear registro de impartición</h1>

<?php
	echo $form->create('AttendanceRegister');
?>
	<fieldset>
	<legend>Datos generales</legend>
	  <dl>
	    <dt><label for="id">Código de barras</label></dt>
	    <dd><textarea id="AttendanceRegisterId" name="data[AttendanceRegister][id]" onchange="getRegisterInfo()" rows="1"></textarea></dd>
	  </dl>
		
		<div class="input">
			<dl>
				<dt><label for="subect">Asignatura</label></dt>
				<dd><input type="input" id="subject" readonly class="disabled" /></dd>
			</dl>
		</div>
		
		<div class="input">
			<dl>
				<dt><label for="activity">Actividad</label></dt>
				<dd><input type="input" id="activity" readonly class="disabled" /></dd>
			</dl>
		</div>
		
		<div class="input">
			<dl>
				<dt><label for="teacher">Profesor</label></dt>
				<dd><input type="input" id="teacher" /></dd>
				<input type="hidden" id="AttendanceRegisterTeacherId" name="data[AttendanceRegister][teacher_id]" />
			</dl>
		</div>
		
		<div class="input">
			<dl>
				<dt><label for="teacher">2º Profesor</label></dt>
				<dd><input type="input" id="teacher_2" /></dd>
				<input type="hidden" id="AttendanceRegisterTeacher_2Id" name="data[AttendanceRegister][teacher_2_id]" />
			</dl>
		</div>
		
		<?php echo $form->input('date', array('label' => 'Fecha', 'type' => 'text', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
		<div class="input">
			<dl>
				<dt>
					<label for="AttendanceRegisterInitialHour" style="display:inline">Hora de incio</label>
				</dt>
				<dd>
					<?php echo $form->hour('initial_hour', true, "07", array('timeFormat' => '24')); ?>
					:<select id="AttendanceRegisterInitialHourMin" name="data[AttendanceRegister][initial_hour][minute]">
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
					:<select id="AttendanceRegisterFinalHourMin" name="data[AttendanceRegister][final_hour][minute]">
						<option value="00">00</option>
						<option value="30">30</option>
					</select>
				</dd>
			</dl>
		</div>
		
		<div class="input">
			<dl>
				<dt><label for="AttendanceRegisterNumStudents">Nº de asistentes</label></dt>
				<dd><input type="input" id="AttendanceRegisterNumStudents" name="data[AttendanceRegister][num_students]" /></dd>
			</dl>
		</div>
		
		
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
				<tr><td colspan=2 ><a href="javascript:;" onclick="addRow()" title="Haga click para añadir un estudiante">Añadir estudiante</a> o <a href="#" onclick="if (confirm('¿Está seguro que desea borrar todos los estudiantes asistentes? Recuerde que para guardar los cambios debe guardar el registro de impartición.')) $('#students').html('')">Borrar todos</a></td></tr>
			</tfoot>
			<tbody id="students">
			</tbody>
		</table>		
	</fieldset>
<?php
	echo $form->end('Crear');
?>
<script type="text/javascript">
	$(function() {
		<?php echo $dateHelper->datepicker("#AttendanceRegisterDate") ?>
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

			$("input#teacher").autocomplete("<?php echo PATH ?>/users/find_teachers_by_name", {formatItem: formatItem}).result(function(event, item){ $("input#AttendanceRegisterTeacherId").val(item[1]); });
	});
</script>
