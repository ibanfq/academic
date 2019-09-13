<fieldset>
<legend>Calificación del criterio</legend>
    <?php if ($auth_is_admin): ?>
        <!--  need to can delete all items -->
        <input type="hidden" name="data[CompetenceCriterionRubric]" value="">
    <?php endif; ?>
    <div class="horizontal-scrollable-content">
        <table>
            <thead>
                <tr>
                    <th style="width:15%">Nivel de ejecución</th>
                    <th>Rúbrica</th>
                    <th style="width:10%">Valoración nota final</th>
                    <th style="width:15%"></th>
                </th>
            </thead>
            <tfoot>
                <?php if ($auth_is_admin): ?>
                    <tr><td colspan="4"><a href="javascript:;" onclick="addRubricRow()" title="Haga click para añadir un nuevo nivel">Añadir nivel</a></td></tr>
                <?php endif; ?>
            </tfoot>
            <tbody id="rubrics" data-delete-confirm="Cuando elimina un nivel de calificación, elimina también las calificaciones con ese valor. ¿Está seguro que desea borrarlo?">
            <?php if (isset($competence_criterion['CompetenceCriterionRubric']) && is_array($competence_criterion['CompetenceCriterionRubric'])): ?>
                <?php $i = 0 ?>
                <?php foreach ($competence_criterion['CompetenceCriterionRubric'] as $competenceCriterionRubric): ?>
                    <?php $id = isset($competenceCriterionRubric['id']) ? $competenceCriterionRubric['id'] : '_new_0' . $i; ?>
                    <tr id="rubrics_row_<?php echo $i?>">
                        <td>
                            <?php if ($auth_is_admin): ?>
                                <input style="display:none;" type="text" required name="data[CompetenceCriterionRubric][<?php echo $id ?>][title]" value="<?php echo htmlspecialchars($competenceCriterionRubric['title']) ?>" class="td_input">
                            <?php endif; ?>
                            <span><?php echo h($competenceCriterionRubric['title']) ?></span>
                        </td>
                        <td>
                            <?php if ($auth_is_admin || $auth_is_coordinator): ?>
                                <input style="display:none;" type="text" name="data[CompetenceCriterionRubric][<?php echo $id ?>][definition]" value="<?php echo htmlspecialchars($competenceCriterionRubric['definition']) ?>" class="td_input">
                            <?php endif; ?>
                            <span><?php echo h($competenceCriterionRubric['definition']) ?></span>
                        </td>
                        <td>
                            <?php if ($auth_is_admin): ?>
                                <input style="display:none;" type="number" required step="0.01" name="data[CompetenceCriterionRubric][<?php echo $id ?>][value]" value="<?php echo htmlspecialchars($competenceCriterionRubric['value']) ?>" class="td_input">
                            <?php endif; ?>
                            <span><?php echo number_format($competenceCriterionRubric['value'], 2) ?></span>
                        </td>
                        <td>
                            <?php if (isset($competenceCriterionRubric['id'])): ?>
                                <input type="hidden" name="data[CompetenceCriterionRubric][<?php echo $id ?>][id]" value="<?php echo $competenceCriterionRubric['id'] ?>">
                            <?php endif ?>
                            <?php if ($auth_is_admin || $auth_is_coordinator): ?>
                                <span data-action="edit">
                                    <a href="javascript:;" data-action="delete" onclick="editRow('rubrics', <?php echo $i ?>)">Editar</a>
                                    <?php if ($auth_is_admin): ?>
                                    |
                                    <?php endif; ?>
                                </span>
                            <?php endif; ?> 
                            <?php if ($auth_is_admin): ?>
                                <span data-action="delete"><a href="javascript:;" onclick="deleteRow('rubrics', <?php echo $i ?>)">Eliminar</a></span>
                            <?php endif; ?>
                            <?php if ($auth_is_admin || $auth_is_coordinator): ?>
                                <span data-action="cancel" style="display:none;"><a href="javascript:;" onclick="cancelRow('rubrics', <?php echo $i ?>)">Cancelar</a></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php $i++; ?>
                <?php endforeach;?>
            <?php endif;?>
            </tbody>
        </table>
    </div>
</fieldset>

<fieldset>
<legend>Asignaturas asignadas</legend>
    <?php if ($auth_is_admin): ?>
        <!--  need to can delete all items -->
        <input type="hidden" name="data[CompetenceCriterionSubject]" value="">
    <?php endif; ?>
    <div class="horizontal-scrollable-content">
        <table>
            <thead>
                <tr>
                    <th></th>
                    <th style="width:15%"></th>
                </th>
            </thead>
            <tfoot>
                <?php if ($auth_is_admin): ?>
                    <tr><td colspan="2"><a href="javascript:;" onclick="addAutocompleteSubjectRow('<?php echo Environment::getBaseUrl() ?>/subjects/find_subjects_by_name?course_id=<?php echo urlencode($competence['Competence']['course_id']) ?>')" title="Haga click para añadir una asignatura">Añadir asignatura</a></td></tr>
                <?php endif; ?>
            </tfoot>
            <tbody id="subjects">
            <?php if (isset($competence_criterion['CompetenceCriterionSubject']) && is_array($competence_criterion['CompetenceCriterionSubject'])): ?>
                <?php $i = 0 ?>
                <?php foreach ($competence_criterion['CompetenceCriterionSubject'] as $competenceCriterionSubject): ?>
                    <?php
                        $id = isset($competenceCriterionSubject['id']) ? $competenceCriterionSubject['id'] : '_new_0' . $i;
                        $name = $competenceCriterionSubject['Subject']['name'];
                    ?>
                    <tr id="subjects_row_<?php echo $i?>">
                        <td><?php echo h($name) ?></td>
                        <td>
                            <?php if ($auth_is_admin): ?>
                                <input type="hidden" name="data[CompetenceCriterionSubject][<?php echo $id ?>][subject_id]" value="<?php echo $competenceCriterionSubject['subject_id'] ?>">
                                <input type="hidden" name="data[CompetenceCriterionSubject][<?php echo $id ?>][Subject][name]" value="<?php echo htmlspecialchars($name) ?>">
                                <span data-action="delete"><a href="javascript:;" onclick="deleteRow('subjects', <?php echo $i ?>)">Eliminar</a></span>
                                <span data-action="cancel" style="display:none;"><a href="javascript:;" onclick="cancelRow('subjects', <?php echo $i ?>)">Cancelar</a></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php $i++; ?>
                <?php endforeach;?>
            <?php endif;?>
            </tbody>
        </table>
    </div>
</fieldset>

<fieldset>
<legend>Profesores evaluadores</legend>
    <?php if ($auth_is_admin || $auth_is_coordinator): ?>
        <!--  need to can delete all items -->
        <input type="hidden" name="data[CompetenceCriterionTeacher]" value="">
    <?php endif; ?>
    <div class="horizontal-scrollable-content">
        <table>
            <thead>
                <tr>
                    <th></th>
                    <th style="width:15%"></th>
                </th>
            </thead>
            <tfoot>
                <?php if ($auth_is_admin || $auth_is_coordinator): ?>
                    <tr><td colspan="2"><a href="javascript:;" onclick="addAutocompleteTeacherRow('<?php echo Environment::getBaseUrl() ?>/users/find_teachers_by_name')" title="Haga click para añadir una asignatura">Añadir profesor</a></td></tr>
                <?php endif; ?>
            </tfoot>
            <tbody id="teachers">
            <?php if (isset($competence_criterion['CompetenceCriterionTeacher']) && is_array($competence_criterion['CompetenceCriterionTeacher'])): ?>
                <?php $i = 0 ?>
                <?php foreach ($competence_criterion['CompetenceCriterionTeacher'] as $competenceCriterionTeacher): ?>
                    <?php
                        $id = isset($competenceCriterionTeacher['id']) ? $competenceCriterionTeacher['id'] : '_new_0' . $i;
                        $full_name = isset($competenceCriterionTeacher['Teacher']['full_name'])
                            ? $competenceCriterionTeacher['Teacher']['full_name']
                            : "{$competenceCriterionTeacher['Teacher']['first_name']} {$competenceCriterionTeacher['Teacher']['last_name']}";
                    ?>
                    <tr id="teachers_row_<?php echo $i?>">
                        <td>
                            <?php echo h($full_name) ?>
                        <td>
                            <?php if ($auth_is_admin || $auth_is_coordinator): ?>
                                <input type="hidden" name="data[CompetenceCriterionTeacher][<?php echo $id ?>][Teacher][full_name]" value="<?php echo htmlspecialchars($full_name) ?>">
                                <input type="hidden" name="data[CompetenceCriterionTeacher][<?php echo $id ?>][teacher_id]" value="<?php echo $competenceCriterionTeacher['teacher_id'] ?>">
                                <span data-action="delete"><a href="javascript:;" onclick="deleteRow('teachers', <?php echo $i ?>)">Eliminar</a></span>
                                <span data-action="cancel" style="display:none;"><a href="javascript:;" onclick="cancelRow('teachers', <?php echo $i ?>)">Cancelar</a></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php $i++; ?>
                <?php endforeach;?>
            <?php endif;?>
            </tbody>
        </table>
    </div>
</fieldset>