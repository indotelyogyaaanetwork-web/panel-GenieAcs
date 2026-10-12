<?php
declare(strict_types=1);
function tg_api(string $method, array $params = []): array {
    $t = setting('bot_token');
    if ($t === '') return ['ok' => false, 'description' => 'Bot token belum diisi'];
    $ch = curl_init("https://api.telegram.org/bot$t/$method");
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $params, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
    $r = curl_exec($ch);
    return $r === false ? ['ok' => false, 'description' => curl_error($ch)] : (json_decode($r, true) ?: ['ok' => false]);
}
function tg_send(string $html, ?string $chat = null): bool {
    $chat ??= setting('bot_chat');
    return $chat !== '' && (tg_api('sendMessage', ['chat_id' => $chat, 'text' => $html, 'parse_mode' => 'HTML'])['ok'] ?? false);
}
