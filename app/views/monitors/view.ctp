<!-- File: /app/views/users/view.ctp -->
<?php $html->addCrumb('Aulas', '/classrooms'); ?>
<?php $html->addCrumb('Monitores', '/monitors'); ?>
<?php $html->addCrumb($monitor['Monitor']['name'], "/monitors/view/{$monitor['Monitor']['id']}"); ?>

<h1><?php echo $monitor['Monitor']['name']?></h1>
<div class="actions">
    <ul>
        <?php if ($auth->user('type') == "Administrador"): ?>
            <li><?php echo $html->link('Modificar monitor', array('action' => 'edit', $monitor['Monitor']['id'])) ?></li>
            <li><?php echo $html->link('Añadir contenido multimedia', array('action' => 'add_media', $monitor['Monitor']['id'])) ?></li>
            <li><?php echo $html->link('Eliminar monitor', array('action' => 'delete', $monitor['Monitor']['id']), null, 'Cuando elimine un monitor, elimina también todo su contenido. ¿Está seguro que desea borrarlo?') ?></li>
        <?php endif; ?>
        <?php if (($auth->user('type') != "Estudiante") && ($auth->user('type') != "Profesor")): ?>
			<li><?php echo $html->link('Presentar en TV', array('controller' => 'monitors', 'action' => 'show', $monitor['Monitor']['id'])) ?></li>
		<?php endif; ?>
    </ul>
</div>

<div class="view">
    <fieldset>
    <legend>Datos generales</legend>
        <dl>
            <dt>Mostrar eventos</dt>
            <dd><?php echo $monitor['Monitor']['show_events']? 'Sí' : 'No'?></dd>
            <dt>Mostrar contenido multimedia</dt>
            <dd><?php echo $monitor['Monitor']['show_media']? 'Sí' : 'No'?></dd>
        </dl>
    </fieldset>

    <fieldset>
        <legend>Aulas</legend>
        <div class="horizontal-scrollable-content">
            <table>
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($monitor['Classroom'] as $row): ?>
                    <tr>
                        <td><?php echo $html->link($row['id'], array('controller' => 'classrooms', 'action' => 'view', $row['id'])) ?></td>
                        <td><?php echo $row['name'] ?></td>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
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
                                echo '<img src="'. htmlspecialchars(PATH.'/'.$row['src']). '" width="120" />';
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
</div>

<?php require('_video_dialogs.ctp') ?>
