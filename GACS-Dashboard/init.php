<?php
// ==== HAPUS FILE INI SETELAH INSTALASI SELESAI ====
declare(strict_types=1);
session_start();
$lock = __DIR__ . '/config/installed.lock';
$msg = ''; $ok = false;
if (is_file($lock)) { http_response_code(403); exit('Sudah terinstal. HAPUS file init.php dari server sekarang.'); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $f = array_map(fn($v) => trim((string)$v), $_POST);
    try {
        foreach (['host', 'name', 'user', 'pass'] as $k) if (preg_match('/[\r\n"\']/', $f[$k] ?? '')) throw new Exception('Karakter tidak valid pada isian database.');
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $db = new mysqli($f['host'], $f['user'], $f['pass'], $f['name'], (int)($f['port'] ?: 3306));
        $db->set_charset('utf8mb4');
        $db->multi_query(file_get_contents(__DIR__ . '/database.sql'));
        while ($db->more_results() && $db->next_result()) { /* habiskan hasil */ }
        if (!$db->query('SELECT COUNT(*) c FROM users')->fetch_assoc()['c']) {   // admin awal sesuai spesifikasi: user1234 / mostech
            $h = password_hash('mostech', PASSWORD_DEFAULT);
            $s = $db->prepare("INSERT INTO users(username,password,role,must_change) VALUES('user1234',?,'admin',1)");
            $s->bind_param('s', $h); $s->execute();
        }
        file_put_contents(__DIR__ . '/.env', "DB_HOST={$f['host']}\nDB_PORT=" . ((int)$f['port'] ?: 3306) . "\nDB_NAME={$f['name']}\nDB_USER={$f['user']}\nDB_PASS={$f['pass']}\nAPP_KEY=" . bin2hex(random_bytes(32)) . "\n");
        @chmod(__DIR__ . '/.env', 0640);
        file_put_contents($lock, date('c'));
        $ok = true;
    } catch (Throwable $e) { $msg = $e->getMessage(); }
}
?><!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Instalasi GACS</title>
<script src="https://cdn.tailwindcss.com"></script><link rel="stylesheet" href="assets/style.css"></head>
<body class="min-h-screen bg-gray-100 flex items-center justify-center p-4"><div class="card w-full max-w-md p-7 space-y-4">
<h1 class="font-bold text-lg">Instalasi GACS Dashboard</h1>
<div class="text-sm bg-red-50 border border-red-300 text-red-800 rounded p-3"><b>PERINGATAN:</b> hapus <code>init.php</code> dari server segera setelah instalasi selesai.</div>
<?php if ($ok): ?>
  <div class="text-sm bg-green-50 border border-green-200 text-green-800 rounded p-3">Instalasi berhasil. Login dengan <b>user1234</b> / <b>mostech</b>; Anda wajib mengganti password pada login pertama.</div>
  <a class="btn btn-accent" href="login.php">Ke halaman login</a>
<?php else: ?>
  <?php if ($msg): ?><div class="text-xs bg-red-50 border border-red-200 text-red-700 rounded p-2"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <form method="post" class="space-y-3">
    <label class="block"><span class="lbl">Host database</span><input class="inp" name="host" value="localhost" required></label>
    <label class="block"><span class="lbl">Port</span><input class="inp" name="port" value="3306"></label>
    <label class="block"><span class="lbl">Nama database</span><input class="inp" name="name" required></label>
    <label class="block"><span class="lbl">User database</span><input class="inp" name="user" required></label>
    <label class="block"><span class="lbl">Password database</span><input class="inp" type="password" name="pass"></label>
    <button class="btn btn-accent">Instal</button>
  </form>
<?php endif; ?></div></body></html>
