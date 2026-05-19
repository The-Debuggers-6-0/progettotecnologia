<?php

require_once __DIR__ . '/include/bootstrap.inc.php';
require_login();

$id = (int)($_GET['id'] ?? 0);

$stmt = db()->prepare(
    'SELECT b.id, b.participants_count, b.total_price, b.status, b.notes, b.created_at,
            e.id AS exp_id, e.title AS exp_title,
            ts.start_datetime
     FROM bookings b
     JOIN time_slots ts ON ts.id = b.time_slot_id
     JOIN experiences e ON e.id = ts.experience_id
     WHERE b.id = ? AND b.user_id = ?'
);
$stmt->execute([$id, $_SESSION['user']['id']]);
$booking = $stmt->fetch();

if (!$booking) {
    header('Location: ' . $config['base'] . '/tours.php');
    exit;
}

$parts = db()->prepare(
    'SELECT name, surname FROM booking_participants WHERE booking_id = ? ORDER BY id'
);
$parts->execute([$id]);
$participants = $parts->fetchAll();

$participantsHtml = '';
foreach ($participants as $i => $p) {
    $participantsHtml .= '<li class="list-group-item">'
        . '<span class="text-muted me-2" style="font-size:.85rem">' . ($i + 1) . '.</span>'
        . htmlspecialchars($p['name'] . ' ' . $p['surname'])
        . '</li>';
}

$dt = new DateTimeImmutable($booking['start_datetime']);

$skin = new_page($config['skin']);
$skin->setContent('title',      'Prenotazione confermata');
$skin->setContent('year',       date('Y'));
$skin->setContent('base',       $config['base']);
$skin->setContent('skin',       $config['skin']);
$skin->setContent('is_logged',  isset($_SESSION['user']['username']) ? '1' : '');
$skin->setContent('user.name',  $_SESSION['user']['name'] ?? '');
$skin->setContent('head',       '');
$skin->setContent('javascript', '');

$block = new_block('booking-success');
$block->setContent('booking_id',         $booking['id']);
$block->setContent('exp_title',          htmlspecialchars($booking['exp_title']));
$block->setContent('exp_detail_url',     $config['base'] . '/tour-detail.php?id=' . $booking['exp_id']);
$block->setContent('slot_date',          $dt->format('d/m/Y'));
$block->setContent('slot_time',          $dt->format('H:i'));
$block->setContent('participants_count', $booking['participants_count']);
$block->setContent('total_price',        number_format($booking['total_price'], 2, ',', '.'));
$block->setContent('participants_html',  $participantsHtml);
$block->setContent('tours_url',          $config['base'] . '/tours.php');

$skin->setContent('body', $block->get());
$skin->close();
