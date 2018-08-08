<?php 
	$html->addCrumb('Cursos', '/courses'); 
	$html->addCrumb($subject['Course']['name'], "/courses/view/{$subject['Course']['id']}"); 
	$html->addCrumb($subject['Subject']['name'], "/subjects/view/{$subject['Subject']['id']}"); 
	$html->addCrumb("Estadísticas estudiantes", "/subjects/students_stats/{$subject['Subject']['id']}");
?>

<h1>Estadísticas estudiantes (se muestra la duración planificada)</h1>
<div class="view">
  <fieldset>
	  <legend>Estudiantes</legend>
		<table>
			<thead>
				<tr>
					<th>Estudiante</th>
					<th>Horas teóricas</th>
					<th>Horas prácticas</th>
					<th>Otras horas</th>
					<th>Total</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($students as $student): ?>
					<tr>
            <?php if ($auth->user('type') == "Estudiante"): ?>
              <td><?php echo "{$student['Student']['first_name']} {$student['Student']['last_name']}" ?></td>
            <?php else: ?>
              <td><?php echo $html->link("{$student['Student']['first_name']} {$student['Student']['last_name']}", array('controller' => 'users', 'action' => 'student_stats_details', $student['Student']['id'], '?' => array('course_id' => $subject['Course']['id'], 'subject_id' => $subject['Subject']['id']))) ?></td>
            <?php endif; ?>
					  <td><?php echo $student[0]['teorical']?></td>
					  <td><?php echo $student[0]['practice']?></td>
					  <td><?php echo $student[0]['others']?></td>
					  <td><?php echo ($student[0]['teorical'] + $student[0]['practice'] + $student[0]['others'])?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</fieldset>
</div>
					
