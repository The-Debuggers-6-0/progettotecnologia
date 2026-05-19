<?php

require_once __DIR__ . '/../include/bootstrap.inc.php';
require_admin();

$rows = db()->query(
    'SELECT l.id, l.name, l.city, l.address,
            COUNT(e.id) AS exp_count
     FROM locations l
     LEFT JOIN experiences e ON e.location_id = l.id
     GROUP BY l.id
     ORDER BY l.city, l.name'
)->fetchAll();

$skin = new_page($config['admin_skin'], 'frame-private');
$skin->setContent('title', 'Location');
$skin->setContent('base',  $config['base']);
$skin->setContent('skin',  $config['admin_skin']);
$skin->setContent('user.username', $_SESSION['user']['username'] ?? '');

$block = new_block('locations-list');
$block->setContent('new_url',  $config['base'] . '/admin/locations-form.php');
$block->setContent('has_rows', count($rows) ? '1' : '');

foreach ($rows as $r) {
    $block->setContent('loc_name',      htmlspecialchars($r['name']));
    $block->setContent('loc_city',      htmlspecialchars($r['city']));
    $block->setContent('loc_address',   htmlspecialchars($r['address'] ?? ''));
    $block->setContent('loc_exp_count', $r['exp_count']);
    $block->setContent('loc_edit_url',  $config['base'] . '/admin/locations-form.php?id=' . $r['id']);
    $block->setContent('loc_delete_url',$config['base'] . '/admin/locations-delete.php?id=' . $r['id']);
}

$skin->setContent('body', $block->get());
$skin->close();
