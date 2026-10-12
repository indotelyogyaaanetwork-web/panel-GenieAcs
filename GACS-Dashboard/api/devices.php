<?php
require __DIR__ . '/../config/config.php'; require_login(true);
$types = ['onu', 'odp', 'odc', 'olt', 'server'];
$type = in_array($_GET['type'] ?? 'onu', $types, true) ? $_GET['type'] : 'onu';
$per  = in_array((int)($_GET['per'] ?? 20), [20, 50, 100], true) ? (int)$_GET['per'] : 20;
$page = max(1, (int)($_GET['page'] ?? 1));
$qs   = trim((string)($_GET['q'] ?? ''));
$w = 'type=?'; $p = [$type];
if ($qs !== '') { $w .= ' AND (serial_number LIKE ? OR mac LIKE ? OR ip LIKE ?)'; $l = '%' . addcslashes($qs, '%_\\') . '%'; array_push($p, $l, $l, $l); }
$total = (int)rows("SELECT COUNT(*) c FROM devices WHERE $w", $p)[0]['c'];
$off = ($page - 1) * $per;                       // $per & $off sudah integer tervalidasi
$data = rows("SELECT id,serial_number,type,mac,ip,ssid,pppoe,rx_signal,temp,clients,status FROM devices WHERE $w ORDER BY (status='online') DESC, last_online DESC, id DESC LIMIT $per OFFSET $off", $p);
$counts = array_fill_keys($types, 0);
foreach (rows('SELECT type, COUNT(*) c, SUM(status="online") o FROM devices GROUP BY type') as $r) $counts[$r['type']] = (int)$r['c'];
$all = rows('SELECT COUNT(*) c, COALESCE(SUM(status="online"),0) o FROM devices')[0];
json_out(['rows' => $data, 'total' => $total, 'page' => $page, 'per' => $per, 'counts' => $counts, 'online' => (int)$all['o'], 'offline' => (int)$all['c'] - (int)$all['o']]);
