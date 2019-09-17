<?php $html->addCrumb('Usuarios', '/institutions/ref:users'); ?>
<?php $html->addCrumb(Environment::institution('name'), Environment::getBaseUrl() . '/users'); ?>
<?php $html->addCrumb("{$student['User']['first_name']} {$student['User']['last_name']}", Environment::getBaseUrl() . "/users/view/{$student['User']['id']}"); ?>
<?php $html->addCrumb('E-portfolio', Environment::getBaseUrl() . "/competence/by_student/{$student['User']['id']}"); ?>

<?php if ($auth->user('type') == "Profesor"): ?>
    <h1>Mis competencias por estudiante: <?php echo h("{$student['User']['first_name']} {$student['User']['last_name']}") ?></h1>
<?php else: ?>
    <h1>Competencias por estudiante: <?php echo h("{$student['User']['first_name']} {$student['User']['last_name']}") ?></h1>
<?php endif; ?>

<?php if ($auth->user('type') != "Estudiante") : ?>
    <div class="actions">
        <ul>
            <li><?php echo $html->link('Ver todas las competencias', array('controller' => 'courses', 'action' => 'index', $academic_year['id'], 'ref' => 'competence')) ?></li>
            <li><?php echo $html->link('Evaluación', array('controller' => 'courses', 'action' => 'index', $academic_year['id'], 'ref' => 'competence_student_stats', 'student_id' => $student['User']['id'])) ?></li>
        </ul>
    </div>
<?php endif; ?>
<div class="<?php if ($auth->user('type') != "Estudiante"): ?>view<?php endif; ?>">
    <?php if (empty($competence) && $auth->user('type') == "Profesor"): ?>
        No tienes criterios de evaluación asignados
    <?php else: ?>
        <div class="horizontal-scrollable-content">
            <table>
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Definición</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($competence as $row): ?>
                    <tr>
                        <td><?php echo $html->link($row['Competence']['code'], array('controller' => 'competence', 'action' => 'view_by_student', $student['User']['id'], $row['Competence']['id'])) ?></td>
                        <td><?php echo $row['Competence']['definition'] ?></td>
                        <td><?php echo $html->link('Objetivos', array('controller' => 'competence', 'action' => 'view_by_student', $student['User']['id'], $row['Competence']['id'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>