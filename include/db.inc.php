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
        //Gestione errori tramite eccezioni: se c'è un errore nella query, viene lanciata un'eccezione invece di restituire false.
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        //I dati tornano con il nome della colonna come chiave invece di un numero — molto più leggibile.
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // PHP manda la query e i dati separati a MySQL,
        // invece di sostituire i dati nella query come stringa: più sicuro contro SQL injection e più efficiente.
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    return $pdo;
}

$db = db();
