<?php

require_once __DIR__ . '/include/bootstrap.inc.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = db()->prepare(
    'SELECT e.*, c.name AS category_name
     FROM experiences e
     LEFT JOIN categories c ON c.id = e.category_id
     WHERE e.id = ? AND e.is_active = 1'
);
$stmt->execute([$id]);
$exp = $stmt->fetch();

if (!$exp) {
    http_response_code(404);
    die('Esperienza non trovata.');
}

$photos = db()->prepare(
    'SELECT filename FROM experience_photos
     WHERE experience_id = ? ORDER BY is_cover DESC, sort_order ASC'
);
$photos->execute([$id]);
$photoList = $photos->fetchAll();

// Costruisce il carousel HTML in PHP per evitare conflitti foreach/placeholder
$title = htmlspecialchars($exp['title']);
if (count($photoList) > 0) {
    $photosHtml = '<div class="owl-single dots-absolute owl-carousel mb-4">';
    foreach ($photoList as $p) {
        $url = htmlspecialchars($config['base'] . '/uploads/experiences/' . $p['filename']);
        $photosHtml .= '<img src="' . $url . '" alt="' . $title . '" class="img-fluid rounded-20">';
    }
    $photosHtml .= '</div>';
} else {
    $url = htmlspecialchars($config['base'] . '/skins/tour/images/hero-slider-1.jpg');
    $photosHtml = '<img src="' . $url . '" alt="' . $title . '" class="img-fluid rounded-20 mb-4">';
}

$skin = new_page($config['skin']);
$skin->setContent('title',     $title);
$skin->setContent('year',      date('Y'));
$skin->setContent('base',      $config['base']);
$skin->setContent('skin',      $config['skin']);
$skin->setContent('is_logged', isset($_SESSION['user']['username']) ? '1' : '');
$skin->setContent('user.name', $_SESSION['user']['name'] ?? '');

$block = new_block('tour-detail');
$block->setContent('exp_title',       $title);
$block->setContent('exp_location',    htmlspecialchars($exp['location'] ?? ''));
$block->setContent('exp_category',    htmlspecialchars($exp['category_name'] ?? ''));
$block->setContent('exp_price',       number_format($exp['price'], 2, ',', '.'));
$block->setContent('exp_description', nl2br(htmlspecialchars($exp['description'] ?? '')));
$block->setContent('exp_duration',    $exp['duration_minutes']
    ? floor($exp['duration_minutes'] / 60) . 'h ' . ($exp['duration_minutes'] % 60) . 'min'
    : '—');
$block->setContent('exp_max_part',    $exp['max_participants'] ?? '—');
$block->setContent('exp_category',    htmlspecialchars($exp['category_name'] ?? ''));
$block->setContent('tours_url',       $config['base'] . '/tours.php');
$block->setContent('photos_html',     $photosHtml);

$skin->setContent('body', $block->get());
$skin->close();
