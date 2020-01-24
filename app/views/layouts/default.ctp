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
<body <?php if (Configure::read('debug') > 1): ?>class="debug"<?php endif; ?>>
<div id="container">
    <div id="header">
            <div class="left">
                <ul class="logo">
                    <li class="hidden-phone">
                        <a href="/academic_years">
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
            <div class="right">
                <?php if (isset($auth)): ?>
                    <div class="controlgroup">
                        <select id="user-logged-menu">
                            <option><?php echo strtr($auth->user('type'), array('Profesor' => 'Prof', 'Administrador' => 'Adm', 'Administrativo' => 'Aux', 'Conserje' => 'Consj', 'Becario' => 'Becario', 'Estudiante' => 'Est')) . ": {$auth->user('first_name')} {$auth->user('last_name')}" ?></option>
                            <?php foreach ($auth->user('types') as $type): ?>
                                <?php if ($auth->user('type') !== $type): ?>
                                    <option data-link="/users/login_as/<?php echo strtolower($type) ?>">Cambiar a <?php echo strtolower($type) ?></option>
                                <?php endif; ?>
                            <?php endforeach ?>
                            <option data-link="/editProfile">Configurar cuenta</option>
                            <option data-link="/users/logout">Desconectar</option>
                        </select>
                    </div>
                <?php endif; ?>
            </div>
      <div class="tabs">
        <ul>
            <?php $section = isset($section)? $section : null ?>
            <?php if (isset($auth)): ?>
                <li class="<?php echo ($section == 'home' ? 'active_tab' : '')?>"><a href="/">Mi agenda</a></li>
                <?php if (($auth->user('type') == "Administrador") || ($auth->user('type') == "Profesor") || ($auth->user('type') == "Administrativo") || ($auth->user('type') == "Becario")): ?>
                    <?php if (($auth->user('type') == "Administrador") || ($auth->user('type') == "Profesor")): ?>
                        <li class="<?php echo ($section == 'my_subjects' ? 'active_tab' : '')?>"><a href="/users/my_subjects">Mis asignaturas</a></li>
                    <?php endif; ?>

                    <?php if (($auth->user('type') == "Administrador") || ($auth->user('type') == "Profesor") || ($auth->user('type') == "Administrativo")): ?>
                        <li class="<?php echo ($section == 'courses' || $section == 'competence' ? 'active_tab' : '')?>"><a href="/academic_years">Cursos</a></li>
                        <?php if ($auth->user('super_admin')): ?>
                            <li class="<?php echo ($section == 'institutions' ? 'active_tab' : '')?>"><a href="/institutions">Centros docentes</a></li>
                        <?php endif; ?>
                        <li class="<?php echo ($section == 'classrooms' ? 'active_tab' : '')?>"><a href="/institutions/ref:classrooms">Aulas</a></li>
                    <?php endif; ?>

                    <?php if (($auth->user('type') == "Administrador") || ($auth->user('type') == "Administrativo") || ($auth->user('type') == "Profesor") || ($auth->user('type') == "Becario")): ?>
                        <li class="<?php echo ($section == 'users' ? 'active_tab' : '')?>"><a href="/institutions/ref:users">Usuarios</a></li>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($auth->user('type') == "Estudiante"): ?>
                    <li class="<?php echo ($section == 'courses' || $section == 'competence' ? 'active_tab' : '')?>"><a href="/academic_years">Cursos</a></li>
                    <li class="<?php echo ($section == 'my_subjects' ? 'active_tab' : '')?>"><a href="/users/my_subjects">Mis asignaturas</a></li>
                    <li class="<?php echo ($section == 'users_attendance_register' ? 'active_tab' : '')?>"><a href="/users_attendance_register/add_by_secret_code">Registrar mi asistencia</a></li>
                <?php endif; ?>
                
                <?php if (($auth->user('type') == 'Administrador') || ($auth->user('type') == 'Becario') || ($auth->user('type') == 'Administrativo')): ?>
                    <li class="<?php echo ($section == 'attendance_registers' ? 'active_tab' : '')?>"><a href="/institutions/ref:attendance_registers">Registros de impartición</a></li>
                <?php endif; ?>
                    
                <?php if ($auth->user('type') == 'Conserje'): ?>
                    <li class="<?php echo ($section == 'classrooms' ? 'active_tab' : '')?>"><a href="/institutions/ref:classrooms">Aulas</a></li>
                <?php endif; ?>
                    
                <?php if (($auth->user('type') == 'Conserje') || ($auth->user('type') == 'Administrativo') || ($auth->user('type') == 'Administrador') || (Configure::read('app.classroom.teachers_can_booking') && $auth->user('type') == 'Profesor')): ?>
                    <li class="<?php echo ($section == 'bookings' ? 'active_tab' : '')?>"><a href="/institutions/ref:bookings">Gestión de aulas</a></li>
                <?php endif; ?>
            <?php else: ?>
                <li class="<?php echo ($section == 'users' ? 'active_tab' : '')?>"><a href="/">Identificarse</a></li>
            <?php endif; ?>
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
    <?php if (Configure::read('debug_email')) echo $this->Session->flash('email') ?>
    </div>

    <div id="footer">
    <p class="logo visible-block-phone">
      <a href="/academic_years">
        <img src="<?php echo Configure::read('app.logo') ?>">
      </a>
    </p>
        <?php if (Configure::read('debug') > 1) echo $this->element('sql_dump') ?>
    </div>
</div>

<script>
    $( "#user-logged-menu" ).selectmenu({
        select: function (event, ui) {
            if (ui.item.element.data('link')) {
                document.location.href = ui.item.element.data('link');
            }
        }
    });
</script>
</body>
</html>
