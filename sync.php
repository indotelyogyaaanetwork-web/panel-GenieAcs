<?php
// Jalankan tiap 5 menit:  */5 * * * * php /path/GACS-Dashboard/cron/sync.php
if (PHP_SAPI !== 'cli') exit('CLI only');
require __DIR__ . '/../config/config.php';
$acs = GenieACS::fromSettings() or exit("GenieACS belum dikonfigurasi\n");

$lock = fopen(sys_get_temp_dir() . '/gacs-sync.lock', 'c');
if (!flock($lock, LOCK_EX | LOCK_NB)) exit("Sinkron sebelumnya masih berjalan\n");   // cegah proses tumpang tindih

try { $list = $acs->devices(); }
catch (Throwable $e) { fwrite(STDERR, 'GAGAL: ' . $e->getMessage() . "\n"); exit(1); }

$old = array_column(rows('SELECT id,serial_number,status,genie_id FROM devices'), null, 'serial_number');
$num = fn($v, $max) => is_numeric($v) && abs((float)$v) <= $max ? (float)$v : null;   // nilai di luar rentang kolom -> NULL
$cut = fn($v, $n) => $v === null ? null : mb_substr((string)$v, 0, $n);
$seen = []; $msgs = []; $bad = 0;

foreach ($list as $d) {
    $sn = $cut($d['sn'], 64); $rx = $num($d['rx'], 99.99); $tp = $num($d['temp'], 999.9);
    if (($d['rx'] !== null && $rx === null) || ($d['temp'] !== null && $tp === null)) $bad++;
    try {
        q('INSERT INTO devices(serial_number,genie_id,type,mac,ip,ssid,pppoe,rx_signal,temp,clients,status,last_online) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)
           ON DUPLICATE KEY UPDATE genie_id=VALUES(genie_id),mac=VALUES(mac),ip=VALUES(ip),ssid=VALUES(ssid),pppoe=VALUES(pppoe),rx_signal=VALUES(rx_signal),
           temp=VALUES(temp),clients=VALUES(clients),status=VALUES(status),last_online=VALUES(last_online)',
          [$sn, $cut($d['id'], 255), 'onu', $cut($d['mac'], 32), $cut($d['ip'], 45), $cut($d['ssid'], 64), $cut($d['pppoe'], 128), $rx, $tp,
           min(max((int)$d['client'], 0), 32000), $d['online'] ? 'online' : 'offline', $d['seen']]);
    } catch (Throwable $e) { fwrite(STDERR, "Lewati $sn: " . $e->getMessage() . "\n"); continue; }   // satu perangkat bermasalah tidak menghentikan sinkron
    $seen[$sn] = 1;
    $was = $old[$sn] ?? null;
    if ($was && $was['status'] === 'online' && !$d['online']) { log_action((int)$was['id'], 'offline', $sn); $msgs[] = '⚠️ ONU <b>' . esc($sn) . '</b> offline (' . esc($d['ssid']) . ')'; }
    elseif ($was && $was['status'] === 'offline' && $d['online']) { log_action((int)$was['id'], 'online', $sn); $msgs[] = '✅ ONU <b>' . esc($sn) . '</b> kembali online'; }
}
// Perangkat yang sudah tidak ada di GenieACS (dihapus/ID berubah) jangan tetap tampil "online"
foreach ($old as $sn => $o)
    if ($o['genie_id'] !== null && $o['status'] === 'online' && !isset($seen[$sn])) { q("UPDATE devices SET status='offline' WHERE id=?", [$o['id']]); log_action((int)$o['id'], 'offline', 'tidak ada di GenieACS'); }

// Batasi notifikasi: perubahan massal dikirim sebagai satu ringkasan
if (count($msgs) > 10) tg_send('⚠️ ' . count($msgs) . ' perubahan status ONU dalam satu sinkron. Cek dashboard.');
else foreach ($msgs as $m) tg_send($m);

set_setting('last_sync', date('c'));
echo 'Sinkron selesai: ' . count($list) . ' perangkat' . ($bad ? ", $bad nilai RX/suhu di luar rentang diabaikan (jalankan cron/probe.php)" : '') . "\n";
