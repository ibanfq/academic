<?php $html->addCrumb('Mis asignaturas', '/users/my_subjects'); ?>
<?php $html->addCrumb($activity['Subject']['name'], "/events/register_student/{$activity['Subject']['id']}"); ?>
<?php $html->addCrumb("Ver alumnos apuntados", "/registrations/view_students_registered/{$activity['Activity']['id']}/{$group['Group']['id']}"); ?>

<h2><?php echo "Alumnos apuntados al grupo {$group['Group']['name']} de la actividad {$activity['Activity']['name']}"?></h2>

<p id="notice"></p>

<table>
	<thead>
		<th style="width:60%">Nombre</th>
		<th></th>
	</thead>
	<tbody>
		<?php
			foreach($registrations as $registration):
				$user_id = $registration['User']['id'];
				echo "<tr id=\"user_{$user_id}\"><td><div id=\"user_{$user_id}_name\">{$registration['User']['first_name']} {$registration['User']['last_name']}</div></td>";
				echo "<td><div class=\"request_options\">";
				if (!$changes_closed):
					$requested = isset($changes_requests[$user_id]);
					$add_request_style = $requested || count($changes_requests)? 'display:none;' : '';
					$request_style = $requested? '' : 'display:none;';
					echo "<a id=\"add_request_$user_id\" class=\"add_request\" href=\"#\" onclick=\"addRequest($user_id);return !1;\" style=\"$add_request_style\">Solicitar cambio</a>";
					echo "<span id=\"request_$user_id\" class=\"request\" style=\"$request_style\">Cambio solicitado. ";
					if ($requested && $changes_requests[$user_id]['student_id'] == $user_id):
						echo "<span id=\"accept_request_$user_id\"><a href=\"#\" onclick=\"acceptRequest($user_id);return !1;\">Aceptar</a> | </span>";
					endif;
					echo "<a href=\"#\" onclick=\"cancelRequest($user_id);return !1;\">Cancelar</a></span>";
				endif;
				echo "</div></td></tr>";
			endforeach;
		?>
	</tbody>
</table>
<?php if (count($registrations) == 0) { ?>
	<p>Todavía no hay ningún estudiante apuntado a este grupo.</p>
<?php } ?>

	
<script type="text/javascript">
	function addRequest(user_id){
		var $link = $('#add_request_'+user_id);
		var d = 'disabled';
		$link.attr(d,d).addClass(d);
		$.ajax({
			type: "POST", 
			url: "<?php echo PATH ?>/registrations/request_add/<?php echo $activity['Activity']['id'] ?>/" + user_id,
			asynchronous: false, 
			complete: function(xhr, status){
				$link.removeClass(d).removeAttr(d);
				switch(status === 'success'? xhr.responseText : 'error'){
				case "success":
					$link.hide();
					$('#request_'+user_id).show();
					$('.add_request').hide();
					break;
				default:
					$('#notice').removeClass('success');
					$('#notice').addClass('error');
					$('#notice').html("Se ha producido algún error que ha impedido solicitar el cambio de grupo. Por favor, contacte con el administrador del sistema para que le ayude a solucionarlo");
					$("html, body").animate({ scrollTop: 0 }, "fast");
				}
			}
		});
	}
	
	function acceptRequest(user_id){
		var $req = $('#request_'+user_id);
		var d = 'disabled';
		$req.find('a').attr(d,d).addClass(d);
		$.ajax({
			type: "POST", 
			url: "<?php echo PATH ?>/registrations/request_accept/<?php echo $activity['Activity']['id'] ?>/" + user_id,
			asynchronous: false, 
			complete: function(xhr, status){
				$req.find('a').removeClass(d).removeAttr(d);
				switch(status === 'success'? xhr.responseText : 'error'){
				case "success":
					$('.request_options').remove();
					$('#user_'+user_id+'_name').wrapInner("<del></del>").append(" <?php echo "{$auth->user('first_name')} {$auth->user('last_name')}"; ?>");
					break;
				default:
					$('#notice').removeClass('success');
					$('#notice').addClass('error');
					$('#notice').html("Se ha producido algún error que ha impedido apuntarle en este grupo. Por favor, contacte con el administrador del sistema para que le ayude a solucionarlo");
					$("html, body").animate({ scrollTop: 0 }, "fast");
				}
			}
		});
	}
	
	function cancelRequest(user_id){
		var $req = $('#request_'+user_id);
		var d = 'disabled';
		$req.find('a').attr(d,d).addClass(d);
		$.ajax({
			type: "POST", 
			url: "<?php echo PATH ?>/registrations/request_cancel/<?php echo $activity['Activity']['id'] ?>/" + user_id,
			asynchronous: false, 
			complete: function(xhr, status){
				$req.find('a').removeClass(d).removeAttr(d);
				switch(status === 'success'? xhr.responseText : 'error'){
				case "success":
				case "requestNotExists":
					$req.hide();
					$('#accept_request_'+user_id).remove();
					if (!$('.request:visible').length) {
						$('.add_request').show();						
					}
					break;
				default:
					$('#notice').removeClass('success');
					$('#notice').addClass('error');
					$('#notice').html("Se ha producido algún error que ha impedido cancelar la solicitud. Por favor, contacte con el administrador del sistema para que le ayude a solucionarlo");
					$("html, body").animate({ scrollTop: 0 }, "fast");
				}
			}
		});
	}
</script>