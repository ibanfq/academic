<?php $html->addCrumb('Reservas', '/bookings'); ?>
<?php $teachers_can_booking = Configure::read('app.classroom.teachers_can_booking'); ?>
<?php $isTeacher = $auth->user('type') == 'Profesor'; ?>

<div id="mobile-query" class="visible-block-phone-portrait"></div>

<script type="text/javascript">
    var currentEvent = null;

    function isMobile() {
        return $('#mobile-query').css('display') !== 'none';
    }

    function isMenusEnabled() {
        return <?php echo Configure::read('app.fullcalendar.menus') ? 'true' : 'false' ?>;
    }

    function copyBooking(id, start) {
        $.ajax({
            cache: false,
            type: "POST",
            url: "<?php echo PATH ?>/bookings/copy/" + id + "/initial_hour:" + toEventDateString(start) + "/classroom:" + $('#classrooms').val(),
            dataType: 'script'
        });
    }

    function deleteBooking(id, parent_id) {
        if (parent_id == '') {
            confirmated = confirm("¿Está seguro de que desea eliminar esta reserva? Al eliminarla se eliminarán también todos las reservas de la misma serie.");
        } else {
            confirmated = confirm("¿Está seguro de que desea eliminar esta reserva?");
        }
        if (confirmated) {
            $.ajax({
                cache: false,
                type: "POST",
                url: "<?php echo PATH?>/bookings/delete/" + id,
                dataType: 'script'
            });
            return true;
        }
        return false;
    }

    function toEventDateString(date) {
        var day = date.getDate();
        var month = date.getMonth() + 1;
        var year = date.getFullYear();
        var hour = date.getHours();
        var minute = date.getMinutes();

        if (day < 10)
            day = "0" + day;
        if (month < 10)
            month = "0" + month;
        if (hour < 10)
            hour = "0" + hour;
        if (minute < 10)
            minute = "0" + minute;

        return year + "/" + month + "/" + day + " " + hour + ":" + minute + ":00";
    }

    function addBooking() {
        var initial_hour = new Date($("#date").val());
        var final_hour = new Date($("#date").val());
        var new_event;

        initial_hour.setHours($('#BookingInitialHourHour').val());
        initial_hour.setMinutes($('#BookingInitialHourMin').val());
        final_hour.setHours($('#BookingFinalHourHour').val());
        final_hour.setMinutes($('#BookingFinalHourMin').val());

        var initial_date = toEventDateString(initial_hour);
        var final_date = toEventDateString(final_hour);

        $.ajax({
            cache: false,
            type: "POST",
            data: {'data[Booking][reason]': $('#BookingReason').val(), 'data[Booking][required_equipment]': $('#BookingRequiredEquipment').val(), 'data[Booking][initial_hour]': initial_date, 'data[Booking][final_hour]': final_date, 'data[Booking][user_type]': $('#BookingUserType').val(), 'data[Booking][classroom_id]': $('#AllClassrooms').attr('checked')? -1 : $('#classrooms').val()<?php if (Configure::read('app.booking.show_tv')): ?>, 'data[Booking][show_tv]': $('#ShowTV').attr('checked')? '1' : '0'<?php endif; ?>},
            url: "<?php echo PATH ?>/bookings/add/" + $('#BookingFinishedAt').val() + "/" + $('#Frequency').val(),
            asynchronous: false,
            dataType: 'script',
            success: function(data){
                $('#form').dialog('close');
            }
        });
    }

    function openEvent(event) {
        var model = event.className.indexOf('booking') !== -1? 'bookings' : 'events';
        var action = 'view';
        var id = event.id.match(/\d+/);
        $.ajax({
            cache: false,
            type: "GET",
            url: "<?php echo PATH ?>/" + model + "/" + action + "/" + id, 
            success: function(data) {
                if (data == "false")
                    alert("Usted no tiene permisos para editar esta reserva");
                else{
                    $('#edit_form').html(data);
                    $('#edit_form').dialog({
                        width:500,
                        position:'top',
                        create: function(event, ui) {
                            var widget = $(event.target).dialog('widget');
                            widget.find(widget.draggable("option", "handle")).addTouch();
                            widget.find('.ui-resizable-handle').addTouch();
                        },
                        close: function(event, ui) {
                            if (currentEvent != null){
                                $('#calendar').fullCalendar('removeEventSource', currentEvent);
                                $('#calendar').fullCalendar('refetchEvents');
                            }
                        }
                    }).addTouch();
                }
            }
        });
    }

    function reset_form(){
        $('#BookingReason').val("")
        $('#BookingRequiredEquipment').val("");
        $('#BookingUserType').val("");
        $('#finished_at').val("");
        $('#finish_date').hide();
        $('#AllClassrooms').removeAttr('checked');
        $('#ShowTV').removeAttr('checked');
    }

    $(document).ready(function() {
        if (isMenusEnabled()) {
            $('.menu').menu({
                select: function (e, ui) {
                    e.preventDefault();
                    var action = ui.item.attr('data-action');
                    if (!action && ui.item.children('ul').length) {
                        return;
                    }
                    var menu = $(this).blur().data('ui-focused', null);
                    switch (action) {
                        case 'select':
                            var start = menu.data('fc-start');
                            var allDay = menu.data('fc-allDay');
                            if (allDay) {
                                var end = menu.data('fc-end');
                            } else {
                                var duration = parseInt(ui.item.attr('data-duration')) * 60 * 1000;
                                var end = new Date(start.getTime() + duration);
                            }
                            $('#calendar').fullCalendar('select', start, end, allDay);
                            break;
                        case 'edit':
                            openEvent(menu.data('fc-event'));
                            break;
                        case 'copy':
                            $('.menu [data-action="paste"]')
                                .data('fc-clipboard', {
                                    event: menu.data('fc-event'),
                                })
                                .removeClass('ui-state-disabled');
                            break;
                        case 'paste':
                            var start = menu.data('fc-start');
                            var allDay = menu.data('fc-allDay')
                            var clipbard = ui.item.data('fc-clipboard');
                            if (clipbard.event) {
                                var event = clipbard.event;
                                if (allDay) {
                                    var date = new Date(start.getTime());
                                    var eventDate = new Date(event.start.getTime());
                                    date.setHours(0,0,0,0);
                                    eventDate.setHours(0,0,0,0);
                                    start = new Date(date.getTime() + event.start.getTime() - eventDate.getTime());
                                }
                                if (event.className.indexOf('booking') !== -1) {
                                    copyBooking(event.id.match(/\d+/), start);
                                } else {
                                    copyEvent(event.id.match(/\d+/), start);
                                }
                            }
                            break;
                        case 'delete':
                            var event = menu.data('fc-event');
                            var eventSource = event.source.events.find(function(source) {
                                return source.id+'' === event.id;
                            });
                            if (eventSource) {
                                if (event.className.indexOf('booking') === -1) {
                                    return;
                                }
                                var success = deleteBooking(
                                    event.id.match(/\d+/),
                                    eventSource.parent_id ? eventSource.parent_id.match(/\d+/) : null
                                );
                                var clipboard = $('.menu [data-action="paste"]').data('fc-clipboard');
                                if (success && clipboard) {
                                    var clipboardEventSource = clipboard.event.source.events.find(function(source) {
                                        return source.id+'' === clipboard.event.id;
                                    });
                                    if (clipboardEventSource.id === event.id || clipboardEventSource.parent_id === event.id) {
                                        $('.menu [data-action="paste"]')
                                            .data('fc-clipboard', false)
                                            .addClass('ui-state-disabled');
                                    }
                                }
                            }
                            break;
                        case 'cancel':
                            $('.menu [data-action="paste"]')
                                .data('fc-clipboard', false)
                                .addClass('ui-state-disabled');
                            break;
                    }
                },
                focus: function (e, ui) {
                    if (!ui.item.is(':visible')) {
                        return;
                    }
                    var menu = $(this).data('ui-focused', ui);
                    switch (ui.item.attr('data-action')) {
                        case 'select':
                            if (!menu.data('fc-allDay')) {
                                var view = $('#calendar').fullCalendar('getView');
                                var start = menu.data('fc-start');
                                var duration = parseInt(ui.item.attr('data-duration')) * 60 * 1000;
                                view.clearOverlays();
                                view.renderSelection(start, new Date(start.getTime() + duration), false);
                            }
                            break;
                        case 'paste':
                            if (!menu.data('fc-allDay')) {
                                var view = $('#calendar').fullCalendar('getView');
                                var start = menu.data('fc-start');
                                var clipbard = ui.item.data('fc-clipboard');
                                var duration = clipbard.event.end.getTime() - clipbard.event.start.getTime();
                                view.clearOverlays();
                                view.renderSelection(start, new Date(start.getTime() + duration), false);
                            }
                            break;
                    }
                },
                blur: function (e) {
                    var menu = $(this);
                    var ui = menu.data('ui-focused');
                    var view = $('#calendar').fullCalendar('getView');
                    if (!menu.is(':visible')) {
                        view.clearOverlays();
                    }
                    if (!ui) {
                        return;
                    }
                    menu.data('ui-focused', null);
                    switch (ui.item.attr('data-action')) {
                        case 'select':
                        case 'paste':
                            if (!menu.data('fc-allDay')) {
                                var view = $('#calendar').fullCalendar('getView');
                                var start = menu.data('fc-start');
                                if (menu.is(':visible')) {
                                    view.clearOverlays();
                                    view.renderSelection(start, new Date(start.getTime() + 30 * 60 * 1000), false);
                                }
                            }
                            break;
                    }
                }
            }).blur(function (e) {
                $(e.target).hide();
            });
        }

        function checkOverlap(calendar, event) {  
            var aStart = Math.round(new Date(event.start));
            var aEnd = Math.round(new Date(event.end));

            var overlap = calendar.fullCalendar('clientEvents', function(currentEvent) {
                if( currentEvent == event) {
                    return false;
                }
                var bStart = Math.round(new Date(currentEvent.start));
                var bEnd = Math.round(new Date(currentEvent.end));

                return (
                    (bStart <= aStart && bEnd > aStart)
                    ||
                    (bStart < aEnd && bEnd >= aEnd)
                    ||
                    (bStart >= aStart && bEnd <= aEnd)
                );
            });

            return ! overlap.length;
        }

        $('#calendar').fullCalendar({
            header: {
                right: 'prev,next today',
                center: 'title',
                left: 'title,month,agendaWeek'
            },
            defaultView: isMobile()? 'basicDay' : 'agendaWeek',
            defaultEventMinutes: isMenusEnabled() ? 30 : 60,
            editable: true,
            selectable: {
                agenda: true
            },
            minTime: 7,
            maxTime: 22,
            firstDay: 1,
            events: [ ],
            timeFormat: 'H:mm',
            allDaySlot: false,
            columnFormat: {
                week: 'ddd d/M'
            },
            monthNames: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
            monthNamesShort: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
            dayNames: ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'],
            dayNamesShort: ['Dom', 'Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab'],
            eventResize: function(event, dayDelta, minuteDelta, revertFunc, jsEvent, ui, view) {
                if (event.className != 'booking') {
                    $('#notice').removeClass('success');
                    $('#notice').addClass('error');
                    $('#notice').html("No se pueden modificar las actividades académicas desde las reservas de aula. Vaya a programar curso si desea modificar una actividad académica.");
                    revertFunc();
                } else {
                    if (!checkOverlap($('#calendar'), event)) {
                        revertFunc();
                        $('#notice').removeClass('success');
                        $('#notice').addClass('error');
                        $('#notice').html("Los eventos no se pueden solapar.");
                        return;
                    }
                    id = event.id.match(/\d+/);
                    $.ajax({
                        cache: false,
                        type: "GET",
                        url: "<?php echo PATH ?>/bookings/update/" + id + "/" + dayDelta + "/" + minuteDelta + "/1",
                        success: function(data) {
                            if (data == "notAllowed") {
                                revertFunc();
                                $('#notice').removeClass('success');
                                $('#notice').addClass('error');
                                $('#notice').html("Usted no tiene permisos para modificar esta reserva. Solo su dueño, un conserje, un administrativo o un administrador pueden hacerlo.");
                            } else if (data && data != "true") {
                                revertFunc();
                                $('#notice').removeClass('success');
                                $('#notice').addClass('error');
                                $('#notice').html(data != "false"? data : "No ha sido posible actualizar la reserva porque coincide con una actividad académica u otra reserva.");
                            } else {
                                $('#notice').removeClass('error');
                                $('#notice').addClass('success');
                                $('#notice').html("La reserva se ha actualizado correctamente.");
                            }
                        }
                    });
                }
            },
            buttonText: {today: 'hoy', month: 'mes', week: 'semana', day: 'día'},
            windowResize: function(view) {
                if (isMobile()) {
                    if (view.name !== 'basicDay') {
                        $('#calendar').fullCalendar('changeView', 'basicDay');
                    }
                } else if (view.name === 'basicDay') {
                    $('#calendar').fullCalendar('changeView', 'agendaWeek');
                    $('#calendar').fullCalendar('render'); // Fix problem with columns width
                }
            },
            //viewRender: function (view, element) {
            viewDisplay: function (view, element) {
                $('[data-fc-view]').each(function () {
                    var item = $(this);
                    var visible = item.attr('data-fc-view').split(',').map(function(name) {
                        return name.trim();
                    }).indexOf(view.name) !== -1;
                    item.toggle(visible);
                })
            },
            eventRender: function (event, element, view) {
                $(element).addTouch();
                if (!event.className || !event.className.length) {
                    element.bind('click', function (jsEvent) {
                        $('.menu:visible').blur();
                    });
                    return;
                }
                if (isMenusEnabled()) {
                    element.bind('click', function (jsEvent) {
                        var menu = $('#menu-event')
                            .data('fc-event', event)
                            .show()
                            .position({my: "left top", of: jsEvent})
                            .focus();
                        menu.find('[data-action="delete"]')
                            .closest('li')[event.className.indexOf('booking') !== -1 && event.deletable ? 'removeClass' : 'addClass']('ui-state-disabled');
                        menu.find('[data-action="copy"]')
                            .closest('li')[event.className.indexOf('booking') !== -1 ? 'removeClass' : 'addClass']('ui-state-disabled');
                    });
                    element.bind('dblclick', function (jsEvent) {
                        openEvent(event);
                    });
                } else {
                    element.bind('click', function (jsEvent) {
                        openEvent(event);
                    });
                }
            },
            eventDrop: function(event, dayDelta, minuteDelta, allDay, revertFunc, jsEvent, ui, view ) {
                if (event.className != 'booking') {
                    $('#notice').removeClass('success');
                    $('#notice').addClass('error');
                    $('#notice').html("No se pueden modificar las actividades académicas desde las reservas de aula. Vaya a programar curso si desea modificar una actividad académica.");
                    revertFunc();
                } else {
                    if (!checkOverlap($('#calendar'), event)) {
                        revertFunc();
                        $('#notice').removeClass('success');
                        $('#notice').addClass('error');
                        $('#notice').html("Los eventos no se pueden solapar.");
                        return;
                    }
                    id = event.id.match(/\d+/);
                    $.ajax({
                        cache: false,
                        type: "GET",
                        url: "<?php echo PATH ?>/bookings/update/" + id + "/" + dayDelta + "/" + minuteDelta,
                        success: function(data) {
                            if (data == "notAllowed") {
                                revertFunc();
                                $('#notice').removeClass('success');
                                $('#notice').addClass('error');
                                $('#notice').html("Usted no tiene permisos para modificar esta reserva. Solo su dueño, un conserje, un administrativo o un administrador pueden hacerlo.");
                            } else if (data && data != "true") {
                                revertFunc();
                                $('#notice').removeClass('success');
                                $('#notice').addClass('error');
                                $('#notice').html(data != "false"? data : "No ha sido posible actualizar la reserva porque coincide con una actividad académica u otra reserva.");
                            } else {
                                $('#notice').removeClass('error');
                                $('#notice').addClass('success');
                                $('#notice').html("La reserva se ha actualizado correctamente.");
                            }
                        }
                    });
                }
            },
            eventMouseover: function(event, jsEvent, view) {
                if (!event.className || !event.className.length) {
                    return;
                }
                var id = event.id.match(/\d+/);
                var url;
                if (event.className == 'booking') {
                    url = "<?php echo PATH ?>/bookings/view/";
                } else {
                    url = "<?php echo PATH ?>/events/view/";
                }

                $.ajax({
                    cache: false,
                    type: "GET",
                    url: url + id,
                    asynchronous: false,
                    success: function(data) {
                        $('#tooltip').html(data).find('a, .actions').remove();
                        $('#BookingDetails').html(data).find('a, .actions').remove();
                    }
                });

                $(this).tooltip({
                    delay: 500,
                    bodyHandler: function() {
                        return $('#BookingDetails').html();
                    },
                    showURL: false
                });

            },
            dayClick: function(date, allDay, jsEvent, view){
                if ($('#classrooms').val() == "") {
                    if (allDay) {
                        alert("Debe seleccionar un aula antes de comenzar a gestionar las reservas");
                    }
                } else {
                    if (allDay) {
                        var start = new Date(date.getTime() + 9 * 3600 * 1000);
                        var end = new Date(date.getTime() + 10 * 3600 * 1000);
                    } else {
                        var start = date;
                        var end = new Date(date.getTime() + 3600 * 1000);
                    }
                    if (isMenusEnabled()) {
                        $('#menu-slot')
                            .data('fc-start', start)
                            .data('fc-end', end)
                            .data('fc-allDay', allDay)
                            .show()
                            .position({my: "left top", of: jsEvent})
                            .focus();
                    } else if (allDay) {
                        $('#calendar').fullCalendar('select', start, end, allDay);
                    }
                }
            },
            select: function(date, endDate, allDay, jsEvent, view) {
                if ($('#menu-slot').is(':visible')) {
                    return;
                }
                reset_form();
                if ($('#classrooms').val() == "") {
                    <?php if ($auth->user('type') == "Administrador"): ?>
                        $('#AllClassrooms').attr('checked', 'checked').attr('disabled', 'disabled');
                        $('#AllClassroomsDefault').val('1');
                    <?php else: ?>
                        alert("Debe seleccionar un aula antes de comenzar a gestionar las reservas");
                        $('#calendar').fullCalendar('unselect');
                        return;
                    <?php endif; ?>
                } else {
                    <?php if ($auth->user('type') == "Administrador"): ?>
                        $('#AllClassrooms').removeAttr('disabled');
                        $('#AllClassroomsDefault').val('0');
                    <?php endif; ?>  
                }
                var initial_hour = ('0'+date.getHours()).slice(-2);
                var initial_minute = ('0'+date.getMinutes()).slice(-2);
                var final_hour = ('0'+endDate.getHours()).slice(-2);
                var final_minute = ('0'+endDate.getMinutes()).slice(-2);

                if (currentEvent != null) {
                    $('#calendar').fullCalendar('removeEventSource', currentEvent);
                    $('#calendar').fullCalendar('refetchEvents');
                }

                var initial_date = toEventDateString(date);
                var final_date = toEventDateString(endDate);
                currentEvent = [{title: "<<vacío>>", start: initial_date, end: final_date, allDay:false}];

                if (!checkOverlap($('#calendar'), currentEvent[0])) {
                    $('#notice').removeClass('success');
                    $('#notice').addClass('error');
                    $('#notice').html("Los eventos no se pueden solapar.");
                    $('#calendar').fullCalendar('unselect');
                    return;
                }

                $('#date').val(initial_date);
                $('#BookingInitialHourHour').val(initial_hour);
                $('#BookingInitialHourMin').val(initial_minute);
                $('#BookingFinalHourHour').val(final_hour);
                $('#BookingFinalHourMin').val(final_minute);
                $('#form').dialog({
                    width:500,
                    position:'top',
                    create: function(event, ui) {
                        var widget = $(event.target).dialog('widget');
                        widget.find(widget.draggable("option", "handle")).addTouch();
                        widget.find('.ui-resizable-handle').addTouch();
                    },
                    close: function(event, ui) {
                        $('#calendar').fullCalendar('unselect');
                        if (currentEvent != null){
                            $('#calendar').fullCalendar('removeEventSource', currentEvent);
                            $('#calendar').fullCalendar('refetchEvents');
                        }
                    }
                }).addTouch();
                $('#calendar').fullCalendar('addEventSource', currentEvent);
                $('#calendar').fullCalendar('refetchEvents');
            }
        });
    });

</script>
<h1>Programar curso</h1>

<p id="notice"></p>

<dl>
    <dt>Aulas</dt>
    <dd>
        <select id="classrooms">
            <option value=""></option>
            <?php foreach ($classrooms as $classroom): ?>
                <option <?php if ($teachers_can_booking && $isTeacher && !$classroom['Classroom']['teachers_can_booking']): ?>disabled="disabled"<?php endif; ?> value="<?php echo h($classroom['Classroom']['id']) ?>"><?php echo h($classroom['Classroom']['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </dd>
</dl>

<div>
    <div id="calendar_container">
        <div id="calendar" class="fc" style="margin: 3em 0pt; font-size: 13px;"></div>
    </div>

    <div id="legend" style="">
        <div id="legend_left">
            <ul>
                <?php foreach(Configure::read('app.color_legend.legend_left') as $id => $description): ?>
                    <li id="<?php echo $id ?>"><?php echo h($description) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div id="legend_right">
            <ul>
                <?php foreach(Configure::read('app.color_legend.legend_right') as $id => $description): ?>
                    <li id="<?php echo $id ?>"><?php echo h($description) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <ul id="menu-event" class="menu" style="display:none;">
      <li data-action="edit"><a href="#">Mostrar</a></li>
      <li data-action="copy"><a href="#">Copiar</a></li>
      <li data-action="delete" class="ui-state-disabled"><a href="#">Borrar</a></li>
      <li data-action="cancel"><a href="#">Cancelar</a></li>
    </ul>
    <ul id="menu-slot" class="menu" style="display:none;">
      <li data-fc-view="month,basicDay" data-action="select"><a href="#">Crear reserva</a></li>
      <li data-fc-view="agendaWeek"><a href="#">Crear reserva</a>
        <ul>
            <li data-action="select" data-duration="60"><a href="#">1 hora</a></li>
            <li data-action="select" data-duration="90"><a href="#">1 hora y 30 minutos</a></li>
            <li data-action="select" data-duration="120"><a href="#">2 horas</a></li>
            <li data-action="select" data-duration="150"><a href="#">2 horas y 30 minutos</a></li>
            <li data-action="select" data-duration="180"><a href="#">3 horas</a></li>
            <li data-action="select" data-duration="210"><a href="#">3 horas y 30 minutos</a></li>
            <li data-action="select" data-duration="240"><a href="#">4 horas</a></li>
        </ul>
      </li>
      <li data-action="paste" class="ui-state-disabled"><a href="#">Pegar</a></li>
      <li data-action="cancel"><a href="#">Cancelar</a></li>
    </ul>

    <div id="edit_form">

    </div>

    <div id="BookingDetails" style="display:none">

    </div>

    <div id="form_container" style="display:none;float:right;padding-top:6em">
        <div id="form">
            <?php echo $form->create('Booking', array('onsubmit' => 'return false;')); ?>
            <fieldset>
                <?php
                    echo $form->input('reason', array('label' => 'Motivo', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>'));
                    echo $form->input('required_equipment', array('type' => 'text_area', 'label' => 'Información', 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>'));
                ?>
                <div class="input">
                    <dl>
                        <label for="BookingInitialHour" style="display:inline">Desde</label>
                        <?php echo $form->hour('initial_hour', true, "07", array('timeFormat' => '24')); ?>:
                        <select id="BookingInitialHourMin" name="data[Booking][initial_hour][minute]">
                            <option value="00">00</option>
                            <option value="30">30</option>
                        </select>
                        <label for="BookingFinalHour" style="display:inline">Hasta</label>
                        <?php echo $form->hour('final_hour', true, "07", array('timeFormat' => '24')); ?>:
                        <select id="BookingFinalHourMin" name="data[Booking][final_hour][minute]">
                            <option value="00">00</option>
                            <option value="30">30</option>
                        </select>
                    </dl>
                </div>
                <div class="input">
                    <dl>
                        <select id="Frequency" name="Frecuency">
                            <option value="">No repetir</option>
                            <option value="1">Diariamente</option>
                            <option value="7">Semanalmente</option>
                        </select>
                        <span id="finish_date" style="display:none">
                            <label for="BookingFinishedAt" style="display:inline"> hasta el</label>&nbsp;&nbsp;<input type="text" name="finished_at" id="BookingFinishedAt" style="width:25%;"/>
                        </span>
                    </dl>
                </div>
                <div class="input">
                    <dl>
                        <select id="BookingUserType" name="data[Booking][user_type]">
                            <option value="">No asignar a nadie</option>
                            <option value="Todos">Todos los usuarios</option>
                            <option value="No-estudiante">Todos menos los estudiantes</option>
                            <?php foreach (array("Administrador", "Administrativo", "Conserje", "Profesor", "Estudiante", "Becario") as $userType): ?>
                                <option value="<?php echo $userType ?>"><?php echo $userType ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span id="finish_date" style="display:none">
                            <label for="BookingFinishedAt" style="display:inline"> hasta el</label>&nbsp;&nbsp;<input type="text" name="finished_at" id="BookingFinishedAt" style="width:25%;"/>
                        </span>
                    </dl>
                </div>
                <?php if ($auth->user('type') == "Administrador"): ?>
                    <div class="input checkbox">
                        <input type="checkbox" id="AllClassrooms" name="AllClassrooms" value="1">
                        <label for="AllClassrooms">Añadir para todas las aulas</label>
                        <input type="hidden" id="AllClassroomsDefault" name="AllClassrooms" value="0">
                    </div>
                <?php endif; ?>
                <?php if (Configure::read('app.booking.show_tv')): ?>
                    <div class="input checkbox">
                        <input type="checkbox" id="ShowTV" name="ShowTV" value="1">
                        <label for="ShowTV">Mostrar en TV</label>
                        <input type="hidden" id="ShowTVDefault" name="ShowTV" value="0">
                    </div>
                <?php endif; ?>
                <input type="hidden" id="date" name="date" style="display:none">
            </fieldset>
            <?php echo $form->submit('Crear', array('onclick' => 'addBooking();'))?>
        </div>
    </div>
</div>
<script type="text/javascript">
    <?php echo $dateHelper->datepicker("#BookingFinishedAt"); ?>
    $('#classrooms').change(function() {
        $('#calendar').fullCalendar('removeEvents');
        $.ajax({
            cache: false,
            type: "GET",
            url: "<?php echo PATH ?>/events/get/" + $('#classrooms').val(),
            dataType: "script"
        });

        $.ajax({
            cache: false,
            type: "GET",
            url: "<?php echo PATH ?>/bookings/get/" + $('#classrooms').val(),
            dataType: "script"
        });
    });

    $('#Frequency').change(function() {
        if ($('#Frequency').val() != "") {
            $('#finish_date').show();
        } else {
            $('#finish_date').hide();
        }
    });


    $(document).ready(function() {
        $('#classrooms').val("");
    });
</script>
