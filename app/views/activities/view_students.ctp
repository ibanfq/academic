<?php 
	$html->addCrumb('Cursos', '/courses'); 
	$html->addCrumb($subject['Course']['name'], "/courses/view/{$subject['Course']['id']}"); 
	$html->addCrumb($subject['Subject']['name'], "/subjects/view/{$subject['Subject']['id']}"); 
	$html->addCrumb($activity['Activity']['name'], "/activities/view/{$activity['Activity']['id']}");
	$html->addCrumb("Examinar estudiantes {$group['Group']['name']}", "/activities/view_students/{$activity['Activity']['id']}/{{$group['Group']['id']}}");
?>

<h1><?php echo $activity['Activity']['name'] ?> - Grupo <?php echo $group['Group']['name'] ?></h1>

<?php if ($auth->user('type') != "Estudiante") : ?>
  <div class="actions">
    <script type="text/javascript">
      function delete_student(activity_id, group_id, student_id){
        if (confirm('¿Está seguro de que desea eliminar al estudiante de este grupo? Después no podrá deshacer el cambio.')) {
          $.ajax({
            type: "GET", 
            url: "<?php echo PATH ?>/activities/delete_student/" + activity_id + "/" + group_id + "/" + student_id,
            dataType: 'script'
          });
        }
      }

      function write_alert(activity_id, group_id){
        $('#form').dialog({width:'400px'});
        $('#message').val("");
      }

      function send_alert(activity_id, group_id){
        if (confirm('¿Está seguro de que desea enviar esta alerta?')) {
          $.ajax({
            type: "POST", 
            url: "<?php echo PATH ?>/activities/send_alert/" + activity_id + "/" + group_id,
                                    data: $('#message').val(),
            dataType: 'script'
          });
        }
      }
    </script>
    <ul>
      <?php if (($activity['Subject']['coordinator_id'] == $auth->user('id')) || ($activity['Subject']['practice_responsible_id'] == $auth->user('id')) || ($auth->user('type') == "Administrador") || ($user_can_send_alerts == true)) {?>
        <li><a href="javascript:;" onclick="write_alert(<?php echo $activity['Activity']['id']?>, <?php echo $group['Group']['id'] ?>)">Enviar alerta</a></li>
      <?php } ?>
    </ul>
  </div>
<?php endif; ?>

<div class="<?php if ($auth->user('type') != "Estudiante"): ?>view<?php endif; ?>">
	<p id="notice"></p>
  <fieldset>
	<legend>Estudiantes</legend>
		<table>
			<thead>
				<tr>
					<th>Nombre</th>
          <?php if ($auth->user('type') != "Estudiante"): ?>
            <th>Correo electrónico</th>
            <th></th>
          <?php endif ?>
				</tr>
			</thead>
			<tbody>
				<?php foreach($students as $student): ?>
					<tr id="row_<?php echo "{$activity['Activity']['id']}_{$group['Group']['id']}_{$student['Student']['id']}" ?>">
						<td><?php echo "{$student['Student']['first_name']} {$student['Student']['last_name']}"?></td>
            <?php if ($auth->user('type') != "Estudiante"): ?>
              <td><a href="mailto:<?php echo $student['Student']['username'] ?>"><?php echo $student['Student']['username'] ?></td>
              <?php if (($activity['Subject']['coordinator_id'] == $auth->user('id')) || ($activity['Subject']['practice_responsible_id'] == $auth->user('id')) || ($auth->user('type') == "Administrador")): ?>
                <td><a href="javascript:;" onclick="delete_student(<?php echo "{$activity['Activity']['id']}, {$group['Group']['id']}, {$student['Student']['id']}" ?>)">Eliminar</a></td>
              <?php endif; ?>
            <?php endif; ?>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</fieldset>
</div>

<div id="form" style="display:none">
	<label for="message">Mensaje</label>
	<textarea id="message" rows="10" cols="20"></textarea>
	<br /><br />
	<div class="submit">
		<input type="submit" value="Enviar" onclick="send_alert(<?php echo "{$activity['Activity']['id']}, {$group['Group']['id']}"?>)" /> o <a href="javascript:;" onclick="$('#form').dialog('close')">Cancelar</a>
	</div>
</div>