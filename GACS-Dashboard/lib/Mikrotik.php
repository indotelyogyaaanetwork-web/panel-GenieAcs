<?php
declare(strict_types=1);
/** Tes koneksi API MikroTik (port 8728). Memakai evilfreelancer/routeros-api-php bila sudah `composer install`. */
function mt_test(): array {
    $h = setting('mt_host'); $p = (int)(setting('mt_port', '8728') ?: 8728);
    if ($h === '') return [false, 'Host MikroTik belum diisi'];
    if (!class_exists('\RouterOS\Client')) {
        $s = @fsockopen($h, $p, $e, $m, 4);
        return $s ? [true, "Port $p terbuka (jalankan composer install untuk uji login API)"] : [false, "Tidak bisa terhubung: $m"];
    }
    try {
        $c = new \RouterOS\Client(['host' => $h, 'port' => $p, 'user' => setting('mt_user'), 'pass' => setting('mt_pass'), 'timeout' => 5]);
        $r = $c->query(new \RouterOS\Query('/system/identity/print'))->read();
        return [true, 'Terhubung ke ' . ($r[0]['name'] ?? $h)];
    } catch (Throwable $e) { return [false, $e->getMessage()]; }
}
