<?php

App::import('model', 'academicModel');

class UserAttendanceRegister extends AcademicModel {
    var $name = "UserAttendanceRegister";
    var $belongsTo = array('User', 'AttendanceRegister');
}
