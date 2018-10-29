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