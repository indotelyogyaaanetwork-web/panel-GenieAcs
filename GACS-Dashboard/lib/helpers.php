<?php
declare(strict_types=1);
function esc(mixed $v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function json_out(array $d, int $c = 200): never {
    http_response_code($c); header('Content-Type: application/json'); echo json_encode($d, JSON_UNESCAPED_UNICODE); exit;
}
function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(16)); }
function csrf_ok(): bool { return hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')); }

/** Wajib login. Halaman: redirect. API: JSON 401, dan POST harus membawa token CSRF. */
function require_login(bool $api = false): void {
    if (empty($_SESSION['uid'])) { if ($api) json_out(['error' => 'Belum login'], 401); header('Location: login.php'); exit; }
    if ($api && $_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_ok()) json_out(['error' => 'Token CSRF tidak valid'], 403);
    if (!$api && !empty($_SESSION['must_change']) && !in_array(basename($_SERVER['SCRIPT_NAME']), ['configuration.php', 'logout.php'], true)) {
        header('Location: configuration.php?force=1'); exit;
    }
}
function body(): array { return json_decode(file_get_contents('php://input'), true) ?: []; }

// --- Enkripsi nilai rahasia di tabel settings (AES-256-GCM, kunci dari APP_KEY) ---
const SECRET_KEYS = ['acs_pass', 'mt_pass', 'bot_token'];
function _key(): string { return hash('sha256', env('APP_KEY'), true); }
function enc(string $s): string {
    $iv = random_bytes(12); $c = openssl_encrypt($s, 'aes-256-gcm', _key(), OPENSSL_RAW_DATA, $iv, $tag);
    return 'e:' . base64_encode($iv . $tag . $c);
}
function dec(string $s): string {
    if (!str_starts_with($s, 'e:')) return $s;
    $b = base64_decode(substr($s, 2));
    $p = openssl_decrypt(substr($b, 28), 'aes-256-gcm', _key(), OPENSSL_RAW_DATA, substr($b, 0, 12), substr($b, 12, 16));
    return $p === false ? '' : $p;
}
function setting(string $k, string $d = ''): string {
    $v = rows('SELECT `value` FROM settings WHERE `key`=?', [$k])[0]['value'] ?? $d;
    return in_array($k, SECRET_KEYS, true) ? dec((string)$v) : (string)$v;
}
function set_setting(string $k, string $v): void {
    if (in_array($k, SECRET_KEYS, true)) $v = enc($v);
    q('INSERT INTO settings(`key`,`value`) VALUES(?,?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)', [$k, $v]);
}
function log_action(?int $dev, string $action, string $detail = ''): void {
    q('INSERT INTO logs(device_id,action,detail) VALUES(?,?,?)', [$dev, $action, $detail]);
}
