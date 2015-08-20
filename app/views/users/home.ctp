

<script type="text/javascript">
$(document).ready(function() {
	$('#calendar').fullCalendar({
		header: {
			right: 'prev,next today',
			center: 'title',
			left: 'month,agendaWeek'
		},
		defaultView: 'agendaWeek',
		defaultEventMinutes: 60,
		editable: false,
		minTime: 7,
		maxTime: 22,
		firstDay: 1,
		events: [ 
		<?php 
			$events_array = array();
			foreach($events as $event):
				$initial_date = date_create($event['Event']['initial_hour']);
				$final_date = date_create($event['Event']['final_hour']);
				
				array_push($events_array, "{id: '{$event['Event']['id']}', title: '{$event['Activity']['name']} ({$event['Subject']['acronym']})', start: '{$initial_date->format('Y-m-d H:i:s')}', end: '{$final_date->format('Y-m-d H:i:s')}', allDay:false, className: '{$activityHelper->getActivityClassName($event['Activity']['type'])}'}");
			endforeach;
			
			echo implode($events_array, ",");

		?>
		],
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
		<?php if (($auth->user('type') == "Profesor") || ($auth->user('type') == "Administrador")) { ?>
		eventClick: function(event, jsEvent, view) {
			if (confirm('¿Desea imprimir la hoja de asistencia de esta actividad?'))
				window.open('<?php echo PATH ?>/attendance_registers/print_attendance_file/' + event.id);
		},
		<?php } ?>
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
<div id="calendar_container">
	<div id="calendar" class="fc" style="margin: 1em 0pt; font-size: 13px;"></div>
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
<p id="calendar_ics">
        <strong>Url de exportación:</strong> <br />
        <?php echo $html->tag('input', null, array(
            'value' => $this->Html->url('/users/calendars/'.$user->getCalendarToken().'.ics', true),
            'onFocus' => 'window.setTimeout((function(){$(this).select();}).bind(this), 100);'
        )); ?>
</p>
