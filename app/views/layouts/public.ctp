<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta content="text/html; charset=utf-8" http-equiv="Content-Type"/>
<title>Academic</title>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<link rel="icon" type="image/x-icon" href="/favicon.ico"/>
<link rel="shortcut icon" type="image/x-icon" href="/favicon.ico"/>
<?php echo $this->element('scripts', array(
    'scripts_for_layout' => $scripts_for_layout,
    'forms_type' => isset($events_schedule) || isset($bookings_schedule)? 'events' : 'generic'
)); ?>

</head>
<body <?php if (Configure::read('debug') > 0): ?>class="debug"<?php endif; ?>>
<div id="container">
	<div id="header">
			<div class="left">
				<ul class="logo">
					<li class="hidden-phone">
						<a href="<?php echo $this->Html->url(null) ?>">
							<img src="<?php echo Configure::read('app.logo') ?>">
						</a>
					</li>
					<li class="hidden-phone">
						<img src="/img/divider.jpg">
					</li>
					<li>
						<a href="<?php echo h(Configure::read('app.logo_ulpgc_link')) ?>">
							<img src="<?php echo Configure::read('app.logo_ulpgc') ?>">
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
      <a href="<?php echo $this->Html->url(null) ?>">
        <img src="<?php echo Configure::read('app.logo') ?>">
      </a>
	</p>
	<?php if (Configure::read('debug') > 1) echo $this->element('sql_dump') ?>
	</div>
</div>
</body>
</html>