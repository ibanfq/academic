<!-- File: /app/views/users/teacher_stats.ctp -->
<?php $html->addCrumb('Usuarios', '/users'); ?>
<?php $html->addCrumb("{$user['User']['first_name']} {$user['User']['last_name']}", "/users/view/{$user['User']['id']}"); ?>
<?php $html->addCrumb("Horario", "/users/view/teacher_schedule/{$user['User']['id']}"); ?>

<script type="text/javascript">
  function update_events() {
    if ($('#course_id').val() == '')
      $('#schedule').html("");
    else {
      $.ajax({
        url: "<?php echo PATH ?>/users/teacher_schedule_details/<?php echo $user['User']['id']?>", 
        data: "course_id=" + $('#course_id').val(), 
        success: function(html){
          $('#schedule').html(html);
          }
        });
      }
  }
  $(function() {
    $('#course_id').change(update_events).change();
    $('#schedule').delegate(".event", "click", function() {
      var btn = $(this);
      var url = "<?php echo PATH ?>/events/view/" + btn.attr('data-id');
      $.ajax({
        cache: false,
        type: "GET",
        url: url,
        success: function(data) {
          if (data == "false")
            alert("Usted no tiene permisos para ver más información.");
          else{
            $('#EventDetails').html(data);
            $('#EventDetails').dialog({
              width:500,
              position:'top'
            });
          }
        }
      });

      return false;
    });
  });
</script>

<h1>Horario de <?php echo "{$user['User']['first_name']} {$user['User']['last_name']}"?></h1>

<div class="actions">
</div>

<div id="EventDetails" style="display:none"></div>

<div class="view">
  <fieldset>
    <legend>Año académico</legend>
    <dl>
      <dt>Curso</dt>
      <dd>
        <select id="course_id">
          <option value='' selected>Seleccione un curso</option>
          <?php foreach($courses as $course): ?>
            <option value="<?php echo $course["Course"]["id"] ?>"><?php echo $course['Course']['name'] ?></option>
          <?php endforeach; ?>
        </select>
      </dd>
    </dl>
  </fieldset>
  
  <div id="schedule">
  </div>
</div>