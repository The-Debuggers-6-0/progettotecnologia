<?php

require_once __DIR__ . '/../include/bootstrap.inc.php';
require_admin();

$rows = db()->query(
    'SELECT r.id, r.rating, r.comment, r.created_at,
            e.title AS exp_title,
            u.name AS user_name, u.surname AS user_surname, u.email AS user_email
     FROM reviews r
     JOIN experiences e ON e.id = r.experience_id
     JOIN users      u ON u.id  = r.user_id
     ORDER BY r.created_at DESC'
)->fetchAll();

$skin = new_page($config['admin_skin'], 'frame-private');
$skin->setContent('title', 'Recensioni');
$skin->setContent('base',  $config['base']);
$skin->setContent('skin',  $config['admin_skin']);

$block = new_block('admin/reviews-list');

$block->setContent('has_rows', count($rows) ? '1' : '');

foreach ($rows as $r) {
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        $stars .= $i <= $r['rating'] ? '★' : '☆';
    }
    $block->setContent('rev_id',       (string)$r['id']);
    $block->setContent('rev_exp',      htmlspecialchars($r['exp_title']));
    $block->setContent('rev_user',     htmlspecialchars($r['user_name'] . ' ' . $r['user_surname']));
    $block->setContent('rev_email',    htmlspecialchars($r['user_email']));
    $block->setContent('rev_stars',    $stars);
    $block->setContent('rev_rating',   (string)$r['rating']);
    $block->setContent('rev_comment',  htmlspecialchars($r['comment'] ?? '—'));
    $block->setContent('rev_date',     (new DateTimeImmutable($r['created_at']))->format('d/m/Y H:i'));
    $block->setContent('rev_del_url',  $config['base'] . '/admin/reviews-delete.php?id=' . $r['id']);
}

$skin->setContent('body', $block->get());
$skin->close();
