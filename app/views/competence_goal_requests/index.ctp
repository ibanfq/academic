<?php $index_by_course = isset($index_by_course) ? $index_by_course : false ?>
<?php $html->addCrumb('Cursos', '/courses'); ?>
<?php $html->addCrumb($course['Course']['name'], "/courses/view/{$course['Course']['id']}"); ?>
<?php $html->addCrumb('E-portfolio', "/competence/by_course/{$course['Course']['id']}"); ?>
<?php $html->addCrumb('Solicitudes de evaluación', "/competence_goal_requests/by_course/{$course['Course']['id']}"); ?>

<?php if ($auth->user('type') == "Estudiante") : ?>
    <h1>Competencias</h1>
<?php else: ?>
    <h1>Mis solicitudes de evaluación</h1>
<?php endif; ?>

<?php if ($auth->user('type') == "Estudiante") : ?>
    <fieldset>
    <legend>Nueva solicitud evaluación</legend>
        <?php echo $this->Form->create('CompetenceGoalRequest', array('action' => $index_by_course ? "add_by_course/{$course['Course']['id']}" : 'add')) ?>

        <div class="input">
            <dl>
                <dt><label for="teacher">Profesor</label></dt>
                <dd><input type="input" id="teacher" required /></dd>
                <?php echo $this->Form->hidden('teacher_id')?>
            </dl>
        </div>

        <?php echo $form->input('goal_id', array('label' => 'Objetivo', 'required' => 'required', 'options' => array("" => "Seleccione un objetivo de aprendizaje"), 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>

        <?php echo $this->Form->end('Añadir') ?>
    </fieldset>
<?php endif; ?>

<?php if ($auth->user('type') == "Estudiante") : ?>
    <h2>Mis solicitudes pendientes de evaluación</h2>
<?php endif; ?>

<div>
    <?php if (empty($competence_goal_requests)): ?>
        No tienes solicitudes de evaluación pendiente
    <?php else: ?>
        <div class="horizontal-scrollable-content">
            <table>
                <thead>
                    <tr>
                        <th>
                            <?php if ($auth->user('type') == "Estudiante") : ?>
                                Profesor
                            <?php else: ?>
                                Estudiante
                            <?php endif; ?>
                        </th>
                        <th>Competencia</th>
                        <th>Objetivo</th>
                        <?php if ($auth->user('type') == "Estudiante") : ?>
                            <th></th>
                        <?php else: ?>
                            <th></th>
                            <th></th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($competence_goal_requests as $row): ?>
                    <tr>
                        <td>
                            <?php if ($auth->user('type') == "Estudiante") : ?>
                                <?php echo "{$row['Teacher']['last_name']}, {$row['Teacher']['first_name']}" ?>
                            <?php else: ?>
                                <?php echo $html->link("{$row['Student']['last_name']}, {$row['Student']['first_name']}", array('controller' => 'users', 'action' => 'view', $row['Student']['id'])) ?>
                            <?php endif; ?>
                            
                        </td>
                        <td><?php echo $html->link("{$row['Competence']['code']} - {$row['Competence']['definition']}", array('controller' => 'competence', 'action' => 'view', $row['Competence']['id'])) ?></td>
                        <td><?php echo $html->link("{$row['CompetenceGoal']['code']} - {$row['CompetenceGoal']['definition']}", array('controller' => 'competence_goals', 'action' => 'view', $row['CompetenceGoal']['id'])) ?></td>
                        <?php if ($auth->user('type') == "Estudiante") : ?>
                            <td><?php echo $html->link(
                                'Cancelar',
                                $index_by_course
                                    ? array('action' => 'reject_by_course', $course['Course']['id'], $row['CompetenceGoalRequest']['id'])
                                    : array('action' => 'reject', $row['CompetenceGoalRequest']['id']),
                                null,
                                'Va a proceder a cancelar la solicitud de evaluación. ¿Está seguro que deseas continuar?')
                            ?></td>
                        <?php else: ?>
                            <td><?php echo $html->link('Evaluar', array('controller' => 'competence_goals', 'action' => 'grade_by_student', $row['Student']['id'], $row['CompetenceGoal']['id'], 'request_id' => $row['CompetenceGoalRequest']['id'])) ?></td>
                            <td><?php echo $html->link(
                                'Rechazar',
                                $index_by_course
                                    ? array('action' => 'reject_by_course', $course['Course']['id'], $row['CompetenceGoalRequest']['id'])
                                    : array('action' => 'reject', $row['CompetenceGoalRequest']['id']),
                                null,
                                'Va a proceder a rechazar la solicitud de evaluación. ¿Está seguro que deseas continuar?')
                            ?></td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                    
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

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
            .autocomplete("<?php echo PATH ?>/users/find_teachers_by_name", {formatItem: formatItem})
            .result(function(event, item) {
                var url = <?php echo $this->Javascript->object(Router::url(array(
                    'controller' => 'api_competence_goals',
                    '[method]' => 'GET',
                    'action' => 'by_teacher',
                    'teacher_id' => '00000000',
                    '?' => array('group_path' => 'CompetenceGoal.competence_id')
                ))); ?>;
                var teacher_id = item[1];
                $("input#CompetenceGoalRequestTeacherId").val(teacher_id);
                var goalIdSelect =  $("#CompetenceGoalRequestGoalId").prop('disabled', true);
                var submit = goalIdSelect.closest('form').find(':submit').prop('disabled', true);
                $.ajax({
                    cache: false,
                    type: "GET",
                    dataType: "json",
                    url: url.replace('00000000', teacher_id),
                    success: function(response) {
                        var data = response.data;
                        goalIdSelect.find(':not(:first)').remove();
                        $.each(data, function () {
                            var competence = this[0].Competence;
                            var group = $('<optgroup>').attr('label', competence.code + " - " + competence.definition);
                            $.each(this, function() {
                                var goal = this.CompetenceGoal;
                                var option = $('<option />').val(goal.id).html(goal.code + " - " + goal.definition);
                                if (this[0].has_requests && this[0].has_requests !== '0') {
                                    option.prop('disabled', true);
                                }
                                option.appendTo(group);
                            });
                            group.appendTo("#CompetenceGoalRequestGoalId");
                        });
                        goalIdSelect.prop('disabled', false);
                        submit.prop('disabled', false);
                    }
                });
            });
    });
</script>