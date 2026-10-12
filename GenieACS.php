<?php
declare(strict_types=1);
/** Klien GenieACS NBI. Path di MAP belum diverifikasi untuk perangkat Anda: jalankan `php cron/probe.php` untuk melihat path sebenarnya. */
final class GenieACS {
    private const W = 'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.';
    private const G = 'InternetGatewayDevice.WANDevice.1.X_GponInterafceConfig.';
    public const MAP = [
        'sn' => 'InternetGatewayDevice.DeviceInfo.SerialNumber', 'mac' => self::W . 'MACAddress', 'ip' => self::W . 'ExternalIPAddress',
        'pppoe' => self::W . 'Username', 'ssid' => 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.SSID',
        'client' => 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.TotalAssociations',
        'rx' => self::G . 'RXPower', 'temp' => self::G . 'TransceiverTemperature',
    ];

    public int $lastCode = 0;   // kode HTTP balasan terakhir (200 = dijalankan, 202 = antre)

    public function __construct(private string $base, private string $user = '', private string $pass = '') {}

    public static function fromSettings(): ?self {
        $raw = trim(setting('acs_host'));
        if ($raw === '') return null;
        $scheme = stripos($raw, 'https://') === 0 ? 'https' : 'http';
        $host = preg_replace('#[:/].*$#', '', preg_replace('#^https?://#i', '', $raw));   // toleran jika user menulis http:// atau :port
        return new self("$scheme://$host:" . (setting('acs_port', '7557') ?: '7557'), setting('acs_user'), setting('acs_pass'));
    }
    public function req(string $m, string $path, ?array $body = null): mixed {
        $ch = curl_init($this->base . $path);
        curl_setopt_array($ch, [CURLOPT_CUSTOMREQUEST => $m, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_HTTPHEADER => ['Content-Type: application/json']]);
        if ($this->user !== '') curl_setopt($ch, CURLOPT_USERPWD, $this->user . ':' . $this->pass);
        if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        $r = curl_exec($ch);
        if ($r === false) throw new RuntimeException('Tidak bisa terhubung: ' . curl_error($ch));
        $this->lastCode = $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($code >= 400) throw new RuntimeException("GenieACS membalas HTTP $code");
        return json_decode($r, true);
    }
    private static function pv(array $d, string $path): mixed {
        foreach (explode('.', $path) as $k) { if (!is_array($d) || !isset($d[$k])) return null; $d = $d[$k]; }
        return is_array($d) ? ($d['_value'] ?? null) : $d;
    }
    /** @return array<int,array<string,mixed>> */
    public function devices(): array {
        $raw = $this->req('GET', '/devices?projection=' . rawurlencode(implode(',', array_merge(['_id', '_lastInform', '_deviceId'], array_values(self::MAP)))));
        if (!is_array($raw)) throw new RuntimeException('Balasan GenieACS bukan JSON daftar perangkat (cek URL, port, atau reverse proxy)');
        // Batas online harus > interval periodic inform ONU (default 900 dtk, atur di ACS Config)
        $win = max(60, (int)setting('acs_online_sec', '900') ?: 900);
        $out = [];
        foreach ($raw as $d) {
            $ts = isset($d['_lastInform']) ? strtotime($d['_lastInform']) : 0;
            $rx = self::pv($d, self::MAP['rx']); $t = self::pv($d, self::MAP['temp']);
            $out[] = ['id' => $d['_id'], 'sn' => self::pv($d, self::MAP['sn']) ?: ($d['_deviceId']['_SerialNumber'] ?? $d['_id']),
                'mac' => self::pv($d, self::MAP['mac']), 'ip' => self::pv($d, self::MAP['ip']), 'ssid' => self::pv($d, self::MAP['ssid']),
                'pppoe' => self::pv($d, self::MAP['pppoe']), 'rx' => is_numeric($rx) ? (float)$rx : null, 'temp' => is_numeric($t) ? (float)$t : null,
                'client' => (int)self::pv($d, self::MAP['client']), 'online' => $ts && time() - $ts <= $win,
                'seen' => $ts ? date('Y-m-d H:i:s', $ts) : null];
        }
        return $out;
    }
    /** @return int 200 = langsung dijalankan, 202 = perangkat tidak merespons connection request, tugas antre */
    public function task(string $id, array $task): int {
        $this->req('POST', '/devices/' . rawurlencode($id) . '/tasks?connection_request', $task);
        return $this->lastCode;
    }
    public function reboot(string $id): int  { return $this->task($id, ['name' => 'reboot']); }
    public function refresh(string $id): int { return $this->task($id, ['name' => 'refreshObject', 'objectName' => 'InternetGatewayDevice']); }
    /** Parameter terpilih untuk halaman detail (password WiFi sengaja tidak ditampilkan). */
    public function flatParams(string $id): array {
        $r = $this->req('GET', '/devices?query=' . rawurlencode(json_encode(['_id' => $id])));
        $keep = '/(WLANConfiguration\.\d+\.(SSID|Channel|Enable|TotalAssociations)|WANPPPConnection\.\d+\.(Username|ExternalIPAddress|ConnectionStatus|MACAddress)|DeviceInfo\.(SoftwareVersion|HardwareVersion|UpTime|ModelName|Manufacturer))$/';
        $out = [];
        $walk = function (array $n, string $p) use (&$walk, &$out, $keep) {
            foreach ($n as $k => $v) {
                $k = (string)$k;   // kunci numerik (instance .1, .2) dari json_decode berupa int
                if (str_starts_with($k, '_') && $k !== '_value') continue;
                if ($k === '_value') { if (preg_match($keep, $p)) $out[$p] = $v; continue; }
                if (is_array($v)) $walk($v, $p === '' ? $k : "$p.$k");
            }
        };
        $walk($r[0] ?? [], '');
        ksort($out);
        return $out;
    }
}
