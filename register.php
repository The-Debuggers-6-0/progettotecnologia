<?php

require_once __DIR__ . '/include/bootstrap.inc.php';

if (!empty($_SESSION['user'])) {
    header('Location: ' . $config['base'] . '/index.php');
    exit;
}

$error  = '';
$ok     = '';
$fields = ['username' => '', 'email' => '', 'name' => '', 'surname' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fields['username'] = trim($_POST['username'] ?? '');
    $fields['email']    = trim($_POST['email']    ?? '');
    $fields['name']     = trim($_POST['name']     ?? '');
    $fields['surname']  = trim($_POST['surname']  ?? '');
    $password           = $_POST['password']         ?? '';
    $confirm            = $_POST['password_confirm'] ?? '';

    if ($fields['username'] === '' || $fields['email'] === '' || $password === '') {
        $error = 'Username, email e password sono obbligatori.';
    } elseif ($password !== $confirm) {
        $error = 'Le due password non coincidono.';
    } elseif (strlen($password) < 8) {
        $error = 'La password deve essere di almeno 8 caratteri.';
    } else {
        try {
            $stmt = db()->prepare(
                'INSERT INTO users (username, email, password, name, surname)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $fields['username'],
                $fields['email'],
                password_hash($password, PASSWORD_DEFAULT),
                $fields['name'],
                $fields['surname'],
            ]);

            // Ogni utente registrato è un "Visitatore": lo aggiungo al gruppo.
            $newUserId = (int) db()->lastInsertId();
            $grp = db()->prepare('SELECT id FROM groups WHERE name = ?');
            $grp->execute(['Visitatori']);
            $groupId = $grp->fetchColumn();
            if ($groupId) {
                db()->prepare('INSERT INTO users_has_groups (users_id, groups_id) VALUES (?, ?)')
                    ->execute([$newUserId, $groupId]);
            }

            header('Location: ' . $config['base'] . '/login.php');
            exit;

        } catch (PDOException $e) {
            // Codice 23000 = violazione UNIQUE (username o email già usati)
            if ($e->getCode() === '23000') {
                $error = 'Username o email già registrati.';
            } else {
                throw $e;
            }
        }
    }
}

$skin = new_page($config['skin']);
$skin->setContent('title',     'Registrati');
$skin->setContent('year',      date('Y'));
$skin->setContent('base',      $config['base']);
$skin->setContent('skin',      $config['skin']);
$skin->setContent('is_logged', '');

$block = new_block('register');
$block->setContent('error',    $error);
foreach ($fields as $key => $val) {
    $block->setContent($key, htmlspecialchars($val));
}

$skin->setContent('body', $block->get());
$skin->close();
