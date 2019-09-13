<?php $html->addCrumb('Cursos', '/academic_years'); ?>

<h1>Cursos</h1>
<?php if ($auth->user('super_admin')) : ?>
  <div class="actions">
    <ul>
      <?php if ($auth->user('type') == "Administrador"): ?>
		<li><?php echo $html->link('Crear curso', array('action' => 'add')) ?></li>
      <?php endif; ?>
    </ul>
  </div>
<?php endif; ?>
<div class="<?php if ($auth->user('super_admin')): ?>view<?php endif; ?>">
	<div class="horizontal-scrollable-content">
		<table>
			<thead>
				<tr>
					<th>Denominación</th>
					<th>Fecha de comienzo</th>
					<th>Fecha de fin</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($academic_years as $academic_year): ?>
				<tr>
					<td><?php echo $html->link($modelHelper->academic_year_name($academic_year), array('controller' => 'academic_years', 'action' => 'view', $academic_year['AcademicYear']['id'])) ?></td>
					<td><?php echo $academic_year['AcademicYear']['initial_date'] ?></td>
					<td><?php echo $academic_year['AcademicYear']['final_date'] ?></td>
				</tr>
				<?php endforeach; ?>
				
			</tbody>
		</table>
	</div>
</div>