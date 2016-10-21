<!-- File: /app/views/courses/view.ctp -->

<?php $html->addCrumb('Cursos', '/courses'); ?>
<?php $html->addCrumb("{$course['Course']['name']}", "/courses/view/{$course['Course']['id']}"); ?>
<?php $html->addCrumb("Estadísticas por profesor", "/courses/stats_by_teacher/{$course['Course']['id']}"); ?>

<h1>Estadísticas por profesor</h1>
<div class="view">
  <fieldset>
	  <legend>Asignaturas</legend>
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
	</fieldset>
</div>
					
