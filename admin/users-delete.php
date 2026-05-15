<?php

require_once __DIR__ . '/../include/bootstrap.inc.php';
require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    // Impedisce all'admin di eliminare se stesso
    if ($id !== (int)$_SESSION['user']['id']) {
        $stmt = db()->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$id]);
    }
}

header('Location: ' . $config['base'] . '/admin/users.php');
exit;
