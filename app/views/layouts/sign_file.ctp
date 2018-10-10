<?php
	$continue = true;
	$first_page = true;
	$i = 0;
	$max_per_page = 16;
	
	function type_to_s($type) {
	  switch($type){
	    case "Clase magistral":
	      return "CM";
	    case "Evaluación":
	      return "EV";
	    case "Otra presencial":
	      return "Otra";
	    case "Práctica en aula":
	      return "A";
	    case "Práctica clínica":
	      return "C";
	    case "Práctica de informática":
	      return "I";
	    case "Práctica de laboratorio":
	      return "L";
	    case "Práctica de microscopía":
	      return "M";
	    case "Práctica de problemas":
	      return "P";
	    case "Práctica externa":
	      return "EX";
	    case "Seminario":
	      return "S";
	    case "Taller/trabajo en grupo":
	      return "TLL";
	    case "Tutoría":
	      return "TU";
	  }
	};
	
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<title>Hoja de asistencia</title>
		<script type="text/javascript">
			$(document).ready(function() {
				window.print();
			});
		</script>
	</head>
	
	<body style="font:9pt Arial, Hevetica, sans-serif;">
		<?php for (;$continue;) { ?>
			<?php if (!$first_page) { ?>
				<p style="page-break-after: always"></p>
				<br />
			<?php } ?>
			<table width="95%" border="0" align="center">
				<tr>
					<td>
						<img src="<?php echo PATH . Configure::read('app.logo_ulpgc_white') ?>">
					</td>
				</tr>
				<tr style="height:10px" />
				<?php if ($first_page) { ?>
					<tr>
						<td colspan="2">
							<div style="border:1px #000 solid;font-size:200%;padding-top:0.5em; padding-left:1em;padding-bottom:0.5em;">
								<div>
									<div style="width:60%;float:left;">
										<strong>Aula:</strong> <?php echo "{$classroom['Classroom']['name']}"?>
									</div>
								</div>
								<br/>
								<div style="padding-top:1em">
									<strong>Fecha</strong>: <?php echo $date->format('d/m/Y') ?>
								</div>
							</div>
						</td>
					</tr>
					<tr style="height:10px" />
				<?php } ?>
				<tr>
					<td colspan="2">
						<table style="width:100%;border:1px #000 solid" cellpadding="0" cellspacing="0">
							<thead>
								<tr style="font:bold">
								  <th style="width:6%;border:1px #000 solid">Id</th>
									<th style="width:8%;border:1px #000 solid">Tipo</th>
									<th style="width:15%;border:1px #000 solid">Asignatura</th>
									<th style="width:35%;border:1px #000 solid">Profesor/es</th>
									<th style="width:8%;border:1px #000 solid">Inicio</th>
									<th style="width:8%;border:1px #000 solid">Fin</th>
									<th style="width:20%;border:1px #000 solid">Firma</th>
								</tr>
							</thead>
							<tbody>
								<?php for($j = 0;($i < count($activities)) && ($j < $max_per_page);$i++, $j++) { ?>
									<tr style="padding-top:0.1em;height:3em">
									  <td style="text-align:center; border:1px #000 solid">
											<?php echo "{$activities[$i]['Event']['id']} "?>
										</td>
										<td style="text-align:center; border:1px #000 solid">
											<?php echo type_to_s($activities[$i]['Activity']['type']) ?>
										</td>
										<td style="text-align:center; padding-left:0.2em; border:1px #000 solid">
											<?php echo "{$activities[$i]['Subject']['acronym']}"?>
										</td>
										<td style="padding-left:0.2em; border:1px #000 solid">
											<?php
                                                                                            echo "{$activities[$i]['Teacher']['first_name']} {$activities[$i]['Teacher']['last_name']}";
                                                                                            if (!empty($activities[$i]['Teacher2']['id'])) {
                                                                                                echo ", {$activities[$i]['Teacher2']['first_name']} {$activities[$i]['Teacher2']['last_name']}";
                                                                                            }
                                                                                        ?>
										</td>
										
										<td style="text-align:center; padding-left:0.2em; border:1px #000 solid">
										  <?php $initial_hour = date_create($activities[$i]['Event']['initial_hour']); ?>
											<?php echo "{$initial_hour->format('H:i')}" ?>
										</td>
										
										<td style="text-align:center; padding-left:0.2em; border:1px #000 solid">
										  <?php $final_hour = date_create($activities[$i]['Event']['final_hour']); ?>
											<?php echo "{$final_hour->format('H:i')}" ?>
										</td>
										
										<td style="padding-left:0.2em; border:1px #000 solid"></td>
									</tr>
									
								<?php
									 
									}

									if ( $i >= count($activities)) {
										$continue = false; 
									  for ($k=0;$k<3;$k++) {
									?>
									
    									<tr style="padding-top:0.1em;height:3em">
    								    <td style="padding-left:0.2em; border:1px #000 solid"></td>
    								    <td style="padding-left:0.2em; border:1px #000 solid"></td>
    								    <td style="padding-left:0.2em; border:1px #000 solid"></td>
    								    <td style="padding-left:0.2em; border:1px #000 solid"></td>
    								    <td style="padding-left:0.2em; border:1px #000 solid"></td>
    								    <td style="padding-left:0.2em; border:1px #000 solid"></td>
    								    <td style="padding-left:0.2em; border:1px #000 solid"></td>
    									</tr>
								<?php
							  }
									} else {
										$max_per_page = 20;
										$first_page = false;
									}
									
									 
								?>
							</tbody>
						<table>
					</td>
				</tr>
			</table>
		<?php } ?>
	</body>
</html>