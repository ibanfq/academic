<?php
  $teachers_can_booking = Configure::read('app.classroom.teachers_can_booking');
  $numAttendees = count($booking['Attendee']);
  $userType = $booking['Booking']['user_type'];
  $editable = call_user_func($authorizeEdit, $booking);
  $deletable = call_user_func($authorizeDelete, $booking);
  $institution_id = empty($booking['Classroom']) ? $booking['Booking']['institution_id'] : $booking['Classroom']['institution_id'];
?>
<?php if ($isAjax): ?>
  <h3><?php echo "{$booking['Booking']['reason']}" ?></h3>
  <br/>
  <?php
    $initial_date = date_create($booking['Booking']['initial_hour']);
    $final_date = date_create($booking['Booking']['final_hour']);
  ?>
  <p><strong>Hora de inicio:</strong> <?php echo $initial_date->format('H:i') ?></p>
  <p><strong>Hora de fin:</strong> <?php echo $final_date->format('H:i') ?></p>
  <?php if ($userType === 'Todos'): ?>
    <p><strong>Tipo de asistentes:</strong> Todos los usuarios</p>
  <?php elseif ($userType === 'No-estudiante'): ?>
    <p><strong>Tipo de asistentes:</strong> Todos menos estudiante</p>
  <?php elseif ($userType): ?>
    <p><strong>Tipo de asistentes:</strong> <?php echo $userType ?></p>
  <?php elseif ($numAttendees): ?>
    <p><strong>Asistentes:</strong> <?php echo $numAttendees ?></p>
  <?php endif ?>
  <?php if (isset($auth) && $userType && $numAttendees && ($auth->user('type') == "Administrador" || $auth->user('type') == "Administrativo" || $auth->user('type') == "Conserje")): ?>
    <p><strong>Otros asistentes:</strong> <?php echo $numAttendees ?></p>
  <?php endif; ?>
  <p><strong>Aula:</strong>
    <?php echo $booking['Booking']['classroom_id'] == -1? 'Todas las aulas' : $booking['Classroom']['name']; ?>
  </p>
  <?php if (Configure::read('app.booking.show_tv')): ?>
    <p><strong>Mostrar en TV:</strong>
      <?php echo $booking['Booking']['show_tv']? 'Si' : 'No'; ?>
    </p>
  <?php endif; ?>
  <p><strong>Más información:</strong> <?php echo $booking['Booking']['required_equipment'] ?></p>
  <br />
  <?php if (isset($auth) && (($auth->user('type') == "Administrador" || $auth->user('type') == "Administrativo" || $auth->user('type') == "Conserje") || ($teachers_can_booking && $auth->user('type') == "Profesor" && $booking['Classroom']['teachers_can_booking']))): ?>
  <p class="actions">
    <?php if ($numAttendees): ?>
      <a class="button button-action" href="/institutions/<?php echo $institution_id ?>/bookings/view/<?php echo $booking['Booking']['id'] ?>">Ver asistentes</a>
    <?php endif ?>
    <?php if ($editable): ?>
      <a class="button button-action" href="<?php echo Environment::getBaseUrl() ?>/bookings/edit/<?php echo $booking['Booking']['id'] ?>">Editar</a>
    <?php endif ?>
    <?php if ($deletable): ?>
      <?php if ($editable): ?>o<?php endif ?>
      <a href="javascript:;" onclick="deleteBooking(<?php echo $booking['Booking']['id'] ?>, '<?php echo $booking['Booking']['parent_id']?>')">Eliminar reserva</a>
    <?php endif; ?>
  </p>
  <?php endif; ?>
<?php else: //No ajax ?>
  <?php $html->addCrumb('Reservas', '/institutions/ref:bookings'); ?>
  <?php $html->addCrumb(Environment::institution('name'), array('action' => 'index')); ?>
  <?php $html->addCrumb('Ver reserva', array('action' => 'view', $booking['Booking']['id'])); ?>

  <h1>Reserva</h1>

  <div class="actions">
  <?php if (isset($auth) && (($auth->user('type') == "Administrador") || $auth->user('type') == "Administrativo" || $auth->user('type') == "Conserje" || ($teachers_can_booking && $auth->user('type') == "Profesor" && $booking['Classroom']['teachers_can_booking']))): ?>
    <ul>
      <?php if ($editable): ?>
        <li><?php echo $html->link('Modificar reserva', array('action' => 'edit', $booking['Booking']['id'])) ?></li>
      <?php endif; ?>
      <?php if ($deletable): ?>
        <li><?php echo $html->link('Eliminar reserva', array('action' => 'delete', $booking['Booking']['id'])) ?></li>
      <?php endif; ?>
    </ul>
  <?php endif; ?>
  </div>
  <div class="view">
    <fieldset>
    <legend>Datos de la reserva</legend>
      <dl>
        <dt>Motivo</dt>
        <dd><?php echo $booking['Booking']['reason'] ?></dd>
      </dl>

      <dl>
        <dt>Fecha</dt>
        <dd><?php
          $initial_hour = date_create($booking['Booking']['initial_hour']);
          $final_hour = date_create($booking['Booking']['final_hour']);
          echo $initial_hour->format('d-m-Y'); 
        ?></dd>
      </dl>

      <dl>
        <dt>Hora de inicio</dt>
        <dd><?php echo $initial_hour->format('H:i') ?></dd>
      </dl>

      <dl>
        <dt>Hora de fin</dt>
        <dd><?php echo $final_hour->format('H:i') ?></dd>
      </dl>
    
      <dl>
        <dt>Aula</dt>
        <dd><?php echo $booking['Booking']['classroom_id'] == -1? 'Todas las aulas' : $booking['Classroom']['name']; ?></dd>
      </dl>

      <?php if (Configure::read('app.booking.show_tv')): ?>
        <dl>
          <dt>Mostrar en TV</dt>
          <dd><?php echo $booking['Booking']['show_tv']? 'Si' : 'No'; ?></dd>
        </dl>
      <?php endif; ?>

      <dl>
        <dt>Más información</dt>
        <dd><?php echo "{$booking['Booking']['required_equipment']}" ?></dd>
      </dl>

      <dl>
        <dt>Tipo de asistentes</dt>
        <dd>
          <?php if ($userType === 'Todos'): ?>
            Todos los usuarios
          <?php elseif ($userType === 'No-estudiante'): ?>
            Todos menos estudiante
          <?php elseif ($userType): ?>
            <?php echo $userType ?>
          <?php else: ?>
            Ninguno
          <?php endif; ?>
        </dd>
      </dl>
    
      <dl>
        <dt>Nº de otros asistentes añadidos</dt>
        <dd><?php echo $numAttendees ?></dd>
      </dl>
    </fieldset>

    <fieldset>
    <legend>Asistentes</legend>
      <?php if ($numAttendees): ?>
        <table>
          <thead>
            <tr>
              <th>Asistente</th>
            </tr>
          </thead>
          <tbody id="students">
            <?php foreach ($booking['Attendee'] as $attendee):?>
              <tr>
                <td><?php echo "{$attendee['first_name']} {$attendee['last_name']}" ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p>No se ha especificado ningún asistente a esta reserva.</p>
      <?php endif; ?>
    </fieldset>
  </div>

<?php endif; ?>
