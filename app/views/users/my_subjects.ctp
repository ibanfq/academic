<!-- File: /app/views/users/view.ctp -->
<?php $html->addCrumb('Mis asignaturas', '/users/my_subjects'); ?>

<h1>Mis asignaturas</h1>

<?php if (count($subjects) > 0) { ?>
	<table>
		<thead>
			<tr>
				<th>Asignatura</th>
				<th>Curso</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($subjects as $subject): ?>
				<tr>
					<td><?php echo $html->link($subject['Subject']['name'], array('controller' => 'events', 'action' => 'register_student', $subject['Subject']['id'])) ?></td>
					<td><?php echo $subject['Subject']['level'] ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
  </table>
<?php } else { ?>
	<p>Actualmente no figura matriculado en ninguna asignatura. Si considera que estos datos son incorrectos, le rogamos que se ponga en contacto con la dirección del centro.</p>
<?php } ?>