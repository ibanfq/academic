<?php $html->addCrumb('Usuarios', '/institutions/ref:users'); ?>
<?php $html->addCrumb(Environment::institution('name'), Environment::getBaseUrl() . '/users'); ?>
<?php $html->addCrumb("{$user['User']['first_name']} {$user['User']['last_name']}", Environment::getBaseUrl() . "/users/view/{$user['User']['id']}"); ?>
<?php $html->addCrumb("Modificar matrícula", Environment::getBaseUrl() . "/users/edit_registration/{$user['User']['id']}"); ?>

<div id="notice" class="message" style="display:none"></div>
<h1>Modificar matrícula</h1>

<table>
	<thead>
		<tr>
			<th>Asignatura</th>
			<th></th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<td colspan=2>
				<a href="javascript:;" onclick="addRow()">Añadir asignatura</a>
			</td>
	</tfoot>
	<tbody id="subjects">
		<?php $i = 0 ?>
		<?php foreach($subjects as $subject):?>
			<tr id="row_<?php echo $i++ ?>">
				<td><?php echo $subject['Subject']['name'] ?></td>
				<td><?php echo $html->link('Eliminar', array('action' => 'delete_subject', $user['User']['id'], $subject['Subject']['id']), null, '¿Está seguro que desea continuar? La asignatura se eliminará con carácter inmediato y el estudiante será eliminado de todos los grupos en los que se haya apuntado.') ?></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>

<script type="text/javascript">
	function addRow(){
		index = $('#subjects > tr').length;
		if (index == 0)
			$('#subjects').html("<tr id='row_" + index + "'><td><input type='text' id='new_subject_" + index + "' class='subjects_autocomplete' /></td><td style='vertical-align:middle'><a href='javascript:;' onclick='saveSubject(" + index + ")'>Guardar</a>&nbsp;<a href='javascript:;' onclick='cancelSubject(" + index + ")'>Cancelar</a><input type='hidden' id='StudentSubject" + index + "' name='data[Student][Subjects][" + index + "]' /></td><script type='text\/javascript'>$('#new_subject_" + index + "').autocomplete('<?php echo Environment::getBaseUrl() ?>\/subjects\/find_subjects_by_name', {formatItem: function (row){if (row[1] != null) return row[0];else return 'No existe ninguna asignatura con este nombre.'; }}).result(function(event, item){ $('#StudentSubject" + index + "').val(item[1]); });<\/script></tr>");
		else
			$('#row_' + (index - 1)).after("<tr id='row_" + index + "'><td><input type='text' id='new_subject_" + index + "' class='subjects_autocomplete' /></td><td style='vertical-align:middle'><a href='javascript:;' onclick='saveSubject(" + index + ")'>Guardar</a>&nbsp;<a href='javascript:;' onclick='cancelSubject(" + index + ")'>Cancelar</a><input type='hidden' id='StudentSubject" + index + "' name='data[Student][Subjects][" + index + "]' /><script type='text\/javascript'>$('#new_subject_" + index + "').autocomplete('<?php echo Environment::getBaseUrl() ?>\/subjects\/find_subjects_by_name', {extraParams: {user_id: <?php echo $user['User']['id'] ?>}, formatItem: function (row){if (row[1] != null) return row[0];else return 'No existe ninguna asignatura con este nombre.'; }}).result(function(event, item){ $('#StudentSubject" + index + "').val(item[1]); });<\/script></tr>");
	}
	
	function cancelSubject(index) {
		$('#row_' + index).remove();
	}
	
	function saveSubject(index){
		$.ajax({
			type: "POST",
			url: "<?php echo Environment::getBaseUrl() ?>/users/save_subject/<?php echo $user['User']['id'] ?>/" + $('#StudentSubject' + index).val(),
			success: function(data){
				$('.message').hide();
				if (data != "error"){
					$('#row_' + index).html(data);
					$('#notice').html("La asignatura se ha añadido correctamente");
					$('#notice').show();
				}
				else
					$('#notice').html("Ha ocurrido un error y no ha podido añadirse la asignatura");
			}
		});
	}
	
</script>