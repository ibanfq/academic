<script type="text/javascript">
	  function open_print_file(){
	    var url;
	    
	    if (($('#classroom').val() == null) || ($('#classroom').val() == ''))
	      alert('Debe seleccionar el aula para la que desea obtener la hoja de firmas.');
	    else {
	      if (($('#date').val() == null) || ($('#date').val() == ''))
	        alert('Debe seleccionar la fecha para la que desea obtener la hoja de firmas.');
	      else {
	        url = "print_sign_file?classroom=" + $('#classroom').val() + "&date=" + $('#date').val();
    	    window.open(url);
	      }
	    }
	  }
</script>
<!-- File: /app/views/classrooms/new.ctp -->
<?php $html->addCrumb('Aulas', '/institutions/ref:classrooms'); ?>
<?php $html->addCrumb(Environment::institution('name'), array('action' => 'index')); ?>
<?php $html->addCrumb('Imprimir hoja de firmas', array('acion' => 'get_sign_file')) ?>


<h1>Imprimir hoja de firmas</h1>

<form action="print_sign_file" method="get" onsubmit="open_print_file(); return false;">
  <fieldset>
    <dl>
      <dt>Aula</dt>
      <dd><?php echo $form->select('classroom', $classrooms); ?>
    </dl>
    
    <dl>
			<dt><label for="date">Fecha</label></dt>
			<dd><input type="text" id="date" name="date" /></dd>
		</dl>
  </fieldset>

  <fieldset class="submit">
    <input type="submit" value="Obtener hoja de firmas" />
  </fieldset>
</form>

<script type="text/javascript">
	$(function() {
		<?php 
			echo $dateHelper->datepicker("#date");
		?>
	});
	
</script>
