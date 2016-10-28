<?php if (isset($event)) {
  if ($event) {
    echo "No ha sido posible actualizar el evento porque coincide con otra actividad del aula <strong>{$event['Classroom']['name']}</strong>.";
  } else {
    echo "No ha sido posible actualizar el evento porque ha superado el número máximo de horas para esta actividad y grupo.";
  }
} elseif (isset($notAllowed)) {
  echo "notAllowed";
} else {
  echo "true";
}
?>