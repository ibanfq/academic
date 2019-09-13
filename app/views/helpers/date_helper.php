<?php
class DateHelperHelper extends AppHelper {
	function datePicker($field_name){
		return "\$(\"{$field_name}\").datepicker({ monthNames: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'], dateFormat: 'dd-mm-yy', dayNames: ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'], dayNamesMin: ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa'], dayNamesShort: ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa'], firstDay: 1, yearRange: '-10:+10', showOn: 'button', buttonImage: '/img/ico/calendar.png', buttonImageOnly: true, buttonText: 'Seleccine una fecha' });";
	}
}
?>