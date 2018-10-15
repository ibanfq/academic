<?php
class MassiveAttendanceRegister extends AppModel {
    var $name = "MassiveAttendanceRegister";

    var $belongsTo = array('Subject');

    var $hasMany = array('AttendanceRegister' => array(
      'className' => 'AttendanceRegister',
        'order' => 'AttendanceRegister.initial_hour ASC',
        'dependent' => true
    ));
}
