<?php

require_once __DIR__ . '/../include/bootstrap.inc.php';
require_admin();

$rows = db()->query(
    'SELECT b.id, b.participants_count, b.total_price, b.status, b.created_at,
            u.name AS user_name, u.surname AS user_surname, u.email AS user_email,
            e.title AS exp_title,
            ts.start_datetime
     FROM bookings b
     JOIN users u       ON u.id  = b.user_id
     JOIN time_slots ts ON ts.id = b.time_slot_id
     JOIN experiences e ON e.id  = ts.experience_id
     ORDER BY b.created_at DESC'
)->fetchAll();

$skin = new_page($config['admin_skin'], 'frame-private');
$skin->setContent('title', 'Prenotazioni');
$skin->setContent('base',  $config['base']);
$skin->setContent('skin',  $config['admin_skin']);
$skin->setContent('user.username', $_SESSION['user']['username'] ?? '');

$block = new_block('bookings-list');
$block->setContent('has_rows', count($rows) ? '1' : '');

foreach ($rows as $r) {
    $dt = new DateTimeImmutable($r['start_datetime']);

    $statusClass = match($r['status']) {
        'confirmed'  => 'success',
        'cancelled'  => 'danger',
        default      => 'warning',
    };
    $statusLabel = match($r['status']) {
        'confirmed'  => 'Confermata',
        'cancelled'  => 'Cancellata',
        default      => 'In attesa',
    };

    $block->setContent('book_id',         $r['id']);
    $block->setContent('book_exp',        htmlspecialchars($r['exp_title']));
    $block->setContent('book_slot',       $dt->format('d/m/Y') . ' ' . $dt->format('H:i'));
    $block->setContent('book_user',       htmlspecialchars($r['user_name'] . ' ' . $r['user_surname']));
    $block->setContent('book_email',      htmlspecialchars($r['user_email']));
    $block->setContent('book_parts',      $r['participants_count']);
    $block->setContent('book_price',      number_format($r['total_price'], 2, ',', '.'));
    $block->setContent('book_status',     $statusLabel);
    $block->setContent('book_status_cls', $statusClass);
    $block->setContent('book_edit_url',   $config['base'] . '/admin/bookings-form.php?id=' . $r['id']);
    $block->setContent('book_delete_url', $config['base'] . '/admin/bookings-delete.php?id=' . $r['id']);
}

$skin->setContent('body', $block->get());
$skin->close();
