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

// Gruppi disponibili per il menu a tendina (admin / Visitatori / ...)
$allGroups = db()->query('SELECT id, name FROM groups ORDER BY id')->fetchAll();
$validIds  = array_map('intval', array_column($allGroups, 'id'));

// Gruppo preselezionato: in modifica quello attuale dell'utente
$selectedGroupId = 0;
if ($isEdit) {
    $g = db()->prepare('SELECT groups_id FROM users_has_groups WHERE users_id = ? LIMIT 1');
    $g->execute([$id]);
    $selectedGroupId = (int) $g->fetchColumn();
}
// Default a "Visitatori" se non c'è un gruppo selezionato (nuovo utente o utente senza gruppo)
if ($selectedGroupId === 0) {
    foreach ($allGroups as $grp) {
        if ($grp['name'] === 'Visitatori') {
            $selectedGroupId = (int) $grp['id'];
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fields['username'] = trim($_POST['username'] ?? '');
    $fields['email']    = trim($_POST['email']    ?? '');
    $fields['name']     = trim($_POST['name']     ?? '');
    $fields['surname']  = trim($_POST['surname']  ?? '');
    $password           = $_POST['password'] ?? '';
    $selectedGroupId    = (int)($_POST['group_id'] ?? 0);

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

            // Gruppo scelto dall'admin (un solo gruppo per utente):
            // sostituisce l'eventuale gruppo precedente.
            $targetUserId = $isEdit ? $id : (int) db()->lastInsertId();
            if (in_array($selectedGroupId, $validIds, true)) {
                db()->prepare('DELETE FROM users_has_groups WHERE users_id = ?')
                    ->execute([$targetUserId]);
                db()->prepare('INSERT INTO users_has_groups (users_id, groups_id) VALUES (?, ?)')
                    ->execute([$targetUserId, $selectedGroupId]);
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

// Costruisco le <option> del menu Gruppo, preselezionando quella corrente
$groupOptions = '';
foreach ($allGroups as $grp) {
    $sel = ((int)$grp['id'] === $selectedGroupId) ? ' selected' : '';
    $groupOptions .= '<option value="' . (int)$grp['id'] . '"' . $sel . '>'
                   . htmlspecialchars($grp['name']) . '</option>';
}
$block->setContent('group_options', $groupOptions);

foreach ($fields as $key => $val) {
    $block->setContent($key, htmlspecialchars((string)$val));
}

$skin->setContent('body', $block->get());
$skin->close();
