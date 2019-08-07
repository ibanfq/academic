<!-- File: /app/views/courses/view.ctp -->

<?php $degrees = Configure::read('app.degrees') ?>
<?php $degreeEnabled = !empty($degrees); ?>

<?php $html->addCrumb('Cursos', '/courses'); ?>
<?php $html->addCrumb("{$course['Course']['name']}", "/courses/view/{$course['Course']['id']}"); ?>
<?php $html->addCrumb('E-portfolio', "/competence/by_course/{$course['Course']['id']}"); ?>
<?php $html->addCrumb('Estudiantes', "/users/index/type:Estudiante/course:{$course['Course']['id']}/ref:competence"); ?>
<?php $html->addCrumb("{$student['User']['first_name']} {$student['User']['last_name']}", "/competence/stats_by_student/{$course['Course']['id']}/{$student['User']['id']}"); ?>

<h1>Evaluación por estudiante: <?php echo "{$student['User']['first_name']} {$student['User']['last_name']}" ?></h1>

<?php if (empty($subjects_stats)): ?>
    Para poder ver la evaluación debes ser coordinador o responable de prácticas de al menos una asignatura con competencias y en la que el alumno esté matriculado
<?php else: ?>
    <div class="actions">
        <ul>
            <?php if ($auth->user('type') == "Administrador"): ?>
                <li><?php echo $html->link('Exportar', array('action' => 'export_stats_by_student', $course['Course']['id'], $student['User']['id'])) ?>
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
                                <th>Código</th>
                                <th>Definición</th>
                                <th>Calificación</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $total = 0 ?>
                            <?php foreach ($subjects_stats[$subject['id']] as $student_grade): ?>
                                <?php $total += $student_grade['CompetenceCriterionRubric']['value']; ?>
                                <tr>
                                <td><?php echo $html->link($student_grade['CompetenceCriterion']['code'], array('controller' => 'competence_criteria', 'action' => 'view', $student_grade['CompetenceCriterion']['id'])) ?></td>
                                <td><?php echo $student_grade['CompetenceCriterion']['definition'] ?></td>
                                <td><span class="tooltip" title="<?php echo htmlspecialchars("{$student_grade['CompetenceCriterionRubric']['title']} - {$student_grade['CompetenceCriterionRubric']['definition']}") ?>">
                                    <?php echo number_format($student_grade['CompetenceCriterionRubric']['value'], 2) ?></td>
                                </span></tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td></td>
                                <td><strong>TOTAL</strong></td>
                                <td><?php echo number_format(round($total, 2), 2) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </fieldset>
        <?php endforeach; ?>
    </div>

    <script type="text/javascript">
        $(function() {
            $('.tooltip').tooltip({
                bodyHandler: function () {
                    return this.tooltipText
                }
            });
        });
    </script>
<?php endif; ?>