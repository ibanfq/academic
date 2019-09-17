<?php $html->addCrumb('Usuarios', '/institutions/ref:users'); ?>
<?php $html->addCrumb(Environment::institution('name'), Environment::getBaseUrl() . '/users'); ?>
<?php $html->addCrumb("{$student['User']['first_name']} {$student['User']['last_name']}", Environment::getBaseUrl() . "/users/view/{$student['User']['id']}"); ?>
<?php $html->addCrumb('E-portfolio', Environment::getBaseUrl() . "/competence/by_student/{$student['User']['id']}"); ?>
<?php $html->addCrumb("Competencia {$competence['Competence']['code']}", Environment::getBaseUrl() . "/competence/view_by_student/{$student['User']['id']}/{$competence['Competence']['id']}"); ?>
<?php $html->addCrumb("Objetivo {$competence_goal['CompetenceGoal']['code']}", Environment::getBaseUrl() . "/competence_goals/view_by_student/{$student['User']['id']}/{$competence_goal['CompetenceGoal']['id']}"); ?>

<?php if ($auth->user('type') == "Profesor"): ?>
    <h1>Mis Criterios de evaluación por estudiante: <?php echo h("{$student['User']['first_name']} {$student['User']['last_name']}") ?></h1>
<?php else: ?>
    <h1>Criterios de evaluación por estudiante: <?php echo h("{$student['User']['first_name']} {$student['User']['last_name']}") ?></h1>
<?php endif; ?>

<?php if ($auth->user('type') != "Estudiante") : ?>
    <div class="actions">
        <ul>
            <li><?php echo $html->link('Ver competencia', array('controller' => 'competence', 'action' => 'view', $competence['Competence']['id'])) ?></li>
            <li><?php echo $html->link('Ver objetivo', array('action' => 'view', $competence_goal['CompetenceGoal']['id'])) ?></li>
            <li><?php echo $html->link('Evaluar', array('controller' => 'competence_goals', 'action' => 'grade_by_student', $student['User']['id'], $competence_goal['CompetenceGoal']['id'])) ?></li>
        </ul>
    </div>
<?php endif; ?>
<div class="<?php if ($auth->user('type') != "Estudiante"): ?>view<?php endif; ?>">
    <?php require('_view_resume.ctp') ?>

    <fieldset>
    <legend>Criterios de evaluación</legend>
        <div class="horizontal-scrollable-content">
            <table>
                <thead>
                    <tr>
                        <th style="width:6em">Código</th>
                        <th style="width:50%">Definición</th>
                        <th style="width:6em">Valoración nota final</th>
                        <th style="width:50%">Rúbrica</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($competence_goal['CompetenceCriterion'] as $criterion): ?>
                    <?php $rubric_id = $criterion['CompetenceCriterionGrade']['rubric_id'] ?>
                        <tr>
                            <td><?php echo h($criterion['code']) ?></td>
                            <td><?php echo h($criterion['definition']) ?></td>
                            <?php if (isset($rubric_id)): ?>
                                <td><?php echo h($criterion['CompetenceCriterionRubric'][$rubric_id]['value']) ?></td>
                                <td><?php echo h($criterion['CompetenceCriterionRubric'][$rubric_id]['definition']) ?></td>
                            <?php else: ?>
                                <td></td>
                                <td></td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </fieldset>
</div>