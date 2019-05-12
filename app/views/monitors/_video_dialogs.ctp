<div id="video-dialog" style="display:none">
</div>

<template id="video-template">
    <video width="100%" controls autoplay muted>
        <source class="video-source" src="" type="">
        Tu navegador no soporta la visualización de videos.
    </video>
</template>

<template id="youtube-template">
    <div class="embed-video-container">
        <div class="player"></div>
    </div>
</template>

<template id="vimeo-template">
    <div class="embed-video-container">
        <iframe frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen src="https://player.vimeo.com/video/%id%?byline=0&portrait=0&title=0&background=0&mute=1&loop=0&autoplay=0&autopause=0&id=%id%"></iframe>
    </div>
</template>

<script src="https://player.vimeo.com/api/player.js"></script>

<script type="text/javascript">
    /* youtube api */
    var tag = document.createElement('script');

    tag.src = "https://www.youtube.com/iframe_api";
    var firstScriptTag = document.getElementsByTagName('script')[0];
    firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
    
    var onYouTubeIframeAPIReadyCallbacks = [];
    var onYouTubeIframeAPIReadyContext;

    function onYouTubeIframeAPIReady() {
        onYouTubeIframeAPIReadyContext = this;
        onYouTubeIframeAPIReadyCallbacks.forEach(function (callback) {
            callback.apply(onYouTubeIframeAPIReadyContext);
        });
    }

    function waitUntilYoutubeIframeAPIReady(callback) {
        if (onYouTubeIframeAPIReadyContext) {
            callback.apply(onYouTubeIframeAPIReadyContext);
        } else {
            onYouTubeIframeAPIReadyCallbacks.push(callback);
        }
    }

    /* view code */
    function openVideo(src, mimeType) {
        var template = document.getElementById('video-template').content.cloneNode(true);
        var videoSource = template.querySelector('.video-source');
        videoSource.src = src;
        videoSource.type = mimeType;
        openDialog($('#video-dialog').empty().append(template));
    }

    function openYoutube(id) {
        waitUntilYoutubeIframeAPIReady(function () {
            var template = document.getElementById('youtube-template').content.cloneNode(true);
            var playerContainer = template.querySelector('.embed-video-container');
            var playerElement = template.querySelector('.player');
            openDialog($('#video-dialog').empty().append(template));
            var player = new YT.Player(playerElement, {
                width: '100%',
                height: '100%',
                videoId: id,
                playerVars: { 'autoplay': 0, 'controls': 1, 'fs': 1, 'iv_load_policy': 3, 'loop': 0, 'modestbranding': 1 },
                events: {
                    'onReady': function (e) {
                        player.setVolume(0);
                        player.playVideo();
                    }
                }
            });
        });
    }

    function openVimeo(id) {
        var template = document.getElementById('vimeo-template').content.cloneNode(true);
        var iframe = template.querySelector('iframe');
        iframe.src = iframe.src.replace(/%id%/g, id)
        openDialog($('#video-dialog').empty().append(template));
        var player = new Vimeo.Player(iframe);
        player.ready().then(function() {
            player.setVolume(0);
            player.play();
        });
    }

    function openDialog(element) {
        element.dialog({
            width:'400px',
            position: {at: 'top'},
            create: function(event, ui) {
                var widget = $(event.target).dialog('widget');
                widget.find(widget.draggable("option", "handle")).addTouch();
                widget.find('.ui-resizable-handle').addTouch();
            },
            close: function(event, ui)
            {
                $(this).dialog("close");
                $(this).empty();
            }
        });
    }
</script>
