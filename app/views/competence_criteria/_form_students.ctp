<fieldset>
<legend>Calificación del criterio</legend>
    <div class="horizontal-scrollable-content">
        <table>
            <thead>
                <tr>
                    <th style="width:20%">Estudiante</th>
                    <th style="width:6em">Valoración nota final</th>
                    <th>Rúbrica</th>
                </tr>
            </thead>
            <tbody id="competence_criterion_grades">
                    <?php foreach ($students as $student): ?>
                    <?php $student_id = $student['Student']['id']; ?>
                    <tr>
                        <td>
                            <?php echo h("{$student['Student']['first_name']} {$student['Student']['last_name']}") ?>
                        </td>
                        <td>
                            <?php echo $form->hidden("CompetenceCriterionGrade.{$student['Student']['id']}.student_id", array('value' => $student_id)); ?>
                            <?php echo $form->select("CompetenceCriterionGrade.{$student['Student']['id']}.rubric_id", $competence_criterion_rubrics_values); ?>
                        </td>
                        <td>
                            <span class="competence_rubric_definition"><?php echo $this->data['CompetenceCriterionGrade'][$student_id]['rubric_id'] ?  h($competence_criterion_rubrics_definitions[$this->data['CompetenceCriterionGrade'][$student_id]['rubric_id']]) : '' ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</fieldset>
