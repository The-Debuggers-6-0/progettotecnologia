<?php

require_once __DIR__ . '/../include/bootstrap.inc.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    $stmt = db()->prepare(
        'SELECT time_slot_id, participants_count, status FROM bookings WHERE id = ?'
    );
    $stmt->execute([$id]);
    $booking = $stmt->fetch();

    if ($booking) {
        // Libera i posti solo se la prenotazione non era già cancellata
        if ($booking['status'] !== 'cancelled') {
            db()->prepare(
                'UPDATE time_slots SET booked_count = GREATEST(0, booked_count - ?) WHERE id = ?'
            )->execute([$booking['participants_count'], $booking['time_slot_id']]);
        }
        db()->prepare('DELETE FROM bookings WHERE id = ?')->execute([$id]);
    }
}

header('Location: ' . $config['base'] . '/admin/bookings.php');
exit;
