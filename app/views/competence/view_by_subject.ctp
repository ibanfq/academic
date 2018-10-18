<?php $html->addCrumb('Cursos', '/courses'); ?>
<?php $html->addCrumb($course['Course']['name'], "/courses/view/{$course['Course']['id']}"); ?>
<?php $html->addCrumb($subject['Subject']['name'], "/subjects/view/{$subject['Subject']['id']}"); ?>
<?php $html->addCrumb('E-portfolio', "/competence/by_subject/{$subject['Subject']['id']}"); ?>
<?php $html->addCrumb("Competencia {$competence['Competence']['code']}", "/competence/view_by_subject/{$subject['Subject']['id']}/{$competence['Competence']['id']}"); ?>

<?php if ($auth->user('type') == "Profesor"): ?>
    <h1>Mis objetivos de aprendizaje</h1>
<?php else: ?>
    <h1>Objetivos de aprendizaje</h1>
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
    </fieldset>
</div>