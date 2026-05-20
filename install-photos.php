<?php
/**
 * install-photos.php — scarica le foto di esempio da Unsplash
 * Da eseguire UNA SOLA VOLTA dopo aver caricato il seed.sql
 * Visita: http://localhost/progettotecnologia/install-photos.php
 */

require_once __DIR__ . '/include/bootstrap.inc.php';

// Foto Unsplash per ogni esperienza (id_esperienza => [url, nome_file])
$photos = [
    1 => 'https://images.unsplash.com/photo-1552832230-c0197dd311b5?w=900&q=85', // Colosseo
    2 => 'https://images.unsplash.com/photo-1514890547357-a9ee288728e0?w=900&q=85', // Gondola Venezia
    3 => 'https://images.unsplash.com/photo-1541370976299-4d24ebbc9077?w=900&q=85', // Uffizi Firenze
    4 => 'https://images.unsplash.com/photo-1534308983496-4fabb1a015ee?w=900&q=85', // Napoli
    5 => 'https://images.unsplash.com/photo-1499678329028-101435549a4e?w=900&q=85', // Cinque Terre
    6 => 'https://images.unsplash.com/photo-1570077188670-e3a8d69ac5ff?w=900&q=85', // Amalfi/Positano
    7 => 'https://images.unsplash.com/photo-1515542622106-78bda8ba0e5b?w=900&q=85', // Roma Fontana di Trevi
];

$uploadDir = __DIR__ . '/uploads/experiences/';
$ctx = stream_context_create(['http' => [
    'user_agent' => 'Mozilla/5.0 (compatible; install-photos)',
    'follow_location' => true,
    'timeout' => 20,
]]);

echo '<style>body{font-family:sans-serif;padding:2rem;max-width:600px}
      .ok{color:#28a745}.err{color:#dc3545}.info{color:#6c757d}</style>';
echo '<h2>Download foto esperienze</h2>';

// Svuota foto esistenti nel DB per le esperienze 1-7
db()->exec('DELETE FROM experience_photos WHERE experience_id BETWEEN 1 AND 7');

foreach ($photos as $expId => $url) {
    $filename = $expId . '_cover.jpg';
    $dest     = $uploadDir . $filename;

    echo "<p>Esperienza #$expId... ";
    flush();

    $data = @file_get_contents($url, false, $ctx);

    if ($data === false || strlen($data) < 5000) {
        echo '<span class="err">ERRORE — immagine non scaricata</span></p>';
        continue;
    }

    file_put_contents($dest, $data);

    $stmt = db()->prepare(
        'INSERT INTO experience_photos (experience_id, filename, is_cover, sort_order)
         VALUES (?, ?, 1, 0)'
    );
    $stmt->execute([$expId, $filename]);

    $kb = round(strlen($data) / 1024);
    echo "<span class=\"ok\">OK ({$kb} KB) — {$filename}</span></p>";
}

echo '<hr><p class="info">Fatto! Puoi eliminare questo file (<code>install-photos.php</code>) dalla root del progetto.</p>';
echo '<p><a href="' . $config['base'] . '/tours.php">→ Vai alle esperienze</a></p>';
