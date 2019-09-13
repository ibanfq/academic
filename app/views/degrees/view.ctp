<!-- File: /app/views/degrees/view.ctp -->

<?php $html->addCrumb('Centros', '/institutions'); ?>
<?php $html->addCrumb($degree['Institution']['name'], "/institutions/view/{$degree['Institution']['id']}"); ?>
<?php $html->addCrumb($degree['Degree']['name'], "/degrees/view/{$degree['Degree']['id']}"); ?>

<h1><?php echo $degree['Degree']['name'] ?></h1>

<?php if ($auth->user('super_admin')) : ?>
  <div class="actions">
    <ul>
      <?php if ($auth->user('type') == "Administrador"): ?>
		<li><?php echo $html->link('Editar titulación', array('action' => 'edit', $degree['Degree']['id'])) ?></li>
		<li><?php echo $html->link('Eliminar titulación', array('action' => 'delete', $degree['Degree']['id']), null, 'Cuando elimina una titulación, elimina también toda su información asociada. ¿Está seguro que desea borrarla?') ?></li>
      <?php endif; ?>
    </ul>
  </div>
<?php endif; ?>
<div class="<?php if ($auth->user('super_admin')): ?>view<?php endif; ?>">
	<fieldset>
	<legend>Titulación</legend>
		<dl>
			<dt>Acrónimo</dt>
			<dd><?php echo $modelHelper->format_acronym($degree['Degree']['acronym']) ?></dd>
		</dl>
		<dl>
			<dt>Denominación</dt>
			<dd><?php echo $degree['Degree']['name'] ?></dd>
		</dl>
	</fieldset>
</div>
