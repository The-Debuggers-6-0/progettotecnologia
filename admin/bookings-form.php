<?php

require_once __DIR__ . '/../include/bootstrap.inc.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);

$stmt = db()->prepare(
    'SELECT b.*, e.title AS exp_title, e.price AS unit_price,
            ts.start_datetime,
            u.name AS user_name, u.surname AS user_surname, u.email AS user_email
     FROM bookings b
     JOIN time_slots ts ON ts.id = b.time_slot_id
     JOIN experiences e ON e.id  = ts.experience_id
     JOIN users u       ON u.id  = b.user_id
     WHERE b.id = ?'
);
$stmt->execute([$id]);
$booking = $stmt->fetch();

if (!$booking) {
    header('Location: ' . $config['base'] . '/admin/bookings.php');
    exit;
}

$parts = db()->prepare(
    'SELECT name, surname FROM booking_participants WHERE booking_id = ? ORDER BY id'
);
$parts->execute([$id]);
$participants = $parts->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newStatus = $_POST['status'] ?? 'pending';
    if (!in_array($newStatus, ['pending', 'confirmed', 'cancelled'])) {
        $newStatus = 'pending';
    }

    // Se si cancella: libera i posti
    if ($newStatus === 'cancelled' && $booking['status'] !== 'cancelled') {
        db()->prepare(
            'UPDATE time_slots SET booked_count = GREATEST(0, booked_count - ?) WHERE id = ?'
        )->execute([$booking['participants_count'], $booking['time_slot_id']]);
    }
    // Se si riattiva da cancelled: re-occupa i posti
    if ($booking['status'] === 'cancelled' && $newStatus !== 'cancelled') {
        db()->prepare(
            'UPDATE time_slots SET booked_count = booked_count + ? WHERE id = ?'
        )->execute([$booking['participants_count'], $booking['time_slot_id']]);
    }

    db()->prepare('UPDATE bookings SET status = ? WHERE id = ?')->execute([$newStatus, $id]);
    header('Location: ' . $config['base'] . '/admin/bookings.php');
    exit;
}

$dt = new DateTimeImmutable($booking['start_datetime']);

$statusOptions = '';
foreach (['pending' => 'In attesa', 'confirmed' => 'Confermata', 'cancelled' => 'Cancellata'] as $val => $label) {
    $sel = ($booking['status'] === $val) ? ' selected' : '';
    $statusOptions .= '<option value="' . $val . '"' . $sel . '>' . $label . '</option>';
}

$participantsHtml = '';
foreach ($participants as $i => $p) {
    $participantsHtml .= '<li class="list-group-item py-2">'
        . '<span class="text-muted me-2">' . ($i + 1) . '.</span>'
        . htmlspecialchars($p['name'] . ' ' . $p['surname'])
        . '</li>';
}

$skin = new_page($config['admin_skin'], 'frame-private');
$skin->setContent('title', 'Prenotazione #' . $id);
$skin->setContent('base',  $config['base']);
$skin->setContent('skin',  $config['admin_skin']);
$skin->setContent('user.username', $_SESSION['user']['username'] ?? '');

$block = new_block('bookings-form');
$block->setContent('booking_id',       $id);
$block->setContent('back_url',         $config['base'] . '/admin/bookings.php');
$block->setContent('exp_title',        htmlspecialchars($booking['exp_title']));
$block->setContent('slot_datetime',    $dt->format('d/m/Y') . ' alle ' . $dt->format('H:i'));
$block->setContent('user_name',        htmlspecialchars($booking['user_name'] . ' ' . $booking['user_surname']));
$block->setContent('user_email',       htmlspecialchars($booking['user_email']));
$block->setContent('participants_count',$booking['participants_count']);
$block->setContent('total_price',      number_format($booking['total_price'], 2, ',', '.'));
$block->setContent('booking_notes',    htmlspecialchars($booking['notes'] ?? ''));
$block->setContent('status_options',   $statusOptions);
$block->setContent('participants_html',$participantsHtml);
$block->setContent('has_notes',        $booking['notes'] ? '1' : '');

$skin->setContent('body', $block->get());
$skin->close();
