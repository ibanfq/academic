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
				  window.open('<?php echo PATH ?>/attendance_registers/print_attendance_file/' + event.id);
		    },
		<?php }} ?>
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

<h1>Calendario de actividades por asignatura</h1>

<p>Seleccione el curso académico y escriba el nombre de la asignatura que desea consultar.</p>
<br/>

<dl>
  <dt>Curso</dt>
  <dd>
    <select id="course_id" name="course_id" onchange="update_content()">
      <?php foreach($courses as $course):?>
        <?php 
          if ($course["Course"]["id"] == $current_course["id"])
            $selected = "selected";
          else
            $selected = "";
        ?>
        <option value="<?php echo $course["Course"]["id"]?>" <?php echo $selected ?> ><?php echo $course["Course"]["name"]?></option>
      <?php endforeach; ?>
    </select>
  </dd>
</dl>

<dl>
	<dt>Asignatura</dt>
	<dd><input type="text" id="subject_name" name="SubjectName" onchange="$('#subject_name').flushCache()"/></dd>
	<script type='text/javascript'>
		$('#subject_name').autocomplete('<?php echo PATH ?>/subjects/find_subjects_by_name/',
		 {
		  extraParams: {
		    course_id: function() { return $('#course_id').val(); }
		  },
		  
			formatItem: function (row)
				{
					if (row[1] != null) 
						return row[0];
					else {
					  return 'No existe ninguna asignatura con este nombre.';
				  }
				}
		}).result(
			function(event, item){ 
			  current_subject = item[1];
			  
				$.ajax({
					type: "GET",
					url: "<?php echo PATH ?>/events/get_by_subject/" + item[1],
					dataType: "script"
				})
			}
		);
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

