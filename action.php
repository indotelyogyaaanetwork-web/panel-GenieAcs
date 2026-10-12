<?php
require __DIR__ . '/../config/config.php'; require_login(true);
$b = body(); $act = $b['action'] ?? '';
if (!in_array($act, ['reboot', 'refresh'], true)) json_out(['error' => 'Aksi tidak dikenal'], 400);
$d = rows('SELECT id,serial_number,genie_id FROM devices WHERE id=?', [(int)($b['id'] ?? 0)])[0] ?? null;
if (!$d || !$d['genie_id']) json_out(['error' => 'Perangkat tidak ditemukan atau belum tersinkron dari GenieACS'], 404);
$acs = GenieACS::fromSettings() ?? json_out(['error' => 'GenieACS belum dikonfigurasi'], 400);
try { $code = $act === 'reboot' ? $acs->reboot($d['genie_id']) : $acs->refresh($d['genie_id']); }
catch (Throwable $e) { json_out(['error' => $e->getMessage()], 502); }
log_action((int)$d['id'], $act . ($code === 202 ? ' (antre)' : ''), 'oleh ' . $_SESSION['user']);
json_out(['ok' => true, 'queued' => $code === 202]);
