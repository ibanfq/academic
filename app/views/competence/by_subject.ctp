<?php $html->addCrumb('Cursos', '/courses'); ?>
<?php $html->addCrumb($course['Course']['name'], "/courses/view/{$course['Course']['id']}"); ?>
<?php $html->addCrumb($subject['Subject']['name'], "/subjects/view/{$subject['Subject']['id']}"); ?>
<?php $html->addCrumb('E-portfolio', "/competence/by_subject/{$subject['Subject']['id']}"); ?>

<?php if ($auth->user('type') == "Profesor" || $auth->user('type') == "Estudiante"): ?>
    <h1>Mis competencias por asignatura: <?php echo h($subject['Subject']['name']) ?></h1>
<?php else: ?>
    <h1>Competencias por asignatura: <?php echo h($subject['Subject']['name']) ?></h1>
<?php endif; ?>

<?php if ($auth->user('type') != "Estudiante") : ?>
    <div class="actions">
        <ul>
            <li><?php echo $html->link('Ver todas las competencias', array('action' => 'by_course', $course['Course']['id'])) ?></li>
        </ul>
    </div>
<?php endif; ?>
<div class="<?php if ($auth->user('type') != "Estudiante"): ?>view<?php endif; ?>">
    <?php if (empty($competence) && ($auth->user('type') == "Profesor" || $auth->user('type') == "Estudiante")): ?>
        No tienes criterios de evaluación asignados para esta asignatura
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
                        <td><?php echo $html->link($row['Competence']['code'], array('controller' => 'competence', 'action' => 'view_by_subject', $subject['Subject']['id'], $row['Competence']['id'])) ?></td>
                        <td><?php echo $row['Competence']['definition'] ?></td>
                        <td><?php echo $html->link('Objetivos', array('controller' => 'competence', 'action' => 'view_by_subject', $subject['Subject']['id'], $row['Competence']['id'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>