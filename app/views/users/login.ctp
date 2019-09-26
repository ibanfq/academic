<?php
    $session->flash('auth');
    echo $form->create('User', array('action' => 'login', 'base' => false));
    echo $form->input('username', array('label' => 'Correo electrónico', 'autocorrect' => 'off', 'autocapitalize' => 'none', 'autocorrect' => 'off', 'autocapitalize' => 'none'));
    echo $form->input('password', array('label' => 'Contraseña'));
 	echo $html->link("¿Olvidó su contraseña?", array('controller' => 'users', 'action' => 'rememberPassword'), array('class' => "remember_password"));
    echo $form->end('Entrar');
?>

<?php echo $html->link('Login con CAS', array('action' => 'cas_login'), array('class' => 'button button-action')) ?>
