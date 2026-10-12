<?php
// Menampilkan path parameter asli dari GenieACS agar MAP di lib/GenieACS.php bisa diisi dengan benar.
//   php cron/probe.php            -> perangkat pertama
//   php cron/probe.php FHTTC1234  -> perangkat dengan nomor seri tertentu
if (PHP_SAPI !== 'cli') exit('CLI only');
require __DIR__ . '/../config/config.php';
$acs = GenieACS::fromSettings() or exit("GenieACS belum dikonfigurasi\n");
$qs = isset($argv[1]) ? '?query=' . rawurlencode(json_encode(['_deviceId._SerialNumber' => $argv[1]])) : '?limit=1';
$r = $acs->req('GET', '/devices' . $qs);
$d = $r[0] ?? exit("Perangkat tidak ditemukan\n");
echo 'Device ID : ' . $d['_id'] . "\nLast inform: " . ($d['_lastInform'] ?? '-') . "\n\n";
$pat = '/(RXPower|TXPower|Temperature|SSID$|\.Username$|ExternalIPAddress|\.MACAddress|SerialNumber|TotalAssociations)/i';
$walk = function (array $n, string $p) use (&$walk, $pat) {
    foreach ($n as $k => $v) {
        $k = (string)$k;
        if ($k === '_value') { if (preg_match($pat, $p)) printf("%-95s = %s\n", $p, is_scalar($v) ? $v : json_encode($v)); continue; }
        if (!str_starts_with($k, '_') && is_array($v)) $walk($v, $p === '' ? $k : "$p.$k");
    }
};
$walk($d, '');
echo "\nCatatan: perhatikan indeks instance (.1, .2) dan satuan nilai RX/suhu sebelum mengisi MAP.\n";
