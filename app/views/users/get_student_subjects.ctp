<select id="subject_id" onchange="update_events()">
  <option value="" selected>Seleccione una asignatura</option>
  <?php foreach($subjects as $subject): ?>
    <option value="<?php echo $subject["Subject"]["id"] ?>"><?php echo $subject["Subject"]["name"] ?></option>
  <?php endforeach; ?>
</select>