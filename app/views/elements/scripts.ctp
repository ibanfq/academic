<?php 
  echo $scripts_for_layout;
  echo $javascript->link('modernizr'); 
	echo $javascript->link('jquery'); 
	echo $javascript->link('jquery-ui');
  echo $javascript->link('jquery.ui.touch');
	echo $javascript->link('jquery.autocomplete');
	echo $javascript->link('jquery.tooltip');
  echo $javascript->link('fullcalendar');

	echo $html->css('cake.generic.css');
  echo $html->css("$forms_type.forms.css");
	echo $html->css('jquery-ui');
	echo $html->css('jquery.autocomplete');
	echo $html->css('fullcalendar.css');
	echo $html->css('jquery.tooltip');
?>

<script type="text/javascript">
  $(document).delegate(".ui-dialog-content", "dialogopen", function () {
    var overlay = $('#ui-widget-overlay');
    if (overlay.length) {
      var prev = $('.ui-dialog-last').removeClass('ui-dialog-last');
      $(this).data('prev', prev);
      overlay.data('counter', overlay.data('counter') + 1);
    } else {
      $('body').append('<div id="ui-widget-overlay" class="ui-widget-overlay"></div>');
    }
    $(this).addClass('ui-dialog-last'); 
  });
  $(document).delegate(".ui-dialog-content", "dialogclose", function () {
    var overlay = $('#ui-widget-overlay');
    var counter = overlay.data('counter');
    if (counter) {
      var prev = $(this).data('prev');
      if (prev) prev.addClass('ui-dialog-last');
      overlay.data('counter', counter - 1);
    } else {
      overlay.remove();
    }
    $(this).removeClass('ui-dialog-last');
  });
</script>
