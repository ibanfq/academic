<!-- File: /app/views/users/view.ctp -->
<?php $html->addCrumb('Mis asignaturas', '/users/my_subjects'); ?>
<?php $html->addCrumb($subject['Subject']['name'], '/users/my_subjects'); ?>

<?php $flexible_groups = (bool) Configure::read('app.registration.flexible_groups'); ?>
<?php $flexible_until_days_to_start = Configure::read('app.activity.teacher_can_block_groups_if_days_to_start'); ?>

<h1>Grupos de la asignatura <?php echo $subject['Subject']['name'] ?></h1>

<p id="notice"></p>

<div class="intro">
	<p>Para consultar la información sobre un grupo, pase el ratón por encima del nombre y espere a que aparezca el cuadro con la información disponible.</p>
	<?php if ($flexible_groups): ?>
		<p>Cuando el fondo está AMARILLO es que tiene cambios solicitados, no se puede cambiar de grupo hasta que no aparezca en VERDE.</p>
		<?php if (is_int($flexible_until_days_to_start)): ?>
			<p>En el caso de que el fondo esté en ROJO, ya no te puedes cambiar de grupo. Esto puede ser porque la práctica ya se impartió o porque el profesor tiene bloqueado el cambio de grupo <?php echo $flexible_until_days_to_start === 1 ? "$flexible_until_days_to_start día" : "$flexible_until_days_to_start días" ?> antes de su impartición. En este caso tienes que hablar con el profesor para poder acudir a otro grupo de prácticas.</p>
		<?php else: ?>
			<p>En el caso de que el fondo esté en ROJO, ya no te puedes cambiar de grupo. Esto puede ser porque la práctica ya se impartió. En este caso tienes que hablar con el profesor para poder acudir a otro grupo de prácticas.</p>
		<?php endif; ?>
	<?php else: ?>
		<p>Una vez elegido un grupo ya no podrás cambiarte. En este caso tienes que hablar con el profesor para poder acudir a otro grupo de prácticas.</p>
	<?php endif; ?>
	<p>
            <strong>IMPORTANTE:</strong> Tenga en cuenta que el número de plazas libres puede ir cambiando debido a que los otros estudiantes van seleccionando sus grupos. Siempre puede <a href="javascript:;" onclick="update_subject_free_seats()">actualizar</a> las plazas disponibles.
            No escojas grupo de prácticas si tienes esa actividad aprobada. En caso de escoger erróneamente grupo ponte en contacto con tu profesor para que te borre de esa actividad.
        </p>
</div>


<?php foreach ($activities_groups as $activity): ?>
	<h2 style="display:inline"><?php echo $activity['name'] ?></h2>
	&nbsp;
	<?php if ((isset($student_groups[$activity['id']])) && ($student_groups[$activity['id']] == -1)) { ?>
			<span>Tienes esta actividad aprobada</span><br /><br />
    <?php } else { ?>
		<?php $activity_has_changes_requests = !empty($changes_requests[$activity['id']]); ?>
		<ul id="group_list_<?php echo $activity['id']?>" class="groups <?php echo $activity['groups_closed'] || $activity_has_changes_requests? 'closed' : 'opened' ?> <?php echo $activity_has_changes_requests? 'has_changes_requests' : '' ?>">
		<?php foreach ($activity['Groups'] as $group): ?>
			<li class="group <?php echo $group['closed'] || $activity_has_changes_requests? 'closed' : 'opened' ?>" id="group_<?php echo $activity['id']?>_<?php echo $group['id'] ?>">
				<?php $free_seats = max(0, $group['free_seats']); ?>
				<?php if ((isset($student_groups[$activity['id']])) && ($group['id'] == $student_groups[$activity['id']])){
					echo "<span class='selected group_label activity_{$activity['id']}' id='{$activity['id']}_{$group['id']}' activity_id='{$activity['id']}' group_id='{$group['id']}'><a href='javascript:;'>{$group['name']} [?]</a></span>";

					echo "<span id='free_seats_{$activity['id']}_{$group['id']}'>Quedan {$free_seats} plazas libres</span>";
          
          			echo "<span>";
					if (!$activity_has_changes_requests) {
						if (!$group['closed']) {
							echo "<a href='javascript:;' onclick='registerMe({$activity['id']}, {$group['id']})' class='register_me_link_activity_{$activity['id']}' id='register_me_link_activity_{$activity['id']}_{$group['id']}' style='display:none'>¡Me apunto!</a></span>";
						}
					}
					echo "</span>";

				} else {

					echo "<span class='group_label activity_{$activity['id']}' id='{$activity['id']}_{$group['id']}' activity_id='{$activity['id']}' group_id='{$group['id']}'><a href='javascript:;'>{$group['name']} [?]</a></span>";
					echo "<span id='free_seats_{$activity['id']}_{$group['id']}'>Quedan {$free_seats} plazas libres</span>";
					echo "<span>";
					if (!$activity_has_changes_requests && $group['free_seats'] > 0) {
						if ((!$group['ended'] && !isset($student_groups[$activity['id']])) || (!$activity['groups_closed'] && !$group['closed'])) {
							echo "<a href='javascript:;' onclick='registerMe({$activity['id']}, {$group['id']})' class='register_me_link_activity_{$activity['id']}' id='register_me_link_activity_{$activity['id']}_{$group['id']}'>¡Me apunto!</a>";
						}
					}
					echo "</span>";

				} ?>
			<?php
				$total_group_changes_requests = isset($changes_requests[$activity['id']][$group['id']])? count($changes_requests[$activity['id']][$group['id']]) : 0;
        echo "<span>";
				echo $html->link('Ver alumnos apuntados', array('controller' => 'registrations', 'action' => 'view_students_registered', $activity['id'], $group['id'], 'class' => ''));
				if ($total_group_changes_requests == 1) {
					echo ' <span>(Tienes 1 solicitud pendiente)</span>';
				} else if ($total_group_changes_requests > 1) {
					echo " <span>(Tienes $total_group_changes_requests solicitudes pendientes)</span>";
				}
        echo "</span>";
			?>

			</li>
		<?php endforeach; ?>
		</ul>
	<?php } ?>

<?php endforeach; ?>

<script type="text/javascript">
	var current_group_label_xhr;
	$('.group_label').tooltip({
		delay: 500,
		bodyHandler: function() {
			activity_id = $('#' + this.id).attr('activity_id');
			group_id = $('#' + this.id).attr('group_id');
			if (current_group_label_xhr) {
				current_group_label_xhr.abort();
			}
			$('#tooltip').html('');
			$('#details').html('');
			current_group_label_xhr = $.ajax({
				type: "GET", 
				url: "<?php echo PATH ?>/events/view_info/" + activity_id + "/" + group_id,
				asynchronous: false,
				success: function(data) {
					current_group_label_xhr = null;
					$('#tooltip').html(data);
					$('#details').html(data);
				}
			});
			setTimeout(function() {
				if (current_group_label_xhr && current_group_label_xhr.readyState < 3) {
					$('#tooltip').html('Cargando...');
					$('#details').html('Cargando...');
				}
			}, 1500);
			return $('#details').html();
		},
		showURL: false
	});
	
	function registerMe(activity_id, group_id){
		var link = $('#register_me_link_activity_' + activity_id + '_' + group_id);
		var d = 'disabled';
		link.attr(d,d).addClass(d);
		$.ajax({
			type: "POST", 
			url: "<?php echo PATH ?>/registrations/add/" + activity_id + "/" + group_id, 
			asynchronous: false, 
			success: function(data){
				
				switch(data){
				case "success":
                    var closed = $('#group_list_' + activity_id + ',#group_' + activity_id + '_' + group_id).hasClass('closed');
					$('.activity_' + activity_id).removeClass('selected');
					$('#' + activity_id + "_" + group_id).addClass('selected');
					$('.group.opened .register_me_link_activity_' + activity_id).toggle(!closed);
					$('.group.closed .register_me_link_activity_' + activity_id).hide();
                    if (closed) {
                        $('#passed_' + activity_id).hide();
                    }
					break;
				case "notEnoughSeatsError":
					$('#notice').removeClass('success');
					$('#notice').addClass('error');
					$('#notice').html("No ha sido posible apuntarle a este grupo porque las plazas disponibles han sido ocupadas por otro usuario.");
					break;
				default:
					$('#notice').removeClass('success');
					$('#notice').addClass('error');
					$('#notice').html("Se ha producido algún error que ha impedido apuntarle en este grupo. Por favor, contacte con el administrador del sistema para que le ayude a solucionarlo");
				}
				link.hide().removeAttr(d).removeClass(d);
				update_subject_free_seats();
			}
		});
	}
	
	function update_subject_free_seats() {
		$.ajax({
			type: "GET",
			asynchronous: false, 
			url: "<?php echo PATH ?>/registrations/get_subject_free_seats/" + <?php echo $subject['Subject']['id'] ?>, 
			dataType: 'script'
		});
	}
</script>

<div style="display:none" id="details"></div>
