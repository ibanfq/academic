<fieldset>
<legend>Calificación del criterio</legend>
    <table>
      <thead>
          <tr>
              <th style="width:20%">Estudiante</th>
              <th style="width:6em">Valoración nota final</th>
              <th>Rúbrica</th>
          </tr>
      </thead>
      <tbody id="user_competence_grades">
            <?php foreach ($students as $student): ?>
            <?php $student_id = $student['Student']['id']; ?>
            <tr>
                <td>
                    <?php echo h("{$student['Student']['first_name']} {$student['Student']['last_name']}") ?>
                </td>
                <td>
                    <?php echo $form->hidden("UserCompetenceGrade.{$student['Student']['id']}.student_id", array('value' => $student_id)); ?>
                    <?php echo $form->select("UserCompetenceGrade.{$student['Student']['id']}.rubric_id", $competence_criterion_rubrics_values); ?>
                </td>
                <td>
                    <span class="user_competence_grade_rubric_definition"><?php echo $this->data['UserCompetenceGrade'][$student_id]['rubric_id'] ?  h($competence_criterion_rubrics_definitions[$this->data['UserCompetenceGrade'][$student_id]['rubric_id']]) : '' ?></span>
                  </td>
            </tr>
            <?php endforeach; ?>
      </tbody>
  </table>
</fieldset>
