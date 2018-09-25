<?php 
	$html->addCrumb('Reservas', '/bookings'); 
    $html->addCrumb('Ver reserva', "/bookings/view/{$booking['Booking']['id']}"); 
	$html->addCrumb('Editar reserva', "/bookings/edit/{$booking['Booking']['id']}");

    $teachers_can_booking = Configure::read('app.classroom.teachers_can_booking');
    $isTeacher = $auth->user('type') == 'Profesor';
	
	$initial_hour = date_create($booking['Booking']['initial_hour']);
    $final_hour = date_create($booking['Booking']['final_hour']);
?>
<h1>Editar reserva</h1>

<?php
	echo $form->create('Booking', array('action' => 'edit'));
?>
	<fieldset>
	<legend>Datos generales</legend>
		<?php echo $form->input('reason', array('label' => 'Motivo', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
		<?php echo $form->input('required_equipment', array('type' => 'text_area', 'label' => 'Información', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
    
		<?php echo $form->input('date', array('label' => 'Fecha', 'type' => 'text', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>', 'value' => $initial_hour->format('d-m-Y'))); ?>
		<div class="input">
			<dl>
				<dt>
					<label for="BookingInitialHour">Hora de incio</label>
				</dt>
				<dd>
					<?php echo $form->hour('initial_hour', true, "07", array('timeFormat' => '24')); ?>
					:
          <select id="BookingInitialHourMin" name="data[Booking][initial_hour][minute]">
						<option value="00">00</option>
						<option value="30">30</option>
					</select>
				</dd>
			</dl>
		</div>
		
		<div class="input">
			<dl>
				<dt>
					<label for="BookingFinalHour">Hora de fin</label>
				</dt>
				
				<dd>
					<?php echo $form->hour('final_hour', true, "07", array('timeFormat' => '24')); ?>
					:
          <select id="BookingFinalHourMin" name="data[Booking][final_hour][minute]">
						<option value="00">00</option>
						<option value="30">30</option>
					</select>
				</dd>
			</dl>
		</div>
  
    <?php echo $form->input('user_type', array('label' => 'Tipo de asistentes', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>', 'onchange' => 'userTypeChanged()', 'empty' => 'Ninguno', 'options' => array("Todos" => "Todos los usuarios", "No-estudiante" => "Todos menos estudiante", "Administrador" => "Administrador", "Administrativo" => "Administrativo" , "Conserje" => "Conserje",  "Profesor" => "Profesor", "Estudiante" => "Estudiante", "Becario" => "Becario"))); ?>
  
    <?php if ($auth->user('type') == "Administrador" || $booking['Booking']['classroom_id'] != -1): ?>
    	<div class="input select required">
    		<dl>
    			<dt><label for="BookingClassroomId">Aula</label></dt>
    			<dd>
			    	<select name="data[Booking][classroom_id]" id="BookingClassroomId">
					    <?php if ($auth->user('type') == "Administrador"): ?>
					    	<option value="-1" <?php if ($booking['Booking']['classroom_id'] == -1): ?>selected="selected"<?php endif; ?>>Todas las aulas</option>
						<?php endif; ?>
						<?php foreach ($classrooms as $classroom): ?>
							<option <?php if ($teachers_can_booking && $isTeacher && !$classroom['Classroom']['teachers_can_booking']): ?>disabled="disabled"<?php elseif($classroom['Classroom']['id'] == $booking['Booking']['classroom_id']): ?>selected="selected"<?php endif; ?> value="<?php echo h($classroom['Classroom']['id']) ?>"><?php echo h($classroom['Classroom']['name']) ?></option>
						<?php endforeach; ?>
					</select>
				</dd>
			</dl>
		</div>
    <?php else: ?>
      <div><dl>
          <dt><label>Aula</label></dt>
          <dd><?php echo $classrooms[$booking['Booking']['classroom_id']] ?></dd>
      </dl></div>
    <?php endif; ?>

    <?php if (Configure::read('app.booking.show_tv')): ?>
    	<?php echo $form->input('show_tv', array('label' => 'Mostrar esta reserva en TV')); ?>
	<?php endif; ?>
		
		<?php echo $form->input('id', array('type' => 'hidden')); ?>
	</fieldset>
	
	<fieldset>
	<legend>Asistentes</legend>
		<table>
			<thead>
				<tr>
					<th style="width:80%">Asistente</th>
					<th>Añadir</th>
				</th>
			</thead>
			<tfoot>
				<tr><td colspan=2 ><a href="javascript:;" onclick="addRow()" title="Haga click para añadir un asistente">Añadir asistente</a></td></tr>
			</tfoot>
			<tbody id="attendees">
				<?php $i = 0 ?>
				<?php foreach ($attendees as $attendee): ?>
					<tr id="row_<?php echo $i?>">
						<td onclick="toogleCheckBox(<?php echo $attendee['Attendee']['id'] ?>)"><?php echo "{$attendee['Attendee']['first_name']} {$attendee['Attendee']['last_name']}"?></td>
						<td><input type="checkbox" name="data[Booking][attendees][<?php echo $attendee['Attendee']['id'] ?>]" value="1" id="attendees_<?php echo $attendee['Attendee']['id'] ?>" checked /></td>
					</tr>
					<?php $i++; ?>
				<?php endforeach;?>
			</tbody>
		</table>		
	</fieldset>
<?php
	echo $form->end('Guardar');
?>
<script type="text/javascript">
	$(function() {
		<?php 
			echo $dateHelper->datepicker("#BookingDate");
		?>
		$('#BookingInitialHourHour').val('<?php echo $initial_hour->format('H') ?>');
		$('#BookingInitialHourMin').val('<?php echo $initial_hour->format('i') ?>');
		$('#BookingFinalHourHour').val('<?php echo $final_hour->format('H') ?>');
		$('#BookingFinalHourMin').val('<?php echo $final_hour->format('i') ?>');
	});
	
	function toogleCheckBox(id){
		$('#attendees_' + id).attr('checked', !($('#attendees_' + id).attr('checked')));
	}
	

	function addRow(){
		index = $('#attendees > tr').length;
		if (index == 0)
			$('#attendees').html("<tr id='row_" + index + "'><td><input type='text' id='new_attendee_" + index + "' class='attendee_autocomplete' /></td><td style='vertical-align:middle'><input type='checkbox' id='new_attendee_"+ index + "_checkbox' value='1' checked onclick='deleteRow(" + index + ")' /></td><script type='text\/javascript'>$('#new_attendee_" + index + "').autocomplete('<?php echo PATH ?>\/users\/find_by_name', {formatItem: 	function (row){if (row[1] != null) return row[0];else return 'No existe ningún usuario con este nombre.'; }}).result(function(event, item){ $('#new_attendee_" + index + "_checkbox').attr('name', 'data[Booking][attendees][' + item[1] + ']'); });<\/script></tr>");
		else
			$('#row_' + (index - 1)).after("<tr id='row_" + index + "'><td><input type='text' id='new_attendee_" + index + "' class='attendee_autocomplete' /></td><td style='vertical-align:middle'><input type='checkbox' id='new_attendee_"+ index + "_checkbox' value='1' checked onclick='deleteRow(" + index + ")' /></td><script type='text\/javascript'>$('#new_attendee_" + index + "').autocomplete('<?php echo PATH ?>\/users\/find_by_name', {formatItem: 	function (row){if (row[1] != null) return row[0];else return 'No existe ningún usuario con este nombre.'; }}).result(function(event, item){ $('#new_attendee_" + index + "_checkbox').attr('name', 'data[Booking][attendees][' + item[1] + ']'); });<\/script></tr>");
	}
	
	function deleteRow(index) {
		$('#row_' + index).remove();
	}
	
	$(document).ready(function() {
  	function formatItem(row){
  		if (row[1] != null)
  			return row[0];
  		else
  			return 'No existe ningún aula con este nombre.';
  	}
  });
</script>