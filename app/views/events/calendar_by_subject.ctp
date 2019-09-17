<div id="mobile-query" class="visible-block-phone-portrait"></div>

<script type="text/javascript">
function isMobile() {
    return $('#mobile-query').css('display') !== 'none';
}

var events;

function update_content() {
  $('#subject_name').val("");
  $('#calendar').fullCalendar('removeEvents');
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
			    if (confirm('¿Desea imprimir la hoja de asistencia de esta actividad?'))
				  window.open('<?php echo Environment::getBaseUrl() ?>/attendance_registers/print_attendance_file/' + event.id);
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

<h1>Calendario de actividades por asignatura</h1>

<p>Seleccione el curso académico y escriba el nombre de la asignatura que desea consultar.</p>
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
					<option value="" selected>Todas las titulaciones</option>
					<?php foreach ($academic_year['Course'] as $course): ?>
						<option value="<?php echo h($course['id']) ?>"><?php echo h($course['Degree']['name']) ?></option>
					<?php endforeach; ?>
				<?php endif; ?>
			</select>
		<?php endforeach; ?>
	</dd>
</dl>

<dl>
	<dt>Asignatura</dt>
	<dd><input type="text" id="subject_name" name="SubjectName" onchange="$('#subject_name').flushCache()"/></dd>
	<script type='text/javascript'>
		$(function() {
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


			$('#subject_name')
				.autocomplete('<?php echo Environment::getBaseUrl() ?>/subjects/find_subjects_by_name/', {
					extraParams: {
						academic_year_id: function () {
							return $('#academic_year').val();
						},
						course_id: function() {
							var course = $('#courses select:visible');
							return course.val();
						}
					},
					formatItem: function (row)
						{
							if (row[1] != null) {
								return row[0];
							}
							return 'No existe ninguna asignatura con este nombre.';
						}
				}).result(
					function(event, item){ 
						current_subject = item[1];
						$('#calendar').fullCalendar('removeEvents');
					
						$.ajax({
							type: "GET",
							url: "<?php echo Environment::getBaseUrl() ?>/events/get_by_subject/" + item[1],
							dataType: "script"
						})
					}
				);
		});
	</script>
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
