<?php $html->addCrumb('Cursos', '/academic_years'); ?>
<?php $html->addCrumb($modelHelper->academic_year_name($subject), "/academic_years/view/{$subject['Course']['academic_year_id']}"); ?>
<?php $html->addCrumb(Environment::institution('name'), Environment::getBaseUrl() . "/courses/index/{$subject['Course']['academic_year_id']}"); ?>
<?php $html->addCrumb("{$degree['Degree']['name']}", Environment::getBaseUrl() . "/courses/view/{$subject['Course']['id']}"); ?>
<?php $html->addCrumb($subject['Subject']['name'], Environment::getBaseUrl() . "/subjects/view/{$subject['Subject']['id']}"); ?>
<?php $html->addCrumb("Programación", Environment::getBaseUrl() . "/subjects/getScheduledInfo/{$subject['Subject']['id']}"); ?>

<h1>Programación de la asignatura</h1>
<table>
	<thead>
		<tr>
			<th>Tipo de actividad</th>
			<th>Actividad</th>
			<th>Grupo</th>
			<th>Duración</th>
			<th>Pendiente de programación</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach($activities as $activity): ?>
			<?php if ($activity['Activity']['duration'] > $activity[0]['scheduled']) {?>
				<tr class="pendant">
			<?php } else { ?>
				<tr>
			<?php }?>
					<td><?php echo $activity['Activity']['type'] ?></td>
					<td><?php echo $activity['Activity']['activity_name'] ?></td>
					<td><?php echo $activity['Group']['group_name'] ?></td>
					<td><?php echo $activity['Activity']['duration'] ?></td>
					<td><?php echo $activity['Activity']['duration'] - $activity[0]['scheduled'] ?></td>
				</tr>
		<?php endforeach;?>
	</tbody>
</table>