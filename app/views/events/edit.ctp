<?php if (isset($event)) { ?>
	<?php echo $form->create('Event', array('onsubmit' => 'return false;')); ?>
		<h3><?php echo "{$event['Activity']['name']}"?></h3>
		<fieldset>
			<div class="input">
				<dl>
					<dt><label>Grupo</label></dt>
					<dd style="margin-left: 8em"><?php echo "{$event['Group']['name']} "?> </dd>
				</dl>
				<dl>
					<dt><label>Fecha</label></dt>
					<dd style="margin-left: 8em"><?php echo date_create($event['Event']['initial_hour'])->format('d/m/Y')?> </dd>
				</dl>
				<dl>
					<dt><label>Hora de comienzo</label></dt>
					<dd style="margin-left: 8em"><?php echo date_create($event['Event']['initial_hour'])->format('H:i')?> </dd>
				</dl>
				<dl>
					<dt><label>Hora de fin</label></dt>
					<dd style="margin-left: 8em"><?php echo date_create($event['Event']['final_hour'])->format('H:i')?> </dd>
				</dl>
				<div>
    				<?php if ($auth->user('type') == "Administrador") { ?>
    	            	<?php echo $form->input('classroom_id', array('label' => 'Aula', 'options' => $classrooms, 'selected' => $event['Event']['classroom_id'], 'div' => false, 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
    				<?php } ?>
                </div>
				<div>
					<dl>
						<dt><label for="teacher_name">Profesor</label></dt>
						<dd><input type="text" name="edit_teacher_name" id="edit_teacher_name" value="<?php echo "{$event['Teacher']['first_name']} {$event['Teacher']['last_name']}"?>" /></dd>
						<input type="hidden" id="teacher_id" name="TeacherId" value="<?php echo $event['Teacher']['id'] ?>" />
					</dl> 
				</div>
				
				<div style="<?php if ($event['Activity']['type'] != 'Evaluación' && empty($event['Teacher_2']['id'])): ?>display:none;<?php endif; ?>">
					<dl>
						<dt><label for="teacher_name_2">2º Profesor</label></dt>
						<dd>
						  <input type="text" name="edit_teacher_2_name" id="edit_teacher_2_name" value="<?php 
						  if (isset($event['Teacher_2']['first_name']))
						    echo "{$event['Teacher_2']['first_name']} {$event['Teacher_2']['last_name']}"
					    ?>" />
					  </dd>
						<input type="hidden" id="teacher_2_id" name="Teacher_2Id" value="<?php echo $event['Teacher_2']['id'] ?>" />
					</dl> 
				</div>

				<?php if (Configure::read('app.event.show_tv')): ?>
	    	        <?php echo $form->input('show_tv', array('label' => 'Mostrar en TV', 'checked' => $event['Event']['show_tv']? 'checked' : '')); ?>
                <?php endif; ?>
			</div>
		</fieldset>
		<div class="submit">
			<input type="submit" value="Actualizar" onclick="
			<?php if ($event['Event']['parent_id'] == null) :?>
                <?php if (Configure::read('app.event.show_tv')): ?>
                    if (confirm('Este evento es el primero de la serie. Si modifica el profesor o la visibilidad en TV se modificará en todos los eventos de la serie. ¿Seguro que desea continuar?'))
                <?php else: ?>
				    if (confirm('Este evento es el primero de la serie. Si modifica el profesor se modificará en todos los eventos de la serie. ¿Seguro que desea continuar?'))
                <?php endif; ?>
			<?php endif; ?>
				{
					<?php if ($auth->user('type') == "Administrador") { ?>
						update_classroom(<?php echo $event['Event']['id'] ?>);
					<?php } else { ?>
						update_teacher(<?php echo $event['Event']['id'] ?>);
					<?php } ?>
				}
			">
			o
			<a class="dialog" href="javascript:;" title="Eliminar este evento" onclick="delete_event(<?php echo $event['Event']['id'] ?>, '<?php echo $event['Event']['parent_id'] ?>');">Eliminar este evento</a>
		</div>

	<script type="text/javascript">
	function formatEditItem(row){
		if (row[1] != null)
			return row[0];
		else
			return 'No existe ningún profesor con este nombre.';
	}
	
	$("#edit_teacher_name").autocomplete("<?php echo PATH ?>/users/find_teachers_by_name", {formatItem: formatEditItem}).result(function(event, item){ $("input#teacher_id").val(item[1]); });
	
	$("#edit_teacher_2_name").autocomplete("<?php echo PATH ?>/users/find_teachers_by_name", {formatItem: formatEditItem}).result(function(event, item){ $("input#teacher_2_id").val(item[1]); });
			
	</script>
<?php } else { ?>
<p>Usted no tiene permisos para editar este evento. Sólo el dueño del evento, los coordinadores de la asignatura o un administrador pueden modificarlo.</p>
<?php }?>
