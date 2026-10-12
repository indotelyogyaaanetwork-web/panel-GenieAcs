<?php
require __DIR__ . '/config/config.php';
if (!empty($_SESSION['uid'])) { header('Location: dashboard.php'); exit; }
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fails = $_SESSION['fails'] ?? 0;
    if ($fails >= 5) sleep(min(10, $fails - 3));            // pelambatan brute-force sederhana
    $u = rows('SELECT * FROM users WHERE username=?', [trim($_POST['username'] ?? '')])[0] ?? null;
    if (!csrf_ok()) $err = 'Sesi kedaluwarsa, muat ulang halaman.';
    elseif ($u && password_verify($_POST['password'] ?? '', $u['password'])) {
        session_regenerate_id(true);
        $_SESSION = ['uid' => (int)$u['id'], 'user' => $u['username'], 'role' => $u['role'], 'must_change' => (int)$u['must_change']];
        header('Location: ' . ($u['must_change'] ? 'configuration.php?force=1' : 'dashboard.php')); exit;
    } else { $_SESSION['fails'] = $fails + 1; $err = 'Username atau password salah.'; }
}
?><!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Masuk - GACS Dashboard</title>
<script src="https://cdn.tailwindcss.com"></script><link rel="stylesheet" href="assets/style.css"></head>
<body class="min-h-screen flex items-center justify-center bg-[#1b2a4a] p-4">
<form method="post" class="card w-full max-w-sm p-7 space-y-4">
  <h1 class="text-center font-bold text-lg">GACS Dashboard</h1>
  <?php if ($err): ?><div class="text-xs bg-red-50 border border-red-200 text-red-700 rounded p-2"><?= esc($err) ?></div><?php endif; ?>
  <input type="hidden" name="csrf" value="<?= esc(csrf()) ?>">
  <label class="block"><span class="lbl">Username</span><input class="inp" name="username" autocomplete="username" required autofocus></label>
  <label class="block"><span class="lbl">Password</span><input class="inp" type="password" name="password" autocomplete="current-password" required></label>
  <button class="btn btn-accent w-full justify-center !py-2.5">Masuk</button>
</form></body></html>
