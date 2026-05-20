<?php

require_once __DIR__ . '/include/bootstrap.inc.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = db()->prepare(
    'SELECT e.*, c.name AS category_name,
            l.name AS loc_name, l.city AS loc_city, l.address AS loc_address,
            l.latitude AS loc_lat, l.longitude AS loc_lng
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
        . '<h5 class="mb-1" style="font-size:1.2rem">' . htmlspecialchars($g['name'] . ' ' . $g['surname']) . '</h5>';
    if ($g['languages']) {
        $guidesHtml .= '<p class="mb-1" style="color:#6c757d;font-size:1.05rem">'
            . '<i class="flaticon-translate" style="font-size:.9rem"></i> '
            . htmlspecialchars($g['languages']) . '</p>';
    }
    if ($g['bio']) {
        $guidesHtml .= '<p class="mb-0" style="font-size:1.05rem">' . nl2br(htmlspecialchars($g['bio'])) . '</p>';
    }
    $guidesHtml .= '</div></div>';
}

// Slot disponibili: prossimi 8 slot attivi con posti liberi
$sstmt = db()->prepare(
    'SELECT id, start_datetime, capacity, booked_count, notes
     FROM time_slots
     WHERE experience_id = ? AND is_active = 1 AND start_datetime >= NOW()
     ORDER BY start_datetime ASC
     LIMIT 8'
);
$sstmt->execute([$id]);
$slotList = $sstmt->fetchAll();

$slotsHtml = '';
foreach ($slotList as $sl) {
    $dt        = new DateTimeImmutable($sl['start_datetime']);
    $available = $sl['capacity'] - $sl['booked_count'];
    $badgeCls  = $available > 0 ? 'success' : 'danger';
    $badgeTxt  = $available > 0 ? $available . ' posti liberi' : 'Esaurito';

    $slotsHtml .= '<div class="d-flex align-items-center justify-content-between"'
        . ' style="padding:1rem 0;border-bottom:1px solid #f0f0f0">'
        . '<div>'
        . '<span class="fw-semibold" style="font-size:1.18rem">'
        . $dt->format('d/m/Y') . '</span>'
        . '<span class="text-muted" style="font-size:1.05rem;display:block;margin-top:.2rem">'
        . '<i class="flaticon-clock" style="font-size:.9rem"></i> ' . $dt->format('H:i') . '</span>';
    if ($sl['notes']) {
        $slotsHtml .= '<div class="text-muted mt-1" style="font-size:1rem">'
            . htmlspecialchars($sl['notes']) . '</div>';
    }
    $bookBtn = '';
    if ($available > 0) {
        $bookUrl = $config['base'] . '/booking.php?slot=' . $sl['id'];
        $bookBtn = '<a href="' . $bookUrl . '" class="btn btn-primary btn-sm ms-2">Prenota</a>';
    }
    $slotsHtml .= '</div>'
        . '<div style="display:flex;align-items:center;gap:.5rem">'
        . '<span class="badge bg-' . $badgeCls . '" style="font-size:.88rem;padding:.45em .85em">'
        . $badgeTxt . '</span>'
        . $bookBtn
        . '</div>'
        . '</div>';
}

// Recensioni — controllo permessi
$userId     = $_SESSION['user']['id'] ?? null;
$hasReviewed = false;
$canReview   = false;

if ($userId) {
    $chk = db()->prepare('SELECT 1 FROM reviews WHERE experience_id = ? AND user_id = ?');
    $chk->execute([$id, $userId]);
    $hasReviewed = (bool)$chk->fetch();

    if (!$hasReviewed) {
        $chk2 = db()->prepare(
            'SELECT 1 FROM bookings b
             JOIN time_slots ts ON ts.id = b.time_slot_id
             WHERE ts.experience_id = ? AND b.user_id = ? AND b.status = "confirmed"
             LIMIT 1'
        );
        $chk2->execute([$id, $userId]);
        $canReview = (bool)$chk2->fetch();
    }
}

// Gestione POST nuova recensione
$reviewError   = '';
$reviewSuccess = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rating'])) {
    if (!$userId) {
        header('Location: ' . $config['base'] . '/login.php');
        exit;
    }
    $rating  = (int)($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $reviewError = 'Seleziona un voto da 1 a 5.';
    } elseif (!$canReview) {
        $reviewError = 'Puoi recensire solo esperienze che hai prenotato e confermato.';
    } else {
        try {
            $ins = db()->prepare(
                'INSERT INTO reviews (experience_id, user_id, rating, comment) VALUES (?, ?, ?, ?)'
            );
            $ins->execute([$id, $userId, $rating, $comment ?: null]);
            $hasReviewed   = true;
            $canReview     = false;
            $reviewSuccess = 'Grazie per la tua recensione!';
        } catch (PDOException $e) {
            $reviewError = 'Hai già recensito questa esperienza.';
        }
    }
}

// Query recensioni
$rstmt = db()->prepare(
    'SELECT r.rating, r.comment, r.created_at, u.name, u.surname
     FROM reviews r
     JOIN users u ON u.id = r.user_id
     WHERE r.experience_id = ?
     ORDER BY r.created_at DESC'
);
$rstmt->execute([$id]);
$reviewList  = $rstmt->fetchAll();
$reviewCount = count($reviewList);
$avgRating   = $reviewCount > 0
    ? round(array_sum(array_column($reviewList, 'rating')) / $reviewCount, 1)
    : 0;

// Costruisce HTML recensioni
$reviewsHtml = '';
foreach ($reviewList as $r) {
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        $stars .= $i <= $r['rating'] ? '★' : '☆';
    }
    $date = (new DateTimeImmutable($r['created_at']))->format('d/m/Y');
    $reviewsHtml .= '<div style="padding:1.25rem 0;border-bottom:1px solid #f0f0f0">'
        . '<div class="d-flex justify-content-between align-items-center mb-1">'
        . '<strong style="font-size:1.2rem">' . htmlspecialchars($r['name'] . ' ' . $r['surname']) . '</strong>'
        . '<span class="text-muted" style="font-size:1rem">' . $date . '</span>'
        . '</div>'
        . '<div style="color:#f4a62a;font-size:1.5rem;margin-bottom:.4rem">' . $stars . '</div>';
    if ($r['comment']) {
        $reviewsHtml .= '<p class="mb-0" style="font-size:1.1rem">' . nl2br(htmlspecialchars($r['comment'])) . '</p>';
    }
    $reviewsHtml .= '</div>';
}

// Costruisce HTML form recensione
$reviewFormHtml = '';
if ($reviewSuccess) {
    $reviewFormHtml = '<div class="alert alert-success mt-4">' . htmlspecialchars($reviewSuccess) . '</div>';
} elseif (!$userId) {
    $reviewFormHtml = '<p class="text-muted mt-4" style="font-size:1.1rem"><a href="' . $config['base'] . '/login.php">Accedi</a> per lasciare una recensione.</p>';
} elseif ($hasReviewed) {
    $reviewFormHtml = '<p class="text-muted mt-4" style="font-size:1.1rem">Hai già recensito questa esperienza.</p>';
} elseif (!$canReview) {
    $reviewFormHtml = '<p class="text-muted mt-4" style="font-size:1.1rem">Prenota questa esperienza per poter lasciare una recensione.</p>';
} else {
    $errHtml = $reviewError
        ? '<div class="alert alert-danger mb-3">' . htmlspecialchars($reviewError) . '</div>'
        : '';
    $reviewFormHtml = '<div class="mt-4 p-4" style="background:#f8f9fa;border-radius:12px">'
        . '<h5 class="mb-3" style="font-size:1.3rem">Lascia la tua recensione</h5>'
        . $errHtml
        . '<form method="post">'
        . '<div class="mb-3">'
        . '<label class="form-label fw-semibold" style="font-size:1.1rem">Voto</label>'
        . '<div class="d-flex gap-2" id="star-rating">';
    for ($i = 1; $i <= 5; $i++) {
        $reviewFormHtml .= '<label class="star-lbl" data-val="' . $i . '" style="cursor:pointer;font-size:1.8rem;color:#ccc">'
            . '<input type="radio" name="rating" value="' . $i . '" required style="display:none"> ★</label>';
    }
    $reviewFormHtml .= '</div></div>'
        . '<div class="mb-3">'
        . '<label class="form-label fw-semibold" style="font-size:1.1rem">Commento <span class="text-muted fw-normal">(facoltativo)</span></label>'
        . '<textarea name="comment" class="form-control" rows="3" placeholder="Racconta la tua esperienza..." style="font-size:1.1rem"></textarea>'
        . '</div>'
        . '<button type="submit" class="btn btn-primary" style="font-size:1.1rem">Invia recensione</button>'
        . '</form></div>';
}

// Mappa Leaflet: solo se la location ha coordinate valide
$mapHtml = '';
$mapHead = '';
$mapJs   = '';
if ($exp['loc_lat'] !== null && $exp['loc_lng'] !== null) {
    $lat       = (float)$exp['loc_lat'];
    $lng       = (float)$exp['loc_lng'];
    $popupTxt  = addslashes($exp['loc_name'] . ' — ' . $exp['loc_city']);
    $leafletBase = $config['base'] . '/skins/tour/vendor/leaflet';

    $mapHead = '<link rel="stylesheet" href="' . $leafletBase . '/leaflet.css">';
    $mapHtml = '<h2 class="section-title" style="font-size:1.8rem">Dove ci trovi</h2>'
        . '<div id="exp-map" style="height:260px;border-radius:12px;overflow:hidden;border:1px solid #e5e5e5"></div>';
    $mapJs = '<script src="' . $leafletBase . '/leaflet.js"></script>'
        . '<script>'
        . 'document.addEventListener("DOMContentLoaded",function(){'
        . 'var m=L.map("exp-map").setView([' . $lat . ',' . $lng . '],15);'
        . 'L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",'
        . '{attribution:"&copy; OpenStreetMap contributors",maxZoom:19}).addTo(m);'
        . 'L.marker([' . $lat . ',' . $lng . ']).addTo(m).bindPopup("' . $popupTxt . '").openPopup();'
        . '});'
        . '</script>';
}

$skin = new_page($config['skin']);
$skin->setContent('title',     $title);
$skin->setContent('year',      date('Y'));
$skin->setContent('base',      $config['base']);
$skin->setContent('skin',      $config['skin']);
$skin->setContent('is_logged', isset($_SESSION['user']['username']) ? '1' : '');
$skin->setContent('user.name', $_SESSION['user']['name'] ?? '');
$skin->setContent('head',      $mapHead);
$smoothScrollJs = '<script>'
    . 'document.addEventListener("DOMContentLoaded",function(){'
    // smooth scroll "Prenota ora"
    . 'document.querySelectorAll(".js-smooth-scroll").forEach(function(a){'
    . 'a.addEventListener("click",function(e){'
    . 'var t=document.getElementById("slots-section");'
    . 'if(t){e.preventDefault();t.scrollIntoView({behavior:"smooth",block:"start"});}'
    . '});});'
    // stelle interattive
    . 'var sr=document.getElementById("star-rating");'
    . 'if(sr){'
    . 'var lbls=sr.querySelectorAll(".star-lbl");'
    . 'var sel=0;'
    . 'function hl(n){lbls.forEach(function(l,i){l.style.color=i<n?"#f4a62a":"#ccc";});}'
    . 'lbls.forEach(function(l,i){'
    . 'l.addEventListener("mouseenter",function(){hl(i+1);});'
    . 'l.addEventListener("mouseleave",function(){hl(sel);});'
    . 'l.addEventListener("click",function(){sel=i+1;hl(sel);});'
    . '});}'
    . '});'
    . '</script>';
$skin->setContent('javascript', $smoothScrollJs . $mapJs);

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
$block->setContent('slots_html',      $slotsHtml);
$block->setContent('has_slots',       $slotsHtml !== '' ? '1' : '');
$block->setContent('map_html',        $mapHtml);
$block->setContent('has_map',         $mapHtml !== '' ? '1' : '');
$block->setContent('reviews_html',    $reviewsHtml);
$block->setContent('has_reviews',     $reviewsHtml !== '' ? '1' : '');
$block->setContent('review_count',    (string)$reviewCount);
$block->setContent('avg_rating',      $avgRating > 0 ? number_format($avgRating, 1) : '');
$block->setContent('review_form',     $reviewFormHtml);

$skin->setContent('body', $block->get());
$skin->close();
