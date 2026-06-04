<?php

require_once __DIR__ . '/include/bootstrap.inc.php';

//Prendo i 6 tour più recenti per la home
$featured = db()->query(
    'SELECT e.id, e.title, e.price, e.location,
            p.filename AS cover
     FROM experiences e
     LEFT JOIN experience_photos p ON p.experience_id = e.id AND p.is_cover = 1
     WHERE e.is_active = 1
     ORDER BY e.created_at DESC
     LIMIT 6'
)->fetchAll();

$skin = new_page($config['skin']);
$skin->setContent('title',     'Home');
$skin->setContent('year',      date('Y'));
$skin->setContent('base',      $config['base']);
$skin->setContent('skin',      $config['skin']);
$skin->setContent('is_logged', isset($_SESSION['user']['username']) ? '1' : '');
$skin->setContent('user.name', $_SESSION['user']['name'] ?? '');

$home = new_block('home');
$home->setContent('has_experiences', count($featured) ? '1' : '');

foreach ($featured as $e) {
    $coverUrl = $e['cover']
        ? $config['base'] . '/uploads/experiences/' . $e['cover']
        : $config['base'] . '/skins/tour/images/hero-slider-1.jpg';

    $home->setContent('experience_url',      $config['base'] . '/tour-detail.php?id=' . $e['id']);
    $home->setContent('experience_photo',    $coverUrl);
    $home->setContent('experience_title',    htmlspecialchars($e['title']));
    $home->setContent('experience_location', htmlspecialchars($e['location'] ?? ''));
    $home->setContent('experience_price',    number_format($e['price'], 2, ',', '.'));
}

$skin->setContent('body', $home->get());
$skin->close();
