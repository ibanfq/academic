<?php $html->addCrumb('Cursos', '/courses'); ?>
<?php $html->addCrumb($course['Course']['name'], "/courses/view/{$course['Course']['id']}"); ?>
<?php $html->addCrumb('E-portfolio', "/competence/by_course/{$course['Course']['id']}"); ?>
<?php $html->addCrumb("Competencia {$competence['Competence']['code']}", "/competence/view/{$competence['Competence']['id']}"); ?>
<?php $html->addCrumb("Objetivo {$competence_goal['CompetenceGoal']['code']}", "/competence_goals/view/{$competence_goal['CompetenceGoal']['id']}"); ?>
<?php $html->addCrumb("Criterio {$competence_criterion['CompetenceCriterion']['code']}", "/competence_criteria/view/{$competence_criterion['CompetenceCriterion']['id']}"); ?>

<h1>Criterio de evaluación</h1>
<?php if ($auth->user('type') != "Estudiante") : ?>
  <div class="actions">
    <ul>
      <?php if ($auth->user('type') == "Administrador"): ?>
        <li><?php echo $html->link('Editar criterio', array('controller' => 'competence_criteria', 'action' => 'edit', $competence_criterion['CompetenceCriterion']['id'])) ?></li>
        <li><?php echo $html->link('Eliminar criterio', array('action' => 'delete', $competence_criterion['CompetenceCriterion']['id']), null, 'Cuando elmina un criterio, elimina también las rúbricas y todas las calificaciones. ¿Está seguro que desea borrarlo?') ?></li>
      <?php endif; ?>
    </ul>
  </div>
<?php endif; ?>
<div class="<?php if ($auth->user('type') != "Estudiante"): ?>view<?php endif; ?>">
    <fieldset>
    <legend>Datos generales</legend>
        <dl>
            <dt>Código</dt>
            <dd><?php echo h($competence_criterion['CompetenceCriterion']['code']) ?></dd>
        </dl>
        <dl>
            <dt>Definición</dt>
            <dd><?php echo h($competence_criterion['CompetenceCriterion']['definition']) ?></dd>
        </dl>
        <dl>
            <dt>Objetivo</dt>
            <dd><?php echo h($competence_goal['CompetenceGoal']['definition']) ?></dd>
        </dl>
        <dl>
            <dt>Competencia</dt>
            <dd><?php echo h($competence['Competence']['definition']) ?></dd>
        </dl>
    </fieldset>

    <fieldset>
    <legend>Calificación del criterio</legend>
        <table>
          <thead>
              <tr>
                  <th>Nivel de ejecución</th>
                  <th>Rúbrica</th>
                  <th>Valoración nota final</th>
              </tr>
          </thead>
          <tbody>
              <?php foreach ($competence_criterion['CompetenceCriterionRubric'] as $rubric): ?>
              <tr>
                  <td><?php echo h($rubric['title']) ?></td>
                  <td><?php echo h($rubric['definition']) ?></td>
                  <td><?php echo h($rubric['value']) ?></td>
              </tr>
              <?php endforeach; ?>
          </tbody>
      </table>
    </fieldset>

    <fieldset>
    <legend>Asignaturas asignadas</legend>
        <table>
          <thead>
              <tr>
                  <th></th>
              </tr>
          </thead>
          <tbody>
              <?php foreach ($competence_criterion['CompetenceCriterionSubject'] as $competenceCriterionSubject): ?>
              <tr>
                  <td><?php echo h($competenceCriterionSubject['Subject']['name']) ?></td>
              </tr>
              <?php endforeach; ?>
          </tbody>
      </table>
    </fieldset>


    <fieldset>
    <legend>Profesores evaluadores</legend>
        <table>
          <thead>
              <tr>
                  <th></th>
              </tr>
          </thead>
          <tbody>
              <?php foreach ($competence_criterion['CompetenceCriterionTeacher'] as $competenceCriterionTeacher): ?>
              <tr>
                  <td><?php echo h("{$competenceCriterionTeacher['Teacher']['first_name']} {$competenceCriterionTeacher['Teacher']['last_name']}") ?></td>
              </tr>
              <?php endforeach; ?>
          </tbody>
      </table>
    </fieldset>
</div>