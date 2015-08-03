<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<!-- File: /app/views/events/schedule.ctp -->
<?php $html->addCrumb('Cursos', '/courses'); ?>
<?php $html->addCrumb($course['Course']['name'], "/courses/view/{$course['Course']['id']}"); ?>
<?php $html->addCrumb('Programar curso', "/events/schedule/{$course['Course']['id']}"); ?>

<script type="text/javascript">
	
	var subjects = [
		<?php 
		$array = array();
		foreach ($subjects as $subject):
			array_push($array, "{name: '{$subject['name']}', id: '{$subject['id']}' }");
		endforeach;
		echo implode(",", $array);
		?>
	];
	
	var currentEvent = null;
	
	function delete_event(event_id, parent_id) {
		if (parent_id == '')
			confirmated = confirm("¿Está seguro de que desea eliminar este evento? Al eliminarlo se eliminarán también todos los eventos de la misma serie.");
		else 
			confirmated = confirm("¿Está seguro de que desea eliminar este evento?");
		
		if (confirmated){
			$.ajax({
				cache: false,
				type: "GET", 
				url: "<?php echo PATH ?>/events/delete/" + event_id, 
				dataType: 'script'
			});
		}
	}
	
	function toEventDateString(date){
		var day = date.getDate();
		var month = date.getMonth() + 1;
		var year = date.getFullYear();
		var hour = date.getHours();
		var minute = date.getMinutes();
		
		if (day < 10)
			day = "0" + day;
		if (month < 10)
			month = "0" + month;
		if (hour < 10)
			hour = "0" + hour;
		if (minute < 10)
			minute = "0" + minute;
		
		return year + "-" + month + "-" + day + " " + hour + ":" + minute + ":00";
	}
	
	function addEvent() {
		var initial_hour = new Date($("#date").val());
		var final_hour = new Date($("#date").val());
		var new_event;
		
		initial_hour.setHours($('#EventInitialHourHour').val());
		initial_hour.setMinutes($('#EventInitialHourMin').val());
		final_hour.setHours($('#EventFinalHourHour').val());
		final_hour.setMinutes($('#EventFinalHourMin').val());
		
		$.ajax({
			cache: false,
			type: "POST", 
			data: {'data[Event][activity_id]': $('#EventActivityId').val(), 'data[Event][group_id]': $('#EventGroupId').val(), 'data[Event][teacher_id]': $('#EventTeacherId').val(), 'data[Event][teacher_2_id]': $('#EventTeacher2Id').val(), 'data[Event][initial_hour]': initial_hour.toString(), 'data[Event][final_hour]': final_hour.toString(), 'data[Event][classroom_id]': $('#classrooms').val()},
			url: "<?php echo PATH ?>/events/add/" + $('#EventFinishedAt').val() + "/" + $('#Frequency').val(),
			asynchronous: false,
			dataType: 'script', 
			success: function(data){
				$('#form').dialog('close');
			}
		});
	}
	
	<?php if ($auth->user('type') == "Administrador") { ?>
		function update_classroom(event_id) {
			var classroom_id = $('#EventClassroomId').val();
			
			if ($("#edit_teacher_2_name").val() == "") {
	  		    $('#teacher_2_id').val("");
  		    }
			  
			$.ajax({
				cache: false,
				type: "GET", 
				url: "<?php echo PATH ?>/events/update_classroom/" + event_id + "/" + classroom_id + "/" + $('#teacher_id').val() + "/" + $('#teacher_2_id').val(),
				asynchronous: false,
				dataType: 'script', 
				success: function(data){
					$('#edit_form').dialog('close');
					$('#edit_form').html("");
					if (classroom_id != $('#classrooms').val()) {
						if ($('#notice').hasClass('success')) {
							$('#calendar').fullCalendar('removeEvents', event_id);
						}
					}
				}
			});
		}
	<?php } else { ?>
		function update_teacher(event_id) {
			if ($("#edit_teacher_2_name").val() == "") {
				$('#teacher_2_id').val("");
			}
		  
			$.ajax({
				cache: false,
				type: "GET", 
				url: "<?php echo PATH ?>/events/update_teacher/" + event_id + "/" + $('#teacher_id').val() + "/" + $('#teacher_2_id').val(),
				asynchronous: false,
				dataType: 'script', 
				success: function(data){
					$('#edit_form').dialog('close');
					$('#edit_form').html("");
				}
			});
		}
	<?php } ?>
	
	function reset_form(){
		$('#subject_name').val("");
		$('#EventActivityId').html("<option value=''>Seleccione una actividad</option>");
		$('#EventGroupId').html("<option value=''>Seleccione un grupo</option>");
		$('#teacher_name').val("");
        $('#teacher_2_name').val("");
		$('#EventTeacherId').val("");
		$('#EventTeacher2Id').val("");
		$('#Frequency').val("");
		$('#finished_at').val("");
		$('#finish_date').hide();
		$('#EventOwnerId').val("");
	}
	
	$(document).ready(function() {
		$('#calendar').fullCalendar({
			header: {
				right: 'prev,next today',
				center: 'title',
				left: 'month,agendaWeek'
			},
			defaultView: 'agendaWeek',
			defaultEventMinutes: 60,
			editable: true,
            selectable: {
                agenda: true
            },
			minTime: 7,
			maxTime: 22,
			firstDay: 1,
			events: [ ],
			timeFormat: 'H:mm',
			allDaySlot: false,
			columnFormat: {
				week: 'ddd d/M'
			},
			monthNames: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
			monthNamesShort: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
			dayNames: ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'],
			dayNamesShort: ['Dom', 'Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab'],
			eventResize: function(event, dayDelta, minuteDelta, revertFunc, jsEvent, ui, view) {
				$.ajax({
					cache: false,
					type: "GET", 
					url: "<?php echo PATH ?>/events/update/" + event.id + "/" + dayDelta + "/" + minuteDelta + "/1",
					success: function(data){
						if (data == "false"){
							revertFunc();
							$('#notice').removeClass('success');
							$('#notice').addClass('error');
							$('#notice').html("No ha sido posible actualizar el evento porque coincide con otra actividad o se ha superado el número máximo de horas para esta actividad y grupo.");
						} else { 
							if (data == "notAllowed") {
								revertFunc();
								$('#notice').removeClass('success');
								$('#notice').addClass('error');
								$('#notice').html("Usted no tiene permisos para modificar este evento. Solo su dueño, los coordinadores de la asignatura o un administrador pueden hacerlo.");
							}
							else {
								$('#notice').removeClass('error');
								$('#notice').addClass('success');
								$('#notice').html("El evento se ha actualizado correctamente.");
							}
						}
					}
				});
			},
			buttonText: {today: 'hoy', month: 'mes', week: 'semana', day: 'día'},
			eventClick: function(event, jsEvent, view) {
				$.ajax({
					cache: false,
					type: "GET",
					url: "<?php echo PATH ?>/events/edit/" + event.id, 
					success: function(data) {
						if (data == "false")
							alert("Usted no tiene permisos para editar este evento");
						else{
							$('#edit_form').html(data);
							$('#edit_form').dialog({
								width:500, 
								position:'top', 
								close: function(event, ui) {
									if (currentEvent != null){
										$('#calendar').fullCalendar('removeEventSource', currentEvent);
										$('#calendar').fullCalendar('refetchEvents');
									}
								}
							});
						}
					}
				});
			},
			eventDrop: function( event, dayDelta, minuteDelta, allDay, revertFunc, jsEvent, ui, view ) {
				$.ajax({
					cache: false,
					type: "GET", 
					url: "<?php echo PATH ?>/events/update/" + event.id + "/" + dayDelta + "/" + minuteDelta,
					success: function(data){
						if (data == "false"){
							revertFunc();
							$('#notice').removeClass('success');
							$('#notice').addClass('error');
							$('#notice').html("No ha sido posible actualizar el evento porque coincide con otra actividad.");
						} else {
							if (data == "notAllowed") {
								revertFunc();
								$('#notice').removeClass('success');
								$('#notice').addClass('error');
								$('#notice').html("Usted no tiene permisos para modificar este evento. Solo su dueño, los coordinadores de la asignatura o un administrador pueden hacerlo.");
							}
							else {
								$('#notice').removeClass('error');
								$('#notice').addClass('success');
								$('#notice').html("El evento se ha actualizado correctamente.");
							}
						}
						
					}
				}); 
			},
			eventMouseover: function(event, jsEvent, view) {
				if (event.className == 'booking')
					url = "<?php echo PATH ?>/bookings/view/";
				else
					url = "<?php echo PATH ?>/events/view/";
					
				$.ajax({
					cache: false,
					type: "GET", 
					url: url + event.id,
					asynchronous: false,
					success: function(data) {
						$('#tooltip').html(data);
						$('#EventDetails').html(data);
					}
				});
				
				
				
				$(this).tooltip({
					delay: 500,
					bodyHandler: function() {
						return $('#EventDetails').html();
					},
					showURL: false
				});
				
			},
			dayClick: function(date, allDay, jsEvent, view){
                if (allDay) {
                    $('#calendar').fullCalendar('select', new Date(date.getTime()+9*60*60*1000), new Date(date.getTime()+10*60*60*1000), allDay);
                }
			},
            select: function(date, endDate, allDay, jsEvent, view) {
				if ($('#classrooms').val() == "") {
					alert("Debe seleccionar un aula antes de comenzar a programar actividades");
                    $('#calendar').fullCalendar('unselect');
                }else{
					reset_form();
					var initial_hour = ('0'+date.getHours()).slice(-2);
                    var initial_minute = ('0'+date.getMinutes()).slice(-2);
                    var final_hour = ('0'+endDate.getHours()).slice(-2);
                    var final_minute = ('0'+endDate.getMinutes()).slice(-2);
                    
					if (currentEvent != null){
						$('#calendar').fullCalendar('removeEventSource', currentEvent);
						$('#calendar').fullCalendar('refetchEvents');
					}
					
				
	 				var initial_date = toEventDateString(date);
					var final_date = toEventDateString(endDate);
					currentEvent = [{title: "<<vacío>>", start: initial_date, end: final_date, allDay:false}];
					$('#date').val(date.toString());
					$('#EventInitialHourHour').val(initial_hour);
					$('#EventInitialHourMin').val(initial_minute);
					$('#EventFinalHourHour').val(final_hour);
					$('#EventFinalHourMin').val(final_minute);
					$('#form').dialog({
						width:500, 
						position:'top', 
						close: function(event, ui) {
							$('#calendar').fullCalendar('unselect');
							if (currentEvent != null){
								$('#calendar').fullCalendar('removeEventSource', currentEvent);
								$('#calendar').fullCalendar('refetchEvents');
							}
						}
					});
					$('#calendar').fullCalendar('addEventSource', currentEvent);
					$('#calendar').fullCalendar('refetchEvents');
				}
            }
		});
	});

</script>
<h1>Programar curso</h1>

<p id="notice"></p>

<dl>
	<dt>Aulas</dt>
	<dd><?php echo $form->select('classrooms', $classrooms); ?></dd>
</dl>

<div>
	<div id="calendar_container">
		<div id="calendar" class="fc" style="margin: 3em 0pt; font-size: 13px;"></div>
	</div>
	
	<div id="legend" style="">
		<div id="legend_left">
			<ul>
				<li id="prac_aula">Práctica aula</li>
				<li id="prac_problemas">Práctica problemas</li>
				<li id="prac_informatica">Práctica informática</li>
				<li id="prac_micros">Práctica microscopía</li>
				<li id="prac_lab">Práctica laboratorio</li>
				<li id="prac_clin">Práctica clínica</li>
				<li id="prac_ext">Práctica externa</li>
			</ul>
		</div>

		<div id="legend_right">
			<ul>
				<li id="clase_magistral">Clase magistral</li>
				<li id="seminario">Seminario</li>
				<li id="taller_trabajo">Taller de trabajo</li>
				<li id="tutoria">Tutoría</li>
				<li id="evaluacion">Evaluación</li>
				<li id="otra_presencial">Otra presencial</li>
			</ul>			
		</div>
	</div>
	

	<div id="edit_form">
		
	</div>
	
	<div id="EventDetails" style="display:none">
		
	</div>
	
	<div id="form_container" style="display:none;float:right;padding-top:6em">
		<div id="form">
			<?php echo $form->create('Event', array('onsubmit' => 'return false;')); ?>
			<fieldset>
				<div class="input">
					<dl><dt><label for="subject_name">Asignatura</label></dt><dd><input type="text" name="subject_name" id="subject_name" /></dd></dl>
				</div>
				<?php 
					echo $form->input('activity_id', array('label' => 'Actividad', 'options' => array("Seleccione una actividad"), 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); 
					echo $form->input('group_id', array('label' => 'Grupo', 'options' => array("Seleccione una actividad"), 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>'));
				?>
				<div class="input">
					<dl>
						<dt><label for="teacher_name">Profesor</label></dt>
						<dd><input type="text" name="teacher_name" id="teacher_name" /></dd>
					</dl> 
				</div>
				
				<div class="input">
					<dl>
						<dt><label for="teacher_2_name">2º Profesor</label></dt>
						<dd><input type="text" name="teacher_2_name" id="teacher_2_name" /></dd>
					</dl> 
				</div>
				
				<div class="input">
					<dl>
						<?php
							echo $form->input('teacher_id', array('type' => 'hidden'));
							echo $form->input('teacher_2_id', array('type' => 'hidden'));
						?>
						<label for="EventInitialHour" style="display:inline">Desde</label>
						<?php echo $form->hour('initial_hour', true, "07", array('timeFormat' => '24')); ?>
						:<select id="EventInitialHourMin" name="data[Event][initial_hour][minute]">
							<option value="00">00</option>
							<option value="30">30</option>
						</select>
						<label for="EventFinalHour" style="display:inline">Hasta</label>
						<?php echo $form->hour('final_hour', true, "07", array('timeFormat' => '24')); ?>
						:<select id="EventFinalHourMin" name="data[Event][final_hour][minute]">
							<option value="00">00</option>
							<option value="30">30</option>
						</select>
					</dl>
				</div>
				<div class="input">
					<dl>
						<select id="Frequency" name="Frecuency">
							<option value="">No repetir</option>
							<option value="1">Diariamente</option>
							<option value="7">Semanalmente</option>
						</select>
						<span id="finish_date" style="display:none">
							<label for="EventFinishedAt" style="display:inline"> hasta el</label>&nbsp;&nbsp;<input type="text" name="finished_at" id="EventFinishedAt" style="width:25%;"/>
						</span>
					</dl>
				</div>
				<input type="hidden" id="date" name="date" style="display:none">
				<?php echo $form->input('owner_id', array('type' => 'hidden', 'value' => $user_id)) ?>
			</fieldset>
			<?php echo $form->submit('Crear', array('onclick' => 'addEvent();'))?>
		</div>
	</div>
</div>
<script type="text/javascript">
	<?php echo $dateHelper->datepicker("#EventFinishedAt"); ?>
	$('#classrooms').change(function() {
		$('#calendar').fullCalendar('removeEvents');
		$.ajax({
			cache: false,
			type: "GET",
			url: "<?php echo PATH ?>/events/get/" + $('#classrooms').val(),
			dataType: "script"
		});
		
		$.ajax({
			cache: false,
			type: "GET",
			url: "<?php echo PATH ?>/bookings/get/" + $('#classrooms').val(),
			dataType: "script"
		});
	});
	
	$('#Frequency').change(function() {
		if ($('#Frequency').val() != "")
			$('#finish_date').show();
		else
			$('#finish_date').hide();
	});
	
	$('#EventActivityId').change(function() {
		$.ajax({
			cache: false,
			type: "GET",
			url: "<?php echo PATH ?>/groups/get/" + $('#EventActivityId').val(),
			success: function(data){
				$('#EventGroupId').html(data);
			}
		});
	});
	

	$(document).ready(function() {
		function formatItem(row){
			if (row[1] != null)
				return row[0];
			else
				return 'No existe ningún profesor con este nombre.';
		}
		
		$("#teacher_name").autocomplete("<?php echo PATH ?>/users/find_teachers_by_name", {formatItem: formatItem}).result(function(event, item){ $("input#EventTeacherId").val(item[1]); });
		
		$("#teacher_2_name").autocomplete("<?php echo PATH ?>/users/find_teachers_by_name", {formatItem: formatItem}).result(function(event, item){ $("input#EventTeacher2Id").val(item[1]); });
	
		$("#subject_name").autocomplete(subjects, {
			minChars: 0,
            matchContains: true,
			formatItem: function(row){
				return row.name;
			},
			formatMatch: function(row, i, max){
				return row.name;
			}
		}).result(function(event,item) {
			$.ajax({
				cache: false,
				type: "GET", 
				url: "<?php echo PATH ?>/activities/get/" + item.id,
				success: function(data){
					$('#EventActivityId').html(data);
				}
			})
		});
		
		$('#classrooms').val("");
		
		
	});
			
</script>
