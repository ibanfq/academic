<div id="content-board">
    <table id="events">
        <thead id="events-head">
            <tr>
                <th class="center" style="width: 80px;">Hora</th>
                <th class="center" style="width: 110px;">Asig.</th>
                <th class="center" style="width: 300px;">Actividad</th>
                <th class="center" style="width: 100px;">Grupo</th>
                <th class="center" style="width: 250px;">Profesor</th>
                <th class="center">Aula</th>
            </th>
        </thead>
        <tbody id="events-body">
            <?php foreach ($events as $event): ?>
                <?php
                    $initial_date = date_create($event['initial_hour']);
                    $final_date = date_create($event['final_hour']);
                ?>
                <tr data-end-at="<?php echo $final_date->format('Y-m-d H:i:s') ?>" style="display:none;">
                    <td class="center"><?php echo $initial_date->format('H:i'); ?></td>
                    <td class="center"><?php echo $event['subject_acronym']; ?></td>
                    <td class="center activity">
                        <div class="vcenter-outer">
                            <div class="vcenter <?php echo $event['type'] === 'booking'? 'booking' : $activityHelper->getActivityClassName($event['type']); ?>">
                                <small><?php echo $event['name']; ?></small>
                            </div>
                        </div>
                    </td>
                    <td class="center"><small><?php echo ucfirst(preg_replace('/^grupo\s/i', '', $event['group_name'])); ?></small></td>
                    <td class="center"><small><?php echo preg_replace('/\s\.{3}$/', '...', $text->truncate("{$event['teacher_first_name']} {$event['teacher_last_name']}", 25)); ?></small></td>
                    <td class="center"><?php echo intval($event['classroom_id']) === -1? 'Todas' : ucfirst(preg_replace('/^aula\s/i', '', $event['classroom_name'])); ?></td>
                </tr>
            <?php endforeach;?>
        </tbody>
    </table>
</div>

<?php if (empty($ajax_section)): ?>
    <script type="text/javascript">
    function refresh() {
        var location = document.createElement('a');
        location.href = window.location.href;
        location.pathname = location.pathname + '/ajax_section:content-board';

        $.get(location.href, function(data) {
            $('#events-body').replaceWith($(data).find('#events-body'));
            events_updated = true;
            $(window).resize();
        });
    }

    var last_minutes;
    var scrolling = false;
    var events_updated = true;
    var scroll_timeout = 5*1000;
    var scroll_velocity = 5;
    var overflowed;

    function tick() {
        var now = new Date();
        var minutes = now.getMinutes();

        if (last_minutes != minutes) {
            if (typeof(last_minutes) === 'number' && now.getMinutes() % 15 === 0) {
                events_updated = false;
            }
            last_minutes = minutes;

            if (!scrolling) {
                if (events_updated) {
                    var rows = $('#events-body tr');
                    if (rows.length) {
                        var Y = now.getFullYear();
                        var m = ('0'+(1+now.getMonth())).slice(-2);
                        var d = ('0'+now.getDate()).slice(-2);
                        var h = ('0'+now.getHours()).slice(-2);
                        var i = ('0'+now.getMinutes()).slice(-2);
                        var s = ('0'+now.getSeconds()).slice(-2);
                        var date = Y+'-'+m+'-'+d+' '+h+':'+i+':'+s;
                        rows.each(function() {
                            if (this.getAttribute('data-end-at') <= date) {
                                $(this).remove();
                            }
                        });
                        checkOverflow();
                    }
                } else {
                    refresh();
                }
            }
        }
    }

    function checkOverflow() {
        var tbody = $('#events-body');
        var last_overflowed = overflowed;
        overflowed = parseInt(tbody.height() - ($(window).height() - tbody.offset().top)) > 0;
        if (last_overflowed !== overflowed) {
            $('#footer')[overflowed? 'fadeIn' : 'fadeOut']();
        }
    }

    function scroll() {
        if (!overflowed) {
            setTimeout(scroll, scroll_timeout);
            return;
        }
        scrolling = true;
        var table = $('#events');
        var tbody = $('#events-body');
        var toScroll = parseInt(tbody.height() - ($(window).height() - tbody.offset().top)/2);
        var duration = toScroll*100/scroll_velocity;
        table.animate({'margin-top': -toScroll}, {
            duration: duration,
            easing: 'linear',
            step: checkOverflow,
            complete: function() {
                toScroll = parseInt(tbody.height());
                duration = (toScroll + parseInt(table.css('margin-top')))*100/scroll_velocity;
                tbody.fadeOut(duration);
                table.animate({'margin-top': -toScroll}, {
                    duration: duration,
                    easing: 'linear',
                    complete: function() {
                        tbody.stop().hide();
                        table.css('margin-top', 0);
                        scrolling = false;
                        last_minutes = null;
                        tick();
                        tbody.fadeIn('slow');
                        checkOverflow();
                        setTimeout(scroll, scroll_timeout);
                    }
                });
            }
        });
    }

    $(window).load(function() {
        $(window).resize(function () {
            var now = new Date();
            var date = now.getFullYear()
                + '-' + ('0'+(1+now.getMonth())).slice(-2)
                + '-' + ('0'+now.getDate()).slice(-2)
                + ' ' + ('0'+now.getHours()).slice(-2)
                + ':' + ('0'+now.getMinutes()).slice(-2)
                + ':' + ('0'+now.getSeconds()).slice(-2)
            ;
            var headerPaddingBottom = $('#header-padding-bottom');
            var table = $('#events');
            var thead = $('#events-head').css('position', 'static');
            var headCells = thead.find('th');
            var rows = $('#events-body tr').each(function() {
                if (this.getAttribute('data-end-at') <= date) {
                    $(this).remove();
                } else {
                    $(this).show();
                }
            });
            var lastRowCells = rows.last().find('td:visible').css('width', 'auto');

            headerPaddingBottom.css('height', thead.height());
            thead
                .css('position', 'absolute')
                .css('top', headerPaddingBottom.offset().top)
                .css('width', table.width())
                .css('z-index', 9000);

            headCells.each(function(i) {
                var th = $(this);
                if (th.data('original-width')) {
                    th.css('width', th.data('original-width'));
                } else {
                    th.data('original-width', (th.attr('style')||'').match(/(?:^|\s)width\s*:/)? th.css('width'): 'auto');
                }
            });
            if (lastRowCells.length) { 
                lastRowCells.each(function(i) {
                    $(headCells[i]).css('width', $(lastRowCells[i]).width());
                    $(lastRowCells[i]).css('width', $(lastRowCells[i]).width());
                });
            }
            checkOverflow();
        }).resize();
        
        setInterval(tick, 500);
        setTimeout(scroll, scroll_timeout);
    });

    </script>
<?php endif; ?>