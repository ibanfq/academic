<p>Hola</p>
<p>Desde Academic te informamos que uno de tus objetivos ha sido evaluado por el profesor solicitado.</p>
<p>
Competencia: <?php echo "{$competence['Competence']['code']} - {$competence['Competence']['definition']}" ?><br />
Objetivo: <?php echo "{$competence_goal['CompetenceGoal']['code']} - {$competence_goal['CompetenceGoal']['definition']}" ?><br />
Profesor evaluador: <?php echo "{$teacher['User']['first_name']} {$teacher['User']['last_name']}" ?><br />
</p>
<p>A continuación la calificación obtenida en cada uno de los criterios:</p>
<?php
    foreach ($competence_goal['CompetenceCriterion'] as $criterion):
        $rubric_id = isset($criterion['CompetenceCriterionGrade']['rubric_id']) ? $criterion['CompetenceCriterionGrade']['rubric_id'] : null;
        $rubric = isset($rubric_id, $criterion['CompetenceCriterionRubric'][$rubric_id]) ? $criterion['CompetenceCriterionRubric'][$rubric_id] : null;
        echo '<p>' . h($criterion['code']) . ' - ' . h($criterion['definition']) . ': ';
        if ($rubric) {
            echo $rubric['value'];
        } else {
            echo 'Sin evaluar';
        }
        echo "<br />\n</p>";
    endforeach;
?>
<p>Un saludo,<br />El equipo de Academic.</p>