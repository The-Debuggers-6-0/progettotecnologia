<?php

require_once __DIR__ . '/../include/bootstrap.inc.php';
require_admin();

$services = db()->query(
    'SELECT s.username,
            GROUP_CONCAT(g.name ORDER BY g.name SEPARATOR ", ") AS grps
     FROM services s
     LEFT JOIN services_has_groups sg ON sg.services_username = s.username
     LEFT JOIN groups g ON g.id = sg.groups_id
     GROUP BY s.username
     ORDER BY s.username'
)->fetchAll();

$skin = new_page($config['admin_skin'], 'frame-private');
$skin->setContent('title', 'Servizi');
$skin->setContent('base',  $config['base']);
$skin->setContent('skin',  $config['admin_skin']);

$block = new_block('services-list');
$block->setContent('new_url', $config['base'] . '/admin/services-form.php');

foreach ($services as $s) {
    $block->setContent('svc_username',   htmlspecialchars($s['username']));
    $block->setContent('svc_groups',     htmlspecialchars($s['grps'] ?? '—'));
    $block->setContent('svc_delete_url', $config['base'] . '/admin/services-delete.php?username=' . urlencode($s['username']));
}

$skin->setContent('body', $block->get());
$skin->close();
