<?php

require_once __DIR__ . '/../include/bootstrap.inc.php';
require_admin();

$expId = isset($_GET['exp']) ? (int)$_GET['exp'] : 0;

if ($expId > 0) {
    $expRow = db()->prepare('SELECT id, title FROM experiences WHERE id = ?');
    $expRow->execute([$expId]);
    $expRow = $expRow->fetch();
    if (!$expRow) $expId = 0;
}

if ($expId > 0) {
    $stmt = db()->prepare(
        'SELECT ts.*, e.title AS exp_title
         FROM time_slots ts
         JOIN experiences e ON e.id = ts.experience_id
         WHERE ts.experience_id = ?
         ORDER BY ts.start_datetime ASC'
    );
    $stmt->execute([$expId]);
} else {
    $stmt = db()->query(
        'SELECT ts.*, e.title AS exp_title
         FROM time_slots ts
         JOIN experiences e ON e.id = ts.experience_id
         ORDER BY ts.start_datetime ASC'
    );
}
$rows = $stmt->fetchAll();

$skin = new_page($config['admin_skin'], 'frame-private');
$skin->setContent('title', 'Slot / Calendario');
$skin->setContent('base',  $config['base']);
$skin->setContent('skin',  $config['admin_skin']);
$skin->setContent('user.username', $_SESSION['user']['username'] ?? '');

$newUrl = $config['base'] . '/admin/time-slots-form.php'
    . ($expId > 0 ? '?exp=' . $expId : '');

$block = new_block('time-slots-list');
$block->setContent('page_title',      $expId > 0 ? 'Slot: ' . htmlspecialchars($expRow['title']) : 'Slot / Calendario');
$block->setContent('new_url',         $newUrl);
$block->setContent('base',            $config['base']);
$block->setContent('has_rows',        count($rows) ? '1' : '');
$block->setContent('exp_filter_name', $expId > 0 ? htmlspecialchars($expRow['title']) : '');

foreach ($rows as $r) {
    $dt        = new DateTimeImmutable($r['start_datetime']);
    $available = $r['capacity'] - $r['booked_count'];

    $block->setContent('slot_exp_title',   htmlspecialchars($r['exp_title']));
    $block->setContent('slot_date',        $dt->format('d/m/Y'));
    $block->setContent('slot_time',        $dt->format('H:i'));
    $block->setContent('slot_capacity',    $r['capacity']);
    $block->setContent('slot_booked',      $r['booked_count']);
    $block->setContent('slot_available',   $available);
    $block->setContent('slot_avail_class', $available > 0 ? 'success' : 'danger');
    $block->setContent('slot_status',      $r['is_active'] ? 'Attivo' : 'Disattivo');
    $block->setContent('slot_status_class',$r['is_active'] ? 'primary' : 'secondary');
    $block->setContent('slot_edit_url',    $config['base'] . '/admin/time-slots-form.php?id=' . $r['id']);
    $block->setContent('slot_delete_url',  $config['base'] . '/admin/time-slots-delete.php?id=' . $r['id']);
}

$skin->setContent('body', $block->get());
$skin->close();
