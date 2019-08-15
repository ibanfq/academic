<?php if (count($subjects_as_coordinator) > 0) { ?>
	<fieldset>
	  <legend>Asignaturas como coordinador</legend>
    <table>
      <thead>
        <tr>
          <th>Código</th>
          <th>Nombre</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($subjects_as_coordinator as $subject): ?>
          <tr>
            <td><?php echo $html->link($subject["Subject"]['code'], array('controller' => 'subjects', 'action' => 'view', $subject["Subject"]['id'])) ?></td>
            <td><?php echo $subject["Subject"]["name"] ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
	</fieldset>
<?php } ?>

<?php if (count($subjects_as_practice_responsible) > 0) { ?>
	<fieldset>
	  <legend>Asignaturas como responsable de prácticas</legend>
    <table>
      <thead>
        <tr>
          <th>Código</th>
          <th>Nombre</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($subjects_as_practice_responsible as $subject): ?>
          <tr>
            <td><?php echo $html->link($subject["Subject"]['code'], array('controller' => 'subjects', 'action' => 'view', $subject["Subject"]['id'])) ?></td>
            <td><?php echo $subject["Subject"]["name"] ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
	</fieldset>
<?php } ?>

<?php if (count($total_hours) > 0) { ?>
  <fieldset>
    <legend>Resumen de horas</legend>
    <dl>
      <dt>Nº total de horas impartidas:</dt>
      <dd><?php 
        if ($total_hours[0][0]['total'] == null)
          echo "0.00";
        else
          echo $total_hours[0][0]['total'];
      ?></dd>
    </dl>
    <dl>
      <dt>Nº de horas teóricas:</dt>
      <dd><?php 
        if ($teorical_hours[0][0]['total'] == null)
          echo "0.00";
        else
          echo $teorical_hours[0][0]['total'];
      ?></dd>
    </dl>
    <dl>
      <dt>Nº de horas prácticas:</dt>
      <dd><?php 
        if ($practical_hours[0][0]['total'] == null)
          echo "0.00";
        else
          echo $practical_hours[0][0]['total'];
      ?></dd>
    </dl>
    <dl>
      <dt>Nº otras horas:</dt>
      <dd><?php 
        if ($other_hours[0][0]['total'] == null)
          echo "0.00";
        else
          echo $other_hours[0][0]['total'];
      ?></dd>
    </dl>
  </fieldset>
<?php } ?>

<?php if (count($hours_group_by_subject) > 0) { ?>
  <fieldset>
    <legend>Desglose de horas por asignatura</legend>
    
    <table>
        <thead>
          <tr>
            <th>Código</th>
            <th>Nombre</th>
            <th>Nº de horas teóricas</th>
            <th>Nº de horas prácticas</th>
            <th>Nº otras horas</th>
            <th>Nº total de horas</th>
          </tr>
        </thead>
        <tbody>
            <?php foreach($hours_group_by_subject as $subject): ?>
                <?php
                  $teorical = $practice = $others = 0;
                  
                  if (isset($subject['T'])) 
                    $teorical = $subject['T']; 
                  
                  if (isset($subject['P'])) 
                    $practice = $subject['P'];
                
                  if (isset($subject['O'])) 
                    $others = $subject['O'];
                ?>
                <tr>
                  <td><?php echo $subject['code']?></td>
                  <td><?php echo $subject['name']?></td>
                  <td><?php echo $teorical ?></td>
                  <td><?php echo $practice ?></td>
                  <td><?php echo $others ?></td>
                  <td><?php echo $teorical + $practice + $others ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
  </fieldset>
<?php } ?>

<?php if (count($registers) > 0) { ?>
  <fieldset>
    <legend>Desglose de eventos</legend>
    <table>
      <thead>
        <tr>
          <th>Fecha</th>
          <th>Asignatura</th>
          <th>Actividad</th>
          <th>Nº de horas</th>
        </tr>
      </thead>
      <tbody>
        <?php 
          $total = 0.0;
          foreach ($registers as $register): 
            $date = date_create($register['AttendanceRegister']['initial_hour']);
        ?>
          <tr>
            <td><?php echo $html->link($date->format('d-m-Y'), array('controller' => 'attendance_registers', 'action' => 'view', $register['AttendanceRegister']['id'])) ?></td>
            <td><?php echo $register['Subject']['code'] ?></td>
            <td><?php echo $register['Activity']['name'] ?></td>
            <td><?php echo $register['AttendanceRegister']['duration'] ?></td>
            <?php $total += $register['AttendanceRegister']['duration'] ?>
          </tr>
        <?php endforeach; ?>

      </tbody>
      <tfoot>
        <tr>
          <td></td>
          <td></td>
          <td align="right"><strong>TOTAL:</strong></td>
          <td><?php echo $total ?></td>
        </tr>
      </tfoot>
    </table>
  </fieldset>
<?php } ?>