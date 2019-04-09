<?php $html->addCrumb('Usuarios', '/users'); ?>
<h1>Usuarios</h1>

<div class="actions">
	<ul>
		<?php if (($auth->user('type') == "Administrador") || ($auth->user('type') == "Administrativo")) {?>
			<li><?php echo $html->link('Crear usuario', array('action' => 'add')) ?></li>
			<li><?php echo $html->link('Importar usuarios', array('action' => 'import')) ?></li>
		<?php } ?>
		<?php if (($auth->user('type') == "Administrador") || ($auth->user('type') == "Administrativo")) {?>
			<li><?php echo $html->link('Modificar permisos', array('action' => 'acl_edit')) ?></li>
		<?php } ?>
		<?php if ($acl->check('events.calendar_by_teacher')) {?>
			<li><?php echo $html->link('Ver agenda del profesorado', array('controller' => 'events', 'action' => 'calendar_by_teacher')) ?></li>
		<?php } ?>
	</ul>
</div>

<div class="view">
	<?php
		echo $form->create('User', array('action' => 'index', 'type' => 'get'))
	?>
		<fieldset>
		<legend>Buscar usuarios</legend>
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
				<th>Nombre completo</th>
				<th>Tipo</th>
				<th>DNI</th>
				<th>Correo electrónico</th>
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
			<?php foreach ($users as $user): ?>
			<tr>
				<td><?php echo $html->link("{$user['User']['last_name']}, {$user['User']['first_name']}", array('controller' => 'users', 'action' => 'view', $user['User']['id'])) ?></td>
				<td><?php echo $user['User']['type'] ?></td>
				<td><?php echo $user['User']['dni'] ?></td>
				<td><?php echo $user['User']['username'] ?></td>
			</tr>
			<?php endforeach; ?>
			
		</tbody>
	</table>
</div>