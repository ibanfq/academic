<!-- File: /app/views/users/view.ctp -->
<?php $html->addCrumb('Aulas', '/classrooms'); ?>
<?php $html->addCrumb($classroom['Classroom']['name'], "/classrooms/view/{$classroom['Classroom']['id']}"); ?>
<h1>
<?php echo $classroom['Classroom']['name']?> - <?php echo $classroom['Classroom']['type'] ?></h1>
<div class="actions">
    <ul>
        <?php if ($auth->user('type') == "Administrador") {?>    
            <li><?php echo $html->link('Modificar aula', array('action' => 'edit', $classroom['Classroom']['id'])) ?></li>
            <li><?php echo $html->link('Eliminar aula', array('action' => 'delete', $classroom['Classroom']['id']), null, 'Cuando elimina un aula, toda sa programación. ¿Está seguro que desea borrarla?') ?></li>
        <?php } ?>
    </ul>
</div>

<div class="view">
    <fieldset>
    <legend>Datos generales</legend>
        <dl>
            <dt>Capacidad</dt>
            <dd><?php echo $classroom['Classroom']['capacity']?></dd>
            <?php if (Configure::read('app.classroom.show_tv')): ?>
                <dt>Mostrar eventos en TV</dt>
                <dd><?php echo $classroom['Classroom']['show_tv']? 'Sí' : 'No'?></dd>
            <?php endif; ?>
            <?php if (Configure::read('app.classroom.teachers_can_booking')): ?>
                <dt>Profesores pueden reservar</dt>
                <dd><?php echo $classroom['Classroom']['teachers_can_booking']? 'Sí' : 'No'?></dd>
            <?php endif; ?>
        </dl>
    </fieldset>
</div>