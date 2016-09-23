<?php $html->addCrumb('Cursos', '/courses'); ?>
<?php $html->addCrumb($course['Course']['name'], "/courses/view/{$course['Course']['id']}"); ?>
<?php $html->addCrumb('Crear registro de impartición masivo', '/massive_attendance_registers/add'); ?>

<h1>Crear registro de impartición masivo</h1>
	<?php echo $form->create('MassiveAttendanceRegister'); ?>
  <fieldset>
    <dl>
      <dt>Aula</dt>
      <dd><?php echo $form->select('classroom', $classrooms); ?>
    </dl>
    
    <dl>
      <dt>Fecha</dt>
      <dd><input type="text" id="date" name="date" value="<?php if (isset($date)) echo $date->format("d-m-Y"); ?>" style="width:10em"/></dd>
    </dl>
    
    <?php 
      if (isset($date))
        echo $html->link('Realizar otra búsqueda', array('action' => 'add', 'controller' => 'massive_attendance_registers', $course['Course']['id']));
    ?>
    
    <input type="hidden" id="course_id" name="course_id" value="<?php echo $course['Course']['id'] ?>"/>
  </fieldset>
  
  <?php if ((isset($date)) && (isset($classroom))) { ?>  
    <fieldset>
      <legend>Registros de impartición</legend>
      <br />
    	<table>
    	  <thead>
    	    <tr>
    	      <th width="15%">Fecha</th>
    	      <th width="10%">Hora de inicio</th>
    	      <th width="5%">Duración</th>
    	      <th width="15%">Asignatura</th>
    	      <th width="10%">Actividad</th>
    	      <th width="5%">Grupo</th>
    	      <th width="20%">Profesor</th>
    	      <th width="20%">2º Profesor</th>
    	    </tr>
    	  </thead>
    	  <tbody>
    	    <?php foreach($registers as $register):?>
    	      <?php $initial_date = date_create($register['AttendanceRegister']['initial_hour']); ?>
    	      <tr>
    	        <td>
    	          <input id="registers_<?php echo $register['AttendanceRegister']['id']?>_initial_date" name="registers[<?php echo $register['AttendanceRegister']['id']?>][initial_date]" value="<?php echo $initial_date->format("d-m-Y") ?>" class="initial_date"/>
    	          <input type="hidden" id="registers_<?php echo $register['AttendanceRegister']['id']?>_event_id" name="registers[<?php echo $register['AttendanceRegister']['id']?>][event_id]" value="<?php echo $register['AttendanceRegister']['event_id'] ?>"/>
    	        </td>
    	        <td><input id="registers_<?php echo $register['AttendanceRegister']['id']?>_initial_hour" name="registers[<?php echo $register['AttendanceRegister']['id']?>][initial_hour]" value="<?php echo $initial_date->format("H:i") ?>" class="initial_hour"/></td>
    	        <td>
								<?php
									$duration = $register['Event']['duration'];
									if ($register['AttendanceRegister']['duration'] != $register['Event']['duration'] && $register['AttendanceRegister']['duration'] > 0) {
										$duration = $register['AttendanceRegister']['duration'];
									}
								?>
								<input id="register_<?php echo $register['AttendanceRegister']['id']?>_final_hour" value="<?php echo $duration ?>" name="registers[<?php echo $register['AttendanceRegister']['id']?>][duration]" class="duration"/>
							</td>
    	        <td><?php echo $register['Subject']['name'] ?></td>
    	        <td>
    	          <?php echo $register['Activity']['name'] ?>
    	          <input type="hidden" name="registers[<?php echo $register['AttendanceRegister']['id']?>][activity_id]" value="<?php echo $register['Activity']['id'] ?>" />
    	        </td>
    	        <td>
    	          <?php echo $register['Group']['name'] ?>
    	          <input type="hidden" name="registers[<?php echo $register['AttendanceRegister']['id']?>][group_id]" value="<?php echo $register['Group']['id'] ?>" />
    	        </td>
    	        <td>
    	          <input id="register_<?php echo $register['AttendanceRegister']['id']?>_teacher_name" value="<?php echo "{$register['User']['first_name']} {$register['User']['last_name']}" ?>" name="registers[<?php echo $register['AttendanceRegister']['id']?>][teacher_name]" class="teacher_name"/>
    	          <input type="hidden" id="register_<?php echo $register['AttendanceRegister']['id']?>_teacher_id" value="<?php echo $register['User']['id'] ?>" name="registers[<?php echo $register['AttendanceRegister']['id']?>][teacher_id]" class="teacher_id"/>
    	        </td>
    	        <td>
    	          <?php
    	            if ($register['AttendanceRegister']['teacher_2_id'] == null)
    	              $teacher2 = "";
    	            else
    	              $teacher2 = "{$register['User2']['first_name']} {$register['User2']['last_name']}";
    	          ?>
    	          <input id="register_<?php echo $register['AttendanceRegister']['id']?>_teacher_name_2" value="<?php echo $teacher2 ?>" name="registers[<?php echo $register['AttendanceRegister']['id']?>][teacher_name_2]" class="teacher_name_2"/>
    	          <input type="hidden" id="register_<?php echo $register['AttendanceRegister']['id']?>_teacher_2_id" value="<?php echo $register['User2']['id'] ?>" name="registers[<?php echo $register['AttendanceRegister']['id']?>][teacher_2_id]" class="teacher_id"/>

    	          <script type ="text/javascript">
									$(document).ready(function() {
										function formatItem(row){
                			if (row[1] != null)
                				return row[0];
                			else
                				return 'No existe ningún profesor con este nombre.';
                		}

										$("input#register_<?php echo $register['AttendanceRegister']['id'] ?>_teacher_name_2").change(function(){
											if ($(this).val().length == 0) {
												$("input#register_<?php echo $register['AttendanceRegister']['id'] ?>_teacher_2_id").val('');
											}
										});


										$("input#register_<?php echo $register['AttendanceRegister']['id'] ?>_teacher_name").autocomplete("<?php echo PATH ?>/users/find_teachers_by_name", {	formatItem: formatItem }).result(function(event, item){
											$("input#register_<?php echo $register['AttendanceRegister']['id'] ?>_teacher_id").val(item[1]);
										});

										$("input#register_<?php echo $register['AttendanceRegister']['id'] ?>_teacher_name_2").autocomplete("<?php echo PATH ?>/users/find_teachers_by_name", { formatItem: formatItem }).result(function(event, item){
											$("input#register_<?php echo $register['AttendanceRegister']['id'] ?>_teacher_2_id").val(item[1]);
										});
                	});
                </script>
    	        </td>
    	      </tr>
    	    <?php endforeach; ?>
    	  </tbody>
    	</table>
    </fieldset>
  <?php } ?>
<?php
  if (isset($date))
	  echo $form->end('Crear');
	else
	  echo $form->end('Buscar eventos');
?>
<script type="text/javascript">
  $(document).ready(function() {
    
    $('#date').datepicker({
      monthNames: ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'], 
      dateFormat: 'dd-mm-yy',
      dayNames: ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'], dayNamesMin: ['D', 'L', 'M', 'X', 'J', 'V', 'S'],
      dayNamesShort: ['Dom', 'Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab'],
      firstDay: 1,
      yearRange: '-10:+10',
      showOn: 'button',
      buttonImage: '/veterinaria/v2/img/ico/calendar.png',
      buttonImageOnly: true,
      buttonText: '%s' });
    
    function set_error(input){
      $(input).css("background-color", "#e32");
      $(input).addClass("error");
    }
    
    $('form').bind('submit', function (event){
      $(".error").removeClass("error");
      
      $.each($('input.initial_date'), function(index, value){
        var date = $(value).val();

        if (date.match(/^\d{1,2}-\d{1,2}-\d{4}$/) == null)
          set_error(value);
      });
      
      $.each($('input.initial_hour'), function(index, value){
        var hour = $(value).val();

        if (hour.match(/^\d{1,2}:\d{2}$/) == null)
          set_error(value);
      });
      
      $.each($('input.duration'), function(index, value){
        var duration = $(value).val();

        if (duration.match(/^\d+(\.\d{2}){0,1}$/) == null)
          set_error(value);
      });
      
      $.each($('input.teacher_name'), function(index, value){
        var tn = $(value).val();

        if (tn == "")
          set_error(value);
      });
      
      if ($(".error").size() > 0){
        alert("Se han encontrado errores mientras se procesaba el formulario.");
        event.preventDefault();
      }
    });
  });
</script>
