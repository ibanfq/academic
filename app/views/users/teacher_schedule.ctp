<!-- File: /app/views/users/teacher_stats.ctp -->
<?php $html->addCrumb('Usuarios', '/institutions/ref:users'); ?>
<?php $html->addCrumb(Environment::institution('name'), Environment::getBaseUrl() . '/users'); ?>
<?php $html->addCrumb("{$user['User']['first_name']} {$user['User']['last_name']}", Environment::getBaseUrl() . "/users/view/{$user['User']['id']}"); ?>
<?php $html->addCrumb("Planificación", Environment::getBaseUrl() . "/users/view/teacher_schedule/{$user['User']['id']}"); ?>

<script type="text/javascript">
  function update_events() {
    var course = $('#courses select:visible');
    if (course && course.val() !== '') {
      $.ajax({
        url: "<?php echo Environment::getBaseUrl() ?>/users/teacher_schedule_details/<?php echo $user['User']['id']?>", 
        data: "course_id=" + course.val(), 
        success: function(html){
          $('#schedule').html(html);
        }
      });
    } else {
      $('#schedule').html("");
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

      $('#schedule').html("");
		}).change();

    $('#schedule').delegate(".event", "click", function() {
      var btn = $(this);
      var url = "<?php echo Environment::getBaseUrl() ?>/events/view/" + btn.attr('data-id');
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
              position: {at: 'top'},
              create: function(event, ui) {
                  var widget = $(event.target).dialog('widget');
                  widget.find(widget.draggable("option", "handle")).addTouch();
                  widget.find('.ui-resizable-handle').addTouch();
              },
            });
          }
        }
      });

      return false;
    });
  });
</script>

<h1>Planificación de <?php echo "{$user['User']['first_name']} {$user['User']['last_name']}"?></h1>

<div class="actions">
</div>

<div id="EventDetails" style="display:none"></div>

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
  
  <div id="schedule">
  </div>
</div>