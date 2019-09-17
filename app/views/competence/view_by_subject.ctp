<?php $html->addCrumb('Cursos', '/academic_years'); ?>
<?php $html->addCrumb($modelHelper->academic_year_name($course), "/academic_years/view/{$course['Course']['academic_year_id']}"); ?>
<?php $html->addCrumb(Environment::institution('name'), Environment::getBaseUrl() . "/courses/index/{$course['Course']['academic_year_id']}"); ?>
<?php $html->addCrumb("{$course['Degree']['name']}", Environment::getBaseUrl() . "/courses/view/{$course['Course']['id']}"); ?>
<?php $html->addCrumb($subject['Subject']['name'], Environment::getBaseUrl() . "/subjects/view/{$subject['Subject']['id']}"); ?>
<?php $html->addCrumb('E-portfolio', Environment::getBaseUrl() . "/competence/by_subject/{$subject['Subject']['id']}"); ?>
<?php $html->addCrumb("Competencia {$competence['Competence']['code']}", Environment::getBaseUrl() . "/competence/view_by_subject/{$subject['Subject']['id']}/{$competence['Competence']['id']}"); ?>

<?php if ($auth->user('type') == "Profesor" || $auth->user('type') == "Estudiante"): ?>
    <h1>Mis objetivos de aprendizaje por asignatura: <?php echo h($subject['Subject']['name']) ?></h1>
<?php else: ?>
    <h1>Objetivos de aprendizaje por asignatura: <?php echo h($subject['Subject']['name']) ?></h1>
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
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($competence['CompetenceGoal'] as $goal): ?>
                    <tr>
                        <td><?php echo $html->link($goal['code'], array('controller' => 'competence_goals', 'action' => 'view_by_subject', $subject['Subject']['id'], $goal['id'])) ?></td>
                        <td><?php echo h($goal['definition']) ?></td>
                        <td><?php echo $html->link('Criterios', array('controller' => 'competence_goals', 'action' => 'view_by_subject', $subject['Subject']['id'], $goal['id'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </fieldset>
</div>