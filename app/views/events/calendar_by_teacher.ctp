<div id="mobile-query" class="visible-block-phone-portrait"></div>

<script type="text/javascript">
function isMobile() {
    return $('#mobile-query').css('display') !== 'none';
}

var events;

function update_content() {
  $('#teacher_name').val("");
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
		
		});
		<?php if ($teacher): ?>
			$.ajax({
				type: "GET",
				url: "<?php echo Environment::getBaseUrl() ?>/events/get_by_teacher/" + <?php echo intval($teacher['Teacher']['id']); ?>,
				dataType: "script"
			});
		<?php endif; ?>
	});
</script>

<?php if ($teacher): ?>
	<h1>Agenda de <?php echo "{$teacher['Teacher']['first_name']} {$teacher['Teacher']['last_name']}"?></h1>
<?php else: ?>
	<h1>Agenda del profesorado</h1>

	<p>Escriba el nombre del profesor que desea consultar.</p>
	<br/>

	<dl>
		<dt>Profesor</dt>
		<dd><input type="text" id="teacher_name" name="TeacherName" onchange="$('#teacher_name').flushCache()"/></dd>
		<script type='text/javascript'>
			$('#teacher_name').autocomplete('<?php echo Environment::getBaseUrl() ?>/users/find_teachers_by_name/',
			{
				formatItem: function (row)
					{
						if (row[1] != null) 
							return row[0];
						else {
						return 'No existe ningún profesor con este nombre.';
					}
					}
			}).result(
				function(event, item){ 
					current_teacher = item[1];
					$('#calendar').fullCalendar('removeEvents');
				
					$.ajax({
						type: "GET",
						url: "<?php echo Environment::getBaseUrl() ?>/events/get_by_teacher/" + item[1],
						dataType: "script"
					})
				}
			);
		</script>
	</dl>
<?php endif; ?>

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
