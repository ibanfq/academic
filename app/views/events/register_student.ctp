<!-- File: /app/views/users/view.ctp -->
<?php $html->addCrumb('Mis asignaturas', '/users/my_subjects'); ?>
<?php $html->addCrumb($subject['Subject']['name'], '/users/my_subjects'); ?>

<h1>Grupos de la asignatura <?php echo $subject['Subject']['name'] ?></h1>

<p id="notice"></p>

<div class="intro">
	<p>Para consultar la información sobre un grupo, pase el ratón por encima del nombre y espere a que aparezca el cuadro con la información disponible.</p>
	<p><strong>IMPORTANTE:</strong> Tenga en cuenta que el número de plazas libres puede ir cambiando debido a que los otros estudiantes van seleccionando sus grupos. Siempre puede <a href="javascript:;" onclick="update_subject_free_seats()">actualizar</a> las plazas disponibles.</p>
</div>


<?php foreach ($activities_groups as $activity): ?>
	<h2 style="display:inline"><?php echo $activity['name'] ?></h2>
	&nbsp;
	<?php if ((isset($student_groups[$activity['id']])) && ($student_groups[$activity['id']] == -1)) { 
		$ul_display = "display:none";
	?>
		<span id="not_passed_<?php echo $activity['id'] ?>"> La tengo aprobada <a href="javascript:;" onclick="i_havent_passed_this(<?php echo $activity['id'] ?>)" class="small_link">Me confundí. No la tengo aprobada</a><br /><br /></span>
		<a href="javascript:;" onclick="i_have_passed_this(<?php echo $activity['id'] ?>)" id="passed_<?php echo $activity['id'] ?>" class="small_link" style="display:none">Tengo esta actividad aprobada</a>

	<?php } else { 
			$ul_display = "";
	?>
		<span id="not_passed_<?php echo $activity['id'] ?>" style="display:none"> La tengo aprobada <a href="javascript:;" onclick="i_havent_passed_this(<?php echo $activity['id'] ?>)" class="small_link">Me confundí. No la tengo aprobada</a><br /><br /></span>
		<a href="javascript:;" onclick="i_have_passed_this(<?php echo $activity['id'] ?>)" id="passed_<?php echo $activity['id'] ?>" class="small_link">Tengo esta actividad aprobada</a>

	<?php } ?>

		<ul class="groups" id="group_list_<?php echo $activity['id']?>" style="<?php echo $ul_display ?>">
		<?php foreach ($activity['Groups'] as $group): ?>
				<li>
					<?php if ((isset($student_groups[$activity['id']])) && ($group['id'] == $student_groups[$activity['id']])){
						echo "<span class='selected group activity_{$activity['id']}' id='{$activity['id']}_{$group['id']}' activity_id='{$activity['id']}' group_id='{$group['id']}'><a href='javascript:;'>{$group['name']} [?]</a></span>";
				
						echo "<span id='free_seats_{$activity['id']}_{$group['id']}'>Quedan {$group['free_seats']} plazas libres</span>";
						echo "<span><a href='javascript:;' onclick='registerMe({$activity['id']}, {$group['id']})' class='register_me_link_activity_{$activity['id']}' id='register_me_link_activity_{$activity['id']}_{$group['id']}' style='display:none'>¡Me apunto!</a></span>";
						
					} else {
					
						echo "<span class='group activity_{$activity['id']}' id='{$activity['id']}_{$group['id']}' activity_id='{$activity['id']}' group_id='{$group['id']}'><a href='javascript:;'>{$group['name']} [?]</a></span>";
						echo "<span id='free_seats_{$activity['id']}_{$group['id']}'>Quedan {$group['free_seats']} plazas libres</span>";
					$style = $group['free_seats'] > 0 ? '' : 'display:none';
					echo "<span><a href='javascript:;' onclick='registerMe({$activity['id']}, {$group['id']})' class='register_me_link_activity_{$activity['id']}' id='register_me_link_activity_{$activity['id']}_{$group['id']}' style='{$style}'>¡Me apunto!</a></span>";
				
				} ?>
				
				<?php echo $html->link('Ver alumnos apuntados', array('controller' => 'registrations', 'action' => 'view_students_registered', $activity['id'], $group['id'], 'class' => '')) ?>
				
				</li>
		<?php endforeach; ?>
		</ul>

<?php endforeach; ?>

<script type="text/javascript">
	$('.group').tooltip({
		delay: 500,
		bodyHandler: function() {
			activity_id = $('#' + this.id).attr('activity_id');
			group_id = $('#' + this.id).attr('group_id');
			$.ajax({
				type: "GET", 
				url: "<?php echo PATH ?>/events/view_info/" + activity_id + "/" + group_id,
				asynchronous: false,
				success: function(data) {
					$('#tooltip').html(data);
					$('#details').html(data);
				}
			});
			
			return $('#details').html();
		},
		showURL: false
	});
	
	function registerMe(activity_id, group_id){
		$.ajax({
			type: "POST", 
			url: "<?php echo PATH ?>/registrations/add/" + activity_id + "/" + group_id, 
			asynchronous: false, 
			success: function(data){
				
				switch(data){
				case "success":
					$('.activity_' + activity_id).removeClass('selected');
					$('#' + activity_id + "_" + group_id).addClass('selected');
					$('.register_me_link_activity_' + activity_id).show();
					$('#register_me_link_activity_' + activity_id + '_' + group_id).hide();
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
	
	function i_have_passed_this(activity_id) {
		$.ajax({
			type: "GET",
			asynchronous: false, 
			url: "<?php echo PATH ?>/registrations/pass_activity/" + activity_id, 
			success: function(result){
				if (result == "success"){
					$('.activity_' + activity_id).removeClass('selected');
					$('#group_list_' + activity_id).hide();
					$('#passed_' + activity_id).hide();
					$('#not_passed_' + activity_id).show();
				} else {
					$('#notice').removeClass('success');
					$('#notice').addClass('error');
					$('#notice').html("Se ha producido algún error que ha impedido marcar esta actividad como aprobada. Por favor, contacte con el administrador del sistema para que le ayude a solucionarlo");
				}
				
			}
		});
	}
	
	function i_havent_passed_this(activity_id) {
		$.ajax({
			type: "GET",
			asynchronous: false, 
			url: "<?php echo PATH ?>/registrations/fail_activity/" + activity_id, 
			success: function(result){
				if (result == "success"){
					update_subject_free_seats();
					$('.activity_' + activity_id).removeClass('selected');
					$('#group_list_' + activity_id).show();
					$('#passed_' + activity_id).show();
					$('#not_passed_' + activity_id).hide();
				} else {
					$('#notice').removeClass('success');
					$('#notice').addClass('error');
					$('#notice').html("Se ha producido algún error que ha impedido marcar esta actividad como aprobada. Por favor, contacte con el administrador del sistema para que le ayude a solucionarlo");
				}
			}
		});
	}
</script>

<div style="display:none" id="details"></div>