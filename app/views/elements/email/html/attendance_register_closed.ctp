<p>
	Hola:<br />
	Desde Academic te informarmos que uno de tus eventos ha sido registrado como impartido.
</p>
<p>
  <?php $initial_date = date_create($attendanceRegister['AttendanceRegister']['initial_hour']); ?>
  Fecha: <?php echo $initial_date->format('d/m/Y H:i') ?><br />
	Nombre actividad: <?php echo $attendanceRegister['Activity']['name'] ?><br />
  Aula: <?php echo $attendanceRegister['Classroom']['name'] ?><br />
</p>
<p>Con los siguientes asistentes:</p>
<p>
  <?php foreach ($attendanceRegister['Students'] as $student): ?>
    <?php echo "{$student['Student']['last_name']} {$student['Student']['first_name']}<br />"; ?>
  <?php endforeach; ?>
</p>
<p>Un saludo,<br />El equipo de Academic.</p>