<?php
require __DIR__ . '/../config/config.php'; require_login(true);
const FIELDS = ['site' => ['site_name'], 'acs' => ['acs_host', 'acs_port', 'acs_user', 'acs_pass'], 'mt' => ['mt_host', 'mt_port', 'mt_user', 'mt_pass'], 'bot' => ['bot_token', 'bot_chat']];
$b = body(); $a = $b['action'] ?? '';

if ($a === 'get') {   // nilai rahasia tidak pernah dikirim ke browser
    $o = [];
    foreach (FIELDS as $ks) foreach ($ks as $k) $o[$k] = in_array($k, SECRET_KEYS, true) ? '' : setting($k);
    foreach (SECRET_KEYS as $k) $o[$k . '_set'] = setting($k) !== '';
    json_out($o);
}
if ($a === 'save' && isset(FIELDS[$b['section'] ?? ''])) {
    foreach (FIELDS[$b['section']] as $k) {
        $v = trim((string)($b['values'][$k] ?? ''));
        if (in_array($k, SECRET_KEYS, true) && $v === '') continue;          // kosong = tidak diubah
        if (str_ends_with($k, '_port') && $v !== '' && !ctype_digit($v)) json_out(['error' => 'Port harus angka'], 400);
        set_setting($k, $v);
    }
    json_out(['ok' => true]);
}
if ($a === 'test') {
    try {
        if ($b['section'] === 'acs') { ($acs = GenieACS::fromSettings()) ?: throw new Exception('Host belum diisi'); $acs->req('GET', '/devices?projection=_id&limit=1'); json_out(['message' => 'Terhubung ke GenieACS']); }
        if ($b['section'] === 'mt') { [$ok, $m] = mt_test(); json_out($ok ? ['message' => $m] : ['error' => $m], $ok ? 200 : 502); }
    } catch (Throwable $e) { json_out(['error' => $e->getMessage()], 502); }
}
if ($a === 'webhook') {
    if (empty($_SERVER['HTTPS'])) json_out(['error' => 'Telegram mewajibkan HTTPS untuk webhook'], 400);
    $secret = setting('bot_secret') ?: bin2hex(random_bytes(16)); set_setting('bot_secret', $secret);
    $url = 'https://' . $_SERVER['HTTP_HOST'] . rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/') . '/webhook/telegram.php';
    $r = tg_api('setWebhook', ['url' => $url, 'secret_token' => $secret]);
    json_out(($r['ok'] ?? false) ? ['message' => "Webhook diset ke $url"] : ['error' => $r['description'] ?? 'Gagal'], ($r['ok'] ?? false) ? 200 : 502);
}
if ($a === 'creds') {
    $u = rows('SELECT * FROM users WHERE id=?', [$_SESSION['uid']])[0];
    $nu = trim((string)($b['user'] ?? '')); $pw = (string)($b['pw'] ?? '');
    if (!password_verify((string)($b['cur'] ?? ''), $u['password'])) json_out(['error' => 'Password saat ini salah'], 400);
    if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $nu)) json_out(['error' => 'Username 3-50 karakter (huruf, angka, _ . -)'], 400);
    if ($pw !== '' && (strlen($pw) < 8 || $pw !== ($b['pw2'] ?? ''))) json_out(['error' => 'Password baru minimal 8 karakter dan harus sama dengan konfirmasi'], 400);
    if (!empty($_SESSION['must_change']) && $pw === '') json_out(['error' => 'Anda wajib mengganti password default'], 400);
    q('UPDATE users SET username=?, password=?, must_change=0 WHERE id=?', [$nu, $pw !== '' ? password_hash($pw, PASSWORD_DEFAULT) : $u['password'], $u['id']]);
    $_SESSION['user'] = $nu; $_SESSION['must_change'] = 0;
    json_out(['message' => 'Kredensial diperbarui']);
}
json_out(['error' => 'Permintaan tidak valid'], 400);
