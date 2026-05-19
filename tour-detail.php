<?php

require_once __DIR__ . '/include/bootstrap.inc.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = db()->prepare(
    'SELECT e.*, c.name AS category_name,
            l.name AS loc_name, l.city AS loc_city, l.address AS loc_address
     FROM experiences e
     LEFT JOIN categories c ON c.id = e.category_id
     LEFT JOIN locations  l ON l.id  = e.location_id
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

// Guida/e assegnate
$gstmt = db()->prepare(
    'SELECT g.name, g.surname, g.bio, g.photo_filename, g.languages
     FROM guides g
     JOIN experience_guides eg ON eg.guide_id = g.id
     WHERE eg.experience_id = ? AND g.is_active = 1
     ORDER BY g.surname, g.name'
);
$gstmt->execute([$id]);
$guideList = $gstmt->fetchAll();

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

// Location strutturata: preferisce il record su locations, poi il testo libero
$locationDisplay = '';
if ($exp['loc_name']) {
    $locationDisplay = htmlspecialchars($exp['loc_name'] . ', ' . $exp['loc_city']);
    if ($exp['loc_address']) {
        $locationDisplay .= ' — ' . htmlspecialchars($exp['loc_address']);
    }
} elseif ($exp['location']) {
    $locationDisplay = htmlspecialchars($exp['location']);
}

// Guide HTML — costruito in PHP (no foreach nel template)
$guidesHtml = '';
foreach ($guideList as $g) {
    $photo = $g['photo_filename']
        ? '<img src="' . htmlspecialchars($config['base'] . '/uploads/guides/' . $g['photo_filename']) . '"
               alt="' . htmlspecialchars($g['name'] . ' ' . $g['surname']) . '"
               class="rounded-circle" style="width:80px;height:80px;min-width:80px;object-fit:cover;margin-right:1.5rem">'
        : '<span class="rounded-circle bg-secondary d-inline-flex align-items-center justify-content-center text-white"
               style="width:80px;height:80px;min-width:80px;font-size:1.8rem;margin-right:1.5rem"><i class="fas fa-user"></i></span>';

    $guidesHtml .= '<div class="d-flex align-items-start mb-4">'
        . $photo
        . '<div>'
        . '<h5 class="mb-1" style="font-size:1.1rem">' . htmlspecialchars($g['name'] . ' ' . $g['surname']) . '</h5>';
    if ($g['languages']) {
        $guidesHtml .= '<p class="mb-1" style="color:#6c757d;font-size:.95rem">'
            . '<i class="flaticon-translate" style="font-size:.85rem"></i> '
            . htmlspecialchars($g['languages']) . '</p>';
    }
    if ($g['bio']) {
        $guidesHtml .= '<p class="mb-0" style="font-size:.95rem">' . nl2br(htmlspecialchars($g['bio'])) . '</p>';
    }
    $guidesHtml .= '</div></div>';
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
$block->setContent('exp_location',    $locationDisplay);
$block->setContent('exp_category',    htmlspecialchars($exp['category_name'] ?? ''));
$block->setContent('exp_price',       number_format($exp['price'], 2, ',', '.'));
$block->setContent('exp_description', nl2br(htmlspecialchars($exp['description'] ?? '')));
$block->setContent('exp_duration',    $exp['duration_minutes']
    ? floor($exp['duration_minutes'] / 60) . 'h ' . ($exp['duration_minutes'] % 60) . 'min'
    : '—');
$block->setContent('exp_max_part',    $exp['max_participants'] ?? '—');
$block->setContent('tours_url',       $config['base'] . '/tours.php');
$block->setContent('photos_html',     $photosHtml);
$block->setContent('guides_html',     $guidesHtml);
$block->setContent('has_guides',      $guidesHtml !== '' ? '1' : '');

$skin->setContent('body', $block->get());
$skin->close();
