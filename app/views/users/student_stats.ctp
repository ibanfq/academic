<!-- File: /app/views/users/teacher_stats.ctp -->
<?php $html->addCrumb('Usuarios', '/users'); ?>
<?php $html->addCrumb("{$user['User']['first_name']} {$user['User']['last_name']}", "/users/view/{$user['User']['id']}"); ?>
<?php $html->addCrumb("Estadísticas", "/users/view/student_stats/{$user['User']['id']}"); ?>

<script type="text/javascript">
  function update_events() {
    if ($('#subject_id').val() == '')
      $('#events').html("");
    else {
      $.ajax({
        url: "<?php echo Environment::getBaseUrl() ?>/users/student_stats_details/<?php echo $user['User']['id']?>", 
        data: "subject_id=" + $('#subject_id').val(), 
        success: function(html){
          $('#events').html(html);
          }
        });
      }
  }
  
  function update_subjects() {
    $('#events').html("");
    if ($('#course_id').val() == '')
      $('#subject_id').html("<option value=''>Seleccione una asignatura</option>");
    else {
      $.ajax({
        url: "<?php echo Environment::getBaseUrl() ?>/users/get_student_subjects/<?php echo $user['User']['id']?>", 
        data: "course_id=" + $('#course_id').val(), 
        success: function(html){
          $('#subject_id_select').html(html);
          }
        });
      }
  }
</script>

<h1>Asistencia de <?php echo "{$user['User']['first_name']} {$user['User']['last_name']}"?> (se muestra la duración planificada)</h1>
<div class="actions">
</div>

<div class="view">
  <fieldset>
    <legend>Asignaturas</legend>
    
    <dl>
      <dt>Curso</dt>
      <dd>
        <select id="course_id" onchange="update_subjects()">
          <option value='' selected>Seleccione un curso</option>
          <?php foreach($courses as $course): ?>
            <option value="<?php echo $course["Course"]["id"] ?>"><?php echo $course['Degree']['name'] ?></option>
          <?php endforeach; ?>
        </select>
      </dd>
    </dl>
    
    <dl>
      <dt>Asignatura</dt>
      <dd id="subject_id_select">
        <select id="subject_id" onchange="update_events()">
          <option value="" selected>Seleccione una asignatura</option>
        </select>
      </dd>
    </dl>
  </fieldset>
  <div id="events">
  </div>
</div>