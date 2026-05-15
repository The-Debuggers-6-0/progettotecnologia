<?php

require_once __DIR__ . '/../include/bootstrap.inc.php';
require_admin();

$username = $_GET['username'] ?? '';

if ($username !== '') {
    $stmt = db()->prepare('DELETE FROM services WHERE username = ?');
    $stmt->execute([$username]);
}

header('Location: ' . $config['base'] . '/admin/services.php');
exit;
