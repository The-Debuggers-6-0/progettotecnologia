<?php

require_once __DIR__ . '/../include/bootstrap.inc.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    $stmt = db()->prepare('DELETE FROM categories WHERE id = ?');
    $stmt->execute([$id]);
}

header('Location: ' . $config['base'] . '/admin/categories.php');
exit;
