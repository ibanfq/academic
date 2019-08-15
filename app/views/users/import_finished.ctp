<!-- File: /app/views/users/new.ctp -->
<?php $html->addCrumb('Usuarios', '/users'); ?>
<?php $html->addCrumb('Importar estudiantes', '/users/import'); ?>

<h1>Importación finalizada</h1>
<p>Se han importado <strong><?php echo $imported_students; ?></strong> estudiante/s.</p>
<?php if (! empty($inexistent_subjects)): ?>
	<br/>
	<p>Las siguientes asignaturas no se han podido importar ya que no figuran en el sistema:</p>
	<br/>
	<?php 
		foreach ($inexistent_subjects as $subject): 
			echo "{$subject}<br/>";
		endforeach; 
	?>
<?php endif; ?>
<?php if (! empty($failed_students)): ?>
	<br/>
	<p>Los siguientes alumnos no se han podido registrar correctamente:</p>
	<br/>
	<?php 
		foreach ($failed_students as $user): 
			echo "{$user}<br/>";
		endforeach; 
	?>
<?php endif; ?>