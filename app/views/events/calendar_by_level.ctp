<div id="mobile-query" class="visible-block-phone-portrait"></div>

<script type="text/javascript">
function isMobile() {
    return $('#mobile-query').css('display') !== 'none';
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
		editable: false,
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
		buttonText: {today: 'hoy', month: 'mes', week: 'semana', day: 'día'},
                windowResize: function(view) {
                    if (isMobile()) {
                        if (view.name !== 'basicDay') {
                            $('#calendar').fullCalendar('changeView', 'basicDay');
                        }
                    } else if (view.name === 'basicDay') {
                        $('#calendar').fullCalendar('changeView', 'agendaWeek');
                    }
                },
		eventMouseover: function(event, jsEvent, view) {
			$.ajax({
				type: "GET", 
				url: "<?php echo PATH ?>/events/view/" + event.id,
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
		
		})
	});
</script>

<h1>Calendario de actividades por curso</h1>

<p>Seleccione un curso para ver su calendario.</p>
<br/>

<dl>
	<dt>Curso</dt>
	<dd>
		<select id="level" name="level">
			<option value="" selected>Seleccione un curso</option>
			<option value="Primero">Primero</option>
			<option value="Segundo">Segundo</option>
			<option value="Tercero">Tercero</option>
			<option value="Cuarto">Cuarto</option>
			<option value="Quinto">Quinto</option>
			<option value="Postgrado">Postgrado</option>
		</select>
	</dd>
</dl>

<div id="calendar_container">
	<div id="calendar" class="fc" style="margin: 3em 0pt; font-size: 13px;"></div>
</div>
<div id="EventDetails" style="display:none">
	
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

<script type="text/javascript">
	$('#level').change(function() {
		$('#calendar').fullCalendar('removeEvents');
		$.ajax({
			cache: false,
			type: "GET",
			url: "<?php echo PATH ?>/events/get_by_level/" + $('#level').val(),
			dataType: "script"
		});
	});
	
	$(document).ready(function() {

		$('#level').val("");

	});
</script>
