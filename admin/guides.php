<?php

require_once __DIR__ . '/../include/bootstrap.inc.php';
require_admin();

$rows = db()->query(
    'SELECT g.id, g.name, g.surname, g.languages, g.email, g.is_active, g.photo_filename,
            COUNT(eg.experience_id) AS exp_count
     FROM guides g
     LEFT JOIN experience_guides eg ON eg.guide_id = g.id
     GROUP BY g.id
     ORDER BY g.surname, g.name'
)->fetchAll();

$skin = new_page($config['admin_skin'], 'frame-private');
$skin->setContent('title', 'Guide');
$skin->setContent('base',  $config['base']);
$skin->setContent('skin',  $config['admin_skin']);
$skin->setContent('user.username', $_SESSION['user']['username'] ?? '');

$block = new_block('guides-list');
$block->setContent('new_url',  $config['base'] . '/admin/guides-form.php');
$block->setContent('has_rows', count($rows) ? '1' : '');

foreach ($rows as $r) {
    $photo = $r['photo_filename']
        ? '<img src="' . $config['base'] . '/uploads/guides/' . htmlspecialchars($r['photo_filename']) . '"
               class="rounded-circle" style="width:36px;height:36px;object-fit:cover">'
        : '<span class="rounded-circle bg-secondary d-inline-flex align-items-center justify-content-center text-white"
               style="width:36px;height:36px"><i class="fas fa-user fa-sm"></i></span>';

    $block->setContent('guide_photo',      $photo);
    $block->setContent('guide_name',       htmlspecialchars($r['name'] . ' ' . $r['surname']));
    $block->setContent('guide_languages',  htmlspecialchars($r['languages'] ?? '—'));
    $block->setContent('guide_email',      htmlspecialchars($r['email'] ?? ''));
    $block->setContent('guide_exp_count',  $r['exp_count']);
    $block->setContent('guide_status',     $r['is_active']
        ? '<span class="badge bg-success">Attivo</span>'
        : '<span class="badge bg-secondary">Inattivo</span>');
    $block->setContent('guide_edit_url',   $config['base'] . '/admin/guides-form.php?id=' . $r['id']);
    $block->setContent('guide_delete_url', $config['base'] . '/admin/guides-delete.php?id=' . $r['id']);
}

$skin->setContent('body', $block->get());
$skin->close();
