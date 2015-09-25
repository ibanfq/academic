<!-- File: /app/views/users/stats.ctp -->
<?php $html->addCrumb('Cursos', '/courses'); ?>
<?php $html->addCrumb("{$course[0]['Course']['name']}", "/courses/view/{$course[0]['Course']['id']}"); ?>
<?php $html->addCrumb("Estadísticas por aula", "/classrooms/stats/{$course[0]['Course']['id']}"); ?>

    
<?php if (!(isset($classroom))) { ?>
  <h1>Ver estadísticas por aula</h1>
  
  <form action="/veterinaria/v2/classrooms/stats" method="get">
    <fieldset>
      <dl>
        <dt>Aula</dt>
        <dd><?php echo $form->select('classrooms', $classrooms); ?>
      <dl>
      <input name="course_id" type="hidden" value="<?php echo $course[0]['Course']['id']?>"/>
    </fieldset>

    <fieldset class="submit">
      <input type="submit" value="Calcular estadísticas" />
    </fieldset>
  </form>
<?php } else { ?>
  <div>
    <h1>
      Estadísticas del <?php echo $classroom['Classroom']['name']?> - <?php echo $classroom['Classroom']['type'] ?>
    </h1>

    <?php echo $html->link('Ver otra aula', array('action' => 'stats', 'controller' => 'classrooms', $course[0]['Course']['id'])) ?>
    
  </div>

  <?php if (count($stats) > 0) { ?>
  	<table>
  	  <thead>
  	    <tr>
  	      <th>Asignatura</th>
  	      <th>Profesor</th>
  	      <th>Nº de horas</th>
  	      <th>Nº de estudiantes</th>
  	      <th>Promedio del grupo</th>
  	    </tr>
  	  </thead>
  
  	  <tbody>
  	    <?php 
  	      $hours = 0;
  	      $students = 0;
  	      $events = 0;
  	    ?>
  	    <?php foreach($stats as $stat): ?>
  	      <?php if ($stat[0]['num_hours'] > 0) { ?>
    	      <tr>
    	        <?php
    	          $hours += $stat[0]['num_hours'];
    	          $students += $stat[0]['num_students'];
    	          $events += $stat[0]['num_events'];
    	        ?>
    	        <td><?php echo $html->link($stat["Subject"]["name"], array('action' => 'view', 'controller' => 'subjects', $stat["Subject"]["id"])) ?></td>
    	        <td><?php echo "{$stat['User']['first_name']} {$stat['User']['last_name']}"?></td>
    	        <td><?php echo "{$stat[0]['num_hours']}"?></td>
    	        <td><?php echo "{$stat[0]['num_students']}"?></td>
    	        <?php $average = round(($stat[0]['num_students'] / $stat[0]['num_events']) * 100) / 100; ?>
    	        <td><?php echo $average?></td>
    	      </tr>
    	    <?php } ?>
  	    <?php endforeach;?>
  	  </tbody>
  
  	  <tfoot>
  	      <tr>
  		      <td colspan="2" style="text-align:right"><strong>TOTALES</strong></td>
  		      <td><?php echo $hours ?></td>
  		      <td><?php echo $students ?></td>
  		      <?php if ($events == 0) $events = 1; ?>
  		      <td><?php echo round(($students / $events) * 100) / 100 ?></td>
  		    </tr>
  	  </tfoot>
  	</table>
  <?php } else { ?>
    <br />
    <p>No se han encontrado registros de actividades en el Aula.</p>
  <?php } ?>
<?php } ?>
