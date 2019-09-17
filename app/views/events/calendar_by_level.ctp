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
		<?php if (isset($auth)) { ?>
		  <?php if (($auth->user('type') == "Administrador") || ($auth->user('type') == "Administrativo") || ($auth->user('type') == "Becario")) { ?>
		    eventClick: function(event, jsEvent, view) {
				if (event.className != 'booking') {
					if (confirm('¿Desea imprimir la hoja de asistencia de esta actividad?')) {
						window.open('<?php echo Environment::getBaseUrl() ?>/attendance_registers/print_attendance_file/' + event.id);
					}
				}
		    },
		<?php }} ?>
		eventRender: function(event, element) {
			element.hoverIntent({
				sensitivity: 1,
				interval: 100,
				over: function () {
					var id = event.id.match(/\d+/);
					var url;
					if (event.className == 'booking')
						url = "<?php echo Environment::getBaseUrl() ?>/bookings/view/";
					else
						url = "<?php echo Environment::getBaseUrl() ?>/events/view/";
					
					var eventDetails = $('#EventDetails');
					if (eventDetails.data('eventId') !== event.id) {
						var currentXhr = eventDetails.data('xhr');
						if (currentXhr) {
							currentXhr.abort();
						}
						eventDetails.empty().data('eventId', event.id);
						$('#tooltip').empty();
						var xhr = $.ajax({
							cache: false,
							type: "GET",
							url: url + id,
							asynchronous: false,
							success: function(data) {
								$('#tooltip').html(data).find('a, .actions').remove();
								eventDetails.html(data).find('a, .actions').remove();
							},
							complete: function() {
								eventDetails.data('xhr', null);
							}
						});
						eventDetails.data('xhr', xhr);
					}
				}
			});
		},
		eventMouseover: function(event, jsEvent, view) {
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
	<dt>Año académico</dt>
	<dd>
		<select id="academic_year" name="academic_year">
			<?php foreach ($academic_years as $academic_year): ?>
				<?php 
					if ($academic_year["id"] == $current_academic_year["id"])
						$selected = "selected";
					else
						$selected = "";
				?>
				<option value="<?php echo h($academic_year['id']) ?>" <?php echo $selected ?>><?php echo h($modelHelper->academic_year_name($academic_year)) ?></option>
			<?php endforeach; ?>
		</select>
	</dd>
</dl>
<dl>
	<dt>Titulación</dt>
	<dd id="courses">
		<?php foreach ($academic_years as $academic_year): ?>
			<select data-academic-year-id="<?php echo h($academic_year['id']) ?>" name="course" disabled style="display:none;">
				<?php if (isset($academic_year['Course'])): ?>
					<option value="">Seleccione una titulación</option>
					<?php foreach ($academic_year['Course'] as $course): ?>
						<option value="<?php echo h($course['id']) ?>"><?php echo h($course['Degree']['name']) ?></option>
					<?php endforeach; ?>
				<?php endif; ?>
				<option value="all" selected>Todas las titulaciones</option>
			</select>
		<?php endforeach; ?>
	</dd>
</dl>
<dl>
	<dt>Curso</dt>
	<dd>
		<select id="level" name="level">
			<option value="" selected>Seleccione un curso</option>
			<?php foreach (Configure::read('app.subject.levels') as $level => $levelName) : ?>
				<option value="<?php echo h($level) ?>"><?php echo h($levelName) ?></option>
			<?php endforeach; ?>
			<option value="all" selected>Todos los cursos</option>
		</select>
	</dd>
</dl>
<dl>
	<dt>Reservas de aulas</dt>
	<dd>
		<select id="booking" name="booking">
			<option value="0" selected>Ocultar</option>
			<option value="1">Mostrar</option>
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
            <?php foreach(Configure::read('app.activities.calendar_legend.legend_left') as $id => $description): ?>
                <li id="<?php echo $id ?>"><?php echo h($description) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div id="legend_right">
        <ul>
            <?php foreach(Configure::read('app.activities.calendar_legend.legend_right') as $id => $description): ?>
                <li id="<?php echo $id ?>"><?php echo h($description) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<script type="text/javascript">
	$(document).ready(function() {
		$('#booking').val(0);
		$('#courses select').val("");
		$('#level').val("");

		$('#level').change(function() {
			$('#calendar').fullCalendar('removeEvents');
			var booking = $('#booking');
			var academic_year = $('#academic_year');
			var course = $('#courses select:visible');
			var level = $('#level');
			if (course.val() && level.val()) {
				var url = "<?php echo Environment::getBaseUrl() ?>/events/get_by_level/" + encodeURIComponent(level.val()) + "/academic_year:" + encodeURIComponent(academic_year.val()) + "/course:" + encodeURIComponent(course.val()) + "/booking:" + encodeURIComponent(booking.val())
				;
				$.ajax({
					cache: false,
					type: "GET",
					url: url,
					dataType: "script"
				});
			}
		});

		$('#courses select, #booking').change(function() {
			$('#level').change();
		});

		$('#academic_year').change(function() {
			var value = $(this).val();
			$('#courses select')
				.prop('disabled', true).css('display', 'none')
				.filter(function () {
					return $(this).data('academic-year-id') == value;
				})
				.prop('disabled', false).css('display', 'block')
				.val('');
			$('#level').change();
		}).change();

	});
</script>
