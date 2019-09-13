<script type="text/javascript">
	function open_print_file(){
		var url;

		if (($('#date').val() == null) || ($('#date').val() == '')) {
			alert('Debe seleccionar la fecha para la que desea obtener la hoja de firmas.');
		} else {
			url = "print_bookings?date=" + $('#date').val();
			window.open(url);
		}
	}
</script>

<?php $html->addCrumb('Aulas', '/institutions/ref:classrooms'); ?>
<?php $html->addCrumb(Environment::institution('name'), array('action' => 'index')); ?>
<?php $html->addCrumb('Imprimir agenda diaria', array('acion' => 'get_bookings')) ?>

<h1>Imprimir agenda diaria</h1>
<form action="print_bookings" method="get" onsubmit="open_print_file(); return false;" style="width: 45%;">
	<fieldset>
		<dl>
			<dt><label for="date" style="width: 100px;">Fecha</label></dt>
			<dd><input type="text" id="date" name="date" /></dd>
		</dl>
		<br />
		<div style="text-align: right;">
			<input type="submit" value="Obtener agenda diaria" />
		</div>
	</fieldset>
</form>

<script type="text/javascript">
	$(function() {
		<?php echo $dateHelper->datepicker("#date") ?>
	});
</script>

