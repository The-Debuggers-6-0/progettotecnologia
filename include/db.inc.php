<?php

/*
 * Connessione al database via PDO (PHP Data Objects, una libreria PHP per interagire con i database).
 * PATTERN SINGLETON:Espone la variabile globale $db (istanza PDO) e la funzione db().
 * La connessione al database viene creata solo una volta, quando la funzione db() viene chiamata per la prima volta.
 * Le chiamate successive a db() restituiscono la stessa istanza di PDO, evitando di creare connessioni multiple al database.
 */

function db(): PDO {

    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    global $config;
    $c = $config['db'];

    $dsn = "mysql:host={$c['host']};port={$c['port']};dbname={$c['name']};charset={$c['charset']}";

    $pdo = new PDO($dsn, $c['user'], $c['pass'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    return $pdo;
}

$db = db();
