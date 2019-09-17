<?php 
  $html->addCrumb('Cursos', '/academic_years');
  $html->addCrumb($modelHelper->academic_year_name($course), "/academic_years/view/{$course['Course']['academic_year_id']}");
  $html->addCrumb(Environment::institution('name'), Environment::getBaseUrl() . "/courses/index/{$course['Course']['academic_year_id']}");
  $html->addCrumb("{$course['Degree']['name']}", Environment::getBaseUrl() . "/courses/view/{$course['Course']['id']}");
  
  if ($subject) {
    $html->addCrumb($subject['Subject']['name'], Environment::getBaseUrl() . "/subjects/view/{$subject['Subject']['id']}");
    $html->addCrumb('Registros de asistencia', Environment::getBaseUrl() . "/attendance_registers/view_my_registers/$course_id/{$subject['Subject']['id']}");
  } else {
    $html->addCrumb('Registros de asistencia', Environment::getBaseUrl() . "/attendance_registers/view_my_registers/$course_id");
  }
?>

<?php if ($subject): ?>
  <h1>Registros de asistencia de <?php echo $subject['Subject']['name'] ?></h1>
<?php else: ?>
  <h1>Registros de asistencia</h1>
<?php endif; ?>

<table>
  <thead>
    <th>Fecha</th>
    <th>Activitdad</th>
    <th>Asignatura</th>
    <th>Nº de asistentes</th>
  </thead>
  
  <tbody>
    <?php foreach($attendance_registers as $register): 
      $date = date_create($register['Event']['initial_hour']);
    ?>
      <tr>
				<td><?php
            if (empty($register['AttendanceRegister']['secret_code'])) {
              echo $html->link($date->format('d-m-Y'), array('action' => 'edit_student_attendance', $register['Event']['id']));
            } else {
              echo $html->link($date->format('d-m-Y'), array('action' => 'view', $register['AttendanceRegister']['id']));
            }
        ?></td>
				<td><?php echo $register['Activity']['name'] ?></td>
				<td><?php echo $register['Subject']['name'] ?></td>
				<td><?php echo $register[0]['num_students'] ?></td>  
			</tr>
    <?php endforeach;?>
  </tbody>
</table>

