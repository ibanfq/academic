<?php

App::import('model', 'academicModel');

class MonitorMedia extends AcademicModel {
    var $name = 'MonitorMedia';

    var $youtubeRegex = '#(?:https?://)?(?:www\.)?(?:youtube\.com/(?:v/|watch\?v=)|youtu\.be/)(?P<id>[\w-]+)#';
    var $vimeoRegex   = '#(?:https?://)?(?:www\.)?vimeo\.com/(?P<id>[\w-]+)#';

    var $validate = array(
        'type' => array(
            'notEmpty' => array(
                'rule' => 'notEmpty',
                'required' => true,
                'message' => 'Debe especificar el tipo del media'
            )
        ),
        'src' => array(
            'notEmpty' => array(
                'rule' => 'notEmpty',
                'required' => true,
                'message' => 'Debe especificar un valor para el media'
            )
        ),
    );

    function getFileMimeType($path) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime_type = $finfo->file($path);
        if ($mime_type === 'image/jpg') {
            $mime_type = 'image/jpeg';
        }
        return $mime_type;
    }

    function isValidImageFile($path) {
        return isValidImageMimeType($this->getFileMimeType($path));
    }

    function isValidVideoFile($path) {
        return isValidVideoMimeType($this->getFileMimeType($path));
    }


    function isValidImageMimeType($mimeType) {
        return in_array($mimeType, array('image/jpeg', 'image/png', 'image/gif'), true);
    }

    function isValidVideoMimeType($mimeType) {
        return in_array($mimeType, array('video/mp4', 'video/webm'), true);
    }

    function isValidYoutubeUrl($url) {
        return preg_match($this->youtubeRegex, $url);
    }

    function isValidVimeoUrl($url) {
        return preg_match($this->vimeoRegex, $url);
    }

    function extractYoutubeId($url) {
        if (preg_match($this->youtubeRegex, $url, $matches)) {
            return isset($matches['id']) ? $matches['id'] : false;
        }
        return false;
    }
    function extractVimeoId($url) {
        if (preg_match($this->vimeoRegex, $url, $matches)) {
            return isset($matches['id']) ? $matches['id'] : false;
        }
        return false;
    }
}
