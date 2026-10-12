<?php
// Jalankan tiap 5 menit:  */5 * * * * php /path/GACS-Dashboard/cron/sync.php
if (PHP_SAPI !== 'cli') exit('CLI only');
require __DIR__ . '/../config/config.php';
$acs = GenieACS::fromSettings() or exit("GenieACS belum dikonfigurasi\n");
$old = array_column(rows('SELECT id,serial_number,status FROM devices'), null, 'serial_number');
foreach ($acs->devices() as $d) {
    q('INSERT INTO devices(serial_number,genie_id,type,mac,ip,ssid,pppoe,rx_signal,temp,clients,status,last_online) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)
       ON DUPLICATE KEY UPDATE genie_id=VALUES(genie_id),mac=VALUES(mac),ip=VALUES(ip),ssid=VALUES(ssid),pppoe=VALUES(pppoe),rx_signal=VALUES(rx_signal),
       temp=VALUES(temp),clients=VALUES(clients),status=VALUES(status),last_online=VALUES(last_online)',
      [$d['sn'], $d['id'], 'onu', $d['mac'], $d['ip'], $d['ssid'], $d['pppoe'], $d['rx'], $d['temp'], $d['client'], $d['online'] ? 'online' : 'offline', $d['seen']]);
    $was = $old[$d['sn']] ?? null;
    if ($was && $was['status'] === 'online' && !$d['online']) {
        log_action((int)$was['id'], 'offline', $d['sn']);
        tg_send('⚠️ ONU <b>' . esc($d['sn']) . '</b> offline (' . esc($d['ssid']) . ')');
    } elseif ($was && $was['status'] === 'offline' && $d['online']) {
        log_action((int)$was['id'], 'online', $d['sn']);
        tg_send('✅ ONU <b>' . esc($d['sn']) . '</b> kembali online');
    }
}
echo "Sinkron selesai\n";
