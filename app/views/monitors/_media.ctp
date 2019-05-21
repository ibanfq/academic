<div id="content-media">
    <?php foreach ($monitor['MonitorMedia'] as $media): ?>
        <?php if ($media['visible']): ?>
            <div class="media-item" data-media-type="<?php echo htmlspecialchars($media['type']) ?>" data-media-duration="<?php echo $media['duration'] ?: '' ?>">
                <?php if ($media['type'] === 'Imagen'): ?>
                    <img src="<?php echo htmlspecialchars(PATH.'/'.$media['src']) ?>">
                <?php elseif ($media['type'] === 'Video'): ?>
                    <video muted>
                        <source class="video-source" src="<?php echo htmlspecialchars(PATH.'/'.$media['src']) ?>" type="<?php echo htmlspecialchars($media['mime_type']) ?>" />
                        Tu navegador no soporta la visualización de videos.
                    </video>
                <?php elseif ($media['type'] === 'Youtube'): ?>
                    <div class="embed-video-container">
                        <div class="youtube-player" data-video-id="<?php echo htmlspecialchars($media['video_id']) ?>"></div>
                    </div>
                <?php elseif ($media['type'] === 'Vimeo'): ?>
                    <div class="embed-video-container">
                        <iframe frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen src="https://player.vimeo.com/video/<?php echo htmlspecialchars($media['video_id']) ?>?byline=0&portrait=0&title=0&background=0&mute=1&loop=0&autoplay=0&autopause=0&id=<?php echo htmlspecialchars($media['video_id']) ?>"></iframe>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>

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
                    video.currentTime = 0;
                }, itemHasClasses(item, animationOut) ? animationDuration : 0);
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

                if (!nextItem.length) {
                    nextItem = parent.children(':first');
                }

                if (!prevItem.length) {
                    prevItem = parent.children(':last');
                }

                if (prevItem.hasClass('media-item-current')) {
                    prevItem.removeClass('media-item-current').removeClass(animationIn).addClass(animationOut);
                }
                item.addClass('media-item-current').removeClass(animationOut).addClass(animationIn);

                if (nextItem) {
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