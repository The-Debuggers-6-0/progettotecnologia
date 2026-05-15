<?php

require_once __DIR__ . '/../include/bootstrap.inc.php';
require_admin();

$rows = db()->query(
    'SELECT c.id, c.name, c.description,
            COUNT(e.id) AS exp_count
     FROM categories c
     LEFT JOIN experiences e ON e.category_id = c.id
     GROUP BY c.id
     ORDER BY c.name'
)->fetchAll();

$skin = new_page($config['admin_skin'], 'frame-private');
$skin->setContent('title', 'Categorie');
$skin->setContent('base',  $config['base']);
$skin->setContent('skin',  $config['admin_skin']);
$skin->setContent('user.username', $_SESSION['user']['username'] ?? '');

$block = new_block('categories-list');
$block->setContent('new_url', $config['base'] . '/admin/categories-form.php');
$block->setContent('has_rows', count($rows) ? '1' : '');

foreach ($rows as $r) {
    $block->setContent('cat_name',        htmlspecialchars($r['name']));
    $block->setContent('cat_description', htmlspecialchars($r['description'] ?? ''));
    $block->setContent('cat_exp_count',   $r['exp_count']);
    $block->setContent('cat_edit_url',    $config['base'] . '/admin/categories-form.php?id=' . $r['id']);
    $block->setContent('cat_delete_url',  $config['base'] . '/admin/categories-delete.php?id=' . $r['id']);
}

$skin->setContent('body', $block->get());
$skin->close();
