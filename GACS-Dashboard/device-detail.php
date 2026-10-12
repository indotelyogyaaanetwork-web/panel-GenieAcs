<?php
require __DIR__ . '/config/config.php'; require_login();
$d = rows('SELECT * FROM devices WHERE id=?', [(int)($_GET['id'] ?? 0)])[0] ?? null;
if (!$d) { http_response_code(404); exit('Perangkat tidak ditemukan'); }
$params = []; $err = '';
if ($d['genie_id'] && ($acs = GenieACS::fromSettings())) { try { $params = $acs->flatParams($d['genie_id']); } catch (Throwable $e) { $err = $e->getMessage(); } }
$title = 'Detail ' . $d['serial_number']; $active = 'devices'; require __DIR__ . '/views/header.php'; ?>
<div class="card mb-6"><div class="flex flex-wrap items-center gap-2 px-5 py-3.5 border-b"><h2 class="text-sm font-semibold mr-auto"><?= esc($d['serial_number']) ?> <span class="badge <?= $d['status'] === 'online' ? 'bg-green-700' : 'bg-red-600' ?>"><?= strtoupper(esc($d['status'])) ?></span></h2>
  <button class="btn btn-gray" data-act="refresh" data-id="<?= (int)$d['id'] ?>"><i class="fa-solid fa-rotate"></i> Refresh</button>
  <button class="btn btn-accent" data-act="reboot" data-id="<?= (int)$d['id'] ?>"><i class="fa-solid fa-bolt"></i> Reboot</button></div>
  <dl class="p-5 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
  <?php foreach (['MAC' => 'mac', 'IP' => 'ip', 'SSID' => 'ssid', 'PPPoE' => 'pppoe', 'RX (dBm)' => 'rx_signal', 'Suhu' => 'temp', 'Klien' => 'clients', 'Terakhir inform' => 'last_online'] as $l => $k): ?>
    <div><dt class="text-xs text-slate-500"><?= $l ?></dt><dd class="font-semibold"><?= esc($d[$k] ?? '-') ?></dd></div><?php endforeach; ?></dl></div>
<div class="card"><div class="px-5 py-3.5 border-b"><h2 class="text-sm font-semibold">Parameter dari GenieACS</h2></div><div class="p-5 overflow-x-auto">
  <?php if ($err): ?><p class="text-sm text-red-700"><?= esc($err) ?></p><?php elseif (!$params): ?><p class="text-sm text-slate-500">Tidak ada parameter (perangkat belum sinkron atau GenieACS belum dikonfigurasi).</p>
  <?php else: ?><table class="tbl w-full"><tbody><?php foreach ($params as $k => $v): ?><tr><td class="font-mono text-xs"><?= esc($k) ?></td><td><?= esc(is_bool($v) ? ($v ? 'true' : 'false') : $v) ?></td></tr><?php endforeach; ?></tbody></table><?php endif; ?></div></div>
<?php require __DIR__ . '/views/footer.php'; ?></body></html>
