<?php

class CourseBehavior extends ModelBehavior {
	function friendly_name(&$model){
		return "{$model->data[$model->alias]['name']} ({$model->data[$model->alias]['initial_date']} - {$model->data[$model->alias]['final_date']})";
	}
}
?>