<?php
	$barcode->barcode();

	$barcode->setType('C128');
	$barcode->setCode(sprintf("%012d", $event['AttendanceRegister']['id']));
	$barcode->setSize(80,200);
	
	$random = rand(0,1000000);
	$file = 'img/barcode/code_'.$random.'.png';
	
	$barcode->writeBarcodeFile($file);

	$initial_hour = date_create($event['Event']['initial_hour']);
	$final_hour = date_create($event['Event']['final_hour']);

	$continue = true;
	$first_page = true;
	$i = 0;
	$max_per_page = 16;
	
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
	
	<body style="font:11pt Arial, Hevetica, sans-serif;">
		<?php for (;$continue;) { ?>
			<?php if (!$first_page) { ?>
				<p style="page-break-after: always"></p>
				<br />
			<?php } ?>
			<table width="95%" border="0" align="center">
				<tr>
					<td>
						<img src="<?php echo PATH."/" ?>img/logo_ulpgc_white.gif" />
					</td>
					<td style="text-align:right;">
						<?php echo $html->image('barcode/code_'.$random.'.png') ?>
					</td>
				</tr>
				<tr style="height:10px" />
				<?php if ($first_page) { ?>
					<tr>
						<td colspan="2">
							<div style="border:1px #000 solid;font-size:120%;padding-top:0.5em; padding-left:1em;padding-bottom:0.5em;">
								<div>
									<div style="width:60%;float:left;">
										<strong>Profesor/es:</strong> 
										<?php echo "{$event['Teacher']['first_name']} {$event['Teacher']['last_name']}"?>
										<?php if ((isset($event['Teacher_2'])) && ($event['Teacher_2']['id'] > 0)) {
										    echo ", {$event['Teacher_2']['first_name']} {$event['Teacher_2']['last_name']}";
										}?>
									</div>
									<div style="width:39%;float:right">
										<strong>Firma:</strong>
									</div>
								</div>
								<br/>
								<div style="padding-top:1em">
									<strong>Asignatura</strong>: <?php echo $subject['Subject']['name'] ?>
								</div>
								<div style="padding-top:1em">
									<strong>Actividad:</strong> <?php echo "{$event['Activity']['name']} ({$event['Activity']['type']})"?>
								</div>
								<div style="padding-top:1em">
							
									<strong>Fecha:</strong> <?php echo "{$initial_hour->format('d/m/Y')}&nbsp;&nbsp;{$initial_hour->format('H:i')} - {$final_hour->format('H:i')}"?>
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
									<th style="width:15%;border:1px #000 solid">DNI</th>
									<th style="width:35%;border:1px #000 solid">Estudiante</th>
									<th style="width:15%;border:1px #000 solid">Grupo</th>
									<th style="width:20%;border:1px #000 solid">Firma</th>
									<th style="width:15%;border:1px #000 solid">Hora de salida</th>
								</tr>
							</thead>
							<tbody>
								<?php for($j = 0;($i < count($students)) && ($j < $max_per_page);$i++, $j++) { ?>
									<tr style="padding-top:0.1em;height:2.5em">
										<td style="text-align:center; border:1px #000 solid">
											<?php echo "{$students[$i]['Student']['dni']} "?>
										</td>
										<td style="padding-left:0.2em; border:1px #000 solid">
											<?php echo "{$students[$i]['Student']['first_name']} {$students[$i]['Student']['last_name']}"?>
										</td>
										<td style="text-align:center; border:1px #000 solid">
											<?php echo "{$event['Group']['name']}"?>
										</td>
										<td style="text-align:center; border:1px #000 solid" />
										<td style="text-align:center; border:1px #000 solid" />
									</tr>
									
								<?php
									 
									}

									if ( $i >= count($students))
										$continue = false;
									else {
										$max_per_page = 20;
										$first_page = false;
									}
									
									 
								?>
							</tbody>
						</table>
					</td>
				</tr>
			</table>
		<?php } ?>
	</body>
</html>
