<!-- File: /app/views/academic_years/view.ctp -->

<?php $html->addCrumb('Cursos', '/academic_years'); ?>
<?php $html->addCrumb($modelHelper->academic_year_name($academic_year), "/academic_years/view/{$academic_year['AcademicYear']['id']}"); ?>

<h1><?php echo "{$modelHelper->academic_year_name($academic_year)} ({$academic_year['AcademicYear']['initial_date'] } - {$academic_year['AcademicYear']['final_date'] })" ?></h1>

<?php if ($auth->user('super_admin')) : ?>
  <div class="actions">
    <ul>
      <?php if ($auth->user('type') == "Administrador"): ?>
		<li><?php echo $html->link('Editar curso', array('action' => 'edit', $academic_year['AcademicYear']['id'])) ?></li>
		<li><?php echo $html->link('Eliminar curso', array('action' => 'delete', $academic_year['AcademicYear']['id']), null, 'Cuando elimina un curso, elimina también toda su información asociada. ¿Está seguro que desea borrarlo?') ?></li>
      <?php endif; ?>
    </ul>
  </div>
<?php endif; ?>
<div class="<?php if ($auth->user('super_admin')): ?>view<?php endif; ?>">
	<?php if ($auth->user('super_admin')): ?>
		<?php echo $form->create('Institution', array('url' => $this->Html->url(null, true), 'type' => 'get')) ?>
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
						<th>Nombre</th>
					</tr>
				</thead>
				<?php if ($paginator->hasPage('Institution', 2)): ?>
					<tfoot>
						<tr>
							<!-- Shows the next and previous links -->
							<?php
								$paginator->options(array('url' => array($academic_year['AcademicYear']['id'], 'q'=>$q)));
								echo $paginator->prev('« Anterior ', null, null, array('class' => 'disabled'));
								echo "&nbsp";
								echo $paginator->numbers();
								echo "&nbsp";
								echo $paginator->next(' Siguiente »', null, null, array('class' => 'disabled'));
							?>
						</tr>
					</tfoot>
				<?php endif ?>
				<tbody>
					<?php foreach ($institutions as $institution): ?>
					<?php $institution_route = array('institution' => $institution['Institution']['id'], 'controller' => 'courses', 'action' => 'index', $academic_year['AcademicYear']['id'], 'base' => false) ?>
					<tr>
						<td><?php echo $html->link($modelHelper->format_acronym($institution['Institution']['acronym']), $institution_route) ?></td>
						<td><?php echo $html->link($institution['Institution']['name'], $institution_route) ?></td>
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
