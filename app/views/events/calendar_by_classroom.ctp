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
          return $('#EventDetails').html();
        },
        showURL: false
      });

    },
		
		})
	});
</script>

<h1>Calendario de actividades por aula</h1>

<p>Seleccione un aula del desplegable para visualizar las actividades en dicha aula.</p>
<br/>

<dl>
	<dt>Aulas</dt>
	<dd><?php echo $form->select('classrooms', $classrooms); ?></dd>
</dl>

<div id="calendar_container">
	<div id="calendar" class="fc" style="margin: 3em 0pt; font-size: 13px;"></div>
</div>
<div id="EventDetails" style="display:none">
	
</div>
<div id="legend" style="">
    <div id="legend_left">
        <ul>
            <?php foreach(Configure::read('app.color_legend.legend_left') as $id => $description): ?>
                <li id="<?php echo $id ?>"><?php echo h($description) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div id="legend_right">
        <ul>
            <?php foreach(Configure::read('app.color_legend.legend_right') as $id => $description): ?>
                <li id="<?php echo $id ?>"><?php echo h($description) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<script type="text/javascript">
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
</script>
