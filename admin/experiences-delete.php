<?php

require_once __DIR__ . '/../include/bootstrap.inc.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    // Elimina prima le foto dal filesystem
    $photos = db()->prepare('SELECT filename FROM experience_photos WHERE experience_id = ?');
    $photos->execute([$id]);
    foreach ($photos->fetchAll() as $p) {
        $path = __DIR__ . '/../uploads/experiences/' . $p['filename'];
        if (file_exists($path)) unlink($path);
    }

    db()->prepare('DELETE FROM experiences WHERE id = ?')->execute([$id]);
}

header('Location: ' . $config['base'] . '/admin/experiences.php');
exit;
