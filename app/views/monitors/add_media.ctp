<!-- File: /app/views/monitors/new.ctp -->
<?php $html->addCrumb('Aulas', '/classrooms'); ?>
<?php $html->addCrumb('Monitores', '/monitors'); ?>
<?php $html->addCrumb($monitor['Monitor']['name'], "/monitors/view/{$monitor['Monitor']['id']}"); ?>
<?php $html->addCrumb('Añadir contenido multimedia', "/monitors/add_media/{$monitor['Monitor']['id']}"); ?>

<h1>Añadir contenido multimedia</h1>
<?php
    echo $form->create('MonitorMedia', array('url' => "/monitors/add_media/{$monitor['Monitor']['id']}", 'enctype' => 'multipart/form-data'));
?>
    <fieldset>
    <legend>Datos generales</legend>
    <?php echo $form->input('type', array('id' => 'monitor-media-type', 'label' => 'Tipo', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>', 'options' => array("Imagen" => "Imagen", "Video" => "Video", "Youtube" => "Youtube", "Vimeo" => "Vimeo"))); ?>
    <div class="input file required" id="upload-group">
        <dl>
            <dt><label for="monitor-media-src-upload">Fichero a subir</label></dt>
            <dd><?php echo $form->file('src', array('type'=>'file', 'id' => 'monitor-media-src-upload')); ?></dd>
        </dl>
        <div class="help-text">
            <div>
                Tamaño máximo: <?php echo $uploadMaxSize ?><br>
            </div>
            <div id="image-group">
                <br>Dimensiones máximas en pantalla dividida:
                <ul>
                    <li>Monitor 1080p: 960x920</li>
                    <li>Monitor 720p: 640x560</li>
                </ul>

                <br>Dimensiones máximas en pantalla completa:
                <ul>
                    <li>Monitor 1080p: 1920x960</li>
                    <li>Monitor 720p: 1280×600</li>
                </ul>
            </div>
            <span id="video-group">Resolución recomendada: 720x480</span>
        </div>
    </div>
    <?php echo $form->input('src', array('label' => 'Dirección de Youtube', 'id' => 'monitor-media-src-youtube', 'placeholder' => 'https://youtu.be/EngW7tLk6R8', 'div' => array('id' => 'youtube-group'), 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
    <?php echo $form->input('src', array('label' => 'Dirección de Vimeo', 'id' => 'monitor-media-src-vimeo', 'placeholder' => 'https://vimeo.com/253989945', 'div' => array('id' => 'vimeo-group'), 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); ?>
    <div class="input text" id="duration-group">
        <dl>
            <dt><label for="monitor-media-duration">Duración en segundos</label></dt>
            <dd><input name="data[MonitorMedia][duration]" type="number" step="1" id="monitor-media-duration" value="<?php echo isset($form->data['MonitorMedia']['duration'])? $form->data['MonitorMedia']['duration'] : '' ?>"></dd>
        </dl>
    </div>
    <?php echo $form->input('visible', array('label' => 'Visible')); ?>
    </fieldset>

<div class="submit">
    <?php echo $form->submit('Crear', array('name' => 'data[action][add]', 'div'=>false)); ?>
    <?php echo $form->submit('Crear y añadir otro', array('name' => 'data[action][add_and_new]', 'div'=>false)); ?>
</div>
<?php echo $form->end(); ?>

<script type="text/javascript">
    $(document).ready(function() {
        $('#monitor-media-type').change(function() {
            var value = $(this).val();
            var isImage = value === 'Imagen';
            var isVideo = value === 'Video';
            var isUpload = isImage || isVideo;
            var isYoutube = value === 'Youtube';
            var isVimeo = value === 'Vimeo';
            $('#image-group').toggle(isImage).find('input').prop('disabled', !isImage);
            $('#video-group').toggle(isVideo).find('input').prop('disabled', !isVideo);
            $('#upload-group').toggle(isUpload).find('input').prop('disabled', !isUpload);
            $('#youtube-group').toggle(isYoutube).find('input').prop('disabled', !isYoutube);
            $('#vimeo-group').toggle(isVimeo).find('input').prop('disabled', !isVimeo);
            $('#duration-group').toggle(isImage).find('input').prop('disabled', !isImage);
        }).change();
	});
</script>
