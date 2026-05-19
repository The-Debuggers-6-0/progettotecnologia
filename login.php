<?php

require_once __DIR__ . '/include/bootstrap.inc.php';

// Se l'utente è già loggato, non ha senso stare qui.
if (!empty($_SESSION['user'])) {
    header('Location: ' . $config['base'] . '/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Compila tutti i campi.';
    } else {
        $stmt = db()->prepare('SELECT id, username, name, password FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $row = $stmt->fetch();

        if ($row && password_verify($password, $row['password'])) {
            $_SESSION['user'] = [
                'id'       => $row['id'],
                'username' => $row['username'],
                'name'     => $row['name'],
            ];

            // Se è admin → dashboard admin, altrimenti → home
            $isAdmin = db()->prepare(
                'SELECT 1 FROM users_has_groups ug
                 JOIN groups g ON g.id = ug.groups_id
                 WHERE ug.users_id = ? AND g.name = ?'
            );
            $isAdmin->execute([$row['id'], 'admin']);
            $dest = $isAdmin->fetch()
                ? $config['base'] . '/admin/index.php'
                : $config['base'] . '/index.php';

            header('Location: ' . $dest);
            exit;
        } else {
            $error = 'Username o password errati.';
        }
    }
}

$skin = new_page($config['skin']);
$skin->setContent('title',     'Accedi');
$skin->setContent('year',      date('Y'));
$skin->setContent('base',      $config['base']);
$skin->setContent('skin',      $config['skin']);
$skin->setContent('is_logged', isset($_SESSION['user']['username']) ? '1' : '');

$block = new_block('login');
$block->setContent('error',    $error);
$block->setContent('username', htmlspecialchars($_POST['username'] ?? ''));

$skin->setContent('body', $block->get());
$skin->close();
