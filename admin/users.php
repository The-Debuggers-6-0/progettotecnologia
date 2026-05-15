<?php

require_once __DIR__ . '/../include/bootstrap.inc.php';
require_admin();

$users = db()->query(
    'SELECT u.id, u.username, u.email, u.name, u.surname, u.created_at,
            GROUP_CONCAT(g.name ORDER BY g.name SEPARATOR ", ") AS grps
     FROM users u
     LEFT JOIN users_has_groups ug ON ug.users_id = u.id
     LEFT JOIN groups g ON g.id = ug.groups_id
     GROUP BY u.id
     ORDER BY u.created_at DESC'
)->fetchAll();

$skin = new_page($config['admin_skin'], 'frame-private');
$skin->setContent('title', 'Utenti');
$skin->setContent('base',  $config['base']);
$skin->setContent('skin',  $config['admin_skin']);

$block = new_block('users-list');
$block->setContent('new_url', $config['base'] . '/admin/users-form.php');

foreach ($users as $u) {
    $block->setContent('user_username',   htmlspecialchars($u['username']));
    $block->setContent('user_email',      htmlspecialchars($u['email']));
    $block->setContent('user_fullname',   htmlspecialchars(trim($u['name'] . ' ' . $u['surname'])));
    $block->setContent('user_groups',     htmlspecialchars($u['grps'] ?? '—'));
    $block->setContent('user_created_at', $u['created_at']);
    $block->setContent('user_edit_url',   $config['base'] . '/admin/users-form.php?id=' . $u['id']);
    $block->setContent('user_delete_url', $config['base'] . '/admin/users-delete.php?id=' . $u['id']);
}

$skin->setContent('body', $block->get());
$skin->close();
