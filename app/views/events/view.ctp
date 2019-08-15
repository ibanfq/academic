<?php if (isset($notAllowed)): ?>
    <h3>No tienes permisos para realizar esta acción</h3>
<?php else: ?>
    <h3><?php echo "{$event['Activity']['name']} ({$subject['Subject']['acronym']})" ?></h3>
    <br/>
    <p><strong>Asignatura:</strong> <?php echo $subject['Subject']['name'] ?></p>
    <p><strong>Tipo de actividad:</strong> <?php echo $event['Activity']['type'] ?></p>
    <p><strong>Grupo:</strong> <?php echo $event['Group']['name'] ?></p>
    <?php
        $initial_date = date_create($event['Event']['initial_hour']);
        $final_date = date_create($event['Event']['final_hour']);
    ?>
    <p><strong>Hora de inicio:</strong> <?php echo $initial_date->format('H:i') ?></p>
    <p><strong>Hora de fin:</strong> <?php echo $final_date->format('H:i') ?></p>
    <p>
        <strong>Profesor/es:</strong> 
        <?php echo "{$event['Teacher']['first_name']} {$event['Teacher']['last_name']}"?>
        <?php if ((isset($event['Teacher_2'])) && (isset($event['Teacher_2']['id']))) { ?>
            <?php echo ", {$event['Teacher_2']['first_name']} {$event['Teacher_2']['last_name']}"?>
        <?php } ?>
    </p>
    <p><strong>Aula:</strong> <?php echo $event['Classroom']['name'] ?></p>
    <?php if (Configure::read('app.event.show_tv')): ?>
        <p>
            <strong>Mostrar en TV:</strong>
            <?php echo $event['Event']['show_tv'] ? 'Si' : 'No' ?>
        </p>
    <?php endif; ?>
    <p><strong>Observaciones:</strong> <?php echo $event['Activity']['notes'] ?>
    <br>
    <?php if (isset($auth) && in_array($auth->user('type'), array("Profesor", "Administrador", "Administrativo", "Becario"))): ?>
        <?php echo $this->Form->create('AttendanceRegister', array('action' => 'add_by_event/'.$event['Event']['id'])) ?>
        <?php echo $form->input('Event.id', array('type' => 'hidden', 'value' => $event['Event']['id'])); ?>
        <div class="actions">
            <ul>
                <li><a class="button" target="_blank" href="<?php echo PATH ?>/attendance_registers/print_attendance_file/<?php echo $event['Event']['id'] ?>">Imprimir hoja de asistencia</a></li>
                <?php
                $today = new DateTime("today");
                $isToday = $today->format('Ymd') === $initial_date->format('Ymd');
                $teachers = array($event['Event']['teacher_id'], $event['Event']['teacher_2_id']);
                if ($isToday && ($auth->user('type') !== "Profesor" || in_array($auth->user('id'), $teachers))):
                ?>
                    <li><input type="submit" value="Crear asistencias" /></li>
                <?php endif; ?>
            </ul>
        </div>
        <?php echo $this->Form->end() ?>
    <?php endif; ?>
<?php endif; ?>