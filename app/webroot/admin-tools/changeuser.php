<?php

if (empty($_GET['id'])) {
    exit;
}

$_GET['url'] = 'favicon.ico'; // Do nothing
require('../index.php');

if (!class_exists('CakeSession')) {
	require LIBS . 'cake_session.php';
}
$session = new CakeSession;
$session->start();

if ($session->check('Auth.User')) {
    $user = $session->read('Auth.User');
    if ($user['type'] === 'Administrador' && $user['super_admin'] && $user['id'] !== $_GET['id']) {
        $userModel = ClassRegistry::init('User');
        $newUser = $userModel->findById($_GET['id']);
        if ($newUser) {
            $session->write('Auth.User', $newUser['User']);
        }
    }
}