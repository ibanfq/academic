<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<!-- File: /app/views/events/schedule.ctp -->
<?php $html->addCrumb('Cursos', '/courses'); ?>
<?php $html->addCrumb($course['Course']['name'], "/courses/view/{$course['Course']['id']}"); ?>
<?php $html->addCrumb('Programar curso', "/events/schedule/{$course['Course']['id']}"); ?>

<div id="mobile-query" class="visible-block-phone-portrait"></div>

<script type="text/javascript">

	var subjects = [
		<?php 
		$array = array();
		foreach ($subjects as $subject):
			array_push($array, "{name: '{$subject['name']}', id: '{$subject['id']}' }");
		endforeach;
		echo implode(",", $array);
		?>
	];
	
	var currentEvent = null;
	
    function isMobile() {
        return $('#mobile-query').css('display') !== 'none';
    }

    function isMenusEnabled() {
        return <?php echo Configure::read('app.fullcalendar.menus') ? 'true' : 'false' ?>;
    }

    function copyEvent(event_id, start) {
		$.ajax({
			cache: false,
			type: "GET",
			url: "<?php echo PATH ?>/events/copy/" + event_id + "/initial_hour:" + toEventDateString(start) + "/classroom:" + $('#classrooms').val(),
			dataType: 'script'
		});
	}
	
	function copyBooking(id, start) {
		$.ajax({
			cache: false,
			type: "POST",
			url: "<?php echo PATH ?>/bookings/copy/" + id + "/initial_hour:" + toEventDateString(start) + "/classroom:" + $('#classrooms').val(),
			dataType: 'script'
		});
	}
  
	function delete_event(event_id, parent_id) {
		if (parent_id === null || parent_id == '') {
			confirmated = confirm("¿Está seguro de que desea eliminar este evento? Al eliminarlo se eliminarán también todos los eventos de la misma serie.");
        } else {
			confirmated = confirm("¿Está seguro de que desea eliminar este evento?");
        }
		
		if (confirmated) {
			$.ajax({
				cache: false,
				type: "GET",
				url: "<?php echo PATH ?>/events/delete/" + event_id,
				dataType: 'script'
			});
			return true;
		}
		return false;
	}
	
	function deleteBooking(id, parent_id) {
		if (parent_id === null || parent_id == '') {
			confirmated = confirm("¿Está seguro de que desea eliminar esta reserva? Al eliminarla se eliminarán también todos las reservas de la misma serie.");
        } else {
			confirmated = confirm("¿Está seguro de que desea eliminar esta reserva?");
        }
		if (confirmated){
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
	
	function toEventDateString(date){
		var day = date.getDate();
		var month = date.getMonth() + 1;
		var year = date.getFullYear();
		var hour = date.getHours();
		var minute = date.getMinutes();
		
		if (day < 10) {
			day = "0" + day;
        }
		if (month < 10) {
			month = "0" + month;
        }
		if (hour < 10) {
			hour = "0" + hour;
        }
		if (minute < 10) {
			minute = "0" + minute;
        }
		
		return year + "-" + month + "-" + day + " " + hour + ":" + minute + ":00";
	}
	
	function addEvent() {
		var initial_hour = new Date($("#date").val());
		var final_hour = new Date($("#date").val());
		var new_event;
        var isEvaluation = $('option:selected', $('#EventActivityId')).attr('data-type') == 'Evaluación';
        var eventTeacher2Id = isEvaluation ? $('#EventTeacher2Id').val() : "";
		
		initial_hour.setHours($('#EventInitialHourHour').val());
		initial_hour.setMinutes($('#EventInitialHourMin').val());
		final_hour.setHours($('#EventFinalHourHour').val());
		final_hour.setMinutes($('#EventFinalHourMin').val());
    
        if ($("#teacher_2_name").val() == "") {
            eventTeacher2Id = "";
        }
		
		$.ajax({
			cache: false,
			type: "POST", 
			data: {'data[Event][activity_id]': $('#EventActivityId').val(), 'data[Event][group_id]': $('#EventGroupId').val(), 'data[Event][teacher_id]': $('#EventTeacherId').val(), 'data[Event][teacher_2_id]': eventTeacher2Id, 'data[Event][initial_hour]': toEventDateString(initial_hour), 'data[Event][final_hour]': toEventDateString(final_hour), 'data[Event][classroom_id]': $('#classrooms').val()<?php if (Configure::read('app.event.show_tv')): ?>, 'data[Event][show_tv]': $('#ShowTV').attr('checked') ? '1' : '0'<?php endif; ?>},
			url: "<?php echo PATH ?>/events/add/" + $('#EventFinishedAt').val() + "/" + $('#Frequency').val(),
			asynchronous: false,
			dataType: 'script', 
			success: function(data){
				var form = $('#form').dialog('close');
				if (!$('#notice').hasClass('error')) {
					form.data('no-reset', true);
				}
			}
		});
	}

    function editEvent(event) {
        var model = event.className.indexOf('booking') !== -1? 'bookings' : 'events';
        var action = event.className.indexOf('booking') !== -1? 'view' : 'edit';
        var id = event.id.match(/\d+/);
        $.ajax({
            cache: false,
            type: "GET",
            url: "<?php echo PATH ?>/" + model + "/" + action + "/" + id, 
            success: function(data) {
                if (data == "false")
                    alert("Usted no tiene permisos para editar este evento");
                else{
                    $('#edit_form').html(data);
                    $('#edit_form').dialog({
                        width:500, 
                        position:'top', 
                        close: function(event, ui) {
                            if (currentEvent != null){
                                $('#calendar').fullCalendar('removeEventSource', currentEvent);
                                $('#calendar').fullCalendar('refetchEvents');
                            }
                        }
                    });
                }
            }
        });
    }
	
	<?php if ($auth->user('type') == "Administrador") { ?>
		function update_classroom(event_id) {
			var classroom_id = $('#EventClassroomId').val();
			
			if ($("#edit_teacher_2_name").val() == "") {
	  		    $('#teacher_2_id').val("");
  		    }

			$.ajax({
				cache: false,
				type: "GET", 
				url: "<?php echo PATH ?>/events/update_classroom/" + event_id + "/" + classroom_id + "/" + $('#teacher_id').val() + "/" + $('#teacher_2_id').val()<?php if (Configure::read('app.event.show_tv')): ?> + "/show_tv:" + ($('#EventShowTv').attr('checked') ? '1' : '0')<?php endif; ?>,
				asynchronous: false,
				dataType: 'script', 
				success: function(data){
					$('#edit_form').dialog('close');
					$('#edit_form').html("");
					if (classroom_id != $('#classrooms').val()) {
						if ($('#notice').hasClass('success')) {
							$('#calendar').fullCalendar('removeEvents', event_id);
						}
					}
				}
			});
		}
	<?php } else { ?>
		function update_teacher(event_id) {
			if ($("#edit_teacher_2_name").val() == "") {
				$('#teacher_2_id').val("");
			}

			$.ajax({
				cache: false,
				type: "GET", 
				url: "<?php echo PATH ?>/events/update_teacher/" + event_id + "/" + $('#teacher_id').val() + "/" + $('#teacher_2_id').val()<?php if (Configure::read('app.event.show_tv')): ?> + "/show_tv:" + ($('#EventShowTv').attr('checked') ? '1' : '0')<?php endif; ?>,
				asynchronous: false,
				dataType: 'script', 
				success: function(data){
					$('#edit_form').dialog('close');
					$('#edit_form').html("");
				}
			});
		}
	<?php } ?>
	
	function reset_form(){
		$('#subject_name').val("");
		$('#EventActivityId').html("<option value=''>Seleccione una actividad</option>");
		$('#EventGroupId').html("<option value=''>Seleccione un grupo</option>");
		$('#teacher_name').val("");
        $('#teacher_2_name').val("");
		$('#EventTeacherId').val("");
		$('#EventTeacher2Id').val("");
		$('#Frequency').val("");
		$('#finished_at').val("");
		$('#finish_date').hide();
		$('#EventOwnerId').val("");
	}
	
	$(document).ready(function() {
        if (isMenusEnabled()) {
        	$('.menu').menu({
        		select: function (e, ui) {
        			var menu = $(this).blur();
        			e.preventDefault();
        			switch (ui.item.attr('data-action')) {
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
		                	editEvent(menu.data('fc-event'));
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
			                	var success = false;
			                    if (event.className.indexOf('booking') !== -1) {
			                        success = deleteBooking(
			                            event.id.match(/\d+/),
			                            eventSource.parent_id ? eventSource.parent_id.match(/\d+/) : null
			                        );
			                    } else {
			                        success = delete_event(
			                            event.id.match(/\d+/),
			                            eventSource.parent_id ? eventSource.parent_id.match(/\d+/) : null
			                        );
			                    }
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
        			if (!menu.is(':visible')) {
        				return;
        			}
        			var ui = menu.data('ui-focused');
        			menu.data('ui-focused', null);
        			switch (ui.item.attr('data-action')) {
        				case 'select':
        				case 'paste':
        					if (!menu.data('fc-allDay')) {
	        					var view = $('#calendar').fullCalendar('getView');
	        					var start = menu.data('fc-start');
	        					view.clearOverlays();
	        					view.renderSelection(start, new Date(start.getTime() + 30 * 60 * 1000), false);
	        				}
        					break;
        			}
        		}
        	}).blur(function (e) {
        		$(e.target).hide();
    		});
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
				if (!event.className || !event.className.length) {
					return;
				}
				var model = event.className.indexOf('booking') !== -1? 'bookings' : 'events';
				var id = event.id.match(/\d+/);
				$.ajax({
					cache: false,
					type: "GET", 
					url: "<?php echo PATH ?>/" + model + "/update/" + id + "/" + dayDelta + "/" + minuteDelta + "/1",
					success: function(data) {
                        if (data == "notAllowed") {
                            revertFunc();
                            $('#notice').removeClass('success');
                            $('#notice').addClass('error');
                            $('#notice').html("Usted no tiene permisos para modificar este evento. Solo su dueño, los coordinadores de la asignatura o un administrador pueden hacerlo.");
                		} else if (data && data != "true") {
                			revertFunc();
                            $('#notice').removeClass('success');
                            $('#notice').addClass('error');
                            $('#notice').html(data != "false"? data : "No ha sido posible actualizar el evento porque coincide con otra actividad o se ha superado el número máximo de horas para esta actividad y grupo.");
                		} else {
                            $('#notice').removeClass('error');
                            $('#notice').addClass('success');
                            $('#notice').html("El evento se ha actualizado correctamente.");
            			}
            		}
				});
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
                            .closest('li')[event.deletable ? 'removeClass' : 'addClass']('ui-state-disabled');
                        menu.find('[data-action="copy"]')
                            .closest('li')[event.className.indexOf('booking') === -1 ? 'removeClass' : 'addClass']('ui-state-disabled');
					});
					element.bind('dblclick', function (jsEvent) {
						editEvent(event);
					});
				} else {
					element.bind('click', function (jsEvent) {
	                    editEvent(event);
					});
				}
            },
			eventDrop: function( event, dayDelta, minuteDelta, allDay, revertFunc, jsEvent, ui, view ) {
				if (!event.className || !event.className.length) {
					return;
				}
				var model = event.className.indexOf('booking') !== -1? 'bookings' : 'events';
				var id = event.id.match(/\d+/);
				$.ajax({
					cache: false,
					type: "GET", 
					url: "<?php echo PATH ?>/" + model + "/update/" + id + "/" + dayDelta + "/" + minuteDelta,
					success: function(data) {
						if (data == "notAllowed") {
                            revertFunc();
                            $('#notice').removeClass('success');
                            $('#notice').addClass('error');
                            $('#notice').html("Usted no tiene permisos para modificar este evento. Solo su dueño, los coordinadores de la asignatura o un administrador pueden hacerlo.");
                        } else if (data && data != "true") {
							revertFunc();
                            $('#notice').removeClass('success');
                            $('#notice').addClass('error');
                            $('#notice').html(data != "false"? data : "No ha sido posible actualizar el evento porque coincide con otra actividad.");
                        } else {
                            $('#notice').removeClass('error');
                            $('#notice').addClass('success');
                            $('#notice').html("El evento se ha actualizado correctamente.");
                        }
					}
				}); 
			},
			eventMouseover: function(event, jsEvent, view) {
				if (!event.className || !event.className.length) {
					return;
				}
				if (event.className.indexOf('booking') !== -1) {
					url = "<?php echo PATH ?>/bookings/view/";
                } else {
					url = "<?php echo PATH ?>/events/view/";
                }
				
				$.ajax({
					cache: false,
					type: "GET", 
					url: url + event.id.match(/\d+/),
					asynchronous: false,
					success: function(data) {
						$('#tooltip').html(data).find('a, .actions').remove();
						$('#EventDetails').html(data).find('a, .actions').remove();
					}
				});
				
				$(this).tooltip({
					delay: 500,
					bodyHandler: function() {
						return $('#EventDetails').html();
					},
					showURL: false
				});
				
			},
			dayClick: function(date, allDay, jsEvent, view){
                if ($('#classrooms').val() == "") {
                    if (allDay) {
                        alert("Debe seleccionar un aula antes de comenzar a programar actividades");
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
				if ($('#classrooms').val() == "") {
					alert("Debe seleccionar un aula antes de comenzar a programar actividades");
                    $('#calendar').fullCalendar('unselect');
                } else {
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
					$('#date').val(toEventDateString(date));
					$('#EventInitialHourHour').val(initial_hour);
					$('#EventInitialHourMin').val(initial_minute);
					$('#EventFinalHourHour').val(final_hour);
					$('#EventFinalHourMin').val(final_minute);
					$('#calendar').fullCalendar('addEventSource', currentEvent);
					$('#calendar').fullCalendar('refetchEvents');
					
					var show_form = function() {
						$('#form').dialog({
							width:500, 
							position:'top', 
							close: function(event, ui) {
								$('#calendar').fullCalendar('unselect');
								if (currentEvent != null){
									$('#calendar').fullCalendar('removeEventSource', currentEvent);
									$('#calendar').fullCalendar('refetchEvents');
								}
							}
						});
					}
					
					if ($('#form').data('no-reset')) {
						$.ajax({
							cache: false,
							type: "GET", 
							url: "<?php echo PATH ?>/groups/get/" + $('#EventActivityId').val(),
							asynchronous: false,
							success: function(data) {
								if (data.match(/\s*<option/)) {
									var options = $(data);
									if (options.length > 1) {
										$('#EventGroupId').html(options);
										return;
									}
								}
								reset_form();
							},
							error: function(){
								reset_form();
							},
							complete: function() {
								$('#form').removeData('no-reset');
								show_form();
							}
						});
					} else {
						reset_form();
						show_form();
					}
				}
            }
		});
	});
</script>
<h1>Programar curso</h1>

<p id="notice"></p>

<dl>
	<dt>Aulas</dt>
	<dd><?php echo $form->select('classrooms', $classrooms); ?></dd>
</dl>

<div>
	<div id="calendar_container">
		<div id="calendar" class="fc" style="margin: 3em 0pt; font-size: 13px;"></div>
	</div>
	
	<div id="legend" style="">
		<div id="legend_left">
			<ul>
				<li id="prac_aula">Práctica aula</li>
				<li id="prac_problemas">Práctica problemas</li>
				<li id="prac_informatica">Práctica informática</li>
				<li id="prac_micros">Práctica microscopía</li>
				<li id="prac_lab">Práctica laboratorio</li>
				<li id="prac_clin">Práctica clínica</li>
				<li id="prac_ext">Práctica externa</li>
			</ul>
		</div>

		<div id="legend_right">
			<ul>
				<li id="clase_magistral">Clase magistral</li>
				<li id="seminario">Seminario</li>
				<li id="taller_trabajo">Taller de trabajo</li>
				<li id="tutoria">Tutoría</li>
				<li id="evaluacion">Evaluación</li>
				<li id="otra_presencial">Otra presencial</li>
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
      <li data-fc-view="month,basicDay" data-action="select"><a href="#">Crear evento</a></li>
	  <li data-fc-view="agendaWeek"><a href="#">Crear evento</a>
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
	
	<div id="EventDetails" style="display:none">
		
	</div>
	
	<div id="form_container" style="display:none;float:right;padding-top:6em">
		<div id="form">
			<?php echo $form->create('Event', array('onsubmit' => 'addEvent();return false;')); ?>
			<fieldset>
				<div class="input">
					<dl><dt><label for="subject_name">Asignatura</label></dt><dd><input type="text" name="subject_name" id="subject_name" /></dd></dl>
				</div>
				<?php 
					echo $form->input('activity_id', array('label' => 'Actividad', 'required' => 'required', 'options' => array("Seleccione una actividad"), 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>')); 
					echo $form->input('group_id', array('label' => 'Grupo', 'required' => 'required', 'options' => array("Seleccione una actividad"), 'before' => '<dl><dt>', 'between' => '</dt><dd>', 'after' => '</dd></dl>'));
				?>
				<div class="input">
					<dl>
						<dt><label for="teacher_name">Profesor</label></dt>
						<dd><input type="text" name="teacher_name" id="teacher_name" /></dd>
					</dl> 
				</div>
				
				<div class="input" style="display:none;">
					<dl>
						<dt><label for="teacher_2_name">2º Profesor</label></dt>
						<dd><input type="text" name="teacher_2_name" id="teacher_2_name" /></dd>
					</dl> 
				</div>
				
				<div class="input">
					<dl>
						<?php
							echo $form->input('teacher_id', array('type' => 'hidden'));
							echo $form->input('teacher_2_id', array('type' => 'hidden'));
						?>
						<label for="EventInitialHour" style="display:inline">Desde</label>
						<?php echo $form->hour('initial_hour', true, "07", array('timeFormat' => '24')); ?>
						:
                        <select id="EventInitialHourMin" name="data[Event][initial_hour][minute]">
							<option value="00">00</option>
							<option value="30">30</option>
						</select>
						<label for="EventFinalHour" style="display:inline">Hasta</label>
						<?php echo $form->hour('final_hour', true, "07", array('timeFormat' => '24')); ?>
						:
                        <select id="EventFinalHourMin" name="data[Event][final_hour][minute]">
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
							<label for="EventFinishedAt" style="display:inline"> hasta el</label>&nbsp;&nbsp;<input type="text" name="finished_at" id="EventFinishedAt" style="width:25%;"/>
						</span>
					</dl>
				</div>
				<?php if (Configure::read('app.event.show_tv')): ?>
                    <div class="input checkbox">
                        <input type="checkbox" id="ShowTV" name="ShowTV" value="1">
                        <label for="ShowTV">Mostrar en TV</label>
                        <input type="hidden" id="ShowTVDefault" name="ShowTV" value="0">
                    </div>
                <?php endif; ?>
				<input type="hidden" id="date" name="date" style="display:none">
				<?php echo $form->input('owner_id', array('type' => 'hidden', 'value' => $user_id)) ?>
			</fieldset>
			<?php echo $form->submit('Crear')?>
		</div>
	</div>
</div>
<script type="text/javascript">
	<?php echo $dateHelper->datepicker("#EventFinishedAt"); ?>
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
	
	$('#EventActivityId').change(function() {
    var isEvaluation = $('option:selected', this).attr('data-type') == 'Evaluación';
    $('#teacher_2_name').closest('.input').toggle(isEvaluation);
		$.ajax({
			cache: false,
			type: "GET",
			url: "<?php echo PATH ?>/groups/get/" + $('#EventActivityId').val(),
			success: function(data){
				$('#EventGroupId').html(data);
			}
		});
	});
	

	$(document).ready(function() {
		function formatItem(row) {
			if (row[1] != null) {
				return row[0];
            } else {
				return 'No existe ningún profesor con este nombre.';
            }
		}
		
		$("#teacher_name").autocomplete("<?php echo PATH ?>/users/find_teachers_by_name", {formatItem: formatItem}).result(function(event, item){ $("input#EventTeacherId").val(item[1]); });
		
		$("#teacher_2_name").autocomplete("<?php echo PATH ?>/users/find_teachers_by_name", {formatItem: formatItem}).result(function(event, item){ $("input#EventTeacher2Id").val(item[1]); });
	
		$("#subject_name").autocomplete(subjects, {
			minChars: 0,
            matchContains: true,
			formatItem: function(row){
				return row.name;
			},
			formatMatch: function(row, i, max){
				return row.name;
			}
		}).result(function(event, item) {
			$.ajax({
				cache: false,
				type: "GET", 
				url: "<?php echo PATH ?>/activities/get/" + item.id,
				success: function(data){
					$('#EventActivityId').html(data);
					$('#EventGroupId').html("<option value=''>Seleccione un grupo</option>");
				}
			})
		});
		
		$('#classrooms').val("");
	});			
</script>
