<?php $html->addCrumb('Cursos', '/academic_years'); ?>
<?php $html->addCrumb($modelHelper->academic_year_name($subject), "/academic_years/view/{$subject['Course']['academic_year_id']}"); ?>
<?php $html->addCrumb(Environment::institution('name'), Environment::getBaseUrl() . "/courses/index/{$subject['Course']['academic_year_id']}"); ?>
<?php $html->addCrumb("{$subject['Degree']['name']}", Environment::getBaseUrl() . "/courses/view/{$subject['Subject']['course_id']}"); ?>
<?php $html->addCrumb($subject['Subject']['name'], Environment::getBaseUrl() . "/subjects/view/{$subject['Subject']['id']}"); ?>
<?php $html->addCrumb($activity['Activity']['name'], Environment::getBaseUrl() . "/activities/view/{$activity['Activity']['id']}"); ?>
<?php $html->addCrumb("Estadísticas estudiantes", Environment::getBaseUrl() . "/activities/students_stats/{$activity['Activity']['id']}"); ?>

<h1><?php echo "{$activity['Activity']['name']} - Estadísticas estudiantes" ?></h1>
<fieldset>
  <legend>Estudiantes</legend>
  <table>
    <thead>
      <tr>
        <th>Estudiante</th>
        <th>Asignatura vinculada</th>
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
          <td><?php
							if (!empty($subjects_users[$register['Student']['id']]['ChildSubject']['code'])) {
								echo strpos($subjects_users[$register['Student']['id']]['ChildSubject']['name'], $subjects_users[$register['Student']['id']]['ChildSubject']['code']) === false
									? "{$subjects_users[$register['Student']['id']]['ChildSubject']['code']} {$subjects_users[$register['Student']['id']]['ChildSubject']['name']}"
									: $subjects_users[$register['Student']['id']]['ChildSubject']['name'];
							}
          ?></td>
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
					
