<table>
    <thead>
        <tr>
            <th style="width: 180px">Hora</th>
            <th style="width: 60%">Actividad</th>
            <th>Aula</th>
        </th>
    </thead>
    <tbody id="events">
        <?php foreach ($events as $event): ?>
            <?php
                $initial_date = date_create($event['Event']['initial_hour']);
            ?>
            <tr data-datetime="<?php echo $initial_date->format('Y-m-d H:i:s') ?>" class="<?php echo $activityHelper->getActivityClassName($event['Activity']['type']); ?>">
                <td><?php echo $initial_date->format('d/m H:s'); ?></td>
                <td>
                    <?php echo "{$event['Subject']['acronym']} | {$event['Activity']['name']}"; ?>
                    <br /><small><?php echo "Grupo: {$event['Group']['name']}"; ?></small>
                </td>
                <td><?php echo $event['Classroom']['name']; ?></td>
            </tr>
        <?php endforeach;?>
    </tbody>
</table>

<aside id="clock"></aside>

<script type="text/javascript">
function refresh() {
    $.get(window.location.href, function(data) {
        $('#events').replaceWith($(data).find('#events'))
    });
}

var last_minute;

function tick() {
    var now = new Date();
    var Y = now.getFullYear();
    var m = ('0'+(1+now.getMonth())).slice(-2);
    var d = ('0'+now.getDate()).slice(-2);
    var h = ('0'+now.getHours()).slice(-2);
    var i = ('0'+now.getMinutes()).slice(-2);
    var s = ('0'+now.getSeconds()).slice(-2);
    var time = h+':'+i+':'+s;
    document.getElementById('clock').innerHTML = time;

    if (last_minute !== i) {
        last_minute = i;
        var rows = $('#events tr');
        for (var i in rows) {
            if (rows[i].getAttribute('data-datetime') <= (Y+'-'+m+'-'+d+' '+time)) {
                $(rows[i]).remove();
            } else {
                break;
            }
        }
        if (time === '04:00:00') {
            refresh();
        }
    }
}

setInterval(tick, 500);
</script>