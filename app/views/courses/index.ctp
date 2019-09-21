<?php
	if ($ref === 'competence_student_stats') {
		$html->addCrumb('Usuarios', '/institutions/ref:users');
		$html->addCrumb(Environment::institution('name'), Environment::getBaseUrl() . '/users');
		$html->addCrumb("{$student['User']['first_name']} {$student['User']['last_name']}", Environment::getBaseUrl() . "/users/view/{$student['User']['id']}");
		$html->addCrumb('E-portfolio', Environment::getBaseUrl() . "/competence/by_student/{$student['User']['id']}");		
		$html->addCrumb('Evaluación', $html->url(null));
	} else {
		$html->addCrumb('Cursos', '/academic_years');
		$html->addCrumb($modelHelper->academic_year_name($academic_year), "/academic_years/view/{$academic_year['AcademicYear']['id']}");
		$html->addCrumb(Environment::institution('name'), Environment::getBaseUrl() . "/courses/index/{$academic_year['AcademicYear']['id']}");
		if ($ref === 'competence') {
			$html->addCrumb('E-portfolio', $html->url(null));
		}
	}
?>

<h1>Titulaciones</h1>
<?php if (!$ref && $auth->user('type') == "Administrador") : ?>
  <div class="actions">
    <ul>
      <li><?php echo $html->link('Añadir titulación', array('action' => 'add', $academic_year['AcademicYear']['id'])) ?></li>
    </ul>
  </div>
<?php endif; ?>
<div class="<?php if (!$ref && $auth->user('type') == "Administrador"): ?>view<?php endif; ?>">
	<div class="horizontal-scrollable-content">
		<table>
			<thead>
				<tr>
					<th>Acronym</th>
					<th>Código</th>
					<th>Nombre</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($courses as $course): ?>
				<tr>
					<?php
						if ($ref === 'competence') {
							$url = array('controller' => 'competence', 'action' => 'by_course', $course['Course']['id']);
						} elseif ($ref === 'competence_student_stats') {
							$url = array('controller' => 'competence', 'action' => 'stats_by_student', $course['Course']['id'], $student['User']['id']);
						} else {
							$url = array('controller' => 'courses', 'action' => 'view', $course['Course']['id']);
						}
					?>
					<td><?php echo $html->link($modelHelper->format_acronym($course['Degree']['acronym']), $url) ?></td>
					<td><?php echo $html->link($course['Degree']['code'], $url) ?></td>
					<td><?php echo $html->link($course['Degree']['name'], $url) ?></td>
				</tr>
				<?php endforeach; ?>
				
			</tbody>
		</table>
	</div>
</div>