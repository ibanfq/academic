<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta content="text/html; charset=utf-8" http-equiv="Content-Type"/>
<title>Academic</title>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<link rel="icon" type="image/x-icon" href="<?php echo PATH ?>/favicon.ico"/>
<link rel="shortcut icon" type="image/x-icon" href="<?php echo PATH ?>/favicon.ico"/>
<?php echo $this->element('scripts', array(
    'scripts_for_layout' => $scripts_for_layout,
    'forms_type' => isset($events_schedule) || isset($bookings_schedule)? 'events' : 'generic'
)); ?>

</head>
<body>
<div id="container">
	<div id="header">
			<div class="left">
				<ul class="logo">
					<li class="hidden-phone">
						<a href="<?php echo PATH?>/calendar_by_classroom">
							<img src="<?php echo PATH . Configure::read('app.logo') ?>">
						</a>
					</li>
					<li class="hidden-phone">
						<img src="<?php echo PATH?>/img/divider.jpg">
					</li>
					<li>
						<a href="http://www.fv.ulpgc.es">
							<img src="<?php echo PATH?>/img/logo_ulpgc.jpg">
						</a>
					</li>
				</ul>
			</div>
	</div>
	
	<div id="content">
		<?php echo $content_for_layout ?>
	</div>

	<div id="footer">
    <p class="logo visible-block-phone">
      <a href="<?php echo PATH?>/calendar_by_classroom">
        <img src="<?php echo PATH . Configure::read('app.logo') ?>">
      </a>
    </p>
	</div>

</body>
</html>