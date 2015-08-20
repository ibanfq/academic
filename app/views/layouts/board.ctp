<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta content="text/html; charset=utf-8" http-equiv="Content-Type"/>
<title>Academic</title>
<link rel="icon" type="image/x-icon" href="<?php echo PATH ?>/favicon.ico"/>
<link rel="shortcut icon" type="image/x-icon" href="<?php echo PATH ?>/favicon.ico"/>
<?php 
	echo $scripts_for_layout;
	echo $javascript->link('jquery'); 

	echo $html->css('cake.generic.css');
    echo $html->css('board.css');
?>


</head>
<body>
<div id="container">
	<div id="content">
		<?php echo $content_for_layout ?>
	</div>

	<div id="footer">
	</div>

</body>
</html>