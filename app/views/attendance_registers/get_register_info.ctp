<?php if (!isset($ar)) { ?>
	$('#students').html("");
	$('#activity').val("");
	$('#subject').val("");
	$('#teacher').val("");
	$('#AttendanceRegisterTeacherId').val("");
	$('#AttendanceRegisterDate').val("");
	$('#AttendanceRegisterInitialHourHour').val("");
	$('#AttendanceRegisterInitialHourMin').val("");

	$('#AttendanceRegisterFinalHourHour').val("");
	$('#AttendanceRegisterFinalHourMin').val("");
<?php } else { ?>
	$('#students').html("<?php
		$i = 0;
		foreach($students as $student):
			echo "<tr id='row_{$i}'><td onclick='toogleCheckBox({$student['Student']['id']})'>{$student['Student']['first_name']} {$student['Student']['last_name']}</td><td style='text-align:center'><input type='checkbox' id='students_{$student['Student']['id']}' name='data[AttendanceRegister][students][{$student['Student']['id']}]' value='1' checked /></td></tr>";
			$i++;
		endforeach;

	?>");

	$('#activity').val("<?php echo $ar['Activity']['name'] ?>");
	$('#subject').val("<?php echo $subject['Subject']['name'] ?>");
	$('#AttendanceRegisterNumStudents').val("<?php echo count($students) ?>");
	$('#teacher').val("<?php echo "{$ar['Teacher']['first_name']} {$ar['Teacher']['last_name']}"?>");
	$('#AttendanceRegisterTeacherId').val("<?php echo "{$ar['Event']['teacher_id']}"?>");
	
	<?php if (isset($ar['Teacher_2']['id'])) {?>
  	
  	$('#teacher_2').val("<?php echo "{$ar['Teacher_2']['first_name']} {$ar['Teacher_2']['last_name']}"?>");
  	$('#AttendanceRegisterTeacher2Id').val("<?php echo "{$ar['Event']['teacher_2_id']}"?>");
  	
	<?php }?>
	
	$('#AttendanceRegisterDate').val("<?php
		$initial_date = date_create($ar['Event']['initial_hour']);
		$final_date = date_create($ar['Event']['final_hour']);
		echo $initial_date->format('d-m-Y');
	?>");
	$('#AttendanceRegisterInitialHourHour').val("<?php
		echo $initial_date->format('H');
	?>");
	$('#AttendanceRegisterInitialHourMin').val("<?php
		echo $initial_date->format('i');
	?>");

	$('#AttendanceRegisterFinalHourHour').val("<?php
		echo $final_date->format('H');
	?>");
	$('#AttendanceRegisterFinalHourMin').val("<?php
		echo $final_date->format('i');
	?>");
<?php } ?>

