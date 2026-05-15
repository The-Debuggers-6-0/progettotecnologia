<?php

require_once __DIR__ . '/../include/bootstrap.inc.php';
require_admin();

$totalUsers    = db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
$totalGroups   = db()->query('SELECT COUNT(*) FROM groups')->fetchColumn();
$totalServices = db()->query('SELECT COUNT(*) FROM services')->fetchColumn();

$skin = new_page($config['admin_skin'], 'frame-private');
$skin->setContent('title', 'Dashboard');
$skin->setContent('base',  $config['base']);
$skin->setContent('skin',  $config['admin_skin']);

$block = new_block('dashboard');
$block->setContent('total_users',    $totalUsers);
$block->setContent('total_groups',   $totalGroups);
$block->setContent('total_services', $totalServices);

$skin->setContent('body', $block->get());
$skin->close();
