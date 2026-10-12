<?php
require __DIR__ . '/../config/config.php'; require_login(true);
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    json_out(['nodes' => rows('SELECT id,serial_number,type,status,lat,lng FROM devices WHERE lat IS NOT NULL'),
              'lines' => array_map(fn($r) => ['name' => $r['name'], 'points' => json_decode($r['points'], true)], rows('SELECT name,points FROM topology'))]);
}
$b = body();
if (($b['action'] ?? '') === 'setpos') {
    $lat = (float)$b['lat']; $lng = (float)$b['lng'];
    if (abs($lat) > 90 || abs($lng) > 180) json_out(['error' => 'Koordinat tidak valid'], 400);
    q('UPDATE devices SET lat=?, lng=? WHERE id=?', [$lat, $lng, (int)$b['id']]); json_out(['ok' => true]);
}
if (($b['action'] ?? '') === 'save') {   // ganti seluruh garis topologi
    db()->begin_transaction(); q('DELETE FROM topology');
    foreach (array_slice($b['lines'] ?? [], 0, 500) as $l) {
        $pts = array_map(fn($p) => [round((float)$p[0], 7), round((float)$p[1], 7)], array_slice($l['points'] ?? [], 0, 2000));
        if (count($pts) >= 2) q('INSERT INTO topology(name,points) VALUES(?,?)', [mb_substr((string)($l['name'] ?? 'Link'), 0, 100), json_encode($pts)]);
    }
    db()->commit(); json_out(['ok' => true]);
}
json_out(['error' => 'Permintaan tidak valid'], 400);
