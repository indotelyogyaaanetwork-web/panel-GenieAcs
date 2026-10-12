<?php
declare(strict_types=1);
define('ROOT', dirname(__DIR__));
const APP_VERSION = '1.1.0-beta';

foreach (@file(ROOT . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $l) {
    if ($l[0] !== '#' && str_contains($l, '=')) { [$k, $v] = explode('=', $l, 2); $_ENV[trim($k)] = trim($v, " \t\"'"); }
}
function env(string $k, string $d = ''): string { return $_ENV[$k] ?? $d; }
date_default_timezone_set(env('TZ', 'Asia/Jakarta'));

if (is_file(ROOT . '/vendor/autoload.php')) require_once ROOT . '/vendor/autoload.php';
require_once ROOT . '/config/database.php';
require_once ROOT . '/lib/helpers.php';
require_once ROOT . '/lib/GenieACS.php';
require_once ROOT . '/lib/Telegram.php';
require_once ROOT . '/lib/Mikrotik.php';

if (PHP_SAPI !== 'cli') {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS'])]);
    session_start();
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
}
