<?php

/*
 * Homepage pubblica.
 * Mostra un hero + una lista (per ora vuota) delle esperienze in evidenza.
 * Le esperienze vere arriveranno con la Slice 2.
 */

require_once __DIR__ . '/include/bootstrap.inc.php';

# Inizializza il template engine e prepara la pagina.
# new_page() e new_block() sono i nostri helper definiti in include/page.inc.php:
# new_page() carica un template completo (con header, footer, ecc.)
# new_block() carica un template "a pezzi" (es. solo il body) da inserire in un template completo.
$skin = new_page($config['skin']);
$skin->setContent('title',     'Home');
$skin->setContent('year',      date('Y'));
$skin->setContent('base',      $config['base']);
$skin->setContent('skin',      $config['skin']);
// is_logged vale "1" se c'e' un utente in sessione, stringa vuota altrimenti.
// Lo usiamo nel frame-public.html con <[if!empty is_logged]> / <[ifempty is_logged]>.
$skin->setContent('is_logged', isset($_SESSION['user']['username']) ? '1' : '');
$skin->setContent('user.name',  $_SESSION['user']['name'] ?? '');

$home = new_block('home');

/* Slice 2 popolera' qui le esperienze in evidenza.
 * Per ora il foreach resta vuoto: serve solo a verificare che
 * il template engine giri correttamente.
 */
$home->setContent('has_experiences', '');

$skin->setContent('body', $home->get());
$skin->close();
