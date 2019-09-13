<div id="content-media">
    <?php foreach ($monitor['MonitorMedia'] as $media): ?>
        <?php if ($media['visible']): ?>
            <div class="media-item" data-media-type="<?php echo htmlspecialchars($media['type']) ?>" data-media-duration="<?php echo $media['duration'] ?: '' ?>">
                <?php if ($media['type'] === 'Imagen'): ?>
                    <img src="<?php echo htmlspecialchars('/'.$media['src']) ?>">
                <?php elseif ($media['type'] === 'Video'): ?>
                    <video muted>
                        <source class="video-source" src="<?php echo htmlspecialchars('/'.$media['src']) ?>" type="<?php echo htmlspecialchars($media['mime_type']) ?>" />
                        Tu navegador no soporta la visualización de videos.
                    </video>
                <?php elseif ($media['type'] === 'Youtube'): ?>
                    <div class="embed-video-container">
                        <div class="player" data-video-id="<?php echo htmlspecialchars($media['video_id']) ?>"></div>
                    </div>
                <?php elseif ($media['type'] === 'Vimeo'): ?>
                    <div class="embed-video-container">
                        <div class="player" data-video-id="<?php echo htmlspecialchars($media['video_id']) ?>"></div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>

<template id="vimeo-template">
    <iframe frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen src="https://player.vimeo.com/video/%id%?byline=0&portrait=0&title=0&background=1&mute=1&loop=1&autoplay=0&autopause=0&id=%id%"></iframe>
</template>


<?php if (empty($ajax_section)): ?>
    <script src="https://player.vimeo.com/api/player.js"></script>

    <script type="text/javascript">
        /* youtube api */
        var tag = document.createElement('script');

        tag.src = "https://www.youtube.com/iframe_api";
        var firstScriptTag = document.getElementsByTagName('script')[0];
        firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
        
        var onYouTubeIframeAPIReadyCallbacks = [];
        var onYouTubeIframeAPIReadyContext;

        var animationIn = 'animated slow fadeIn delay-2s';
        var animationOut = 'animated slow fadeOut';
        var animationDuration = 2000;
        var animationDelay = 2000;

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

        function itemHasClasses(item, classes) {
            return classes.split(' ').every(function (className) {
                return item.hasClass(className);
            });
        }

        function prepareMediaItem(item) {
            var mediaType = item.data('mediaType');

            var onReady = function () {
                item.addClass('media-item-ready');
                if (item.hasClass('media-item-waiting-ready')) {
                    item.removeClass('media-item-waiting-ready');
                    showMediaItem(item);
                }
            }

            if (mediaType === 'Imagen') {
                onReady();
            } else if (mediaType === 'Video') {
                var video = item.find('video')[0];
                video.onseeked = onReady;
                setTimeout(function () {
                    video.currentTime = 0; // trigger onseeked event
                }, itemHasClasses(item, animationOut) ? animationDuration : 0);
            } else if (mediaType === 'Vimeo') {
                var playerElement = item.find('.player');
                var player = playerElement.data('vimeo-player');
                var template, iframe, videoId;
                if (player) {
                    onReady();
                } else {
                    videoId = playerElement.data('video-id');
                    template = document.getElementById('vimeo-template').content.cloneNode(true);
                    iframe = template.querySelector('iframe');
                    iframe.src = iframe.src.replace(/%id%/g, videoId)
                    playerElement.append(template);
                    var player = new Vimeo.Player(iframe);
                    player.ready().then(function() {
                        item.find('.player').data('vimeo-player', player);
                        player.setVolume(0);
                        player.play();
                        player.pause();
                        player.setCurrentTime(0);
                        player.rewinding = false;
                        player.on('timeupdate', function (e) {
                            var previous = player.videoSeconds;
                            var ended = e.secods === e.duration || typeof previous === 'number' && previous > e.seconds;
                            var isEnding = !ended && e.duration + 0.5 < e.seconds + animationDuration/1000;
                            player.videoSeconds = e.seconds;
                            if (isEnding && !player.isEndingCalled) {
                                player.isEndingCalled = true;
                                player.onVideoEnding && player.onVideoEnding.call(this);
                            } else if (ended) {
                                if (player.rewinding) {
                                    player.rewinding = false;
                                } else {
                                    player.onVideoEnded && player.onVideoEnded.call(this);
                                }
                            }
                            if (!isEnding) {
                                player.isEndingCalled = false;
                            }
                        });
                        onReady();
                    });
                }
            } else if (mediaType === 'Youtube') {
                waitUntilYoutubeIframeAPIReady(function () {
                    var playerContainer = item.find('.embed-video-container');
                    var playerElement = item.find('.player');
                    var player = playerElement.data('youtube-player');
                    var videoId;
                    if (player) {
                        player.playVideo();
                        onReady();
                    } else {
                        videoId = playerElement.data('video-id');
                        player = new YT.Player(playerElement[0], {
                            width: '100%',
                            height: '100%',
                            videoId: videoId,
                            playerVars: { 'autoplay': 0, 'controls': 1, 'fs': 1, 'iv_load_policy': 3, 'loop': 1, 'playlist': videoId, 'modestbranding': 1 },
                            events: {
                                'onReady': function (e) {
                                    item.find('.player').data('youtube-player', player);
                                    player.setVolume(0);
                                    player.playVideo();
                                    onReady();
                                    setInterval(function () {
                                        var previous = player.videoSeconds;
                                        var duration = player.getDuration();
                                        var seconds = player.getCurrentTime();
                                        var ended = seconds === duration || typeof previous === 'number' && previous > seconds;
                                        var isEnding = !ended && duration + 0.5 < seconds + animationDuration/1000;
                                        player.videoSeconds = seconds;
                                        if (isEnding && !player.isEndingCalled) {
                                            if (!item.hasClass('media-item-current')) {
                                                player.rewinding = true;
                                                player.seekTo(0);
                                            } else {
                                                player.onVideoEnding && player.onVideoEnding.call(this);
                                            }
                                            player.isEndingCalled = true;
                                        } else if (ended) {
                                            if (player.rewinding) {
                                                player.rewinding = false;
                                            } else {
                                                player.onVideoEnded && player.onVideoEnded.call(this);
                                            }
                                        }
                                        if (!isEnding) {
                                            player.isEndingCalled = false;
                                        }
                                    }, 250);
                                }
                            }
                        });
                    };
                });
            }
        }

        function showMediaItem(item) {
            if (!item.hasClass('media-item-ready')) {
                item.addClass('media-item-waiting-ready');
            } else {
                var mediaType = item.data('mediaType');
                var parent = item.parent();
                var nextItem = item.next();
                var prevItem = item.prev();

                item.removeClass('media-item-next');

                if (!nextItem.length) {
                    nextItem = parent.children(':first');
                }

                if (!prevItem.length) {
                    prevItem = parent.children(':last');
                }

                if (nextItem[0] === item[0]) {
                    nextItem = null;
                }

                if (prevItem.hasClass('media-item-current')) {
                    prevItem.removeClass('media-item-current').removeClass(animationIn).addClass(animationOut);
                }
                item.addClass('media-item-current').removeClass(animationOut).addClass(animationIn);

                if (nextItem) {
                    nextItem.addClass('media-item-next');
                    prepareMediaItem(nextItem);
                }

                if (mediaType === 'Imagen') {
                    if (nextItem && item.data('mediaDuration')) {
                        setTimeout(function () {
                            showMediaItem(nextItem);
                        }, animationDuration + animationDelay + 1000 * item.data('mediaDuration'));
                    }
                } else if (mediaType === 'Video') {
                    var video = item.find('video')[0];
                    video.onended = function () {
                        if (nextItem) {
                            showMediaItem(nextItem);
                        } else {
                            video.play();
                        }
                    }
                    setTimeout(function () {
                        video.play();
                    }, animationDelay);
                } else if (mediaType === 'Vimeo') {
                    var playerContainer = item.find('.embed-video-container');
                    var player = item.find('.player').data('vimeo-player');
                    player.onVideoEnding = function () {
                        if (nextItem) {
                            player.onVideoEnding = null;
                            showMediaItem(nextItem);
                            setTimeout(function () {
                                player.pause();
                                player.rewinding = true;
                                player.setCurrentTime(0);
                            }, animationDuration);
                        }
                    };
                    player.onVideoEnded = function () {
                        if (nextItem) {
                            player.pause();
                            player.onVideoEnded = null;
                            player.onVideoEnding && player.onVideoEnding.call(this);
                        }
                    }
                    setTimeout(function () {
                        player.play();
                    }, animationDelay);
                } else if (mediaType === 'Youtube') {
                    var playerContainer = item.find('.embed-video-container');
                    var playerElement = item.find('.player');
                    var player = playerElement.data('youtube-player');
                    player.onVideoEnding = function (e) {
                        if (nextItem) {
                            player.onVideoEnding = null;
                            showMediaItem(nextItem);
                            setTimeout(function () {
                                if (!item.hasClass('media-item-next')) {
                                    player.pauseVideo();
                                }
                            }, animationDuration);
                        }
                    };
                    player.onVideoEnded = function () {
                        if (nextItem) {
                            player.onVideoEnded = null;
                            player.onVideoEnding && player.onVideoEnding.call(this);
                        }
                    }
                    player.rewinding = true;
                    player.seekTo(0);
                    setTimeout(function () {
                        player.rewinding = true;
                        player.seekTo(0);
                    }, animationDelay);
                }
            }
        }

        $(window).load(function() {
            var firstItem = $('#content-media .media-item:first');
            prepareMediaItem(firstItem);
            showMediaItem(firstItem);
        });
    </script>
<?php endif; ?>