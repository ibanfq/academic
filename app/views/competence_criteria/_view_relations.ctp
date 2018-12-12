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

<?php if (!isset($subject)): ?>
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
<?php endif; ?>

<?php if ($auth_is_admin || $auth_is_coordinator): ?>
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
<?php endif; ?>