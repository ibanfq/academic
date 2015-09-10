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
                <td class="center"><small><?php echo $text->truncate("{$event['teacher_first_name']} {$event['teacher_last_name']}", 25); ?></small></td>
                <td class="center"><?php echo ucfirst(preg_replace('/^aula\s/i', '', $event['classroom_name'])); ?></td>
            </tr>
        <?php endforeach;?>
    </tbody>
</table>

<aside id="clock">
    <h2>Hora local</h2>
    <p id="time"></p>
</aside>

<script type="text/javascript">
function refresh() {
    $.get(window.location.href, function(data) {
        $('#events-body').replaceWith($(data).find('#events-body'));
        $(window).resize();
    });
}

var last_minutes;
var scrolling = false;
var events_updated = true;
var scroll_timeout = 30*1000;
var scroll_velocity = 5;
var overflowed;

function tick() {
    var now = new Date();
    var h = ('0'+now.getHours()).slice(-2);
    var i = ('0'+now.getMinutes()).slice(-2);
    var s = ('0'+now.getSeconds()).slice(-2);
    var time = h+':'+i;
    document.getElementById('time').innerHTML = time;

    if (last_minutes != i) {
        last_minutes = i;
        if (time === '04:00') {
            events_updated = false;
        }

        if (!scrolling) {
            if (events_updated) {
                var rows = $('#events-body tr');
                if (rows.length) {
                    var Y = now.getFullYear();
                    var m = ('0'+(1+now.getMonth())).slice(-2);
                    var d = ('0'+now.getDate()).slice(-2);
                    var date = Y+'-'+m+'-'+d+' '+time+':'+s;
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
    var now = new Date();
    var date = now.getFullYear()
        + '-' + ('0'+(1+now.getMonth())).slice(-2)
        + '-' + ('0'+now.getDate()).slice(-2)
        + ' ' + ('0'+now.getHours()).slice(-2)
        + ':' + ('0'+now.getMinutes()).slice(-2)
        + ':' + ('0'+now.getSeconds()).slice(-2)
    ;
    $('#events-body tr').each(function() {
        if (this.getAttribute('data-end-at') <= date) {
            $(this).remove();
        } else {
            $(this).show();
        }
    });
    $(window).resize(function () {
        var content = $('#content').css('padding-top', 0);
        var thead = $('#events-head').css('position', 'static');
        var th = thead.find('th');
        var td = $('#events-body tr:last td').css('width', 'auto');
        th.each(function(i) {
            var th_i = $(this);
            if (th_i.data('original-width')) {
                th_i.css('width', th_i.data('original-width'));
            } else {
                th_i.data('original-width', (th_i.attr('style')||'').match(/(?:^|\s)width\s*:/)? th_i.css('width'): 'auto');
            }
        });
        if (td.length) { 
            td.each(function(i) {
                $(th[i]).css('width', $(td[i]).width());
                $(td[i]).css('width', $(td[i]).width());
            });
            thead.css('position', 'fixed').css('top', thead.offset().top).css('z-index', 9000);
            content.css('padding-top', thead.height());
        }
        checkOverflow();
    }).resize();
    
    setInterval(tick, 500);
    setTimeout(scroll, scroll_timeout);
});

</script>