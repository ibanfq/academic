<!-- File: /app/views/users/view.ctp -->

<?php $html->addCrumb('Cursos', '/academic_years'); ?>
<?php
	if (! empty($academic_year)) {
		$html->addCrumb($modelHelper->academic_year_name($academic_year), "/academic_years/view/{$academic_year['id']}");
	}
?>
<?php $html->addCrumb('Mis asignaturas', '/users/my_subjects'); ?>

<h1>Mis asignaturas</h1>

<?php if (count($subjects) > 0): ?>
	<table>
		<thead>
			<tr>
				<th>Asignatura</th>
				<th>Titulación</th>
				<th>Curso</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($subjects as $subject): ?>
				<tr>
					<?php if ($auth->user('type') == 'Estudiante'): ?>
						<td><?php echo $html->link($subject['Subject']['name'], array('institution' => $subject['Course']['institution_id'], 'controller' => 'events', 'action' => 'register_student', $subject['Subject']['id'])) ?></td>
						<td><?php echo $subject['Degree']['name'] ?></td>
					<?php else: ?>
						<td><?php echo $html->link($subject['Subject']['name'], array('institution' => $subject['Course']['institution_id'], 'controller' => 'subjects', 'action' => 'view', $subject['Subject']['id'])) ?></td>
						<td><?php echo $html->link($subject['Degree']['name'], array('institution' => $subject['Course']['institution_id'], 'controller' => 'courses', 'action' => 'view', $subject['Course']['id'])) ?></td>
					<?php endif; ?>
					<td><?php echo $subject['Subject']['level'] ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
  </table>
<?php elseif ($auth->user('type') == 'Estudiante'): ?>
	<p>Actualmente no figura matriculado en ninguna asignatura. Si considera que estos datos son incorrectos, le rogamos que se ponga en contacto con la dirección del centro.</p>
<?php else: ?>
	<p>Actualmente no figura como profesor en ninguna asignatura. Si considera que estos datos son incorrectos, le rogamos que se ponga en contacto con la dirección del centro.</p>
<?php endif ?>