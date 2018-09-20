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
                        $('#calendar').fullCalendar('render'); // Fix problem with columns width
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

<?php if (!empty(Configure::read('app.degrees'))): ?>
	<dl>
		<dt>Titulación</dt>
		<dd>
			<select id="degree" name="degree">
				<option value="" selected>Seleccione una titulación</option>
				<?php foreach (Configure::read('app.degrees') as $degree => $degreeName) : ?>
					<option value="<?php echo h($degree) ?>"><?php echo h($degreeName) ?></option>
				<?php endforeach; ?>
			</select>
		</dd>
	</dl>
<?php endif; ?>
<dl>
	<dt>Curso</dt>
	<dd>
		<select id="level" name="level">
			<option value="" selected>Seleccione un curso</option>
			<?php foreach (Configure::read('app.subject.levels') as $level => $levelName) : ?>
				<option value="<?php echo h($level) ?>"><?php echo h($levelName) ?></option>
			<?php endforeach; ?>
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
	var degreeLevels = <?php echo json_encode(Configure::read('app.subject.degree_levels')) ?>;
	$('#degree').change(function() {
		$('#calendar').fullCalendar('removeEvents');
		var degree = $(this).val();
		var level = $('#level').val('').attr('disabled', 'disabled');
		var options = level.find('option').attr('disabled', 'disabled');
		if (degree in degreeLevels) {
			for (var i in degreeLevels[degree]) {
				options.filter(function () {
					return this.value === degreeLevels[degree][i];
				}).removeAttr('disabled');
			}
			level.removeAttr('disabled');
		}
	}).change();
	$('#level').change(function() {
		$('#calendar').fullCalendar('removeEvents');
		var degree = $('#degree');
		var level = $('#level');
		var url = degree.length
			? "<?php echo PATH ?>/events/get_by_degree_and_level/" + encodeURIComponent(degree.val()) + "/" + encodeURIComponent(level.val())
			: "<?php echo PATH ?>/events/get_by_level/" + encodeURIComponent(level.val())
		;
		$.ajax({
			cache: false,
			type: "GET",
			url: url,
			dataType: "script"
		});
	});
	
	$(document).ready(function() {

		$('#degree').val("");
		$('#level').val("");

	});
</script>
