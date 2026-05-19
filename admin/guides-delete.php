<?php

require_once __DIR__ . '/../include/bootstrap.inc.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    $stmt = db()->prepare('SELECT photo_filename FROM guides WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if ($row && $row['photo_filename']) {
        $file = __DIR__ . '/../uploads/guides/' . $row['photo_filename'];
        if (file_exists($file)) unlink($file);
    }

    db()->prepare('DELETE FROM guides WHERE id = ?')->execute([$id]);
}

header('Location: ' . $config['base'] . '/admin/guides.php');
exit;
