<?php
    $session->flash('auth');
    echo $form->create('User', array('action' => 'login'));
    echo $form->input('username', array('label' => 'Correo electrónico'));
    echo $form->input('password', array('label' => 'Contraseña'));
 	echo $html->link("¿Olvidó su contraseña?", array('controller' => 'users', 'action' => 'rememberPassword'), array('class' => "remember_password"));
    echo $form->end('Entrar');
?>