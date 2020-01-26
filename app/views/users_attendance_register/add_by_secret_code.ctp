<p style="margin: 2em 0;">Temporalmente se ha dejado activo este sistema de identificación para aquellos estudiantes que pudiesen llegar a tener problemas con el acceso por MiULPGC.<br><br>Si ese es su caso, tenga en cuenta que las contraseñas han sido reseteadas y que por tanto deberá solicitar una nueava usando el enlace del pie de página.<br><br></p>
<?php
    echo $form->create('UserAttendanceRegister', array('action' => 'add_by_secret_code'));
    echo $form->input('AttendanceRegister.secret_code', array('label' => 'Código de acceso', 'autocomplete' => 'off', 'div'=>array('class'=>'required')));
    if (!isset($auth) && Configure::read('app.users_attendance_register.by_password')) {
      echo $form->input('User.username', array('label' => 'Correo electrónico o DNI', 'autocorrect' => 'off', 'autocapitalize' => 'none'));
      echo $form->input('User.password', array('label' => 'Contraseña', 'value' => ''));
    }
    echo $html->link("¿Quiere recuperar su contraseña?", array('controller' => 'users', 'action' => 'rememberPassword'), array('class' => "remember_password"));
    echo $form->end('Entrar');
?>