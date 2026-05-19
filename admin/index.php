<?php

require_once __DIR__ . '/../include/bootstrap.inc.php';
require_admin();

$totalUsers       = db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
$totalGroups      = db()->query('SELECT COUNT(*) FROM groups')->fetchColumn();
$totalServices    = db()->query('SELECT COUNT(*) FROM services')->fetchColumn();
$totalExp         = db()->query('SELECT COUNT(*) FROM experiences WHERE is_active = 1')->fetchColumn();
$totalExpDraft    = db()->query('SELECT COUNT(*) FROM experiences WHERE is_active = 0')->fetchColumn();
$totalCategories  = db()->query('SELECT COUNT(*) FROM categories')->fetchColumn();
$totalLocations   = db()->query('SELECT COUNT(*) FROM locations')->fetchColumn();
$totalGuides      = db()->query('SELECT COUNT(*) FROM guides WHERE is_active = 1')->fetchColumn();
$totalSlots       = db()->query('SELECT COUNT(*) FROM time_slots WHERE is_active = 1 AND start_datetime >= NOW()')->fetchColumn();
$totalBookings    = db()->query('SELECT COUNT(*) FROM bookings')->fetchColumn();
$pendingBookings  = db()->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();

$skin = new_page($config['admin_skin'], 'frame-private');
$skin->setContent('title', 'Dashboard');
$skin->setContent('base',  $config['base']);
$skin->setContent('skin',  $config['admin_skin']);
$skin->setContent('user.username', $_SESSION['user']['username'] ?? '');

$block = new_block('dashboard');
$block->setContent('total_users',      $totalUsers);
$block->setContent('total_groups',     $totalGroups);
$block->setContent('total_services',   $totalServices);
$block->setContent('total_exp',        $totalExp);
$block->setContent('total_exp_draft',  $totalExpDraft);
$block->setContent('total_categories', $totalCategories);
$block->setContent('total_locations',  $totalLocations);
$block->setContent('total_guides',     $totalGuides);
$block->setContent('total_slots',      $totalSlots);
$block->setContent('total_bookings',   $totalBookings);
$block->setContent('pending_bookings', $pendingBookings);

$skin->setContent('body', $block->get());
$skin->close();
