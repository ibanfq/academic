<!-- File: /app/views/users/teacher_stats.ctp -->
<?php $html->addCrumb('Usuarios', '/institutions/ref:users'); ?>
<?php $html->addCrumb(Environment::institution('name'), Environment::getBaseUrl() . '/users'); ?>
<?php $html->addCrumb("{$user['User']['first_name']} {$user['User']['last_name']}", Environment::getBaseUrl() . "/users/view/{$user['User']['id']}"); ?>
<?php $html->addCrumb("Estadísticas", Environment::getBaseUrl() . "/users/view/student_stats/{$user['User']['id']}"); ?>

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
        <select id="academic_year" name="academic_year">
          <?php foreach ($academic_years as $academic_year): ?>
            <?php 
              if ($academic_year["id"] == $current_academic_year["id"])
                $selected = "selected";
              else
                $selected = "";
            ?>
            <option value="<?php echo h($academic_year['id']) ?>" <?php echo $selected ?>><?php echo h($modelHelper->academic_year_name($academic_year)) ?></option>
          <?php endforeach; ?>
        </select>
      </dd>
    </dl>
    <dl>
      <dt>Titulación</dt>
      <dd id="courses">
        <?php foreach ($academic_years as $academic_year): ?>
          <select data-academic-year-id="<?php echo h($academic_year['id']) ?>" name="course" disabled style="display:none;">
            <?php if (isset($academic_year['Course'])): ?>
              <option value="" selected>Todas las titulaciones</option>
              <?php foreach ($academic_year['Course'] as $course): ?>
                <option value="<?php echo h($course['id']) ?>"><?php echo h($course['Degree']['name']) ?></option>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>
        <?php endforeach; ?>
      </dd>
    </dl>

    <dl>
      <dt>Asignatura</dt>
      <dd><input type="text" id="subject_name" name="SubjectName" onchange="$('#subject_name').flushCache()"/></dd>
      <script type='text/javascript'>
        $(function() {
          $('#courses select').change(function() {
            $('#events').html("");
            $('#subject_name').val('');
          });

          $('#academic_year').change(function() {
            var value = $(this).val();
            $('#courses select')
              .prop('disabled', true).css('display', 'none')
              .filter(function () {
                return $(this).data('academic-year-id') == value;
              })
              .prop('disabled', false).css('display', 'block')
              .val('');
            
            $('#events').html("");
            $('#subject_name').val('');
          }).change();


          $('#subject_name')
            .autocomplete('<?php echo Environment::getBaseUrl() ?>/subjects/find_subjects_by_name/', {
              extraParams: {
                academic_year_id: function () {
                  return $('#academic_year').val();
                },
                course_id: function() {
                  var course = $('#courses select:visible');
                  return course.val();
                }
              },
              formatItem: function (row)
                {
                  if (row[1] != null) {
                    return row[0];
                  }
                  return 'No existe ninguna asignatura con este nombre.';
                }
            }).result(
              function(event, item){ 
                current_subject = item[1];
                
                $.ajax({
                  url: "<?php echo Environment::getBaseUrl() ?>/users/student_stats_details/<?php echo $user['User']['id']?>", 
                  data: "subject_id=" + current_subject, 
                  success: function(html){
                    $('#events').html(html);
                  }
                });
              }
            );
        });
      </script>
    </dl>
  </fieldset>

  <div id="events">
  </div>
</div>