<?php $initial_date = date_create($event['Event']['initial_hour']); ?>
Hola

Hemos detectado que no se ha registrado en Academic la impartición del evento “<?php echo $event['Activity']['name'] ?>”, programado el “<?php echo $initial_date->format('d/m/Y') ?>” a las “<?php echo $initial_date->format('H:i') ?>”.

Rogamos que en el caso de que haya olvidado registrar la impartición de dicho evento se ponga en contacto con el Decanato entregando evidencia de su impartición: "listado de asistencia de los estudiantes en formato papel". En caso contrario se entenderá que dicho evento no ha sido impartido.

Un saludo,
El equipo de Academic.