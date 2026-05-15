<?php

/*
 * Configurazione globale dell'applicazione.
 * Contiene tutte le impostazioni che possono essere modificate per adattare l'applicazione al proprio ambiente.
 * 
 * Le costanti NONE / FILE / MEMORY servono al template engine
 * (template2.inc.php) per la modalita' di cache.
 */

define('NONE',   0);
define('FILE',   1);
define('MEMORY', 2);

$config = [

    /* --- Database (XAMPP default) -------------------------------- */
    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'progettotecnologia',
        'user'    => 'root',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],

    /* --- Skin e paths -------------------------------------------- */
    'skin'         => 'tour',          // frontend skin
    'admin_skin'   => 'admin',         // backend skin
    'base'         => '/progettotecnologia', // base URL relativa alla root di XAMPP
    'upload_dir'   => 'uploads',

    /* --- Cache del template engine ------------------------------- */
    'cache_folder'  => 'cache',
    'cache_mode'    => NONE,           // NONE durante lo sviluppo
    'cache_timeout' => 600,

    /* --- Lingua (no multilingua per ora) ------------------------- */
    'languages'        => [],
    'currentlanguage'  => 'it',
    'currenttab'       => '',

    /* --- App ----------------------------------------------------- */
    'app_name' => 'Esperienze & Tour',
];
