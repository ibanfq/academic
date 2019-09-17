<!-- File: /app/views/institutions/view.ctp -->

<?php $html->addCrumb('Centros', '/institutions'); ?>
<?php $html->addCrumb($institution['Institution']['name'], "/institutions/view/{$institution['Institution']['id']}"); ?>

<h1><?php echo $institution['Institution']['name'] ?></h1>

<?php if ($auth->user('super_admin')) : ?>
  <div class="actions">
    <ul>
      <?php if ($auth->user('type') == "Administrador"): ?>
		<li><?php echo $html->link('Crear titulación', array('controller' => 'degrees', 'action' => 'add', $institution['Institution']['id'])) ?></li>
		<li><?php echo $html->link('Editar centro', array('action' => 'edit', $institution['Institution']['id'])) ?></li>
		<li><?php echo $html->link('Eliminar centro', array('action' => 'delete', $institution['Institution']['id']), null, 'Cuando elimina un centro, elimina también toda su información asociada. ¿Está seguro que desea borrarlo?') ?></li>
      <?php endif; ?>
    </ul>
  </div>
<?php endif; ?>
<div class="<?php if ($auth->user('super_admin')): ?>view<?php endif; ?>">
	<fieldset>
	<legend>Centro</legend>
		<dl>
			<dt>Acrónimo</dt>
			<dd><?php echo $modelHelper->format_acronym($institution['Institution']['acronym']) ?></dd>
		</dl>
		<dl>
			<dt>Denominación</dt>
			<dd><?php echo $institution['Institution']['name'] ?></dd>
		</dl>
	</fieldset>

	<fieldset>
	<legend>Títulaciones</legend>
		<div class="horizontal-scrollable-content">
			<table>
				<thead>
					<tr>
						<th>Acrónimo</th>
						<th>Nombre</th>
					</tr>
				</thead>
				<tbody>
					<?php if (isset($institution['Degree'])): ?>
						<?php foreach ($institution['Degree'] as $degree): ?>
							<?php $url = array('controller' => 'degrees', 'action' => 'view', $degree['id']); ?>
							<tr>
								<td><?php echo $html->link($modelHelper->format_acronym($degree['acronym']), $url) ?></td>
								<td><?php echo $html->link($degree['name'], $url); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</fieldset>

	<fieldset>
	<legend>Administradores</legend>
		<div class="horizontal-scrollable-content">
			<table>
				<thead>
					<tr>
						<th>Nombre completo</th>
						<th>DNI</th>
						<th>Correo electrónico</th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ($administrators as $user): ?>
					<tr>
						<td><?php echo $html->link("{$user['User']['last_name']}, {$user['User']['first_name']}", $html->url(array('institution' => $institution['Institution']['id'], 'controller' => 'users', 'action' => 'view', $user['User']['id'], 'base' => false))) ?></td>
						<td><?php echo $user['User']['dni'] ?></td>
						<td><?php echo $user['User']['username'] ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</fieldset>
</div>
