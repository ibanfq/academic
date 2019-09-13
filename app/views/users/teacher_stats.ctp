<!-- File: /app/views/users/teacher_stats.ctp -->
<?php $html->addCrumb('Usuarios', '/users'); ?>
<?php $html->addCrumb("{$user['User']['first_name']} {$user['User']['last_name']}", "/users/view/{$user['User']['id']}"); ?>
<?php $html->addCrumb("Estadísticas de ejecución", "/users/view/teacher_stats/{$user['User']['id']}"); ?>

<script type="text/javascript">
  function update_events() {
    if ($('#course_id').val() == '')
      $('#stats').html("");
    else {
      $.ajax({
        url: "<?php echo Environment::getBaseUrl() ?>/users/teacher_stats_details/<?php echo $user['User']['id']?>", 
        data: "course_id=" + $('#course_id').val(), 
        success: function(html){
          $('#stats').html(html);
          }
        });
      }
  }
  $(function() {
    $('#course_id').change(update_events).change();
  });
</script>

<h1>Estadísticas de ejecución de <?php echo "{$user['User']['first_name']} {$user['User']['last_name']}"?></h1>

<div class="actions">
</div>

<div class="view">
  <fieldset>
    <legend>Año académico</legend>
    <dl>
      <dt>Curso</dt>
      <dd>
        <select id="course_id">
          <option value='' selected>Seleccione un curso</option>
          <?php foreach($courses as $course): ?>
            <option value="<?php echo $course["Course"]["id"] ?>"><?php echo $course['Degree']['name'] ?></option>
          <?php endforeach; ?>
        </select>
      </dd>
    </dl>
  </fieldset>
  
  <div id="stats">
  </div>
</div>