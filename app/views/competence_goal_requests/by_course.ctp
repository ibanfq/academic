<?php $html->addCrumb('Cursos', '/courses'); ?>
<?php $html->addCrumb($course['Course']['name'], "/courses/view/{$course['Course']['id']}"); ?>
<?php $html->addCrumb('E-portfolio', "/competence/by_course/{$course['Course']['id']}"); ?>
<?php $html->addCrumb('Solicitudes de evaluación', "/competence_goal_requests/by_course/{$course['Course']['id']}"); ?>

<h1>Mis solicitudes de evaluación</h1>

<div>
    <?php if (empty($competence_goal_requests)): ?>
        No tienes solicitudes de evaluación pendiente
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>
                        <?php if ($auth->user('type') == "Estudiante") : ?>
                            Profesor
                        <?php else: ?>
                            Estudiante
                        <?php endif; ?>
                    </th>
                    <th>Competencia</th>
                    <th>Objetivo</th>
                    <?php if ($auth->user('type') == "Estudiante") : ?>
                        <th></th>
                    <?php else: ?>
                        <th></th>
                        <th></th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($competence_goal_requests as $row): ?>
                <tr>
                    <td>
                        <?php if ($auth->user('type') == "Estudiante") : ?>
                            <?php echo $html->link("{$row['Teacher']['last_name']}, {$row['Teacher']['first_name']}", array('controller' => 'users', 'action' => 'view', $row['Teacher']['id'])) ?>
                        <?php else: ?>
                            <?php echo $html->link("{$row['Student']['last_name']}, {$row['Student']['first_name']}", array('controller' => 'users', 'action' => 'view', $row['Student']['id'])) ?>
                        <?php endif; ?>
                        
                    </td>
                    <td><?php echo "{$row['Competence']['code']} - {$row['Competence']['definition']}" ?></td>
                    <td><?php echo "{$row['CompetenceGoal']['code']} - {$row['CompetenceGoal']['definition']}" ?></td>
                    <?php if ($auth->user('type') == "Estudiante") : ?>
                        <td>Cancelar</td>
                    <?php else: ?>
                        <td><?php echo $html->link('Evaluar', array('controller' => 'competence_goals', 'action' => 'grade_by_student', $row['Student']['id'], $row['CompetenceGoal']['id'], 'request_id' => $row['CompetenceGoalRequest']['id'])) ?></td>
                        <td><?php echo $html->link('Rechazar', array('action' => 'reject_by_course', $course['Course']['id'], $row['CompetenceGoalRequest']['id']), null, 'Va a proceder a rechazar la solicitud de evaluación. ¿Está seguro que deseas continuar?') ?></td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                
            </tbody>
        </table>
    <?php endif; ?>
</div>