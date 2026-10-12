<?php
require __DIR__ . '/config/config.php'; require_login();
$logs = rows('SELECT l.`timestamp` ts, l.action, l.detail, d.serial_number sn FROM logs l LEFT JOIN devices d ON d.id=l.device_id ORDER BY l.id DESC LIMIT 200');
$title = 'Log Aktivitas'; $active = 'view-logs'; require __DIR__ . '/views/header.php'; ?>
<div class="card"><div class="p-5 overflow-x-auto"><table class="tbl w-full"><thead><tr><th>WAKTU</th><th>PERANGKAT</th><th>AKSI</th><th>KETERANGAN</th></tr></thead><tbody>
<?php foreach ($logs as $l): ?><tr><td><?= esc($l['ts']) ?></td><td><?= esc($l['sn'] ?? '-') ?></td><td><?= esc($l['action']) ?></td><td><?= esc($l['detail']) ?></td></tr><?php endforeach; ?>
<?php if (!$logs): ?><tr><td colspan="4" class="text-center text-slate-400 !py-8">Belum ada log.</td></tr><?php endif; ?></tbody></table></div></div>
<?php require __DIR__ . '/views/footer.php'; ?></body></html>
