<?php $html->addCrumb('Cursos', '/courses'); ?>
<?php $html->addCrumb($course['Course']['name'], "/courses/view/{$course['Course']['id']}"); ?>
<?php $html->addCrumb('E-portfolio', "/competence/by_course/{$course['Course']['id']}"); ?>

<h1>Competencias</h1>
<?php if ($auth->user('type') != "Estudiante") : ?>
  <div class="actions">
    <ul>
      <?php if ($auth->user('type') == "Administrador"): ?>
        <li><?php echo $html->link('Crear competencia', array('action' => 'add_to_course', $course['Course']['id'])) ?></li>
      <?php endif; ?>
    </ul>
  </div>
<?php endif; ?>
<div class="<?php if ($auth->user('type') != "Estudiante"): ?>view<?php endif; ?>">
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