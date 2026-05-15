<?php

require_once __DIR__ . '/../include/bootstrap.inc.php';
require_admin();

$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;
$error  = '';
$fields = ['name' => '', 'description' => ''];

if ($isEdit) {
    $stmt = db()->prepare('SELECT name, description FROM groups WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        header('Location: ' . $config['base'] . '/admin/groups.php');
        exit;
    }
    $fields = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields['name']        = trim($_POST['name']        ?? '');
    $fields['description'] = trim($_POST['description'] ?? '');

    if ($fields['name'] === '') {
        $error = 'Il nome del gruppo è obbligatorio.';
    } else {
        try {
            if ($isEdit) {
                $stmt = db()->prepare('UPDATE groups SET name=?, description=? WHERE id=?');
                $stmt->execute([$fields['name'], $fields['description'], $id]);
            } else {
                $stmt = db()->prepare('INSERT INTO groups (name, description) VALUES (?,?)');
                $stmt->execute([$fields['name'], $fields['description']]);
            }
            header('Location: ' . $config['base'] . '/admin/groups.php');
            exit;
        } catch (PDOException $e) {
            $error = 'Errore nel salvataggio.';
            throw $e;
        }
    }
}

$skin = new_page($config['admin_skin'], 'frame-private');
$skin->setContent('title', $isEdit ? 'Modifica gruppo' : 'Nuovo gruppo');
$skin->setContent('base',  $config['base']);
$skin->setContent('skin',  $config['admin_skin']);

$block = new_block('groups-form');
$block->setContent('form_title', $isEdit ? 'Modifica gruppo' : 'Nuovo gruppo');
$block->setContent('error',      $error);
$block->setContent('back_url',   $config['base'] . '/admin/groups.php');
$block->setContent('action_url', $config['base'] . '/admin/groups-form.php' . ($isEdit ? '?id=' . $id : ''));
$block->setContent('name',        htmlspecialchars($fields['name']));
$block->setContent('description', htmlspecialchars($fields['description']));

$skin->setContent('body', $block->get());
$skin->close();
