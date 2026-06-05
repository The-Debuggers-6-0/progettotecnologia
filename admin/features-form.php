<?php

require_once __DIR__ . '/../include/bootstrap.inc.php';
require_admin();

// Icone disponibili sul frontend (font flaticon) -> etichetta leggibile
$icons = [
    'flaticon-house'      => 'Casa',
    'flaticon-restaurant' => 'Ristorante / Gastronomia',
    'flaticon-mail'       => 'Busta / Email',
    'flaticon-phone-call' => 'Telefono / Supporto',
    'flaticon-plane'      => 'Aereo / Viaggio',
    'flaticon-swimming'   => 'Nuoto / Mare',
    'flaticon-playground' => 'Attività / Famiglia',
];

$id    = (int)($_GET['id'] ?? 0);
$error = '';
$data  = ['icon' => 'flaticon-house', 'title' => '', 'description' => '', 'sort_order' => 0];

if ($id > 0) {
    $row = db()->prepare('SELECT * FROM home_features WHERE id = ?');
    $row->execute([$id]);
    $data = $row->fetch() ?: $data;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['icon']        = trim($_POST['icon'] ?? '');
    $data['title']       = trim($_POST['title'] ?? '');
    $data['description'] = trim($_POST['description'] ?? '');
    $data['sort_order']  = (int)($_POST['sort_order'] ?? 0);

    if ($data['title'] === '') {
        $error = 'Il titolo è obbligatorio.';
    } elseif (!isset($icons[$data['icon']])) {
        $error = 'Seleziona un\'icona valida.';
    } else {
        if ($id === 0) {
            $stmt = db()->prepare(
                'INSERT INTO home_features (icon, title, description, sort_order) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$data['icon'], $data['title'], $data['description'], $data['sort_order']]);
        } else {
            $stmt = db()->prepare(
                'UPDATE home_features SET icon = ?, title = ?, description = ?, sort_order = ? WHERE id = ?'
            );
            $stmt->execute([$data['icon'], $data['title'], $data['description'], $data['sort_order'], $id]);
        }
        header('Location: ' . $config['base'] . '/admin/features.php');
        exit;
    }
}

// Costruisco le <option> del menu icone, con la scelta corrente preselezionata
$iconOptions = '';
foreach ($icons as $class => $label) {
    $selected = ($class === $data['icon']) ? ' selected' : '';
    $iconOptions .= '<option value="' . htmlspecialchars($class) . '"' . $selected . '>'
                  . htmlspecialchars($label) . '</option>';
}

$skin = new_page($config['admin_skin'], 'frame-private');
$skin->setContent('title', $id ? 'Modifica box' : 'Nuovo box');
$skin->setContent('base',  $config['base']);
$skin->setContent('skin',  $config['admin_skin']);
$skin->setContent('user.username', $_SESSION['user']['username'] ?? '');

// Carico il font flaticon per l'anteprima dal vivo dell'icona scelta
$skin->setContent('head',
    '<link rel="stylesheet" href="' . $config['base'] . '/skins/tour/fonts/flaticon/font/flaticon.css">');

$block = new_block('features-form');
$block->setContent('form_title',   $id ? 'Modifica box' : 'Nuovo box');
$block->setContent('action_url',   $config['base'] . '/admin/features-form.php' . ($id ? '?id=' . $id : ''));
$block->setContent('error',        $error);
$block->setContent('icon_options', $iconOptions);
$block->setContent('feat_icon',        htmlspecialchars($data['icon']));
$block->setContent('feat_title',       htmlspecialchars($data['title']));
$block->setContent('feat_description', htmlspecialchars($data['description'] ?? ''));
$block->setContent('feat_order',       (int)$data['sort_order']);
$block->setContent('back_url',     $config['base'] . '/admin/features.php');

$skin->setContent('body', $block->get());
$skin->close();
