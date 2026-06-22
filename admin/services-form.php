<?php

require_once __DIR__ . '/../include/bootstrap.inc.php';
require_admin();

$error    = '';
$username = '';

// Gruppi disponibili per il menu a tendina (admin / Visitatori / ...)
$allGroups = db()->query('SELECT id, name FROM groups ORDER BY id')->fetchAll();
$validIds  = array_map('intval', array_column($allGroups, 'id'));
$selectedGroupId = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username        = trim($_POST['username'] ?? '');
    $selectedGroupId = (int)($_POST['group_id'] ?? 0);

    if ($username === '') {
        $error = 'Lo username del servizio è obbligatorio.';
    } else {
        try {
            $stmt = db()->prepare('INSERT INTO services (username) VALUES (?)');
            $stmt->execute([$username]);

            // Collega il servizio al gruppo scelto dall'admin
            if (in_array($selectedGroupId, $validIds, true)) {
                db()->prepare('INSERT INTO services_has_groups (services_username, groups_id) VALUES (?, ?)')
                    ->execute([$username, $selectedGroupId]);
            }

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

// Costruisco le <option> del menu Gruppo, preselezionando quella scelta (in caso di errore)
$groupOptions = '';
foreach ($allGroups as $grp) {
    $sel = ((int)$grp['id'] === $selectedGroupId) ? ' selected' : '';
    $groupOptions .= '<option value="' . (int)$grp['id'] . '"' . $sel . '>'
                   . htmlspecialchars($grp['name']) . '</option>';
}
$block->setContent('group_options', $groupOptions);

$skin->setContent('body', $block->get());
$skin->close();
