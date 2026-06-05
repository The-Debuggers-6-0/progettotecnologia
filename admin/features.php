<?php

require_once __DIR__ . '/../include/bootstrap.inc.php';
require_admin();

$rows = db()->query(
    'SELECT id, icon, title, description, sort_order
     FROM home_features
     ORDER BY sort_order, id'
)->fetchAll();

$skin = new_page($config['admin_skin'], 'frame-private');
$skin->setContent('title', 'Box "Perché sceglierci"');
$skin->setContent('base',  $config['base']);
$skin->setContent('skin',  $config['admin_skin']);
$skin->setContent('user.username', $_SESSION['user']['username'] ?? '');

// Carico il font flaticon per mostrare l'anteprima reale delle icone
$skin->setContent('head',
    '<link rel="stylesheet" href="' . $config['base'] . '/skins/tour/fonts/flaticon/font/flaticon.css">');

$block = new_block('features-list');
$block->setContent('new_url',  $config['base'] . '/admin/features-form.php');
$block->setContent('has_rows', count($rows) ? '1' : '');

foreach ($rows as $r) {
    $block->setContent('feat_icon',        htmlspecialchars($r['icon']));
    $block->setContent('feat_title',       htmlspecialchars($r['title']));
    $block->setContent('feat_description', htmlspecialchars($r['description'] ?? ''));
    $block->setContent('feat_order',       $r['sort_order']);
    $block->setContent('feat_edit_url',    $config['base'] . '/admin/features-form.php?id=' . $r['id']);
    $block->setContent('feat_delete_url',  $config['base'] . '/admin/features-delete.php?id=' . $r['id']);
}

$skin->setContent('body', $block->get());
$skin->close();
