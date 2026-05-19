<?php

require_once __DIR__ . '/include/bootstrap.inc.php';
require_login();

$slotId = (int)($_GET['slot'] ?? $_SESSION['cart']['slot_id'] ?? 0);

if (!$slotId) {
    header('Location: ' . $config['base'] . '/tours.php');
    exit;
}

// Slot + esperienza
$stmt = db()->prepare(
    'SELECT ts.id AS slot_id, ts.start_datetime, ts.capacity, ts.booked_count, ts.notes AS slot_notes,
            (ts.capacity - ts.booked_count) AS available,
            e.id AS exp_id, e.title, e.price, e.max_participants
     FROM time_slots ts
     JOIN experiences e ON e.id = ts.experience_id
     WHERE ts.id = ? AND ts.is_active = 1 AND ts.start_datetime >= NOW()'
);
$stmt->execute([$slotId]);
$slot = $stmt->fetch();

if (!$slot || $slot['available'] <= 0) {
    header('Location: ' . $config['base'] . '/tours.php');
    exit;
}

// Salva nel carrello di sessione
$_SESSION['cart'] = ['slot_id' => $slotId, 'exp_id' => $slot['exp_id']];

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $count     = (int)($_POST['participants_count'] ?? 1);
    $notes     = trim($_POST['notes'] ?? '');
    $pnames    = $_POST['pname']    ?? [];
    $psurnames = $_POST['psurname'] ?? [];

    if ($count < 1 || $count > $slot['available']) {
        $error = 'Numero di partecipanti non valido.';
    } else {
        $valid = true;
        for ($i = 0; $i < $count; $i++) {
            if (empty(trim($pnames[$i] ?? '')) || empty(trim($psurnames[$i] ?? ''))) {
                $valid = false;
                break;
            }
        }
        if (!$valid) {
            $error = 'Inserisci nome e cognome per tutti i partecipanti.';
        } else {
            $totalPrice = $slot['price'] * $count;
            try {
                db()->beginTransaction();

                // Ricontrollo disponibilità dentro la transazione
                $chk = db()->prepare(
                    'SELECT capacity - booked_count AS avail FROM time_slots WHERE id = ? FOR UPDATE'
                );
                $chk->execute([$slotId]);
                $avail = (int)$chk->fetchColumn();

                if ($avail < $count) {
                    db()->rollBack();
                    $error = 'Spiacenti, i posti disponibili sono cambiati. Rimangono solo ' . $avail . '.';
                } else {
                    $ins = db()->prepare(
                        'INSERT INTO bookings (user_id, time_slot_id, participants_count, total_price, status, notes)
                         VALUES (?, ?, ?, ?, \'confirmed\', ?)'
                    );
                    $ins->execute([
                        $_SESSION['user']['id'], $slotId, $count,
                        $totalPrice, $notes ?: null
                    ]);
                    $bookingId = (int)db()->lastInsertId();

                    $insPart = db()->prepare(
                        'INSERT INTO booking_participants (booking_id, name, surname) VALUES (?, ?, ?)'
                    );
                    for ($i = 0; $i < $count; $i++) {
                        $insPart->execute([
                            $bookingId,
                            trim($pnames[$i]),
                            trim($psurnames[$i])
                        ]);
                    }

                    db()->prepare(
                        'UPDATE time_slots SET booked_count = booked_count + ? WHERE id = ?'
                    )->execute([$count, $slotId]);

                    db()->commit();
                    unset($_SESSION['cart']);

                    header('Location: ' . $config['base'] . '/booking-success.php?id=' . $bookingId);
                    exit;
                }
            } catch (Exception $e) {
                db()->rollBack();
                $error = 'Errore durante la prenotazione. Riprova.';
            }
        }
    }
}

$maxPart = min($slot['available'], 10);
$selectedCount = min(max(1, (int)($_POST['participants_count'] ?? 1)), $maxPart);

$countOptions = '';
for ($i = 1; $i <= $maxPart; $i++) {
    $sel = ($i === $selectedCount) ? ' selected' : '';
    $countOptions .= '<option value="' . $i . '"' . $sel . '>' . $i . '</option>';
}

$dt = new DateTimeImmutable($slot['start_datetime']);

// JS per campi partecipanti dinamici e totale aggiornato
$unitPriceJs = number_format($slot['price'], 2, '.', '');
$bookingJs = '<script>'
    . '(function(){'
    . 'var up=' . $unitPriceJs . ';'
    . 'function upd(){'
    .   'var n=parseInt(document.getElementById("part-count").value,10);'
    .   'var c=document.getElementById("part-box");'
    .   'var ex=c.querySelectorAll(".prow").length;'
    .   'while(ex<n){'
    .     'var d=document.createElement("div");'
    .     'd.className="prow mb-3 p-3 rounded";'
    .     'd.style.background="#f8f9fa";'
    .     'd.innerHTML="<h6 class=\"mb-3\">Partecipante "+(ex+1)+"</h6>'
    .       '<div class=\"row\">'
    .       '<div class=\"col-md-6 mb-2\"><label class=\"form-label small\">Nome *<\/label>'
    .         '<input type=\"text\" name=\"pname["+ex+"]\" class=\"form-control\" required><\/div>'
    .       '<div class=\"col-md-6 mb-2\"><label class=\"form-label small\">Cognome *<\/label>'
    .         '<input type=\"text\" name=\"psurname["+ex+"]\" class=\"form-control\" required><\/div>'
    .       '<\/div>";'
    .     'c.appendChild(d);ex++;'
    .   '}'
    .   'while(ex>n){c.removeChild(c.lastChild);ex--;}'
    .   'document.getElementById("part-disp").textContent=n;'
    .   'var t=(up*n).toFixed(2).replace(".",",");'
    .   'document.getElementById("tot-price").textContent=t;'
    . '}'
    . 'document.getElementById("part-count").addEventListener("change",upd);'
    . 'upd();'
    . '})();'
    . '</script>';

$skin = new_page($config['skin']);
$skin->setContent('title',      'Prenota: ' . htmlspecialchars($slot['title']));
$skin->setContent('year',       date('Y'));
$skin->setContent('base',       $config['base']);
$skin->setContent('skin',       $config['skin']);
$skin->setContent('is_logged',  isset($_SESSION['user']['username']) ? '1' : '');
$skin->setContent('user.name',  $_SESSION['user']['name'] ?? '');
$skin->setContent('head',       '');
$skin->setContent('javascript', $bookingJs);

$block = new_block('booking');
$block->setContent('error',         htmlspecialchars($error));
$block->setContent('exp_title',     htmlspecialchars($slot['title']));
$block->setContent('exp_detail_url',$config['base'] . '/tour-detail.php?id=' . $slot['exp_id']);
$block->setContent('slot_date',     $dt->format('d/m/Y'));
$block->setContent('slot_time',     $dt->format('H:i'));
$block->setContent('slot_available',$slot['available']);
$block->setContent('slot_notes',    htmlspecialchars($slot['slot_notes'] ?? ''));
$block->setContent('exp_price',     number_format($slot['price'], 2, ',', '.'));
$block->setContent('count_options', $countOptions);
$block->setContent('notes_val',     htmlspecialchars($_POST['notes'] ?? ''));
$block->setContent('has_slot_notes',$slot['slot_notes'] ? '1' : '');

$skin->setContent('body', $block->get());
$skin->close();
