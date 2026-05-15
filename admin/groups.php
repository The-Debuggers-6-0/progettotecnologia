<?php

require_once __DIR__ . '/../include/bootstrap.inc.php';
require_admin();

$groups = db()->query(
    'SELECT g.id, g.name, g.description,
            COUNT(ug.users_id) AS user_count
     FROM groups g
     LEFT JOIN users_has_groups ug ON ug.groups_id = g.id
     GROUP BY g.id
     ORDER BY g.name'
)->fetchAll();

$skin = new_page($config['admin_skin'], 'frame-private');
$skin->setContent('title', 'Gruppi');
$skin->setContent('base',  $config['base']);
$skin->setContent('skin',  $config['admin_skin']);

$block = new_block('groups-list');
$block->setContent('new_url', $config['base'] . '/admin/groups-form.php');

foreach ($groups as $g) {
    $block->setContent('group_name',        htmlspecialchars($g['name']));
    $block->setContent('group_description', htmlspecialchars($g['description'] ?? '—'));
    $block->setContent('group_user_count',  $g['user_count']);
    $block->setContent('group_edit_url',    $config['base'] . '/admin/groups-form.php?id=' . $g['id']);
    $block->setContent('group_delete_url',  $config['base'] . '/admin/groups-delete.php?id=' . $g['id']);
}

$skin->setContent('body', $block->get());
$skin->close();
