<!-- File: /app/views/subjects/add.ctp -->
<?php $html->addCrumb('Cursos', '/courses'); ?>
<?php $html->addCrumb($subject['Course']['name'], "/courses/view/{$subject['Course']['id']}"); ?>
<?php $html->addCrumb($subject['Subject']['name'], "/subjects/view/{$subject['Subject']['id']}"); ?>
<?php $html->addCrumb("Gestionar prácticas repetidores", "/subjects/students_edit/{$subject['Subject']['id']}"); ?>

<h1>Gestionar prácticas de estudiantes repetidores</h1>
<?php
	echo $form->create('Subject', array('action' => 'students_edit'));
	echo $form->input('id', array('type' => 'hidden'));
?>
	<fieldset>
	<legend>Estudiantes</legend>
		<table>
			<thead>
				<tr>
					<th style="width:80%">Estudiante</th>
					<th>Tiene TODAS las prácticas aprobadas</th>
				</th>
			</thead>
			<tbody>
				<?php foreach ($subject['Students'] as $student): ?>
					<tr>
						<td><?php echo "{$student['Student']['first_name']} {$student['Student']['last_name']}"?></td>
						<td><?php echo $form->checkbox("Students.{$student['Student']['id']}.practices_approved", array('value' => '1', 'checked' => (bool)$student['SubjectStudent']['practices_approved'])); ?></td>
					</tr>
				<?php endforeach;?>
			</tbody>
		</table>
	</fieldset>
<?php
	echo $form->end('Modificar');
?>
