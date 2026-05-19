<?php

require_once __DIR__ . '/../include/bootstrap.inc.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    // ON DELETE SET NULL su experiences.location_id — nessun file da rimuovere
    $stmt = db()->prepare('DELETE FROM locations WHERE id = ?');
    $stmt->execute([$id]);
}

header('Location: ' . $config['base'] . '/admin/locations.php');
exit;
