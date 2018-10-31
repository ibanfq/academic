<?php $html->addCrumb('Cursos', '/courses'); ?>
<?php $html->addCrumb($course['Course']['name'], "/courses/view/{$course['Course']['id']}"); ?>
<?php $html->addCrumb('E-portfolio', "/competence/by_course/{$course['Course']['id']}"); ?>
<?php $html->addCrumb("Competencia {$competence['Competence']['code']}", "/competence/view/{$competence['Competence']['id']}"); ?>
<?php $html->addCrumb("Objetivo {$competence_goal['CompetenceGoal']['code']}", "/competence_goals/view/{$competence_goal['CompetenceGoal']['id']}"); ?>

<?php if ($auth->user('type') == "Profesor"): ?>
    <h1>Mis Criterios de evaluación</h1>
<?php else: ?>
    <h1>Criterios de evaluación</h1>
<?php endif; ?>

<?php if ($auth->user('type') != "Estudiante") : ?>
    <div class="actions">
        <ul>
            <?php if ($auth->user('type') == "Administrador"): ?>
                <li><?php echo $html->link('Crear criterio', array('controller' => 'competence_criteria', 'action' => 'add_to_goal', $competence_goal['CompetenceGoal']['id'])) ?></li>
                <li><?php echo $html->link('Editar objetivo', array('controller' => 'competence_goals', 'action' => 'edit', $competence_goal['CompetenceGoal']['id'])) ?></li>
                <li><?php echo $html->link('Eliminar objetivo', array('action' => 'delete', $competence_goal['CompetenceGoal']['id']), null, 'Cuando elimina un objetivo, elimina también los criterios, rúbricas y todas las calificaciones. ¿Está seguro que desea borrarlo?') ?></li>
            <?php endif; ?>
        </ul>
    </div>
<?php endif; ?>
<div class="<?php if ($auth->user('type') != "Estudiante"): ?>view<?php endif; ?>">
    <?php require('_view_resume.ctp') ?>

    <fieldset>
    <legend>Criterios de evaluación</legend>
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
              <?php foreach ($competence_goal['CompetenceCriterion'] as $criterion): ?>
              <tr>
                  <td><?php echo $html->link($criterion['code'], array('controller' => 'competence_criteria', 'action' => 'view', $criterion['id'])) ?></td>
                  <td><?php echo h($criterion['definition']) ?></td>
                  <td><?php echo $html->link('Rúbricas, asignaturas y profesores', array('controller' => 'competence_criteria', 'action' => 'view', $criterion['id'])) ?></td>
                  <td><?php echo $html->link('Evaluar', array('controller' => 'competence_criteria', 'action' => 'grade', 'ref' => 'competence_goals', $criterion['id'])) ?></td>
              </tr>
              <?php endforeach; ?>
          </tbody>
      </table>
    </fieldset>
</div>