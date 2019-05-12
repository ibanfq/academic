<!-- File: /app/views/monitors/new.ctp -->
<?php $html->addCrumb('Aulas', '/classrooms'); ?>
<?php $html->addCrumb('Monitores', '/monitors'); ?>
<?php $html->addCrumb($monitor['Monitor']['name'], "/monitors/view/{$monitor['Monitor']['id']}"); ?>
<?php $html->addCrumb('Modificar contenido multimedia', "/monitors/edit/{$monitor['Monitor']['id']}"); ?>

<h1>Modificar contenido multimedia monitor</h1>
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
        <legend>Contenido multimedia</legend>
        <div class="horizontal-scrollable-content">
        <table>
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Contenido</th>
                        <th>Duración</th>
                        <th>Visible</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($monitor['MonitorMedia'] as $row): ?>
                    <tr>
                        <td><?php echo $row['type'] ?></td>
                        <td><?php
                            if ($row['type'] === 'Imagen') {
                                echo '<img src="'. htmlspecialchars(PATH.'/'.$row['src']). '" width="120"></img>';
                            } elseif ($row['type'] === 'Video') {
                                echo '<a href="#" onclick="openVideo(\'' . htmlspecialchars(PATH.'/'.$row['src']) .'\', \'' . htmlspecialchars($row['mime_type']) .'\'); return false;">Ver video</a>';
                            } elseif ($row['type'] === 'Youtube') {
                                echo '<a href="#" onclick="openYoutube(\'' . htmlspecialchars($row['video_id']) .'\'); return false;">Ver video</a>';
                            } elseif ($row['type'] === 'Vimeo') {
                                echo '<a href="#" onclick="openVimeo(\'' . htmlspecialchars($row['video_id']) .'\'); return false;">Ver video</a>';
                            }
                        ?></td>
                        <td><?php echo $row['duration'] ? $row['duration'].' sg' : '' ?></td>
                        <td><?php echo $row['visible'] ? 'Si' : 'No' ?></td>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </fieldset>

<?php
    echo $form->end('Modificar');
?>

<script type="text/javascript">
    $(function() {
		
	});    
</script>