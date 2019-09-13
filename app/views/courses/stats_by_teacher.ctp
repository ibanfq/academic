<!-- File: /app/views/courses/view.ctp -->

<?php $html->addCrumb('Cursos', '/academic_years'); ?>
<?php $html->addCrumb($modelHelper->academic_year_name($course), "/academic_years/view/{$course['Course']['academic_year_id']}"); ?>
<?php $html->addCrumb(Environment::institution('name'), Environment::getBaseUrl() . "/courses/index/{$course['Course']['academic_year_id']}"); ?>
<?php $html->addCrumb("{$course['Degree']['name']}", Environment::getBaseUrl() . "/courses/view/{$course['Course']['id']}"); ?>
<?php $html->addCrumb("Estadísticas por profesor", Environment::getBaseUrl() . "/courses/stats_by_teacher/{$course['Course']['id']}"); ?>

<h1>Estadísticas por profesor</h1>
<div class="view">
  <fieldset>
		<legend>Asignaturas</legend>
		<div class="horizontal-scrollable-content">
			<table>
				<thead>
					<tr>
						<th>Profesor</th>
						<th>Horas teóricas</th>
						<th>Horas prácticas</th>
						<th>Otras horas</th>
						<th>Total</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($teachers as $teacher): ?>
						<?php $total = ($teacher[0]['teorical'] + $teacher[0]['practice'] + $teacher[0]['others']) ?>
						<?php if ($total): ?>
							<tr>
								<td><?php echo $html->link("{$teacher['Teacher']['first_name']} {$teacher['Teacher']['last_name']}", array('controller' => 'users', 'action' => 'teacher_stats_details', $teacher['Teacher']['id'], '?' => array('course_id' => $course['Course']['id']))) ?></td>
								<td><?php echo $teacher[0]['teorical']?></td>
								<td><?php echo $teacher[0]['practice']?></td>
								<td><?php echo $teacher[0]['others']?></td>
								<td><?php echo $total ?></td>
							</tr>
						<?php endif ?>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</fieldset>
</div>
					
