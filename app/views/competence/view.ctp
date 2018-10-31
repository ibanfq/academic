<?php $html->addCrumb('Cursos', '/courses'); ?>
<?php $html->addCrumb($course['Course']['name'], "/courses/view/{$course['Course']['id']}"); ?>
<?php $html->addCrumb('E-portfolio', "/competence/by_course/{$course['Course']['id']}"); ?>
<?php $html->addCrumb("Competencia {$competence['Competence']['code']}", "/competence/view/{$competence['Competence']['id']}"); ?>

<?php if ($auth->user('type') == "Profesor"): ?>
    <h1>Mis objetivos de aprendizaje</h1>
<?php else: ?>
    <h1>Objetivos de aprendizaje</h1>
<?php endif; ?>

<?php if ($auth->user('type') != "Estudiante") : ?>
    <div class="actions">
        <ul>
            <?php if ($auth->user('type') == "Administrador"): ?>
                <li><?php echo $html->link('Crear objetivo', array('controller' => 'competence_goals', 'action' => 'add_to_competence', $competence['Competence']['id'])) ?></li>
                <li><?php echo $html->link('Editar competencia', array('controller' => 'competence', 'action' => 'edit', $competence['Competence']['id'])) ?></li>
                <li><?php echo $html->link('Eliminar competencia', array('action' => 'delete', $competence['Competence']['id']), null, 'Cuando elimina una competencia, elimina también los objetivos, criterios, rúbricas y todas las calificaciones. ¿Está seguro que desea borrarlo?') ?></li>
            <?php endif; ?>
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
                  <td><?php echo $html->link($goal['code'], array('controller' => 'competence_goals', 'action' => 'view', $goal['id'])) ?></td>
                  <td><?php echo h($goal['definition']) ?></td>
                  <td><?php echo $html->link('Criterios', array('controller' => 'competence_goals', 'action' => 'view', $goal['id'])) ?></td>
              </tr>
              <?php endforeach; ?>
          </tbody>
      </table>
    </fieldset>
</div>