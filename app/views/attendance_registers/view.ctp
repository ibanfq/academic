<?php
  $isTeacherOfEvent = in_array($auth->user('id'), array($ar['Event']['teacher_id'], $ar['Event']['teacher_2_id']));
  $hasSecretCode = !empty($ar['AttendanceRegister']['secret_code']);
  $students_with_user_gone = array();
  foreach ($ar['Students'] as $student):
    if (!empty($student['UserAttendanceRegister']['user_gone'])):
      $students_with_user_gone[] = $student;
    endif;
  endforeach;
  $has_students_with_user_gone = count($students_with_user_gone);
          
  if ($auth->user('type') !== 'Profesor') {
    $html->addCrumb('Registros de impartición', '/attendance_registers'); 
  } else {
    $html->addCrumb('Registros de impartición', "/attendance_registers/view_my_registers/{$subject['Subject']['course_id']}"); 
  }
	$html->addCrumb('Ver registro de impartición', "/attendance_registers/view/{$ar['AttendanceRegister']['id']}"); 
?>

<h1>Registro de impartición</h1>

<div class="actions">
<?php if ($auth->user('type') === 'Profesor'): ?>
  <ul>
    <li><?php echo $html->link('Modificar registro', array('action' => 'edit_student_attendance', $ar['Event']['id']), array('id' => 'btn-edit', 'style' => $hasSecretCode? 'display:none' : '')) ?></li>
    <?php if ($hasSecretCode): ?>
      <li><?php echo $html->link('Finalizar registro', array('action' => 'finalize', $ar['AttendanceRegister']['id']), array('id' => 'btn-finalize', 'class' => $has_students_with_user_gone? '': 'disabled')) ?></li>
    <?php endif; ?>
  </ul>
<?php else: ?>
  <ul>
    <li><?php echo $html->link('Modificar registro', array('action' => 'edit', $ar['AttendanceRegister']['id'])) ?></li>
    <?php if ($hasSecretCode): ?>
      <li><?php echo $html->link('Finalizar registro', array('action' => 'finalize', $ar['AttendanceRegister']['id']), array('id' => 'btn-finalize', 'class' => $has_students_with_user_gone? '': 'disabled')) ?></li>
    <?php endif; ?>
    <li><?php echo $html->link('Eliminar registro', array('action' => 'delete', $ar['AttendanceRegister']['id'])) ?></li>
  </ul>
<?php endif; ?>
</div>
<div class="view">
	<fieldset>
	<legend>Datos de la actividad</legend>
		<dl>
			<dt>Asignatura</dt>
			<dd><?php echo $subject['Subject']['name'] ?></dd>
		</dl>
		
		<dl>
			<dt>Actividad</dt>
			<dd><?php echo $ar['Activity']['name'] ?></dd>
    </dl>
    
    <dl>
			<dt>Grupo</dt>
			<dd><?php echo $ar['Group']['name'] ?></dd>
		</dl>
		
		<dl>
			<dt>Fecha</dt>
			<dd><?php
			 	$initial_hour = date_create($ar['AttendanceRegister']['initial_hour']);
				$final_hour = date_create($ar['AttendanceRegister']['final_hour']);
				echo $initial_hour->format('d-m-Y'); 
			?></dd>
		</dl>
		
		<dl>
			<dt>Hora de inicio</dt>
			<dd><?php echo $initial_hour->format('H:i') ?></dd>
		</dl>
		
		<dl>
			<dt>Hora de fin</dt>
			<dd><?php echo $final_hour->format('H:i') ?></dd>
		</dl>
		
		<dl>
			<dt>Classroom</dt>
			<dd><?php echo "{$ar['Classroom']['name']}" ?></dd>
		</dl>
  
		<dl>
			<dt>Profesor</dt>
			<dd><?php echo "{$ar['Teacher']['first_name']} {$ar['Teacher']['last_name']}" ?></dd>
		</dl>
		
		<?php if (isset($ar['Teacher_2']['id'])) { ?>
		  <dl>
  			<dt>2º Profesor</dt>
  			<dd><?php echo "{$ar['Teacher_2']['first_name']} {$ar['Teacher_2']['last_name']}" ?></dd>
  		</dl>
		<?php }?>
		
    <?php if ($isTeacherOfEvent && $hasSecretCode): ?>
      <dl id="secretCodeWrapper">
        <dt>Código de acceso</dt>
        <dd><?php echo $ar['AttendanceRegister']['secret_code'] ?></dd>
      </dl>
      <dl id="durationWrapper" style="display:none;">
        <dt>Duración</dt>
        <dd id="duration"></dd>
      </dl>
    <?php else: ?>
      <dl>
        <dt>Duración</dt>
        <dd><?php echo $ar['AttendanceRegister']['duration'] ?></dd>
      </dl>
    <?php endif; ?>

    <dl>
			<dt>Asistentes</dt>
			<dd id="students-count"><?php echo count($students_with_user_gone) . ' / ' . count($ar['Students']) ?></dd>
		</dl>
	</fieldset>
	
	<fieldset>
	<legend>Estudiantes</legend>
		<table id="studentsTable">
			<thead>
				<tr>
					<th style="width:80%;">Estudiante</th>
          <?php if($isTeacherOfEvent && $hasSecretCode): ?>
            <th></th>
          <?php endif; ?>
				</tr>
			</thead>
      <?php if ($isTeacherOfEvent && $hasSecretCode): ?>
        <tfoot id="studentsFoot">
          <tr><td colspan=2 ><a id="btn-add" class="<?php echo $has_students_with_user_gone? '' : 'disabled' ?>" href="#" onclick="addRow(); return false;" title="Haga click para añadir un estudiante">Añadir estudiante</a></td></tr>
        </tfoot>
      <?php endif; ?>
			<tbody id="students">
        <?php $i = 0 ?>
				<?php foreach ($students_with_user_gone as $student):?>
          <tr id="row_<?php echo $i?>" data-id="<?php echo $student['Student']['id'] ?>">
            <td><?php echo "{$student['Student']['first_name']} {$student['Student']['last_name']}" ?></td>
            <?php if ($isTeacherOfEvent && $hasSecretCode): ?>
              <td><a onclick="deleteRow(<?php echo $i ?>); return false;" href="#">Eliminar</a></td>
            <?php endif; ?>
          </tr>
          <?php $i++; ?>
				<?php endforeach; ?>
			</tbody>
		</table>
	</fielset>
</div>

<?php if ($isTeacherOfEvent && $hasSecretCode): ?>
<script type="text/javascript">
  function refresh() {
    $.ajax({
      type: "GET",
      url: "<?php echo PATH ?>/api/attendance_registers/<?php echo $ar['AttendanceRegister']['id'] ?>",
      dataType: "json",
      success: function (response) {
        var index = parseInt(($('#students > tr:last').attr('id') || '_0').split('_')[1]) + 1;
        var total = response.data.Students.length;
        $('#students tr[data-id]').addClass('refreshing');
        var registered = response.data.Students.reduce(function (sum, student) {
          if (student.UserAttendanceRegister.user_gone == true) {
            if (!$('#students tr[data-id=' + student.Student.id + ']').removeClass('refreshing').length) {
              var row = $('<tr id="row_' + index + '" data-id="' + student.Student.id + '"><td></td><td></td></tr>');
              row.find('td:first').html(student.Student.first_name + ' ' + student.Student.last_name);
              row.find('td:last').html('<a onclick="deleteRow(' + index + '); return false;" href="#">Eliminar</a>');
              $('#students').append(row);
            }
            return sum + 1;
          }
          return sum;
        }, 0);
        $('#students tr.refreshing').remove();
        $('#students-count').text(registered + ' / ' + total);
        if (!$('#students tr[data-id]').length) {
          $('#students tr').remove();
          $('#btn-finalize, #btn-add').addClass('disabled');
        } else {
          $('#btn-finalize, #btn-add').removeClass('disabled');
        }
        if (!response.data.AttendanceRegister.secret_code) {
          $('#duration').text(response.data.AttendanceRegister.duration);
          $('#btn-finalize').remove();
          $('#studentsFoot').remove();
          $('#studentsTable thead th:last').remove();
          $('#students > tr td:last-child').remove();
          $('#secretCodeWrapper').remove();
          $('#durationWrapper').show();
          $('#btn-edit').show();
        } else {
          setTimeout(refresh, 5000);
        }
      } 
    });
  }
  setTimeout(refresh, 5000);
  
  function addRow(){
    var index = parseInt(($('#students > tr:last').attr('id') || '_0').split('_')[1]) + 1;
    $('#students').append("<tr id='row_" + index + "'><td><input type='text' id='new_student_" + index + "' class='student_autocomplete' /></td><td></td><script type='text\/javascript'>$('#new_student_" + index + "').autocomplete('<?php echo PATH ?>\/users\/find_students_by_name', {formatItem: 	function (row){if (row[1] != null) return row[0];else return 'No existe ningún estudiante con este nombre.'; }}).result(function(event, item){ addStudent(" + index + ", item[1]); });<\/script></tr>");
    $('#row_'+index+' input').focus();
  }
  
  function addStudent(index, id) {
    if (id && !$('#students tr[data-id='+id+']').length) {
      var row = $('#row_'+index);
      row.attr('data-id', id);
      row.find('input').addClass('disabled').attr('disabled', 'disabled');
      row.find('td:last').html('Añadiendo...');
      $.ajax({
        type: "POST", 
        url: "<?php echo PATH ?>/api/users_attendance_register/",
        data: {'User[id]': id, 'AttendanceRegister[id]': <?php echo $ar['AttendanceRegister']['id'] ?>},
        dataType: "json",
        success: function (response) {
          var total = response.data.Students.length;
          var registered = response.data.Students.reduce(function (sum, student) {
            return student.UserAttendanceRegister.user_gone == true? sum + 1 : sum;
          }, 0);
          $('#students-count').text(registered + ' / ' + total);
          row.find('td:first').html(response.data.Student.first_name + ' ' + response.data.Student.last_name);
          row.find('td:last').html('<a onclick="deleteRow(' + index + '); return false;" href="#">Eliminar</a>');
        },
        error: function (xhr) {
          var response = xhr.response? JSON.parse(xhr.response) : {}
          if (response.status === 'error') {
            alert(response.message);
          } else {
            alert('Un error inesperado a impedido añadir a ' + row.find('input').val());
          }
          row.remove();
        }
      });
    } else {
      deleteRow(index);
    }
  }
  
  function deleteRow(index) {
    var row = $('#row_'+index); 
    if (row) {
      var id = row.attr('data-id');
      if (id) {
        link = row.find('td:last a').addClass('disabled').text('Eliminando...');
        $.ajax({
          type: "DELETE", 
          url: "<?php echo PATH ?>/api/users/" + id + "/attendance_registers/<?php echo $ar['AttendanceRegister']['id'] ?>",
          dataType: 'json',
          success: function (response) {
            var total = response.data.Students.length;
            var registered = response.data.Students.reduce(function (sum, student) {
              return student.UserAttendanceRegister.user_gone == true? sum + 1 : sum;
            }, 0);
            $('#students-count').text(registered + ' / ' + total);
            row.remove();
            if (!$('#students tr[data-id]').length) {
              $('#students tr').remove();
              $('#btn-finalize, #btn-add').addClass('disabled');
            }
          },
          error: function (xhr) {
            var response = xhr.response? JSON.parse(xhr.response) : {}
            if (response.status === 'error') {
              alert(response.message);
            } else {
              alert('Un error inesperado a impedido eliminar a ' + row.find('td:first').text());
            }
            link.removeClass('disabled').text('Eliminar');
          }
        });
      } else {
        row.remove();
      }
    }
	}
</script>
<?php endif; ?>
