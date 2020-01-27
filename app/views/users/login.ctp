<?php
    /*
    $session->flash('auth');
    
    echo $form->create('User', array('action' => 'login', 'base' => false));
    echo $form->input('username', array('label' => 'Correo electrónico', 'autocorrect' => 'off', 'autocapitalize' => 'none', 'autocorrect' => 'off', 'autocapitalize' => 'none'));
    echo $form->input('password', array('label' => 'Contraseña'));
    echo $html->link("¿Olvidó su contraseña?", array('controller' => 'users', 'action' => 'rememberPassword'), array('class' => "remember_password"));
    echo $form->end('Entrar');
    */
?>

<p style="margin: 2em 0;">El sistema de identificación de Academic ha sido unificado con el de MiULPGC.<br><br>A continuación se te pedirá que te identifiques mediante el sistema de clave única de MiULPGC para poder continuar.</p>
<?php echo $html->link('Acceder con MiULPGC', array('action' => 'cas_login'), array('class' => 'button button-action')) ?>
