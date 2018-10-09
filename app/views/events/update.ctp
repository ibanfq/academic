<?php
if (isset($success)) {
	echo "true";
} elseif (isset($notAllowed)) {
	echo("notAllowed");
} elseif (isset($booking_overlaped)) {
  $initial_date = date_create($booking_overlaped['Booking']['initial_hour']);
  $message = "No ha sido posible actualizar el evento porque coincide el día <strong>{$initial_date->format('d-m-Y')}</strong> con la reserva <strong>{$booking_overlaped['Booking']['reason']}</strong>";
  if ($booking_overlaped['Classroom']['name']) {
    $message .= " del aula <strong>{$booking_overlaped['Classroom']['name']}</strong>";
  }
  echo ($message);
} elseif (isset($event_overlaped)) {
  $initial_date = date_create($event_overlaped['Event']['initial_hour']);
  $message = "No ha sido posible actualizar el evento porque coincide el día <strong>{$initial_date->format('d-m-Y')}</strong> con la actividad <strong>{$activity_overlaped['Activity']['name']}</strong> de la asignatura <strong>{$activity_overlaped['Subject']['name']}</strong> del aula <strong>{$event_overlaped['Classroom']['name']}</strong>";
  echo ($message);
} else {
	echo "No ha sido posible actualizar el evento porque ha superado el número máximo de horas para esta actividad y grupo.";
}
?>