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
						<a href="<?php echo PATH?>/courses">
							<img src="<?php echo PATH?>/img/logo.jpg">
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
			<div class="right">
				<?php if (isset($auth)) {?>
					<a href="<?php echo PATH?>/editProfile" title="Haga clic aquí para editar sus datos" class="profile"><?php echo "{$auth->user('first_name')} {$auth->user('last_name')}" ?></a>
					<a href="<?php echo PATH?>/users/logout" class="logout">Salir</a>
				<?php } ?>
			</div>
      <div class="tabs">
        <ul>
          <?php $section = isset($section)? $section : null ?>
    			<?php if (isset($auth)) { ?>
            <li class="<?php echo ($section == 'home' ? 'active_tab' : '')?>"><a href="<?php echo PATH?>/">Mi agenda</a></li>
						<?php if (($auth->user('type') == "Administrador") || ($auth->user('type') == "Profesor") || ($auth->user('type') == "Administrativo")) { ?>
							<?php if (($auth->user('type') == "Administrador") || ($auth->user('type') == "Profesor") || ($auth->user('type') == "Administrativo")) { ?>
								<li class="<?php echo ($section == 'courses' ? 'active_tab' : '')?>"><a href="<?php echo PATH?>/courses">Cursos</a></li>
								<li class="<?php echo ($section == 'classrooms' ? 'active_tab' : '')?>"><a href="<?php echo PATH?>/classrooms">Aulas</a></li>
							<?php } ?>
							<?php if (($auth->user('type') == "Administrador") || ($auth->user('type') == "Administrativo") || ($auth->user('type') == "Profesor") || ($auth->user('type') == "Becario")) { ?>
								<li class="<?php echo ($section == 'users' ? 'active_tab' : '')?>"><a href="<?php echo PATH?>/users">Usuarios</a></li>
							<?php } ?>
						<?php } else { 
							if ($auth->user('type') == "Estudiante") {
						?>
							<li class="<?php echo ($section == 'my_subjects' ? 'active_tab' : '')?>"><a href="<?php echo PATH?>/users/my_subjects">Mis asignaturas</a></li>
              <li class="<?php echo ($section == 'users_attendance_register' ? 'active_tab' : '')?>"><a href="<?php echo PATH?>/users_attendance_register/add_by_secret_code">Registrar mi asistencia</a></li>
						<?php }} ?>
						<?php if (($auth->user('type') == 'Administrador') || ($auth->user('type') == 'Becario') || ($auth->user('type') == 'Administrativo')) { ?>
							<li class="<?php echo ($section == 'attendance_registers' ? 'active_tab' : '')?>"><a href="<?php echo PATH?>/attendance_registers">Registros de impartición</a></li>
						<?php } ?>
						
						<?php if ($auth->user('type') == 'Conserje') { ?>
						    <li class="<?php echo ($section == 'classrooms' ? 'active_tab' : '')?>"><a href="<?php echo PATH?>/classrooms">Aulas</a></li>
						<?php } ?>
						
						<?php if (($auth->user('type') == 'Conserje') || ($auth->user('type') == 'Administrador')) { ?>
							<li class="<?php echo ($section == 'bookings' ? 'active_tab' : '')?>"><a href="<?php echo PATH?>/bookings">Gestión de aulas</a></li>
						<?php }?>
          <?php } else { ?>
              <li class="<?php echo ($section == 'users' ? 'active_tab' : '')?>"><a href="<?php echo PATH?>/">Identificarse</a></li>
              <li class="<?php echo ($section == 'users_attendance_register' ? 'active_tab' : '')?>"><a href="<?php echo PATH?>/users_attendance_register/add_by_secret_code">Registrar mi asistencia</a></li>
          <?php } ?>
        </ul>
      </div>
	</div>
	
	<div id="content">
		<?php if (isset($auth)) { ?>
			<div class="nav">
				Estás en: <?php echo $html->getCrumbs(' > ', 'Inicio'); ?>
			</div>
		<?php } ?>
		<?php echo $this->Session->flash(); ?>
		<?php echo $content_for_layout ?>
	</div>

	<div id="footer">
    <p class="logo visible-block-phone">
      <a href="<?php echo PATH?>/courses">
        <img src="<?php echo PATH?>/img/logo.jpg">
      </a>
    </p>
		<?php if (Configure::read('debug') > 0) echo $this->element('sql_dump') ?>
	</div>

</body>
</html>
