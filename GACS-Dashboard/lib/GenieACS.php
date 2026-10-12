<?php
declare(strict_types=1);
/** Klien GenieACS NBI. SESUAIKAN path parameter di MAP dengan perangkat Anda (cek di UI GenieACS). */
final class GenieACS {
    private const W = 'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.';
    private const G = 'InternetGatewayDevice.WANDevice.1.X_GponInterafceConfig.';
    public const MAP = [
        'sn' => 'InternetGatewayDevice.DeviceInfo.SerialNumber', 'mac' => self::W . 'MACAddress', 'ip' => self::W . 'ExternalIPAddress',
        'pppoe' => self::W . 'Username', 'ssid' => 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.SSID',
        'client' => 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.TotalAssociations',
        'rx' => self::G . 'RXPower', 'temp' => self::G . 'TransceiverTemperature',
    ];
    public const ONLINE_SEC = 600;

    public function __construct(private string $base, private string $user = '', private string $pass = '') {}

    public static function fromSettings(): ?self {
        $h = setting('acs_host');
        return $h === '' ? null : new self('http://' . $h . ':' . (setting('acs_port', '7557') ?: '7557'), setting('acs_user'), setting('acs_pass'));
    }
    public function req(string $m, string $path, ?array $body = null): mixed {
        $ch = curl_init($this->base . $path);
        curl_setopt_array($ch, [CURLOPT_CUSTOMREQUEST => $m, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_HTTPHEADER => ['Content-Type: application/json']]);
        if ($this->user !== '') curl_setopt($ch, CURLOPT_USERPWD, $this->user . ':' . $this->pass);
        if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        $r = curl_exec($ch);
        if ($r === false) throw new RuntimeException('Tidak bisa terhubung: ' . curl_error($ch));
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
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
        $out = [];
        foreach ($raw as $d) {
            $ts = isset($d['_lastInform']) ? strtotime($d['_lastInform']) : 0;
            $rx = self::pv($d, self::MAP['rx']); $t = self::pv($d, self::MAP['temp']);
            $out[] = ['id' => $d['_id'], 'sn' => self::pv($d, self::MAP['sn']) ?: ($d['_deviceId']['_SerialNumber'] ?? $d['_id']),
                'mac' => self::pv($d, self::MAP['mac']), 'ip' => self::pv($d, self::MAP['ip']), 'ssid' => self::pv($d, self::MAP['ssid']),
                'pppoe' => self::pv($d, self::MAP['pppoe']), 'rx' => is_numeric($rx) ? (float)$rx : null, 'temp' => is_numeric($t) ? (float)$t : null,
                'client' => (int)self::pv($d, self::MAP['client']), 'online' => $ts && time() - $ts <= self::ONLINE_SEC,
                'seen' => $ts ? date('Y-m-d H:i:s', $ts) : null];
        }
        return $out;
    }
    public function task(string $id, array $task): void {
        $this->req('POST', '/devices/' . rawurlencode($id) . '/tasks?connection_request', $task);
    }
    public function reboot(string $id): void  { $this->task($id, ['name' => 'reboot']); }
    public function refresh(string $id): void { $this->task($id, ['name' => 'refreshObject', 'objectName' => 'InternetGatewayDevice']); }
    /** Parameter terpilih untuk halaman detail (password WiFi sengaja tidak ditampilkan). */
    public function flatParams(string $id): array {
        $r = $this->req('GET', '/devices?query=' . rawurlencode(json_encode(['_id' => $id])));
        $keep = '/(WLANConfiguration\.\d+\.(SSID|Channel|Enable|TotalAssociations)|WANPPPConnection\.\d+\.(Username|ExternalIPAddress|ConnectionStatus|MACAddress)|DeviceInfo\.(SoftwareVersion|HardwareVersion|UpTime|ModelName|Manufacturer))$/';
        $out = [];
        $walk = function (array $n, string $p) use (&$walk, &$out, $keep) {
            foreach ($n as $k => $v) {
                if ($k[0] === '_' && $k !== '_value') continue;
                if ($k === '_value') { if (preg_match($keep, $p)) $out[$p] = $v; continue; }
                if (is_array($v)) $walk($v, $p === '' ? $k : "$p.$k");
            }
        };
        $walk($r[0] ?? [], '');
        ksort($out);
        return $out;
    }
}
