<?php
require __DIR__ . '/../config/config.php';
// Telegram mengirim header rahasia yang kita set saat setWebhook; tolak selain itu.
$secret = setting('bot_secret');
if ($secret === '' || !hash_equals($secret, $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '')) { http_response_code(403); exit; }
$u = json_decode(file_get_contents('php://input'), true);
$chat = (string)($u['message']['chat']['id'] ?? '');
if ($chat === '' || $chat !== setting('bot_chat')) { http_response_code(200); exit; }   // hanya layani chat terdaftar

$cmd = strtolower(strtok(trim($u['message']['text'] ?? ''), " @\n"));
$r = rows("SELECT COUNT(*) t, COALESCE(SUM(status='online'),0) o FROM devices WHERE type='onu'")[0];
switch ($cmd) {
    case '/status': tg_send("<b>GACS</b>\nTotal ONU: {$r['t']}\nOnline: {$r['o']}\nOffline: " . ($r['t'] - $r['o']), $chat); break;
    case '/offline':
        $l = rows("SELECT serial_number, ssid FROM devices WHERE type='onu' AND status='offline' ORDER BY last_online DESC LIMIT 30");
        tg_send($l ? "<b>ONU offline</b>\n" . implode("\n", array_map(fn($d) => '• ' . esc($d['serial_number']) . ' (' . esc($d['ssid']) . ')', $l)) : 'Semua ONU online ✅', $chat); break;
    default: tg_send("Perintah: /status, /offline", $chat);
}
echo 'ok';
