<?php $html->addCrumb('Cursos', '/academic_years'); ?>
<?php $html->addCrumb($modelHelper->academic_year_name($course), "/academic_years/view/{$course['Course']['academic_year_id']}"); ?>
<?php $html->addCrumb(Environment::institution('name'), Environment::getBaseUrl() . "/courses/index/{$course['Course']['academic_year_id']}"); ?>
<?php $html->addCrumb("{$course['Degree']['name']}", Environment::getBaseUrl() . "/courses/view/{$course['Course']['id']}"); ?>
<?php $html->addCrumb($subject['Subject']['name'], Environment::getBaseUrl() . "/subjects/view/{$subject['Subject']['id']}"); ?>
<?php $html->addCrumb('E-portfolio', Environment::getBaseUrl() . "/competence/by_subject/{$subject['Subject']['id']}"); ?>
<?php $html->addCrumb("Competencia {$competence['Competence']['code']}", Environment::getBaseUrl() . "/competence/view_by_subject/{$subject['Subject']['id']}/{$competence['Competence']['id']}"); ?>
<?php $html->addCrumb("Objetivo {$competence_goal['CompetenceGoal']['code']}", Environment::getBaseUrl() . "/competence_goals/view_by_subject/{$subject['Subject']['id']}/{$competence_goal['CompetenceGoal']['id']}"); ?>

<?php if ($auth->user('type') == "Profesor" || $auth->user('type') == "Estudiante"): ?>
    <h1>Mis Criterios de evaluación por asignatura: <?php echo h($subject['Subject']['name']) ?></h1>
<?php else: ?>
    <h1>Criterios de evaluación por asignatura: <?php echo h($subject['Subject']['name']) ?></h1>
<?php endif; ?>

<?php if ($auth->user('type') != "Estudiante") : ?>
    <div class="actions">
        <ul>
            <li><?php echo $html->link('Ver competencia', array('controller' => 'competence', 'action' => 'view', $competence['Competence']['id'])) ?></li>
            <li><?php echo $html->link('Ver objetivo', array('action' => 'view', $competence_goal['CompetenceGoal']['id'])) ?></li>
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
                        <th>Código</th>
                        <th>Definición</th>
                        <?php if ($auth->user('type') == "Estudiante"): ?>
                            <th>Calificación</th>
                            <th>Rúbrica</th>
                        <?php else: ?>
                            <th></th>
                        <?php endif; ?>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($competence_goal['CompetenceCriterion'] as $criterion): ?>
                    <tr>
                        <td><?php echo $html->link($criterion['code'], array('controller' => 'competence_criteria', 'action' => 'view_by_subject', $subject['Subject']['id'], $criterion['id'])) ?></td>
                        <td><?php echo h($criterion['definition']) ?></td>
                        <?php if ($auth->user('type') == "Estudiante"): ?>
                            <td><?php if ($criterion['CompetenceCriterionRubric']): ?>
                                <?php echo $criterion['CompetenceCriterionRubric']['value']; ?>
                            <?php endif; ?></td>
                            <td><?php if ($criterion['CompetenceCriterionRubric']): ?>
                                <?php echo "{$criterion['CompetenceCriterionRubric']['title']} - {$criterion['CompetenceCriterionRubric']['definition']}"; ?>
                            <?php endif; ?></td>
                        <?php else: ?>
                            <td><?php echo $html->link('Evaluar', array('controller' => 'competence_criteria', 'action' => 'grade_by_subject', 'ref' => 'competence_goals', $subject['Subject']['id'], $criterion['id'])) ?></td>
                        <?php endif; ?>
                        <td><?php echo $html->link('Rúbricas, asignaturas y profesores', array('controller' => 'competence_criteria', 'action' => 'view_by_subject', $subject['Subject']['id'], $criterion['id'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </fieldset>
    
    <?php
        if ($auth->user('type') == "Estudiante") :
            $referer = "competence_goals:view_by_subject:{$subject['Subject']['id']}";
            require('_student_goal_requests.ctp');
        endif;
    ?>
</div>