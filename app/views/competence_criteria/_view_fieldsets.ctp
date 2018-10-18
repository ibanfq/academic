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