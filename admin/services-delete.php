<?php

require_once __DIR__ . '/../include/bootstrap.inc.php';
require_admin();

$username = $_GET['username'] ?? '';

if ($username !== '') {
    // Rimuove prima i collegamenti ai gruppi (la FK su services non è in CASCADE)
    db()->prepare('DELETE FROM services_has_groups WHERE services_username = ?')
        ->execute([$username]);

    $stmt = db()->prepare('DELETE FROM services WHERE username = ?');
    $stmt->execute([$username]);
}

header('Location: ' . $config['base'] . '/admin/services.php');
exit;
