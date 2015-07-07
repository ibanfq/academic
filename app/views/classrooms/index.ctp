<?php $html->addCrumb('Aulas', '/classrooms'); ?>

<h1>Aulas</h1>

<div class="actions">
	<ul>
		<?php if ($auth->user('type') == "Administrador") {?>
			<li><?php echo $html->link('Crear aula', array('action' => 'add')) ?></li>
		<?php } ?>
		
		<?php if (($auth->user('type') != "Estudiante") && ($auth->user('type') != "Profesor")) {?>
			<li><?php echo $html->link('Imprimir hoja de firmas', array('action' => 'get_sign_file')) ?></li>
			<li><?php echo $html->link('Imprimir agenda diaria', array('action' => 'get_bookings')) ?></li>
		<?php } ?>
	</ul>
</div>

<div class="view">
	<?php
		echo $form->create('Classroom', array('action' => 'index', 'type' => 'get'))
	?>
		<fieldset>
		<legend>Buscar aulas</legend>
			<?php
				echo $form->text('q', array('value' => $q));
			?>
		</fieldset>
	<?php
		echo $form->end('Buscar');
	?>
	<table>
		<thead>
			<tr>
				<th>Nombre</th>
				<th>Tipo</th>
				<th>Capacidad</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<!-- Shows the next and previous links -->
				<?php
                                        $paginator->options(array('url' => array('q'=>$q)));
					echo $paginator->prev('« Anterior ', null, null, array('class' => 'disabled'));
					echo "&nbsp";
					echo $paginator->numbers();
					echo "&nbsp";
					echo $paginator->next(' Siguiente »', null, null, array('class' => 'disabled'));
				?>
			</tr>
		</tfoot>
		<tbody>
			<?php foreach ($classrooms as $classroom): ?>
			<tr>
				<td><?php echo $html->link($classroom['Classroom']['name'], array('controller' => 'classrooms', 'action' => 'view', $classroom['Classroom']['id'])) ?></td>
				<td><?php echo $classroom['Classroom']['type'] ?></td>
				<td><?php echo $classroom['Classroom']['capacity'] ?></td>
			</tr>
			<?php endforeach; ?>
			
		</tbody>
	</table>
</div>
