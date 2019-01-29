<?php $html->addCrumb('Usuarios', '/users'); ?>
<?php $html->addCrumb("{$student['User']['first_name']} {$student['User']['last_name']}", "/users/view/{$student['User']['id']}"); ?>
<?php $html->addCrumb($course['Course']['name'], "/courses/view/{$course['Course']['id']}"); ?>
<?php $html->addCrumb('E-portfolio', "/competence/by_student/{$student['User']['id']}"); ?>
<?php $html->addCrumb("Competencia {$competence['Competence']['code']}", "/competence/view_by_student/{$student['User']['id']}/{$competence['Competence']['id']}"); ?>

<?php if ($auth->user('type') == "Profesor"): ?>
    <h1>Mis objetivos de aprendizaje por estudiante: <?php echo h("{$student['User']['first_name']} {$student['User']['last_name']}") ?></h1>
<?php else: ?>
    <h1>Objetivos de aprendizaje por estudiante: <?php echo h("{$student['User']['first_name']} {$student['User']['last_name']}") ?></h1>
<?php endif; ?>

<?php if ($auth->user('type') != "Estudiante") : ?>
    <div class="actions">
        <ul>
            <li><?php echo $html->link('Ver competencia', array('action' => 'view', $competence['Competence']['id'])) ?></li>
        </ul>
    </div>
<?php endif; ?>
<div class="<?php if ($auth->user('type') != "Estudiante"): ?>view<?php endif; ?>">
    <fieldset>
    <legend>Datos generales</legend>
        <dl>
            <dt>Código</dt>
            <dd><?php echo h($competence['Competence']['code']) ?></dd>
        </dl>
        <dl>
            <dt>Definición</dt>
            <dd><?php echo h($competence['Competence']['definition']) ?></dd>
        </dl>
    </fieldset>

    <fieldset>
    <legend>Objetivos de aprendizaje</legend>
        <div class="horizontal-scrollable-content">
            <table>
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Definición</th>
                        <th></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($competence['CompetenceGoal'] as $goal): ?>
                    <tr>
                        <td><?php echo $html->link($goal['code'], array('controller' => 'competence_goals', 'action' => 'view_by_student', $student['User']['id'], $goal['id'])) ?></td>
                        <td><?php echo h($goal['definition']) ?></td>
                        <td><?php echo $html->link('Criterios', array('controller' => 'competence_goals', 'action' => 'view_by_student', $student['User']['id'], $goal['id'])) ?></td>
                        <td><?php echo $html->link('Evaluar', array('controller' => 'competence_goals', 'action' => 'grade_by_student', $student['User']['id'], $goal['id'], 'ref' => 'competence')) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </fieldset>
</div>