<!-- File: /app/views/monitors/new.ctp -->
<?php $html->addCrumb('Aulas', '/classrooms'); ?>
<?php $html->addCrumb('Monitores', '/monitors'); ?>
<?php $html->addCrumb('Crear monitor', "/monitors/add"); ?>

<h1>Crear monitor</h1>
<?php
    echo $form->create('Monitor');
?>
    <fieldset>
    <legend>Datos generales</legend>
        <?php echo $form->input('name', array('label' => 'Nombre', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
        <?php echo $form->input('show_events', array('label' => 'Mostrar eventos de las aulas')); ?>
    <?php echo $form->input('show_media', array('label' => 'Mostrar contenido multimedia')); ?>
    </fieldset>
<?php
    echo $form->end('Crear');
?>