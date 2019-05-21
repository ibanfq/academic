<?php $html->addCrumb('Aulas', '/classrooms'); ?>
<?php $html->addCrumb('Monitores', '/monitors'); ?>

<h1>Monitores</h1>

<div class="actions">
	<ul>
		<?php if ($auth->user('type') == "Administrador"): ?>
			<li><?php echo $html->link('Crear monitor', array('action' => 'add')) ?></li>
		<?php endif; ?>
		
		<?php if (($auth->user('type') != "Estudiante") && ($auth->user('type') != "Profesor")): ?>
			<li><?php echo $html->link('Presentar todas los eventos en TV', array('controller' => 'monitors', 'action' => 'board')) ?></li>
		<?php endif; ?>
	</ul>
</div>

<div class="view">
	<?php
		echo $form->create('Monitor', array('action' => 'index', 'type' => 'get'))
	?>
		<fieldset>
		<legend>Buscar monitores</legend>
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
				<th>Mostrar eventos</th>
				<th>Mostrar contenido multimedia</th>
				<?php if (($auth->user('type') != "Estudiante") && ($auth->user('type') != "Profesor")): ?>
					<th></th>
				<?php endif; ?>
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
			<?php foreach ($monitors as $monitor): ?>
			<tr>
				<td><?php echo $html->link($monitor['Monitor']['name'], array('controller' => 'monitors', 'action' => 'view', $monitor['Monitor']['id'])) ?></td>
				<td><?php echo $monitor['Monitor']['show_events'] ? 'Si' : 'No' ?></td>
				<td><?php echo $monitor['Monitor']['show_media'] ? 'Si' : 'No' ?></td>
				<td><?php echo $html->link('Presentar en TV', array('controller' => 'monitors', 'action' => 'show', $monitor['Monitor']['id'])) ?></td>
			</tr>
			<?php endforeach; ?>
			
		</tbody>
	</table>
</div>
