<!-- File: /app/views/courses/view.ctp -->

<?php $degrees = Configure::read('app.degrees') ?>
<?php $degreeEnabled = !empty($degrees); ?>

<?php $html->addCrumb('Cursos', '/courses'); ?>
<?php $html->addCrumb("{$course['Course']['name']}", "/courses/view/{$course['Course']['id']}"); ?>
<?php $html->addCrumb('E-portfolio', "/competence/by_course/{$course['Course']['id']}"); ?>
<?php if ($subject): ?>
    <?php $html->addCrumb('Asignaturas', "/courses/view/{$course['Course']['id']}/ref:competence"); ?>
    <?php $html->addCrumb($subject['Subject']['name'], "/competence/stats_by_subject/{$course['Course']['id']}/{$subject['Subject']['id']}"); ?>
<?php else: ?>
    <?php $html->addCrumb('Evaluación por asignatura', "/competence/stats_by_subject/{$course['Course']['id']}"); ?>
<?php endif; ?>

<h1>Evaluación por asignatura</h1>

<?php if (empty($subjects_stats)): ?>
    Para poder ver la evaluación debes ser coordinador o responable de prácticas de al menos una asignatura con competencias
<?php else: ?>
    <div class="actions">
        <ul>
            <?php if ($auth->user('type') == "Administrador"): ?>
                <?php if ($subject): ?>
                    <li><?php echo $html->link('Exportar', array('action' => 'export_stats_by_subject', $course['Course']['id'], $subject['Subject']['id'])) ?>
                <?php else: ?>
                    <li><?php echo $html->link('Exportar', array('action' => 'export_stats_by_subject', $course['Course']['id'])) ?>
                <?php endif; ?>
            <?php endif; ?>
        </ul>
    </div>
    <div class="view">
        <?php foreach ($subjects as $subject): ?>
            <fieldset>
                <legend><?php echo "{$subject['code']} - {$subject['name']}" ?></legend>
                <div class="horizontal-scrollable-content">
                    <table>
                        <thead>
                            <tr>
                                <th>Estudiante</th>
                                <th>Calificación</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subjects_stats[$subject['id']] as $student_stats): ?>
                                <tr>
                                <td><?php echo $html->link("{$student_stats['Student']['first_name']} {$student_stats['Student']['last_name']}", array('controller' => 'competence', 'action' => 'stats_by_student', $course['Course']['id'], $student_stats['Student']['id'])) ?></td>
                                <td><?php echo number_format($student_stats[0]['total'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </fieldset>
        <?php endforeach; ?>
    </div>
<?php endif; ?>