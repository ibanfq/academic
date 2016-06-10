<?php
    echo $form->create('UserAttendanceRegister', array('action' => 'add_by_secret_code'));
    echo $form->input('AttendanceRegister.secret_code', array('label' => 'Código de acceso', 'autocomplete' => 'off', 'div'=>array('class'=>'required')));
    if (!isset($auth) || $auth->user('type') != "Estudiante") {
      echo $form->input('User.username', array('label' => 'Correo electrónico o DNI'));
      echo $form->input('User.password', array('label' => 'Contraseña', 'value' => ''));
    }
    echo $form->end('Entrar');
?>