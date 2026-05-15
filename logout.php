<?php

require_once __DIR__ . '/include/bootstrap.inc.php';

$_SESSION = [];
session_destroy();

header('Location: ' . $config['base'] . '/index.php');
exit;
