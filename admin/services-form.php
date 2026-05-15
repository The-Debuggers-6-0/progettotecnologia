<?php

require_once __DIR__ . '/../include/bootstrap.inc.php';
require_admin();

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');

    if ($username === '') {
        $error = 'Lo username del servizio è obbligatorio.';
    } else {
        try {
            $stmt = db()->prepare('INSERT INTO services (username) VALUES (?)');
            $stmt->execute([$username]);
            header('Location: ' . $config['base'] . '/admin/services.php');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $error = 'Username già in uso.';
            } else {
                throw $e;
            }
        }
    }
}

$skin = new_page($config['admin_skin'], 'frame-private');
$skin->setContent('title', 'Nuovo servizio');
$skin->setContent('base',  $config['base']);
$skin->setContent('skin',  $config['admin_skin']);

$block = new_block('services-form');
$block->setContent('error',      $error);
$block->setContent('back_url',   $config['base'] . '/admin/services.php');
$block->setContent('action_url', $config['base'] . '/admin/services-form.php');
$block->setContent('username',   htmlspecialchars($username));

$skin->setContent('body', $block->get());
$skin->close();
