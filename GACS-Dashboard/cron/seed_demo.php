<?php
// Isi 46 ONU contoh untuk uji tampilan:  php cron/seed_demo.php
if (PHP_SAPI !== 'cli') exit('CLI only');
require __DIR__ . '/../config/config.php';
mt_srand(42);
$ss = ['Devinaaa', 'RIZAL', 'Toko NR', 'StayHome', 'MONICA', 'KOST AJENG', 'Perkutut', 'Azam', 'Me cell', 'GHEFIVE'];
for ($i = 0; $i < 46; $i++) {
    q('INSERT IGNORE INTO devices(serial_number,type,mac,ip,ssid,pppoe,rx_signal,temp,clients,status,last_online,lat,lng) VALUES(?,?,?,?,?,?,?,?,?,?,NOW(),?,?)',
      [sprintf('FHTTC%06X', mt_rand(0, 0xFFFFFF)), 'onu', sprintf('00:0A:C2:%02X:%02X:%02X', mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255)),
       '193.186.255.' . (200 + $i), $ss[$i % 10], "user$i@example.net", -round(8 + mt_rand(0, 1700) / 100, 2), round(42 + mt_rand(0, 120) / 10, 1),
       mt_rand(0, 5), 'online', -7.8 + mt_rand(-100, 100) / 1000, 110.4 + mt_rand(-100, 100) / 1000]);
}
echo "46 ONU contoh ditambahkan\n";
