<?php
    echo $form->create('UserAttendanceRegister', array('action' => 'add_by_secret_code'));
    echo $form->input('AttendanceRegister.secret_code', array('label' => 'Código de acceso', 'autocomplete' => 'off', 'div'=>array('class'=>'required')));
    if (!isset($auth) && Configure::read('app.users_attendance_register.by_password')) {
      echo $form->input('User.username', array('label' => 'Correo electrónico o DNI', 'autocorrect' => 'off', 'autocapitalize' => 'none'));
      echo $form->input('User.password', array('label' => 'Contraseña', 'value' => ''));
    }
    echo $form->end('Entrar');
?>