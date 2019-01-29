Hola:
Desde Academic te informamos que el estudiante "<?php echo $user_2['first_name'] ?> <?php echo $user_2['last_name'] ?>" te solicita que le cambies el grupo.

Asignatura: <?php echo $subject['name'] ?>
Nombre actividad: <?php echo $activity['name'] ?>
Tu grupo elegido: <?php echo $group['name'] ?>
El grupo propuesto: <?php echo $group_2['name'] ?>

Para proceder a confirmar o cancelar el cambio accede al siguiente enlace: <?php echo $this->Html->url('/registrations/view_students_registered/'.$subject['id'].'/'.$group_2['id'], true) ?>

Un saludo,
El equipo de Academic.