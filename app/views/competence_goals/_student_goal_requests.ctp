<fieldset>
    <legend>Nueva solicitud evaluación</legend>
    <?php echo $this->Form->create('CompetenceGoalRequest', array('action' => 'add', 'url' => array('?' => array('referer' => $referer)))) ?>

    <div class="input">
        <dl>
            <dt><label for="teacher">Profesor</label></dt>
            <dd><input type="input" id="teacher" required /></dd>
            <?php echo $this->Form->hidden('teacher_id')?>
        </dl>
    </div>

    <?php echo $form->hidden('goal_id', array('value' => $competence_goal['CompetenceGoal']['id'])); ?>

    <?php echo $this->Form->end('Añadir') ?>
</fieldset>
<fieldset>
    <legend>Mis solicitudes pendientes de evaluación</legend>

    <?php if (empty($competence_goal_requests)): ?>
        No tienes solicitudes de evaluación pendiente
    <?php else: ?>
        <div class="horizontal-scrollable-content">
            <table>
                <thead>
                    <tr>
                        <th>Profesor</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($competence_goal_requests as $row): ?>
                    <tr>
                        <td>
                            <?php echo "{$row['Teacher']['last_name']}, {$row['Teacher']['first_name']}" ?>
                        </td>
                        <td><?php echo $html->link(
                            'Cancelar', array(
                                'controller' => 'competence_goal_requests',
                                'action' => 'reject',
                                $row['CompetenceGoalRequest']['id'],
                                '?' => array('referer' => $referer)
                            ),
                            null,
                            'Va a proceder a cancelar la solicitud de evaluación. ¿Está seguro que deseas continuar?')
                        ?></td>
                    </tr>
                    <?php endforeach; ?>
                    
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</fieldset>

<script type="text/javascript">
    $(document).ready(function() {
        function formatItem(row){
            if (row[1] != null) {
                return row[0];
            } else {
                return 'No existe ningún profesor con este nombre.';
            }
        }

        $("input#teacher")
            .autocomplete("<?php echo PATH ?>/users/find_teachers_by_competence_goal_and_name/<?php echo rawurlencode($competence_goal['CompetenceGoal']['id']) ?>", {formatItem: formatItem})
            .result(function(event, item) {
                var teacher_id = item[1];
                $("input#CompetenceGoalRequestTeacherId").val(teacher_id);
            });
    });
</script>