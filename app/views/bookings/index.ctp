<?php $html->addCrumb('Reservas', '/bookings'); ?>

<div id="mobile-query" class="visible-block-phone-portrait"></div>

<script type="text/javascript">
function isMobile() {
    return $('#mobile-query').css('display') !== 'none';
}

	var currentEvent = null;

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

	function addBooking() {
		var initial_hour = new Date($("#date").val());
		var final_hour = new Date($("#date").val());
		var new_event;

		initial_hour.setHours($('#BookingInitialHourHour').val());
		initial_hour.setMinutes($('#BookingInitialHourMin').val());
		final_hour.setHours($('#BookingFinalHourHour').val());
		final_hour.setMinutes($('#BookingFinalHourMin').val());

    var initial_date = toEventDateString(initial_hour);
    var final_date = toEventDateString(final_hour);

		$.ajax({
			cache: false,
			type: "POST",
			data: {'data[Booking][reason]': $('#BookingReason').val(), 'data[Booking][required_equipment]': $('#BookingRequiredEquipment').val(), 'data[Booking][initial_hour]': initial_date, 'data[Booking][final_hour]': final_date, 'data[Booking][user_type]': $('#BookingUserType').val(), 'data[Booking][classroom_id]': $('#AllClassrooms').attr('checked')? -1 : $('#classrooms').val()},
			url: "<?php echo PATH ?>/bookings/add/" + $('#BookingFinishedAt').val() + "/" + $('#Frequency').val(),
			asynchronous: false,
			dataType: 'script',
			success: function(data){
				$('#form').dialog('close');
			}
		});
	}

	function deleteBooking(id, parent_id){
		if (parent_id == '')
			confirmated = confirm("¿Está seguro de que desea eliminar esta reserva? Al eliminarla se eliminarán también todos las reservas de la misma serie.");
		else
			confirmated = confirm("¿Está seguro de que desea eliminar esta reserva?");
		if (confirmated){
			$.ajax({
				cache: false,
				type: "POST",
				url: "<?php echo PATH?>/bookings/delete/" + id,
				dataType: 'script'
			});
		}
	}

	function reset_form(){
		$('#BookingReason').val("")
		$('#BookingRequiredEquipment').val("");
    $('#BookingUserType').val("");
		$('#finished_at').val("");
		$('#finish_date').hide();
	}

	$(document).ready(function() {
		$('#calendar').fullCalendar({
			header: {
				right: 'prev,next today',
				center: 'title',
				left: 'title,month,agendaWeek'
			},
			defaultView: isMobile()? 'basicDay' : 'agendaWeek',
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
				if (event.className != 'booking') {
					$('#notice').removeClass('success');
					$('#notice').addClass('error');
					$('#notice').html("No se pueden modificar las actividades académicas desde las reservas de aula. Vaya a programar curso si desea modificar una actividad académica.");
					revertFunc();
				}
				else {
					id = event.id.match(/\d+/);
					$.ajax({
						cache: false,
						type: "GET",
						url: "<?php echo PATH ?>/bookings/update/" + id + "/" + dayDelta + "/" + minuteDelta + "/1",
						success: function(data){
							if (data == "notAllowed") {
                revertFunc();
                $('#notice').removeClass('success');
                $('#notice').addClass('error');
                $('#notice').html("Usted no tiene permisos para modificar esta reserva. Solo su dueño, un conserje, un administrativo o un administrador pueden hacerlo.");
              } else if (data && data != "true") {
                revertFunc();
                $('#notice').removeClass('success');
                $('#notice').addClass('error');
                $('#notice').html(data != "false"? data : "No ha sido posible actualizar la reserva porque coincide con una actividad académica u otra reserva.");
              } else {
                $('#notice').removeClass('error');
                $('#notice').addClass('success');
                $('#notice').html("La reserva se ha actualizado correctamente.");
							}
						}
					});
				}
			},
			buttonText: {today: 'hoy', month: 'mes', week: 'semana', day: 'día'},
      windowResize: function(view) {
          if (isMobile()) {
              if (view.name !== 'basicDay') {
                  $('#calendar').fullCalendar('changeView', 'basicDay');
              }
          } else if (view.name === 'basicDay') {
              $('#calendar').fullCalendar('changeView', 'agendaWeek');
              $('#calendar').fullCalendar('render'); // Fix problem with columns width
          }
      },
			eventClick: function(event, jsEvent, view) {
				var id = event.id.match(/\d+/);
				var url;
				if (event.className == 'booking')
					url = "<?php echo PATH ?>/bookings/view/";
				else
					url = "<?php echo PATH ?>/events/view/";
				$.ajax({
					cache: false,
					type: "GET",
					url: url + id,
					success: function(data) {
						if (data == "false")
							alert("Usted no tiene permisos para editar esta reserva");
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
			eventDrop: function(event, dayDelta, minuteDelta, allDay, revertFunc, jsEvent, ui, view ) {
				if (event.className != 'booking') {
					$('#notice').removeClass('success');
					$('#notice').addClass('error');
					$('#notice').html("No se pueden modificar las actividades académicas desde las reservas de aula. Vaya a programar curso si desea modificar una actividad académica.");
					revertFunc();
				}
				else {
					id = event.id.match(/\d+/);
					$.ajax({
						cache: false,
						type: "GET",
						url: "<?php echo PATH ?>/bookings/update/" + id + "/" + dayDelta + "/" + minuteDelta,
						success: function(data){
              if (data == "notAllowed") {
                revertFunc();
                $('#notice').removeClass('success');
                $('#notice').addClass('error');
                $('#notice').html("Usted no tiene permisos para modificar esta reserva. Solo su dueño, un conserje, un administrativo o un administrador pueden hacerlo.");
              } else if (data && data != "true") {
                revertFunc();
                $('#notice').removeClass('success');
                $('#notice').addClass('error');
                $('#notice').html(data != "false"? data : "No ha sido posible actualizar la reserva porque coincide con una actividad académica u otra reserva.");
              } else {
                $('#notice').removeClass('error');
                $('#notice').addClass('success');
                $('#notice').html("La reserva se ha actualizado correctamente.");
							}
						}
					});
				}
			},
			eventMouseover: function(event, jsEvent, view) {
				var id = event.id.match(/\d+/);
        var url;
				if (event.className == 'booking')
					url = "<?php echo PATH ?>/bookings/view/";
				else
					url = "<?php echo PATH ?>/events/view/";

				$.ajax({
					cache: false,
					type: "GET",
					url: url + id,
					asynchronous: false,
					success: function(data) {
						$('#tooltip').html(data).find('a, .actions').remove();
						$('#BookingDetails').html(data).find('a, .actions').remove();
					}
				});

				$(this).tooltip({
					delay: 500,
					bodyHandler: function() {
						return $('#BookingDetails').html();
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
          <?php if ($auth->user('type') == "Administrador"): ?>
            $('#AllClassrooms').attr('checked', 'checked').attr('disabled', 'disabled');
            $('#AllClassroomsDefault').val('1');
          <?php else: ?>
            alert("Debe seleccionar un aula antes de comenzar a programar actividades");
            $('#calendar').fullCalendar('unselect');
            return;
          <?php endif; ?>
				} else {
          <?php if ($auth->user('type') == "Administrador"): ?>
            $('#AllClassrooms').removeAttr('disabled');
            $('#AllClassroomsDefault').val('0');
          <?php endif; ?>  
        }
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
        $('#date').val(initial_date);
        $('#BookingInitialHourHour').val(initial_hour);
        $('#BookingInitialHourMin').val(initial_minute);
        $('#BookingFinalHourHour').val(final_hour);
        $('#BookingFinalHourMin').val(final_minute);
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

	<div id="BookingDetails" style="display:none">

	</div>

	<div id="form_container" style="display:none;float:right;padding-top:6em">
		<div id="form">
			<?php echo $form->create('Booking', array('onsubmit' => 'return false;')); ?>
			<fieldset>
				<?php
					echo $form->input('reason', array('label' => 'Motivo', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>'));
					echo $form->input('required_equipment', array('type' => 'text_area', 'label' => 'Información', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>'));
				?>
				<div class="input">
					<dl>
						<label for="BookingInitialHour" style="display:inline">Desde</label>
						<?php echo $form->hour('initial_hour', true, "07", array('timeFormat' => '24')); ?>
						:
            <select id="BookingInitialHourMin" name="data[Booking][initial_hour][minute]">
							<option value="00">00</option>
							<option value="30">30</option>
						</select>
						<label for="BookingFinalHour" style="display:inline">Hasta</label>
						<?php echo $form->hour('final_hour', true, "07", array('timeFormat' => '24')); ?>
						:
            <select id="BookingFinalHourMin" name="data[Booking][final_hour][minute]">
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
							<label for="BookingFinishedAt" style="display:inline"> hasta el</label>&nbsp;&nbsp;<input type="text" name="finished_at" id="BookingFinishedAt" style="width:25%;"/>
						</span>
					</dl>
				</div>
        <div class="input">
					<dl>
						<select id="BookingUserType" name="data[Booking][user_type]">
							<option value="">No asignar a nadie</option>
							<option value="Todos">Todos los usuarios</option>
							<option value="No-estudiante">Todos menos los estudiantes</option>
              <?php foreach (array("Administrador", "Administrativo", "Conserje", "Profesor", "Estudiante", "Becario") as $userType): ?>
                <option value="<?php echo $userType ?>"><?php echo $userType ?></option>
              <?php endforeach; ?>
						</select>
						<span id="finish_date" style="display:none">
							<label for="BookingFinishedAt" style="display:inline"> hasta el</label>&nbsp;&nbsp;<input type="text" name="finished_at" id="BookingFinishedAt" style="width:25%;"/>
						</span>
					</dl>
				</div>
        <?php if ($auth->user('type') == "Administrador"): ?>
          <div class="input checkbox">
            <input type="checkbox" id="AllClassrooms" name="AllClassrooms" value="1">
            <label for="AllClassrooms">Añadir para todas las aulas</label>
            <input type="hidden" id="AllClassroomsDefault" name="AllClassrooms" value="0">
          </div>
        <?php endif; ?>
        <input type="hidden" id="date" name="date" style="display:none">
			</fieldset>
			<?php echo $form->submit('Crear', array('onclick' => 'addBooking();'))?>
		</div>
	</div>
</div>
<script type="text/javascript">
	<?php echo $dateHelper->datepicker("#BookingFinishedAt"); ?>
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


	$(document).ready(function() {
		$('#classrooms').val("");
	});

</script>
