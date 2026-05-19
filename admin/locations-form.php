<?php

require_once __DIR__ . '/../include/bootstrap.inc.php';
require_admin();

$id    = (int)($_GET['id'] ?? 0);
$error = '';
$data  = ['name' => '', 'city' => '', 'address' => '', 'description' => '',
          'latitude' => '', 'longitude' => ''];

if ($id > 0) {
    $stmt = db()->prepare('SELECT * FROM locations WHERE id = ?');
    $stmt->execute([$id]);
    $data = $stmt->fetch() ?: $data;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['name']        = trim($_POST['name']        ?? '');
    $data['city']        = trim($_POST['city']        ?? '');
    $data['address']     = trim($_POST['address']     ?? '');
    $data['description'] = trim($_POST['description'] ?? '');
    $data['latitude']    = trim($_POST['latitude']    ?? '');
    $data['longitude']   = trim($_POST['longitude']   ?? '');

    if ($data['name'] === '' || $data['city'] === '') {
        $error = 'Nome e città sono obbligatori.';
    } else {
        $lat = $data['latitude']  !== '' ? (float)$data['latitude']  : null;
        $lon = $data['longitude'] !== '' ? (float)$data['longitude'] : null;

        if ($id === 0) {
            $stmt = db()->prepare(
                'INSERT INTO locations (name, city, address, description, latitude, longitude)
                 VALUES (?,?,?,?,?,?)'
            );
            $stmt->execute([$data['name'], $data['city'], $data['address'],
                            $data['description'], $lat, $lon]);
        } else {
            $stmt = db()->prepare(
                'UPDATE locations SET name=?, city=?, address=?, description=?,
                 latitude=?, longitude=? WHERE id=?'
            );
            $stmt->execute([$data['name'], $data['city'], $data['address'],
                            $data['description'], $lat, $lon, $id]);
        }
        header('Location: ' . $config['base'] . '/admin/locations.php');
        exit;
    }
}

$skin = new_page($config['admin_skin'], 'frame-private');
$skin->setContent('title', $id ? 'Modifica location' : 'Nuova location');
$skin->setContent('base',  $config['base']);
$skin->setContent('skin',  $config['admin_skin']);
$skin->setContent('user.username', $_SESSION['user']['username'] ?? '');

$block = new_block('locations-form');
$block->setContent('form_title',    $id ? 'Modifica location' : 'Nuova location');
$block->setContent('action_url',    $config['base'] . '/admin/locations-form.php' . ($id ? '?id=' . $id : ''));
$block->setContent('back_url',      $config['base'] . '/admin/locations.php');
$block->setContent('error',         $error);
$block->setContent('loc_name',      htmlspecialchars($data['name']));
$block->setContent('loc_city',      htmlspecialchars($data['city']));
$block->setContent('loc_address',   htmlspecialchars($data['address']));
$block->setContent('loc_description', htmlspecialchars($data['description']));
$block->setContent('loc_latitude',  htmlspecialchars($data['latitude'] ?? ''));
$block->setContent('loc_longitude', htmlspecialchars($data['longitude'] ?? ''));

$skin->setContent('body', $block->get());
$skin->close();
