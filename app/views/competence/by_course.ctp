<?php $html->addCrumb('Cursos', '/courses'); ?>
<?php $html->addCrumb($course['Course']['name'], "/courses/view/{$course['Course']['id']}"); ?>
<?php $html->addCrumb('E-portfolio', "/competence/by_course/{$course['Course']['id']}"); ?>

<?php if ($auth->user('type') == "Profesor" || $auth->user('type') == "Estudiante"): ?>
    <h1>Mis competencias</h1>
<?php else: ?>
    <h1>Competencias</h1>
<?php endif; ?>

<div class="actions">
<ul>
    <?php if ($auth->user('type') == "Administrador"): ?>
    <li><?php echo $html->link('Crear competencia', array('action' => 'add_to_course', $course['Course']['id'])) ?></li>
    <?php endif; ?>
    <?php if (in_array($auth->user('type'), array("Administrador", "Profesor", "Estudiante"))): ?>
    <li><?php echo $html->link('Solicitudes de evaluación', array('controller' => 'competence_goal_requests', 'action' => 'by_course', $course['Course']['id'])) ?></li>
    <?php endif; ?>
    <?php if (in_array($auth->user('type'), array("Administrador", "Profesor"))): ?>
    <li><?php echo $html->link('Evaluación por asignaturas', array('controller' => 'courses', 'action' => 'view', 'ref' => 'competences', $course['Course']['id'])) ?></li>
    <?php endif; ?>
</ul>
</div>
<div class="view">
    <?php if (empty($competence) && $auth->user('type') == "Profesor"): ?>
        No tienes criterios de evaluación asignados
    <?php else: ?>
        <div class="horizontal-scrollable-content">
            <table>
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Definición</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($competence as $row): ?>
                    <tr>
                        <td><?php echo $html->link($row['Competence']['code'], array('controller' => 'competence', 'action' => 'view', $row['Competence']['id'])) ?></td>
                        <td><?php echo $row['Competence']['definition'] ?></td>
                        <td><?php echo $html->link('Objetivos', array('controller' => 'competence', 'action' => 'view', $row['Competence']['id'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>