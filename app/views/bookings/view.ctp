<?php
  $numAttendees = count($booking['Attendee']);
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
  <?php if ($numAttendees): ?>
    <p><strong>Asistentes:</strong> <?php echo $numAttendees ?></p>
  <?php endif ?>
  <p><strong>Aula:</strong>
    <?php echo $booking['Booking']['classroom_id'] == -1? 'Todas las aulas' : $booking['Classroom']['name']; ?>
  </p>
  <p><strong>Más información:</strong> <?php echo $booking['Booking']['required_equipment'] ?></p>
  <br />
  <?php if (isset($auth) && (($auth->user('type') == "Administrador") || ($auth->user('type') == "Conserje"))): ?>
  <p class="actions">
    <?php if ($numAttendees): ?>
      <a class="button button-action" href="<?php echo PATH ?>/bookings/view/<?php echo $booking['Booking']['id'] ?>">Ver asistentes</a>
    <?php endif ?>
    <?php if ($auth->user('type') == "Administrador" || $auth->user('id') == $booking['Booking']['user_id']): ?>
      <a class="button button-action" href="<?php echo PATH ?>/bookings/edit/<?php echo $booking['Booking']['id'] ?>">Editar</a>
      o
    <?php endif ?>
    <a href="javascript:;" onclick="deleteBooking(<?php echo $booking['Booking']['id'] ?>, '<?php echo $booking['Booking']['parent_id']?>')">Eliminar reserva</a>
  </p>
  <?php endif; ?>
<?php else: //No ajax ?>
  <?php
    $html->addCrumb('Reservas', '/bookings'); 
    $html->addCrumb('Ver reserva', "/bookings/view/{$booking['Booking']['id']}"); 
  ?>

  <h1>Reserva</h1>

  <div class="actions">
  <?php if (isset($auth) && (($auth->user('type') == "Administrador") || ($auth->user('type') == "Conserje"))): ?>
    <ul>
      <?php if ($auth->user('type') == "Administrador" || $auth->user('id') == $booking['Booking']['user_id']): ?>
        <li><?php echo $html->link('Modificar reserva', array('action' => 'edit', $booking['Booking']['id'])) ?></li>
      <?php endif; ?>
      <li><?php echo $html->link('Eliminar reserva', array('action' => 'delete', $booking['Booking']['id'])) ?></li>
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

      <dl>
        <dt>Más información</dt>
        <dd><?php echo "{$booking['Booking']['required_equipment']}" ?></dd>
      </dl>

      <dl>
        <dt>Nº de asistentes</dt>
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
    </fielset>
  </div>

<?php endif; ?>
