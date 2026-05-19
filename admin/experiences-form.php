<?php

require_once __DIR__ . '/../include/bootstrap.inc.php';
require_admin();

function slugify(string $text): string {
    $map = ['à'=>'a','á'=>'a','â'=>'a','ä'=>'a','è'=>'e','é'=>'e','ê'=>'e',
            'ë'=>'e','ì'=>'i','í'=>'i','î'=>'i','ï'=>'i','ò'=>'o','ó'=>'o',
            'ô'=>'o','ö'=>'o','ù'=>'u','ú'=>'u','û'=>'u','ü'=>'u','ñ'=>'n'];
    $text = mb_strtolower(strtr($text, $map), 'UTF-8');
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    return preg_replace('/[\s-]+/', '-', trim($text));
}

function upload_cover(int $exp_id): ?string {
    if (empty($_FILES['cover']['name'])) return null;
    $f = $_FILES['cover'];
    if ($f['error'] !== UPLOAD_ERR_OK) return null;
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) return null;
    if ($f['size'] > 5 * 1024 * 1024) return null;
    $dir      = __DIR__ . '/../uploads/experiences/';
    $filename = $exp_id . '_' . uniqid() . '.' . $ext;
    return move_uploaded_file($f['tmp_name'], $dir . $filename) ? $filename : null;
}

$id    = (int)($_GET['id'] ?? 0);
$error = '';
$data  = [
    'title' => '', 'slug' => '', 'description' => '', 'short_description' => '',
    'price' => '0.00', 'duration_minutes' => '', 'max_participants' => '',
    'category_id' => '', 'location' => '', 'location_id' => '', 'is_active' => 1,
];
$current_cover   = null;
$assigned_guides = [];

$categories = db()->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$locations  = db()->query('SELECT id, name, city FROM locations ORDER BY city, name')->fetchAll();
$all_guides = db()->query('SELECT id, name, surname FROM guides WHERE is_active=1 ORDER BY surname, name')->fetchAll();

if ($id > 0) {
    $stmt = db()->prepare(
        'SELECT e.*, p.filename AS cover
         FROM experiences e
         LEFT JOIN experience_photos p ON p.experience_id = e.id AND p.is_cover = 1
         WHERE e.id = ?'
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row) {
        $data          = array_merge($data, $row);
        $current_cover = $row['cover'];

        $gstmt = db()->prepare('SELECT guide_id FROM experience_guides WHERE experience_id = ?');
        $gstmt->execute([$id]);
        $assigned_guides = array_column($gstmt->fetchAll(), 'guide_id');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['title']             = trim($_POST['title'] ?? '');
    $data['slug']              = trim($_POST['slug']  ?? '');
    $data['description']       = trim($_POST['description'] ?? '');
    $data['short_description'] = trim($_POST['short_description'] ?? '');
    $data['price']             = $_POST['price'] ?? '0';
    $data['duration_minutes']  = $_POST['duration_minutes'] !== '' ? (int)$_POST['duration_minutes'] : null;
    $data['max_participants']  = $_POST['max_participants'] !== '' ? (int)$_POST['max_participants'] : null;
    $data['category_id']       = $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : null;
    $data['location']          = trim($_POST['location'] ?? '');
    $data['location_id']       = $_POST['location_id'] !== '' ? (int)$_POST['location_id'] : null;
    $data['is_active']         = isset($_POST['is_active']) ? 1 : 0;
    $selected_guides           = array_map('intval', $_POST['guides'] ?? []);

    if ($data['title'] === '') {
        $error = 'Il titolo è obbligatorio.';
    } else {
        if ($data['slug'] === '') {
            $data['slug'] = slugify($data['title']);
        }

        try {
            if ($id === 0) {
                $stmt = db()->prepare(
                    'INSERT INTO experiences
                     (title, slug, description, short_description, price,
                      duration_minutes, max_participants, category_id, location, location_id, is_active)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?)'
                );
                $stmt->execute([
                    $data['title'], $data['slug'], $data['description'],
                    $data['short_description'], $data['price'],
                    $data['duration_minutes'], $data['max_participants'],
                    $data['category_id'], $data['location'], $data['location_id'], $data['is_active'],
                ]);
                $id = (int)db()->lastInsertId();
            } else {
                $stmt = db()->prepare(
                    'UPDATE experiences SET
                     title=?, slug=?, description=?, short_description=?, price=?,
                     duration_minutes=?, max_participants=?, category_id=?, location=?, location_id=?, is_active=?
                     WHERE id=?'
                );
                $stmt->execute([
                    $data['title'], $data['slug'], $data['description'],
                    $data['short_description'], $data['price'],
                    $data['duration_minutes'], $data['max_participants'],
                    $data['category_id'], $data['location'], $data['location_id'], $data['is_active'], $id,
                ]);
            }

            // Aggiorna guide assegnate
            db()->prepare('DELETE FROM experience_guides WHERE experience_id = ?')->execute([$id]);
            $insGuide = db()->prepare('INSERT INTO experience_guides (experience_id, guide_id) VALUES (?,?)');
            foreach ($selected_guides as $gid) {
                $insGuide->execute([$id, $gid]);
            }

            $filename = upload_cover($id);
            if ($filename) {
                db()->prepare('DELETE FROM experience_photos WHERE experience_id = ? AND is_cover = 1')
                    ->execute([$id]);
                db()->prepare('INSERT INTO experience_photos (experience_id, filename, is_cover) VALUES (?,?,1)')
                    ->execute([$id, $filename]);
            }

            header('Location: ' . $config['base'] . '/admin/experiences.php');
            exit;

        } catch (PDOException $e) {
            $error = 'Slug già in uso. Scegli uno slug diverso.';
        }
    }
}

// Build category options HTML
$catOptions = '<option value="">-- Nessuna categoria --</option>';
foreach ($categories as $cat) {
    $sel        = ((string)$cat['id'] === (string)$data['category_id']) ? ' selected' : '';
    $catOptions .= '<option value="' . $cat['id'] . '"' . $sel . '>'
                 . htmlspecialchars($cat['name']) . '</option>';
}

// Build location options HTML
$locOptions = '<option value="">-- Nessuna sede --</option>';
foreach ($locations as $loc) {
    $sel        = ((string)$loc['id'] === (string)$data['location_id']) ? ' selected' : '';
    $locOptions .= '<option value="' . $loc['id'] . '"' . $sel . '>'
                 . htmlspecialchars($loc['name'] . ' (' . $loc['city'] . ')') . '</option>';
}

// Build guide checkboxes HTML
$guidesHtml = '';
foreach ($all_guides as $g) {
    $checked     = in_array((int)$g['id'], (array)$assigned_guides) ? ' checked' : '';
    $guidesHtml .= '<div class="form-check">'
        . '<input class="form-check-input" type="checkbox" name="guides[]"'
        . ' id="guide_' . $g['id'] . '" value="' . $g['id'] . '"' . $checked . '>'
        . '<label class="form-check-label" for="guide_' . $g['id'] . '">'
        . htmlspecialchars($g['name'] . ' ' . $g['surname'])
        . '</label></div>';
}
if ($guidesHtml === '') {
    $guidesHtml = '<p class="text-muted small mb-0">Nessuna guida attiva disponibile.</p>';
}

$skin = new_page($config['admin_skin'], 'frame-private');
$skin->setContent('title', $id ? 'Modifica esperienza' : 'Nuova esperienza');
$skin->setContent('base',  $config['base']);
$skin->setContent('skin',  $config['admin_skin']);
$skin->setContent('user.username', $_SESSION['user']['username'] ?? '');

$block = new_block('experiences-form');
$block->setContent('form_title',        $id ? 'Modifica esperienza' : 'Nuova esperienza');
$block->setContent('action_url',        $config['base'] . '/admin/experiences-form.php' . ($id ? '?id=' . $id : ''));
$block->setContent('back_url',          $config['base'] . '/admin/experiences.php');
$block->setContent('error',             $error);
$block->setContent('exp_title',         htmlspecialchars($data['title']));
$block->setContent('exp_slug',          htmlspecialchars($data['slug']));
$block->setContent('exp_short_desc',    htmlspecialchars($data['short_description']));
$block->setContent('exp_description',   htmlspecialchars($data['description']));
$block->setContent('exp_price',         htmlspecialchars($data['price']));
$block->setContent('exp_duration',      htmlspecialchars($data['duration_minutes'] ?? ''));
$block->setContent('exp_max_part',      htmlspecialchars($data['max_participants'] ?? ''));
$block->setContent('exp_location',      htmlspecialchars($data['location']));
$block->setContent('exp_active_check',  $data['is_active'] ? 'checked' : '');
$block->setContent('category_options',  $catOptions);
$block->setContent('location_options',  $locOptions);
$block->setContent('guides_checkboxes', $guidesHtml);
$block->setContent('cover_preview',     $current_cover
    ? '<img src="' . $config['base'] . '/uploads/experiences/' . htmlspecialchars($current_cover) . '"
            class="img-thumbnail mb-2" style="max-height:150px">'
    : '');

$skin->setContent('body', $block->get());
$skin->close();
