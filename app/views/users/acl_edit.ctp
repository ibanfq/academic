<!-- File: /app/views/users/edit.ctp -->
<?php $html->addCrumb('Usuarios', '/users'); ?>
<?php $html->addCrumb("Modificar permisos", "/users/acl_edit"); ?>

<h1>Modificar permisos</h1>
<?php
	echo $form->create('User', array('action' => 'acl_edit'));
?>
    <table>
        <thead>
            <tr>
                <th></th>
                <th>Todos</th>
                <?php foreach ($roleOptions as $roleName): ?>
                    <th><?php echo $roleName ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($resourcesOptions as $resourceKey => $resourceName): ?>
                <tr>
                    <td><?php echo $resourceName ?></td>
                    <td><input type="checkbox" class="check_all" name="data[acl][all]<?php echo "[$resourceKey]" ?>" value="1" <?php echo empty($acl['all'][$resourceKey]) ? '' : 'checked' ?> /></td>
                    <?php foreach ($roleOptions as $roleKey => $_): ?>
                        <td>
                            <input type="checkbox" name="data[acl]<?php echo "[$roleKey][$resourceKey]" ?>" value="1" <?php echo empty($acl[$roleKey][$resourceKey]) ? '' : 'checked' ?> <?php echo empty($acl['all'][$resourceKey]) ? '' : 'disabled' ?> />
                            <input type="hidden" disabled name="data[acl]<?php echo "[$roleKey][$resourceKey]" ?>" />
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        <tbody>
    </table>
<?php
	echo $form->end('Modificar');
?>

<script>
    $(function () {
		$('#UserAclEditForm').on('change', '.check_all', function () {
            var checked = $(this).prop('checked');
            $(this).closest('tr').find('input[type=checkbox]').not(this).each(function () {
                var current = $(this);
                current.prop('disabled', checked);
                current.siblings('input[type=hidden]')
                    .prop('disabled', !checked)
                    .val(current.prop('checked') ? 1 : 0);
            });
        }).find('.check_all').change();
    });
</script>