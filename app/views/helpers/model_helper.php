<?php
class ModelHelperHelper extends AppHelper {
	function academic_year_name($model) {
		$initial_date = isset($model['Course']['initial_date'])
			? $model['Course']['initial_date']
			: ( isset($model['AcademicYear']['initial_date']) ? $model['AcademicYear']['initial_date']: $model['initial_date'] );
		$final_date = isset($model['Course']['final_date'])
			? $model['Course']['final_date']
			: ( isset($model['AcademicYear']['final_date']) ? $model['AcademicYear']['final_date']: $model['final_date'] );
		return date('Y', strtotime($initial_date)) . ' - ' . date('Y', strtotime($final_date));
	}

	function full_name($user){
		return $user['User']['first_name'].($user['User']['last_name'] != '' ? ' '.$user['User']['last_name'] : '');
	}
	
	function full_name_surname_first($user){
		return ($user['User']['last_name'] != '' ? $user['User']['last_name'].', ' : '').$user['User']['first_name'];
	}

	function format_acronym($acronym) {
		return strtoupper(($acronym));
	}
}
?>