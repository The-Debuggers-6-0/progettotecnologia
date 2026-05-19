<?php

require_once __DIR__ . '/../include/bootstrap.inc.php';
require_admin();

$id    = isset($_GET['id'])  ? (int)$_GET['id']  : 0;
$expId = isset($_GET['exp']) ? (int)$_GET['exp'] : 0;

// Slot corrente (modifica)
$slot = null;
if ($id > 0) {
    $s = db()->prepare('SELECT * FROM time_slots WHERE id = ?');
    $s->execute([$id]);
    $slot = $s->fetch();
    if (!$slot) {
        header('Location: ' . $config['base'] . '/admin/time-slots.php');
        exit;
    }
    $expId = (int)$slot['experience_id'];
}

// Lista esperienze per il dropdown
$experiences = db()->query('SELECT id, title FROM experiences ORDER BY title')->fetchAll();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $experienceId = (int)($_POST['experience_id'] ?? 0);
    $startDate    = trim($_POST['start_date'] ?? '');
    $startTime    = trim($_POST['start_time'] ?? '');
    $capacity     = (int)($_POST['capacity']     ?? 0);
    $notes        = trim($_POST['notes']         ?? '');
    $isActive     = isset($_POST['is_active']) ? 1 : 0;

    if ($experienceId <= 0 || !$startDate || !$startTime || $capacity < 1) {
        $error = 'Esperienza, data, ora e capienza sono obbligatori.';
    } else {
        $startDatetime = $startDate . ' ' . $startTime . ':00';

        if ($id > 0) {
            $stmt = db()->prepare(
                'UPDATE time_slots
                 SET experience_id=?, start_datetime=?, capacity=?, notes=?, is_active=?
                 WHERE id=?'
            );
            $stmt->execute([$experienceId, $startDatetime, $capacity, $notes ?: null, $isActive, $id]);
        } else {
            $stmt = db()->prepare(
                'INSERT INTO time_slots (experience_id, start_datetime, capacity, notes, is_active)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$experienceId, $startDatetime, $capacity, $notes ?: null, $isActive]);
        }

        $redirect = $config['base'] . '/admin/time-slots.php?exp=' . $experienceId;
        header('Location: ' . $redirect);
        exit;
    }
}

// Valori da mostrare nel form
$fExpId    = $_POST['experience_id'] ?? ($slot['experience_id'] ?? $expId);
$fDate     = $_POST['start_date']    ?? ($slot ? (new DateTimeImmutable($slot['start_datetime']))->format('Y-m-d') : '');
$fTime     = $_POST['start_time']    ?? ($slot ? (new DateTimeImmutable($slot['start_datetime']))->format('H:i') : '');
$fCapacity = $_POST['capacity']      ?? ($slot['capacity'] ?? 10);
$fNotes    = $_POST['notes']         ?? ($slot['notes']    ?? '');
$fActive   = isset($_POST['is_active'])
    ? (int)$_POST['is_active']
    : ($slot ? (int)$slot['is_active'] : 1);

// Costruisce opzioni dropdown esperienze
$expOptions = '';
foreach ($experiences as $e) {
    $sel        = ($e['id'] == $fExpId) ? ' selected' : '';
    $expOptions .= '<option value="' . $e['id'] . '"' . $sel . '>'
        . htmlspecialchars($e['title']) . '</option>';
}

$backUrl = $config['base'] . '/admin/time-slots.php'
    . ($expId > 0 ? '?exp=' . $expId : '');

$skin = new_page($config['admin_skin'], 'frame-private');
$skin->setContent('title', $id > 0 ? 'Modifica slot' : 'Nuovo slot');
$skin->setContent('base',  $config['base']);
$skin->setContent('skin',  $config['admin_skin']);
$skin->setContent('user.username', $_SESSION['user']['username'] ?? '');

$block = new_block('time-slots-form');
$block->setContent('form_title',          $id > 0 ? 'Modifica slot' : 'Nuovo slot');
$block->setContent('back_url',            $backUrl);
$block->setContent('error',               htmlspecialchars($error));
$block->setContent('exp_options',         $expOptions);
$block->setContent('slot_date',           htmlspecialchars($fDate));
$block->setContent('slot_time',           htmlspecialchars($fTime));
$block->setContent('slot_capacity',       (int)$fCapacity);
$block->setContent('slot_notes',          htmlspecialchars($fNotes));
$block->setContent('slot_active_checked', $fActive ? 'checked' : '');

$skin->setContent('body', $block->get());
$skin->close();
