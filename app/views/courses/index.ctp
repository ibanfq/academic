<?php $html->addCrumb('Cursos', '/academic_years'); ?>
<?php $html->addCrumb($modelHelper->academic_year_name($academic_year), "/academic_years/view/{$academic_year['AcademicYear']['id']}"); ?>
<?php $html->addCrumb(Environment::institution('name'), Environment::getBaseUrl() . "/courses/index/{$academic_year['AcademicYear']['id']}"); ?>

<h1>Titulaciones</h1>
<?php if ($auth->user('type') == "Administrador") : ?>
  <div class="actions">
    <ul>
      <li><?php echo $html->link('Añadir titulación', array('action' => 'add', $academic_year['AcademicYear']['id'])) ?></li>
    </ul>
  </div>
<?php endif; ?>
<div class="<?php if ($auth->user('type') == "Administrador"): ?>view<?php endif; ?>">
	<div class="horizontal-scrollable-content">
		<table>
			<thead>
				<tr>
					<th>Acronym</th>
					<th>Nombre</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($courses as $course): ?>
				<tr>
					<?php $url = array('controller' => 'courses', 'action' => 'view', $course['Course']['id']); ?>
					<td><?php echo $html->link($modelHelper->format_acronym($course['Degree']['acronym']), $url) ?></td>
					<td><?php echo $html->link($course['Degree']['name'], $url) ?></td>
				</tr>
				<?php endforeach; ?>
				
			</tbody>
		</table>
	</div>
</div>