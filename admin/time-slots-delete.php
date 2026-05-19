<?php

require_once __DIR__ . '/../include/bootstrap.inc.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    $s = db()->prepare('SELECT experience_id FROM time_slots WHERE id = ?');
    $s->execute([$id]);
    $row = $s->fetch();

    if ($row) {
        db()->prepare('DELETE FROM time_slots WHERE id = ?')->execute([$id]);
        header('Location: ' . $config['base'] . '/admin/time-slots.php?exp=' . $row['experience_id']);
        exit;
    }
}

header('Location: ' . $config['base'] . '/admin/time-slots.php');
exit;
