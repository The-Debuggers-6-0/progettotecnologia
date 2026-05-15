<?php

require_once __DIR__ . '/../include/bootstrap.inc.php';
require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $stmt = db()->prepare('DELETE FROM groups WHERE id = ?');
    $stmt->execute([$id]);
}

header('Location: ' . $config['base'] . '/admin/groups.php');
exit;
