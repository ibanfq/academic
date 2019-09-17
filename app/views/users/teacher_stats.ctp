<!-- File: /app/views/users/teacher_stats.ctp -->
<?php $html->addCrumb('Usuarios', '/institutions/ref:users'); ?>
<?php $html->addCrumb(Environment::institution('name'), Environment::getBaseUrl() . '/users'); ?>
<?php $html->addCrumb("{$user['User']['first_name']} {$user['User']['last_name']}", Environment::getBaseUrl() . "/users/view/{$user['User']['id']}"); ?>
<?php $html->addCrumb("Estadísticas de ejecución", Environment::getBaseUrl() . "/users/view/teacher_stats/{$user['User']['id']}"); ?>

<script type="text/javascript">
  function update_events() {
    var course = $('#courses select:visible');
    if (course && course.val() !== '') {
      $.ajax({
        url: "<?php echo Environment::getBaseUrl() ?>/users/teacher_stats_details/<?php echo $user['User']['id']?>", 
        data: "course_id=" + course.val(), 
        success: function(html){
          $('#stats').html(html);
        }
      });
    } else {
      $('#stats').html("");
    }
  }
  $(function() {
    $('#courses select').change(function() {
			update_events();
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

			$('#stats').html("");
		}).change();
  });
</script>

<h1>Estadísticas de ejecución de <?php echo "{$user['User']['first_name']} {$user['User']['last_name']}"?></h1>

<div class="actions">
</div>

<div class="view">
  <fieldset>
    <legend>Titulación</legend>
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
              <option value="">Seleccione una titulación</option>
              <?php foreach ($academic_year['Course'] as $course): ?>
                <option value="<?php echo h($course['id']) ?>"><?php echo h($course['Degree']['name']) ?></option>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>
        <?php endforeach; ?>
      </dd>
    </dl>
  </fieldset>
  
  <div id="stats">
  </div>
</div>