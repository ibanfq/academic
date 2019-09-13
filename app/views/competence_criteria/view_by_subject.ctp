<?php $html->addCrumb('Cursos', '/courses'); ?>
<?php $html->addCrumb($course['Degree']['name'], "/courses/view/{$course['Course']['id']}"); ?>
<?php $html->addCrumb($subject['Subject']['name'], "/subjects/view/{$subject['Subject']['id']}"); ?>
<?php $html->addCrumb('E-portfolio', "/competence/by_subject/{$subject['Subject']['id']}"); ?>
<?php $html->addCrumb("Competencia {$competence['Competence']['code']}", "/competence/view_by_subject/{$subject['Subject']['id']}/{$competence['Competence']['id']}"); ?>
<?php $html->addCrumb("Objetivo {$competence_goal['CompetenceGoal']['code']}", "/competence_goals/view_by_subject/{$subject['Subject']['id']}/{$competence_goal['CompetenceGoal']['id']}"); ?>
<?php $html->addCrumb("Criterio {$competence_criterion['CompetenceCriterion']['code']}", "/competence_criteria/view_by_subject/{$subject['Subject']['id']}/{$competence_criterion['CompetenceCriterion']['id']}"); ?>

<?php if ($auth->user('type') == "Profesor"): ?>
    <h1>Mi criterio de evaluación</h1>
<?php else: ?>
    <h1>Criterio de evaluación</h1>
<?php endif; ?>

<?php if ($auth->user('type') != "Estudiante") : ?>
    <div class="actions">
        <ul>
            <li><?php echo $html->link('Evaluar', array('controller' => 'competence_criteria', 'action' => 'grade_by_subject', $subject['Subject']['id'], $competence_criterion['CompetenceCriterion']['id'])) ?></li>
            <?php if ($auth_is_admin || $auth_is_coordinator): ?>
                <li><?php echo $html->link('Editar criterio', array('controller' => 'competence_criteria', 'action' => 'edit_by_subject', $subject['Subject']['id'], $competence_criterion['CompetenceCriterion']['id'])) ?></li>
            <?php endif; ?>
            <?php if ($auth_is_admin): ?>
                <li><?php echo $html->link('Eliminar criterio', array('action' => 'delete_by_subject', $subject['Subject']['id'], $competence_criterion['CompetenceCriterion']['id']), null, 'Cuando elimina un criterio, elimina también las rúbricas y todas las calificaciones. ¿Está seguro que desea borrarlo?') ?></li>
            <?php endif; ?>
        </ul>
    </div>
<?php endif; ?>
<div class="<?php if ($auth->user('type') != "Estudiante"): ?>view<?php endif; ?>">
    <?php require('_view_resume.ctp') ?>
    <?php require('_view_relations.ctp') ?>
</div>