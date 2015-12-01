<?php
	if ($success)
		echo "success";
	else {
		if (isset($error))
			echo $error;
		else
			echo "error";
	}
?>