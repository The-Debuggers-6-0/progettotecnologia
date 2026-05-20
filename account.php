<?php

require_once __DIR__ . '/include/bootstrap.inc.php';
require_login();

$userId = $_SESSION['user']['id'];

// Dati utente aggiornati dal DB
$user = db()->prepare('SELECT id, username, email, name, surname FROM users WHERE id = ?');
$user->execute([$userId]);
$user = $user->fetch();

$profileSuccess = '';
$profileError   = '';
$passwordSuccess = '';
$passwordError   = '';

// POST: aggiorna profilo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'profile') {
        $name    = trim($_POST['name']    ?? '');
        $surname = trim($_POST['surname'] ?? '');
        $email   = trim($_POST['email']   ?? '');

        if ($name === '' || $surname === '' || $email === '') {
            $profileError = 'Nome, cognome e email sono obbligatori.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $profileError = 'Indirizzo email non valido.';
        } else {
            try {
                $upd = db()->prepare(
                    'UPDATE users SET name = ?, surname = ?, email = ? WHERE id = ?'
                );
                $upd->execute([$name, $surname, $email, $userId]);
                $_SESSION['user']['name'] = $name;
                $profileSuccess = 'Profilo aggiornato con successo.';
                $user['name']    = $name;
                $user['surname'] = $surname;
                $user['email']   = $email;
            } catch (PDOException $e) {
                $profileError = 'Email già in uso da un altro account.';
            }
        }

        // Risposta JSON per richieste AJAX
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => $profileError === '',
                'message' => $profileError ?: $profileSuccess,
            ]);
            exit;
        }
    }

    if ($_POST['action'] === 'password') {
        $current  = $_POST['current_password'] ?? '';
        $new      = $_POST['new_password']     ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        $row = db()->prepare('SELECT password FROM users WHERE id = ?');
        $row->execute([$userId]);
        $hash = $row->fetchColumn();

        if (!password_verify($current, $hash)) {
            $passwordError = 'La password attuale non è corretta.';
        } elseif ($new === $current) {
            $passwordError = 'La nuova password deve essere diversa da quella attuale.';
        } elseif (strlen($new) < 6) {
            $passwordError = 'La nuova password deve essere di almeno 6 caratteri.';
        } elseif ($new !== $confirm) {
            $passwordError = 'Le due password non coincidono.';
        } else {
            $upd = db()->prepare('UPDATE users SET password = ? WHERE id = ?');
            $upd->execute([password_hash($new, PASSWORD_DEFAULT), $userId]);
            $passwordSuccess = 'Password aggiornata con successo.';
        }

        // Risposta JSON per richieste AJAX
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => $passwordError === '',
                'message' => $passwordError ?: $passwordSuccess,
            ]);
            exit;
        }
    }
}

// Storico prenotazioni
$bstmt = db()->prepare(
    'SELECT b.id, b.participants_count, b.total_price, b.status, b.created_at,
            e.id AS exp_id, e.title AS exp_title,
            ts.start_datetime
     FROM bookings b
     JOIN time_slots ts ON ts.id = b.time_slot_id
     JOIN experiences e  ON e.id  = ts.experience_id
     WHERE b.user_id = ?
     ORDER BY b.created_at DESC'
);
$bstmt->execute([$userId]);
$bookings = $bstmt->fetchAll();

// Costruisce HTML storico prenotazioni
$bookingsHtml = '';
foreach ($bookings as $b) {
    $dt       = new DateTimeImmutable($b['start_datetime']);
    $created  = (new DateTimeImmutable($b['created_at']))->format('d/m/Y');
    $statusMap = [
        'confirmed' => ['label' => 'Confermata', 'cls' => 'success'],
        'pending'   => ['label' => 'In attesa',  'cls' => 'warning'],
        'cancelled' => ['label' => 'Cancellata', 'cls' => 'danger'],
    ];
    $st = $statusMap[$b['status']] ?? ['label' => $b['status'], 'cls' => 'secondary'];

    $bookingsHtml .= '<div style="padding:1.2rem 0;border-bottom:1px solid #f0f0f0">'
        . '<div class="d-flex justify-content-between align-items-start flex-wrap gap-2">'
        . '<div>'
        . '<a href="' . $config['base'] . '/tour-detail.php?id=' . $b['exp_id'] . '"'
        . ' class="fw-semibold" style="font-size:1.15rem;color:#1A374D;text-decoration:none">'
        . htmlspecialchars($b['exp_title']) . '</a>'
        . '<div class="text-muted mt-1" style="font-size:1rem">'
        . '<i class="flaticon-calendar" style="font-size:.9rem"></i> '
        . $dt->format('d/m/Y') . ' &mdash; ' . $dt->format('H:i')
        . ' &nbsp;|&nbsp; ' . $b['participants_count'] . ' partecipant' . ($b['participants_count'] == 1 ? 'e' : 'i')
        . '</div>'
        . '<div class="text-muted" style="font-size:.95rem">Prenotato il ' . $created . '</div>'
        . '</div>'
        . '<div class="text-end">'
        . '<span class="badge bg-' . $st['cls'] . '" style="font-size:.9rem;padding:.45em .85em">'
        . $st['label'] . '</span>'
        . '<div class="fw-semibold mt-1" style="font-size:1.1rem">&euro;'
        . number_format($b['total_price'], 2, ',', '.') . '</div>'
        . '</div>'
        . '</div>'
        . '</div>';
}

$skin = new_page($config['skin']);
$skin->setContent('title',     'Il mio account');
$skin->setContent('year',      date('Y'));
$skin->setContent('base',      $config['base']);
$skin->setContent('skin',      $config['skin']);
$skin->setContent('is_logged', '1');
$skin->setContent('user.name', $_SESSION['user']['name'] ?? '');
$skin->setContent('head',      '');
$accountJs = '<script>'
    . 'function ajaxForm(id,resetOnSuccess){'
    . 'var form=document.getElementById(id);'
    . 'if(!form)return;'
    . 'form.addEventListener("submit",function(e){'
    . 'e.preventDefault();'
    . 'fetch(window.location.href,{'
    . 'method:"POST",'
    . 'headers:{"X-Requested-With":"XMLHttpRequest"},'
    . 'body:new FormData(form)'
    . '})'
    . '.then(function(r){return r.json();})'
    . '.then(function(data){'
    . 'showToast(data.success,data.message);'
    . 'if(data.success&&resetOnSuccess){form.reset();}'
    . '});'
    . '});'
    . '}'
    . 'document.addEventListener("DOMContentLoaded",function(){'
    . 'ajaxForm("profile-form",false);'
    . 'ajaxForm("pw-form",true);'
    . '});'
    . 'function showToast(ok,msg){'
    . 'var t=document.createElement("div");'
    . 't.textContent=msg;'
    . 't.style.cssText="position:fixed;top:1.5rem;left:50%;transform:translateX(-50%);z-index:9999;'
    . 'padding:.9rem 1.8rem;border-radius:10px;color:#fff;font-size:1.05rem;white-space:nowrap;'
    . 'box-shadow:0 4px 16px rgba(0,0,0,.18);opacity:1;transition:opacity .4s;'
    . 'background:"+(ok?"#28a745":"#dc3545")+";";'
    . 'document.body.appendChild(t);'
    . 'setTimeout(function(){t.style.opacity="0";setTimeout(function(){t.remove();},400);},3200);'
    . '}'
    . '</script>';
$skin->setContent('javascript', $accountJs);

$block = new_block('account');
$block->setContent('username',        htmlspecialchars($user['username']));
$block->setContent('user_name',       htmlspecialchars($user['name'] ?? ''));
$block->setContent('user_surname',    htmlspecialchars($user['surname'] ?? ''));
$block->setContent('user_email',      htmlspecialchars($user['email']));
$block->setContent('profile_success', $profileSuccess);
$block->setContent('profile_error',   $profileError);
$block->setContent('password_success', $passwordSuccess);
$block->setContent('password_error',   $passwordError);
$block->setContent('has_bookings',    $bookingsHtml !== '' ? '1' : '');
$block->setContent('bookings_html',   $bookingsHtml);
$block->setContent('tours_url',       $config['base'] . '/tours.php');

$skin->setContent('body', $block->get());
$skin->close();
