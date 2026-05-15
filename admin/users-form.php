<?php

require_once __DIR__ . '/../include/bootstrap.inc.php';
require_admin();

$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;
$error  = '';
$fields = ['username' => '', 'email' => '', 'name' => '', 'surname' => ''];

// In modifica carica i dati esistenti
if ($isEdit) {
    $stmt = db()->prepare('SELECT username, email, name, surname FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        header('Location: ' . $config['base'] . '/admin/users.php');
        exit;
    }
    $fields = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fields['username'] = trim($_POST['username'] ?? '');
    $fields['email']    = trim($_POST['email']    ?? '');
    $fields['name']     = trim($_POST['name']     ?? '');
    $fields['surname']  = trim($_POST['surname']  ?? '');
    $password           = $_POST['password'] ?? '';

    if ($fields['username'] === '' || $fields['email'] === '') {
        $error = 'Username ed email sono obbligatori.';
    } elseif (!$isEdit && $password === '') {
        $error = 'La password è obbligatoria per i nuovi utenti.';
    } else {
        try {
            if ($isEdit) {
                if ($password !== '') {
                    $stmt = db()->prepare(
                        'UPDATE users SET username=?, email=?, name=?, surname=?, password=? WHERE id=?'
                    );
                    $stmt->execute([
                        $fields['username'], $fields['email'],
                        $fields['name'],     $fields['surname'],
                        password_hash($password, PASSWORD_DEFAULT), $id,
                    ]);
                } else {
                    $stmt = db()->prepare(
                        'UPDATE users SET username=?, email=?, name=?, surname=? WHERE id=?'
                    );
                    $stmt->execute([
                        $fields['username'], $fields['email'],
                        $fields['name'],     $fields['surname'], $id,
                    ]);
                }
            } else {
                $stmt = db()->prepare(
                    'INSERT INTO users (username, email, name, surname, password) VALUES (?,?,?,?,?)'
                );
                $stmt->execute([
                    $fields['username'], $fields['email'],
                    $fields['name'],     $fields['surname'],
                    password_hash($password, PASSWORD_DEFAULT),
                ]);
            }
            header('Location: ' . $config['base'] . '/admin/users.php');
            exit;

        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $error = 'Username o email già in uso.';
            } else {
                throw $e;
            }
        }
    }
}

$skin = new_page($config['admin_skin'], 'frame-private');
$skin->setContent('title', $isEdit ? 'Modifica utente' : 'Nuovo utente');
$skin->setContent('base',  $config['base']);
$skin->setContent('skin',  $config['admin_skin']);

$block = new_block('users-form');
$block->setContent('form_title',  $isEdit ? 'Modifica utente' : 'Nuovo utente');
$block->setContent('error',       $error);
$block->setContent('back_url',    $config['base'] . '/admin/users.php');
$block->setContent('action_url',  $config['base'] . '/admin/users-form.php' . ($isEdit ? '?id=' . $id : ''));
$block->setContent('password_hint', $isEdit ? '(lascia vuoto per non cambiare)' : '');
foreach ($fields as $key => $val) {
    $block->setContent($key, htmlspecialchars((string)$val));
}

$skin->setContent('body', $block->get());
$skin->close();
