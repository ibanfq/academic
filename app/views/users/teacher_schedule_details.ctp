<fieldset>
  <legend>Horario</legend>
	<table>
		<thead>
			<tr>
				<th>Fecha</th>
				<th>Asignatura</th>
				<th>Actividad</th>
        <th>Inicio</th>
        <th>Fin</th>
				<th>Nº de horas</th>
			</tr>
		</thead>
		<tbody>
			<?php 
			  $total = 0.0;
				foreach ($events as $event): 
					$initial_hour = date_create($event['Event']['initial_hour']);
          $final_hour = date_create($event['Event']['final_hour']);
			?>
				<tr>
          <td><a href="#" data-id="<?php echo $event['Event']['id'] ?>" class="event"><?php echo $initial_hour->format('d-m-Y') ?></a></td>
					<td><?php echo $event['Subject']['code'] ?></td>
					<td><?php echo $event['Activity']['name'] ?></td>
          <td><?php echo $initial_hour->format('H:i') ?></td>
          <td><?php echo $final_hour->format('H:i') ?></td>
					<td><?php echo $event['Event']['duration'] ?></td>
					<?php $total += $event['Event']['duration'] ?>
				</tr>
			<?php endforeach; ?>

		</tbody>
		<tfoot>
		  <tr>
		    <td></td>
		    <td></td>
        <td></td>
		    <td colspan="2" align="right"><strong>TOTAL:</strong></td>
		    <td><?php echo $total ?></td>
		  </tr>
		</tfoot>
	</table>
</fieldset>