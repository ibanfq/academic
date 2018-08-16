<?php 
	$html->addCrumb('Cursos', '/courses'); 
	$html->addCrumb($subject['Course']['name'], "/courses/view/{$subject['Course']['id']}");
	$html->addCrumb($subject['Subject']['name'], "/subjects/view/{$subject['Subject']['id']}");
  $html->addCrumb($activity['Activity']['name'], "/activities/view/{$activity['Activity']['id']}"); 
	$html->addCrumb("Estadísticas estudiantes", "/activities/students_stats/{$activity['Activity']['id']}");
?>

<h1><?php echo "{$activity['Activity']['name']} - Estadísticas estudiantes" ?></h1>
<fieldset>
  <legend>Estudiantes</legend>
  <table>
    <thead>
      <tr>
        <th>Estudiante</th>
        <th>Fecha asistencia</th>
        <th>Grupo apuntado</th>
        <th>Profesor que la impartió</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($registers as $register):
        $initial_hour = empty($register['UserAttendanceRegister']['user_gone'])? null : date_create($register['Event']['initial_hour']);
        $withoutAttendenceRegisters = (!isset($prevStudent) || $prevStudent !== $register['Student']['id']) && !$initial_hour;
        $prevStudent = $register['Student']['id'];
        if ($initial_hour || $withoutAttendenceRegisters) :
      ?>
        <tr>
          <td><?php echo "{$register['Student']['first_name']} {$register['Student']['last_name']}" ?></td>
          <td><?php echo $initial_hour ? $initial_hour->format('d-m-Y') : '' ?></td>
          <td><?php echo $initial_hour ? $register['Group']['name'] : $register['RegistrationGroup']['name'] ?></td>
          <td><?php
            if ($initial_hour):
              echo "{$register['Teacher']['first_name']} {$register['Teacher']['last_name']}";
              if (!empty($register['Teacher2']['first_name'])):
                echo " | {$register['Teacher2']['first_name']} {$register['Teacher2']['last_name']}";
              endif;
            endif;
          ?></td>
        </tr>
      <?php endif; endforeach; ?>
    </tbody>
  </table>
</fieldset>
					
