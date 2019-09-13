<!-- File: /app/views/group/view.ctp -->
<?php $html->addCrumb('Cursos', '/academic_years'); ?>
<?php $html->addCrumb($modelHelper->academic_year_name($subject), "/academic_years/view/{$subject['Course']['academic_year_id']}"); ?>
<?php $html->addCrumb(Environment::institution('name'), Environment::getBaseUrl() . "/courses/index/{$subject['Course']['academic_year_id']}"); ?>
<?php $html->addCrumb("{$degree['Degree']['name']}", Environment::getBaseUrl() . "/courses/view/{$subject['Course']['id']}"); ?>
<?php $html->addCrumb($subject['Subject']['name'], Environment::getBaseUrl() . "/subjects/view/{$subject['Subject']['id']}"); ?>
<?php $html->addCrumb($group['Group']['name'], Environment::getBaseUrl() . "/groups/view/{$group['Group']['id']}"); ?>

<h1><?php echo $group['Group']['name'] ?></h1>

<?php if ($auth->user('type') != "Estudiante") : ?>
  <div class="actions">
    <ul>
      <li><?php echo $html->link('Modificar grupo', array('action' => 'edit', $group['Group']['id'])) ?></li>
      <li><?php echo $html->link('Eliminar grupo', array('action' => 'delete', $group['Group']['id']), null, 'Cuando elimina un grupo, elimina también toda su programación asociada. ¿Está seguro que desea borrarlo?') ?></li>
    </ul>
  </div>
<?php endif; ?>

<div class="<?php if ($auth->user('type') != "Estudiante"): ?>view<?php endif; ?>">
	<fieldset>
	<legend>Datos generales</legend>
		<dl>
			<dt>Nombre</dt>
			<dd><?php echo $group['Group']['name']?></dd>
		</dl>
		<dl>
			<dt>Tipo</dt>
			<dd><?php echo $group['Group']['type'] ?></dd>
		</dl>
		<dl>
			<dt>Capacidad</dt>
			<dd><?php echo $group['Group']['capacity'] ?></dd>
		</dl>
		<dl>
			<dt>Observaciones</dt>
			<dd><?php echo $group['Group']['notes'] ?></dd>
		</dl>
	</fieldset>
</div>