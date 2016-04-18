<?php
require_once('models/academic_model.php');

class UserAttendanceRegister extends AcademicModel {
	var $name = "UserAttendanceRegister";
	var $belongsTo = array('User', 'AttendanceRegister');
}
