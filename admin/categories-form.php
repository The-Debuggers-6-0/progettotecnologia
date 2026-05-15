<?php

require_once __DIR__ . '/../include/bootstrap.inc.php';
require_admin();

$id    = (int)($_GET['id'] ?? 0);
$error = '';
$data  = ['name' => '', 'description' => ''];

if ($id > 0) {
    $row = db()->prepare('SELECT * FROM categories WHERE id = ?');
    $row->execute([$id]);
    $data = $row->fetch() ?: $data;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['name']        = trim($_POST['name'] ?? '');
    $data['description'] = trim($_POST['description'] ?? '');

    if ($data['name'] === '') {
        $error = 'Il nome è obbligatorio.';
    } else {
        if ($id === 0) {
            $stmt = db()->prepare('INSERT INTO categories (name, description) VALUES (?, ?)');
            $stmt->execute([$data['name'], $data['description']]);
        } else {
            $stmt = db()->prepare('UPDATE categories SET name = ?, description = ? WHERE id = ?');
            $stmt->execute([$data['name'], $data['description'], $id]);
        }
        header('Location: ' . $config['base'] . '/admin/categories.php');
        exit;
    }
}

$skin = new_page($config['admin_skin'], 'frame-private');
$skin->setContent('title', $id ? 'Modifica categoria' : 'Nuova categoria');
$skin->setContent('base',  $config['base']);
$skin->setContent('skin',  $config['admin_skin']);
$skin->setContent('user.username', $_SESSION['user']['username'] ?? '');

$block = new_block('categories-form');
$block->setContent('form_title',  $id ? 'Modifica categoria' : 'Nuova categoria');
$block->setContent('action_url',  $config['base'] . '/admin/categories-form.php' . ($id ? '?id=' . $id : ''));
$block->setContent('error',       $error);
$block->setContent('cat_name',        htmlspecialchars($data['name']));
$block->setContent('cat_description', htmlspecialchars($data['description'] ?? ''));
$block->setContent('back_url',    $config['base'] . '/admin/categories.php');

$skin->setContent('body', $block->get());
$skin->close();
