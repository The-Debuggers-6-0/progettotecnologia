<?php

require_once __DIR__ . '/../include/bootstrap.inc.php';
require_admin();

function upload_guide_photo(int $guide_id): ?string {
    if (empty($_FILES['photo']['name'])) return null;
    $f = $_FILES['photo'];
    if ($f['error'] !== UPLOAD_ERR_OK) return null;
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) return null;
    if ($f['size'] > 5 * 1024 * 1024) return null;
    $dir      = __DIR__ . '/../uploads/guides/';
    $filename = $guide_id . '_' . uniqid() . '.' . $ext;
    return move_uploaded_file($f['tmp_name'], $dir . $filename) ? $filename : null;
}

$id    = (int)($_GET['id'] ?? 0);
$error = '';
$data  = ['name' => '', 'surname' => '', 'bio' => '', 'photo_filename' => '',
          'languages' => '', 'email' => '', 'phone' => '', 'is_active' => 1];

if ($id > 0) {
    $stmt = db()->prepare('SELECT * FROM guides WHERE id = ?');
    $stmt->execute([$id]);
    $data = $stmt->fetch() ?: $data;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['name']      = trim($_POST['name']      ?? '');
    $data['surname']   = trim($_POST['surname']   ?? '');
    $data['bio']       = trim($_POST['bio']       ?? '');
    $data['languages'] = trim($_POST['languages'] ?? '');
    $data['email']     = trim($_POST['email']     ?? '');
    $data['phone']     = trim($_POST['phone']     ?? '');
    $data['is_active'] = isset($_POST['is_active']) ? 1 : 0;

    if ($data['name'] === '' || $data['surname'] === '') {
        $error = 'Nome e cognome sono obbligatori.';
    } else {
        if ($id === 0) {
            $stmt = db()->prepare(
                'INSERT INTO guides (name, surname, bio, languages, email, phone, is_active)
                 VALUES (?,?,?,?,?,?,?)'
            );
            $stmt->execute([
                $data['name'], $data['surname'], $data['bio'],
                $data['languages'], $data['email'], $data['phone'], $data['is_active'],
            ]);
            $id = (int)db()->lastInsertId();
        } else {
            $stmt = db()->prepare(
                'UPDATE guides SET name=?, surname=?, bio=?, languages=?, email=?, phone=?, is_active=?
                 WHERE id=?'
            );
            $stmt->execute([
                $data['name'], $data['surname'], $data['bio'],
                $data['languages'], $data['email'], $data['phone'], $data['is_active'], $id,
            ]);
        }

        $filename = upload_guide_photo($id);
        if ($filename) {
            // Rimuovi vecchia foto se presente
            if (!empty($data['photo_filename'])) {
                $old = __DIR__ . '/../uploads/guides/' . $data['photo_filename'];
                if (file_exists($old)) unlink($old);
            }
            db()->prepare('UPDATE guides SET photo_filename=? WHERE id=?')
                ->execute([$filename, $id]);
        }

        header('Location: ' . $config['base'] . '/admin/guides.php');
        exit;
    }
}

$photoPreview = '';
if (!empty($data['photo_filename'])) {
    $url = $config['base'] . '/uploads/guides/' . htmlspecialchars($data['photo_filename']);
    $photoPreview = '<img src="' . $url . '" class="img-thumbnail mb-2" style="max-height:150px">';
}

$skin = new_page($config['admin_skin'], 'frame-private');
$skin->setContent('title', $id ? 'Modifica guida' : 'Nuova guida');
$skin->setContent('base',  $config['base']);
$skin->setContent('skin',  $config['admin_skin']);
$skin->setContent('user.username', $_SESSION['user']['username'] ?? '');

$block = new_block('guides-form');
$block->setContent('form_title',     $id ? 'Modifica guida' : 'Nuova guida');
$block->setContent('action_url',     $config['base'] . '/admin/guides-form.php' . ($id ? '?id=' . $id : ''));
$block->setContent('back_url',       $config['base'] . '/admin/guides.php');
$block->setContent('error',          $error);
$block->setContent('guide_name',     htmlspecialchars($data['name']));
$block->setContent('guide_surname',  htmlspecialchars($data['surname']));
$block->setContent('guide_bio',      htmlspecialchars($data['bio'] ?? ''));
$block->setContent('guide_languages',htmlspecialchars($data['languages'] ?? ''));
$block->setContent('guide_email',    htmlspecialchars($data['email'] ?? ''));
$block->setContent('guide_phone',    htmlspecialchars($data['phone'] ?? ''));
$block->setContent('guide_active',   $data['is_active'] ? 'checked' : '');
$block->setContent('photo_preview',  $photoPreview);

$skin->setContent('body', $block->get());
$skin->close();
