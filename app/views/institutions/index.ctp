<?php
	if ($ref === 'classrooms') {
		$html->addCrumb('Aulas', $html->url());	
	} elseif ($ref === 'users') {
		$html->addCrumb('Usuarios', $html->url());	
	} elseif ($ref === 'attendance_registers') {
		$html->addCrumb('Registros de impartición', $html->url());	
	} elseif ($ref === 'bookings') {
		$html->addCrumb('Reservas', $html->url());	
	} else {
		$html->addCrumb('Centros', $html->url());	
	}
?>

<h1>Centros docentes</h1>
<?php if ((!$ref || $ref === 'users') && $auth->user('super_admin')) : ?>
  <div class="actions">
    <ul>
	  <?php if ($auth->user('type') == "Administrador"): ?>
	  	<?php if (! $ref): ?>
			<li><?php echo $html->link('Crear centro', array('action' => 'add')) ?></li>
		<?php endif; ?>
		<?php if ($ref === 'users'): ?>
			<li><?php echo $html->link('Ver todos los usuarios', array('controller' => 'users')) ?></li>
		<?php endif; ?>
      <?php endif; ?>
    </ul>
  </div>
<?php endif; ?>
<div class="<?php if ((!$ref || $ref === 'users') && $auth->user('super_admin')): ?>view<?php endif; ?>">
	<?php if ($auth->user('super_admin')): ?>
		<?php echo $form->create('User', array('url' => $this->Html->url(null, true), 'type' => 'get')) ?>
			<fieldset>
			<legend>Buscar centro</legend>
				<?php echo $form->text('q', array('value' => $q)); ?>
			</fieldset>
		<?php echo $form->end('Buscar'); ?>
	<?php else: ?>
		<fieldset>
		<legend>Tus centros</legend>
	<?php endif; ?>

	<?php if (empty($institutions) && ! $auth->user('super_admin')): ?>
		No estás registrado en ningún centro
	<?php else: ?>
		<div class="horizontal-scrollable-content">
			<table>
				<thead>
					<tr>
						<th>Acrónimo</th>
						<th>Código</th>
						<th>Denominación</th>
					</tr>
				</thead>
				<?php if ($paginator->hasPage('Institution', 2)): ?>
					<tfoot>
						<tr>
							<!-- Shows the next and previous links -->
							<?php
								$paginator->options(array('url' => array('academic_year' => isset($academic_year) ? $academic_year['AcademicYear']['id'] : null, 'ref' => $ref, 'q'=>$q)));
								echo $paginator->prev('« Anterior ', null, null, array('class' => 'disabled'));
								echo "&nbsp";
								echo $paginator->numbers();
								echo "&nbsp";
								echo $paginator->next(' Siguiente »', null, null, array('class' => 'disabled'));
							?>
						</tr>
					</tfoot>
				<?php endif; ?>
				<tbody>
					<?php foreach ($institutions as $institution): ?>
					<tr>
						<?php
							if ($ref) {
								$url = array('controller' => $ref, 'action' => 'index', 'institution' => $institution['Institution']['id'], 'base' => false);
							} else {
								$url = array('controller' => 'institutions', 'action' => 'view', $institution['Institution']['id']);
							}
						?>
						<td><?php echo $html->link($modelHelper->format_acronym($institution['Institution']['acronym']), $url) ?></td>
						<td><?php echo $html->link($institution['Institution']['code'], $url) ?></td>
						<td><?php
							echo $html->link($institution['Institution']['name'], $url)
						?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>

	<?php if (! $auth->user('super_admin')): ?>
		</fieldset>
	<?php endif; ?>
</div>