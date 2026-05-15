<?php

/*
 * Helper per creare pagine usando il template engine del docente
 * SENZA passare per le classi Skin/Skinlet (che chiamano Template con
 * un path gia' contenente .html, mentre Template aggiunge un altro .html
 * causando il "file non trovato").
 *
 * Usiamo direttamente la classe Template fornita dal docente,
 * passando il path SENZA estensione: il Template aggiunge .html da solo.
 */


/**
 * Crea il "frame" della pagina (header + footer + buco <[body]>).
 * Equivalente a `new Skin($skinName)`, ma senza il bug.
 */
function new_page(string $skinName, string $frame = ''): Template {

    $GLOBALS['current_skin']     = $skinName;
    $GLOBALS['config']['skin']   = $skinName;

    // Se l'app prevede auth (esiste la classe Auth) usiamo il frame privato,
    // altrimenti quello pubblico. Stessa logica della classe Skin del docente.
    if ($frame === '') {
        $frame = class_exists('Auth') ? 'frame-private' : 'frame-public';
    }

    // Percorso assoluto: funziona sia dalla root che da sottocartelle (es. admin/).
    $root = __DIR__ . '/..';
    return new Template("{$root}/skins/{$skinName}/dtml/{$frame}");
}


/**
 * Crea un "blocco" di contenuto da inserire dentro un placeholder del frame.
 * Equivalente a `new Skinlet($name)`, ma senza il bug.
 */
function new_block(string $template): Template {

    $skinName = $GLOBALS['current_skin'] ?? $GLOBALS['config']['skin'];
    $root = __DIR__ . '/..';
    return new Template("{$root}/skins/{$skinName}/dtml/{$template}");
}
