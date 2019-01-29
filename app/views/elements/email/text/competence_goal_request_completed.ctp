Hola

Desde Academic te informamos que uno de tus objetivos ha sido evaluado por el profesor solicitado.

Competencia: <?php echo "{$competence['Competence']['code']} - {$competence['Competence']['definition']}\n" ?>
Objetivo: <?php echo "{$competence_goal['CompetenceGoal']['code']} - {$competence_goal['CompetenceGoal']['definition']}\n" ?>
Profesor evaluador: <?php echo "{$teacher['User']['first_name']} {$teacher['User']['last_name']}\n" ?>

A continuación la calificación obtenida en cada uno de los criterios:
<?php
    foreach ($competence_goal['CompetenceCriterion'] as $criterion):
        $rubric_id = isset($criterion['CompetenceCriterionGrade']['rubric_id']) ? $criterion['CompetenceCriterionGrade']['rubric_id'] : null;
        $rubric = isset($rubric_id, $criterion['CompetenceCriterionRubric'][$rubric_id]) ? $criterion['CompetenceCriterionRubric'][$rubric_id] : null;
        echo "\n" . h($criterion['code']) . ' - ' . h($criterion['definition']) . ': ';
        if ($rubric) {
            echo $rubric['value'];
        } else {
            echo 'Sin evaluar';
        }
        echo "\n";
    endforeach;
?>

Un saludo,
El equipo de Academic.