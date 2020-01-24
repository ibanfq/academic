<?php
    $session->flash('auth');
    if (Configure::read('debug')) {
        echo $form->create('User', array('action' => 'login', 'base' => false));
        echo $form->input('username', array('label' => 'Correo electrónico', 'autocorrect' => 'off', 'autocapitalize' => 'none', 'autocorrect' => 'off', 'autocapitalize' => 'none'));
        echo $form->input('password', array('label' => 'Contraseña'));
        echo $html->link("¿Olvidó su contraseña?", array('controller' => 'users', 'action' => 'rememberPassword'), array('class' => "remember_password"));
        echo $form->end('Entrar');
    }
?>

<p style="padding: 2em 0;"><big>Para poder acceder a Academic se require identificación mediante MiULPGC</big></p>
<?php echo $html->link('Acceder a MiULPGC', array('action' => 'cas_login'), array('class' => 'button button-action')) ?>
