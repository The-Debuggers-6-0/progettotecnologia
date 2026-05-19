<?php

require_once __DIR__ . '/../include/bootstrap.inc.php';
require_admin();

$rows = db()->query(
    'SELECT e.id, e.title, e.location, e.price, e.is_active,
            c.name AS category_name,
            p.filename AS cover
     FROM experiences e
     LEFT JOIN categories c ON c.id = e.category_id
     LEFT JOIN experience_photos p ON p.experience_id = e.id AND p.is_cover = 1
     ORDER BY e.created_at DESC'
)->fetchAll();

$skin = new_page($config['admin_skin'], 'frame-private');
$skin->setContent('title', 'Esperienze');
$skin->setContent('base',  $config['base']);
$skin->setContent('skin',  $config['admin_skin']);
$skin->setContent('user.username', $_SESSION['user']['username'] ?? '');

$block = new_block('experiences-list');
$block->setContent('new_url',  $config['base'] . '/admin/experiences-form.php');
$block->setContent('has_rows', count($rows) ? '1' : '');

foreach ($rows as $r) {
    $coverUrl = $r['cover']
        ? $config['base'] . '/uploads/experiences/' . $r['cover']
        : $config['base'] . '/skins/tour/images/hero-slider-1.jpg';

    $block->setContent('exp_title',      htmlspecialchars($r['title']));
    $block->setContent('exp_location',   htmlspecialchars($r['location'] ?? ''));
    $block->setContent('exp_price',      number_format($r['price'], 2, ',', '.'));
    $block->setContent('exp_category',   htmlspecialchars($r['category_name'] ?? '—'));
    $block->setContent('exp_cover_url',  $coverUrl);
    $block->setContent('exp_status',     $r['is_active'] ? 'Attiva' : 'Bozza');
    $block->setContent('exp_status_class', $r['is_active'] ? 'success' : 'secondary');
    $block->setContent('exp_slots_url',  $config['base'] . '/admin/time-slots.php?exp=' . $r['id']);
    $block->setContent('exp_edit_url',   $config['base'] . '/admin/experiences-form.php?id=' . $r['id']);
    $block->setContent('exp_delete_url', $config['base'] . '/admin/experiences-delete.php?id=' . $r['id']);
}

$skin->setContent('body', $block->get());
$skin->close();
