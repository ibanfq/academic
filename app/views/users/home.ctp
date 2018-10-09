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
		events: [ 
		<?php 
      $events_array = array();
      foreach($events as $event):
        $initial_date = date_create($event['Event']['initial_hour']);
        $final_date = date_create($event['Event']['final_hour']);

        array_push($events_array, "{id: '{$event['Event']['id']}', title: '{$event['Activity']['name']} ({$event['Subject']['acronym']})', start: '{$initial_date->format('Y-m-d H:i:s')}', end: '{$final_date->format('Y-m-d H:i:s')}', allDay:false, className: '{$activityHelper->getActivityClassName($event['Activity']['type'])}'}");
      endforeach;
      foreach($bookings as $booking):
        $initial_date = date_create($booking['Booking']['initial_hour']);
        $final_date = date_create($booking['Booking']['final_hour']);

        array_push($events_array, "{id: 'booking_{$booking['Booking']['id']}', start: '{$initial_date->format('Y-m-d H:i:s')}', end: '{$final_date->format('Y-m-d H:i:s')}', title: '{$booking['Booking']['reason']}', allDay: false, className: 'booking'}");
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
              position:'top'
            });
          }
        }
      });
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
          $('#EventDetails').html(data).find('a, .actions').remove();
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
<div id="edit_form">
		
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
<p id="calendar_ics">
  <strong>Url de exportación:</strong> <br />
  <?php echo $html->tag('input', null, array(
      'value' => $this->Html->url('/users/calendars/'.$user->getCalendarToken().'.ics', true),
      'onFocus' => 'window.setTimeout((function(){$(this).select();}).bind(this), 100);'
  )); ?>
</p>
