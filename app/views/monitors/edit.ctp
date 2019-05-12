<!-- File: /app/views/monitors/new.ctp -->
<?php $html->addCrumb('Aulas', '/classrooms'); ?>
<?php $html->addCrumb('Monitores', '/monitors'); ?>
<?php $html->addCrumb($monitor['Monitor']['name'], "/monitors/view/{$monitor['Monitor']['id']}"); ?>
<?php $html->addCrumb('Modificar monitor', "/monitors/edit/{$monitor['Monitor']['id']}"); ?>

<h1>Modificar monitor</h1>
<?php
    echo $form->create('Monitor', array('action' => 'edit'));
?>
    <fieldset>
    <legend>Datos generales</legend>
    <?php echo $form->input('name', array('label' => 'Nombre', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
    <?php echo $form->input('show_events', array('label' => 'Mostrar eventos de las aulas')); ?>
    <?php echo $form->input('show_media', array('label' => 'Mostrar contenido multimedia')); ?>
    </fieldset>

    <fieldset>
        <legend>Aulas</legend>
        <div class="horizontal-scrollable-content">
            <table>
                <thead>
                    <tr>
                        <th width="20%">Código</th>
                        <th>Nombre</th>
                        <th width="20%">Asociar aula</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr><td colspan="3"><a href="#" onclick="addClassroomRow()" title="Haga click para añadir un aula">Añadir aula</a></td></tr>
                </tfoot>
                <tbody id="classrooms">
                    <?php foreach ($form->data['Classroom'] as $row): ?>
                    <tr>
                        <td class="toogle_row">
                            <?php echo h($row['id']) ?>
                            <input type="hidden" name="data[Classroom][<?php echo h($row['id']) ?>][id]" value="<?php echo h($row['id']) ?>" />
                        </td>
                        <td class="toogle_row">
                            <?php echo h($row['name']) ?>
                            <input type="hidden" name="data[Classroom][<?php echo h($row['id']) ?>][name]" value="<?php echo h($row['name']) ?>" />
                        </td>
                        <td>
                            <input type="checkbox" class="classroom_check" name="data[Classroom][<?php echo h($row['id']) ?>][show_in_monitor]" value="1" checked />
                        </td>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </fieldset>
    <template id="classroom_row">
        <tr class="new">
            <td><span class="classroom_id"></span><input type="hidden" class="classroom_id" /></td>
            <td><input class="classroom_name" type="text" /></td>
            <td style='vertical-align:middle'><input type='checkbox' class="classroom_check" value='1' checked></td>
        </tr>
    </template>

    <fieldset>
        <legend>Contenido multimedia</legend>
        <div class="horizontal-scrollable-content">
        <table>
                <thead>
                    <tr>
                        <th></th>
                        <th>Tipo</th>
                        <th>Contenido</th>
                        <th>Duración (sg)</th>
                        <th>Visible</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="media">
                    <?php $i = -1; ?>
                    <?php foreach ($form->data['MonitorMedia'] as $row): ?>
                    <?php $i++; ?>
                    <tr>
                        <td width="10px" class="sortable-handle" style="vertical-align">
                            <span class="ui-icon ui-icon-arrowthick-2-n-s" style="margin-top: 0"></span>
                            <input type="hidden" name="data[MonitorMedia][<?php echo h($row['id']) ?>][id]" value="<?php echo h($row['id']) ?>" />
                            <input type="hidden" class="media_order" name="data[MonitorMedia][<?php echo h($row['id']) ?>][order]" value="<?php echo h($i) ?>" />
                        </td>
                        <td>
                            <?php echo $row['type'] ?>
                        </td>
                        <td>
                            <?php
                                if ($row['type'] === 'Imagen') {
                                    echo '<img src="'. htmlspecialchars(PATH.'/'.$row['src']). '" width="120"></img>';
                                } elseif ($row['type'] === 'Video') {
                                    echo '<a href="#" onclick="openVideo(\'' . htmlspecialchars(PATH.'/'.$row['src']) .'\', \'' . htmlspecialchars($row['mime_type']) .'\'); return false;">Ver video</a>';
                                } elseif ($row['type'] === 'Youtube') {
                                    echo '<a href="#" onclick="openYoutube(\'' . htmlspecialchars($row['video_id']) .'\'); return false;">Ver video</a>';
                                } elseif ($row['type'] === 'Vimeo') {
                                    echo '<a href="#" onclick="openVimeo(\'' . htmlspecialchars($row['video_id']) .'\'); return false;">Ver video</a>';
                                }
                            ?>
                        </td>
                        <td>
                            <?php if ($row['type'] === 'Imagen'): ?>
                                <input type="number" step="1"  name="data[MonitorMedia][<?php echo h($row['id']) ?>][duration]" value="<?php echo h($row['duration']) ?>" />
                            <?php endif; ?>
                        </td>
                        <td>
                            <input type="hidden" name="data[MonitorMedia][<?php echo h($row['id']) ?>][visible]" value="0" />
                            <input type="checkbox" name="data[MonitorMedia][<?php echo h($row['id']) ?>][visible]" value="1" <?php echo empty($row['visible']) ? '' : 'checked' ?> />
                        </td>
                        <td>
                            <a href="#" class="media_delete_button">Eliminar</a>
                            <a href="#" class="media_cancel_deletion_button" style="display:none;">Cancelar</a>
                            <input type="hidden" class="media_delete_input" name="data[MonitorMedia][<?php echo h($row['id']) ?>][delete]" value="<?php echo empty($row['delete']) ? 0 : 1 ?>" />
                        </td>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </fieldset>

<?php
    echo $form->end('Modificar');
?>

<?php require('_video_dialogs.ctp') ?>

<script type="text/javascript">
    $(function() {
		$('#classrooms').on('click', '.toogle_row', function () {
            var tr = $(this).closest('tr');
            var checkbox = tr.find('input.classroom_check');
            checkbox.attr('checked', checkbox.attr('checked'));
        });

        $('#media').sortable({
            handle: '.sortable-handle',
            create: function(event, ui) {
                var widget = $(event.target).sortable('widget');
                widget.find('.sortable-handle').addTouch();
            },
            start: function(event, ui) {
                ui.placeholder.height(ui.helper.outerHeight());
            },
            stop: function (event, ui) {
                var widget = $(event.target).sortable('widget');
                widget.find('.media_order').each(function(i) {
                    $(this).val(i);
                });
            },
            helper: function(e, tr) {
                var $originals = tr.children();
                var $helper = tr.clone();
                $helper.children().each(function(index)
                {
                // Set helper cell sizes to match the original sizes
                $(this).width($originals.eq(index).width());
                });
                return $helper;
            }
        });

        $('.media_delete_button').on('click', function () {
            var row = $(this).closest('tr');
            var actionsCell = $(this).closest('td');
            actionsCell.find('.media_delete_button').hide();
            actionsCell.find('.media_cancel_deletion_button').show();
            actionsCell.find('.media_delete_input').val(1);
            actionsCell.prevAll().css('opacity', 0.3).addClass('line_through');
        })
        $('.media_delete_input[value=1]').closest('td').find('.media_delete_button').click();

        $('.media_cancel_deletion_button').on('click', function () {
            var row = $(this).closest('tr');
            var actionsCell = $(this).closest('td');
            actionsCell.find('.media_delete_button').show();
            actionsCell.find('.media_cancel_deletion_button').hide();
            actionsCell.find('.media_delete_input').val(0);
            actionsCell.prevAll().css('opacity', 1).removeClass('line_through');;
        });
	});
    
    function addClassroomRow(){
		var template = document.getElementById('classroom_row').content.cloneNode(true);
        var tr = $('#classrooms').append(template).find('tr:last');
        tr.find('input.classroom_name')
            .autocomplete('<?php echo PATH ?>\/classrooms\/find_by_name', {
                formatItem: function (row) {
                    if (row[1] != null) {
                        return row[0];
                    } else {
                        return 'No existe ningún aula con este nombre.';
                    }
                }
            })
            .result(function(event, item) {
                tr.find('span.classroom_id').text(item[1]);
                tr.find('input.classroom_id').attr('name', 'data[Classroom][' + item[1] + '][id]').val(item[1]);
                tr.find('input.classroom_name').attr('name', 'data[Classroom][' + item[1] + '][name]').data('selected', item[0]);
                tr.find('input.classroom_check').attr('name', 'data[Classroom][' + item[1] + '][show_in_monitor]');
            })
            .blur(function () {
                var input = tr.find('input.classroom_name');
                input.val(input.data('selected'));
            })
            .focus();
        tr.find('input.classroom_check').on('click', function () {
            tr.remove();
        });
	}
</script>