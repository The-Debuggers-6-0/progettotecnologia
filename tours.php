<?php

require_once __DIR__ . '/include/bootstrap.inc.php';

$catId = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;

$categories = db()->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();

// Se è stata selezionata una categoria, filtro per quella categoria, altrimenti prende tutte le esperienze
if ($catId > 0) {
    $stmt = db()->prepare(
        'SELECT e.id, e.title, e.slug, e.short_description, e.price, e.location,
                p.filename AS cover
         FROM experiences e
         LEFT JOIN experience_photos p ON p.experience_id = e.id AND p.is_cover = 1
         WHERE e.is_active = 1 AND e.category_id = ?
         ORDER BY e.created_at DESC'
    );
    $stmt->execute([$catId]);
} else {
    $stmt = db()->query(
        'SELECT e.id, e.title, e.slug, e.short_description, e.price, e.location,
                p.filename AS cover
         FROM experiences e
         LEFT JOIN experience_photos p ON p.experience_id = e.id AND p.is_cover = 1
         WHERE e.is_active = 1
         ORDER BY e.created_at DESC'
    );
}
$experiences = $stmt->fetchAll();

$skin = new_page($config['skin']);
$skin->setContent('title',     'Esperienze');
$skin->setContent('year',      date('Y'));
$skin->setContent('base',      $config['base']);
$skin->setContent('skin',      $config['skin']);
$skin->setContent('is_logged', isset($_SESSION['user']['username']) ? '1' : '');
$skin->setContent('user.name', $_SESSION['user']['name'] ?? '');

$block = new_block('tours');

// Filtro categorie
$btnStyle = 'margin-right:1rem;padding:.75rem 2rem;font-size:1.1rem;border-radius:2rem;';
$filterHtml = '<a href="' . $config['base'] . '/tours.php" class="btn '
    . ($catId === 0 ? 'btn-primary' : 'btn-outline-primary') . '" style="' . $btnStyle . '">Tutte</a>';
foreach ($categories as $cat) {
    $active      = ($cat['id'] == $catId) ? 'btn-primary' : 'btn-outline-primary';
    $filterHtml .= '<a href="' . $config['base'] . '/tours.php?cat=' . $cat['id']
                 . '" class="btn ' . $active . '" style="' . $btnStyle . '">'
                 . htmlspecialchars($cat['name']) . '</a>';
}
$block->setContent('category_filters', $filterHtml);
$block->setContent('has_experiences',  count($experiences) ? '1' : '');

foreach ($experiences as $e) {
    $coverUrl = $e['cover']
        ? $config['base'] . '/uploads/experiences/' . $e['cover']
        : $config['base'] . '/skins/tour/images/hero-slider-1.jpg';

    $block->setContent('experience_url',      $config['base'] . '/tour-detail.php?id=' . $e['id']);
    $block->setContent('experience_photo',    $coverUrl);
    $block->setContent('experience_title',    htmlspecialchars($e['title']));
    $block->setContent('experience_location', htmlspecialchars($e['location'] ?? ''));
    $block->setContent('experience_price',    number_format($e['price'], 2, ',', '.'));
    $block->setContent('experience_short',    htmlspecialchars($e['short_description'] ?? ''));
}

$skin->setContent('body', $block->get());
$skin->close();
