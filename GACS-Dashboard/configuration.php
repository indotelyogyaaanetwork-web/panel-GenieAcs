<?php
require __DIR__ . '/config/config.php'; require_login();
$title = 'Konfigurasi'; $active = 'configuration'; $force = !empty($_SESSION['must_change']); require __DIR__ . '/views/header.php';
$tabs = ['site' => 'Site Config', 'acs' => 'ACS Config', 'mt' => 'MikroTik Config', 'bot' => 'Bot Config'];
$f = fn($id, $l, $t = 'text', $ph = '') => "<label class='block'><span class='lbl'>$l</span><input class='inp' type='$t' id='$id' placeholder='$ph' autocomplete='off'></label>"; ?>
<?php if ($force): ?><div class="mb-4 rounded border border-amber-300 bg-amber-50 text-amber-800 text-[13px] px-5 py-3.5">Anda masih memakai password default. Ganti sekarang untuk melanjutkan.</div><?php endif; ?>
<div class="flex flex-wrap border-b mb-4 text-sm" id="tabs"><?php foreach ($tabs as $k => $l): ?><button data-t="<?= $k ?>" class="px-4 py-2.5 -mb-px border rounded-t <?= $k === 'site' ? 'bg-white border-b-white' : 'border-transparent text-blue-600' ?>"><?= $l ?></button><?php endforeach; ?></div>

<div class="card tab" id="t-site"><div class="px-5 py-3.5 border-b"><h2 class="text-sm font-semibold">Ganti Kredensial Login</h2></div><div class="p-5 space-y-5">
  <?= $f('cur', 'Password Saat Ini', 'password') ?><?= $f('user', 'Username Baru') ?><?= $f('pw', 'Password Baru (kosongkan jika tidak ingin mengubah)', 'password', 'kosongkan jika tidak ingin mengubah') ?><?= $f('pw2', 'Konfirmasi Password Baru', 'password') ?>
  <button class="btn btn-accent" id="b-creds"><i class="fa-regular fa-floppy-disk"></i> Simpan Perubahan</button></div></div>

<div class="card tab hidden" id="t-acs"><div class="px-5 py-3.5 border-b"><h2 class="text-sm font-semibold">Koneksi GenieACS</h2></div><div class="p-5 space-y-5">
  <?= $f('acs_host', 'Host', 'text', '10.0.0.19') ?><?= $f('acs_port', 'Port', 'text', '7557') ?><?= $f('acs_user', 'Username') ?><?= $f('acs_pass', 'Password', 'password') ?>
  <div class="flex gap-2"><button class="btn btn-accent" data-save="acs">Simpan</button><button class="btn btn-gray" data-test="acs">Test Connection</button></div></div></div>

<div class="card tab hidden" id="t-mt"><div class="px-5 py-3.5 border-b"><h2 class="text-sm font-semibold">Koneksi MikroTik (API)</h2></div><div class="p-5 space-y-5">
  <?= $f('mt_host', 'Host') ?><?= $f('mt_port', 'Port', 'text', '8728') ?><?= $f('mt_user', 'Username') ?><?= $f('mt_pass', 'Password', 'password') ?>
  <div class="flex gap-2"><button class="btn btn-accent" data-save="mt">Simpan</button><button class="btn btn-gray" data-test="mt">Test Connection</button></div></div></div>

<div class="card tab hidden" id="t-bot"><div class="px-5 py-3.5 border-b"><h2 class="text-sm font-semibold">Telegram Bot</h2></div><div class="p-5 space-y-5">
  <?= $f('bot_token', 'Bot Token', 'password') ?><?= $f('bot_chat', 'Chat ID') ?>
  <div class="flex gap-2"><button class="btn btn-accent" data-save="bot">Simpan</button><button class="btn btn-gray" id="b-hook">Set Webhook</button></div></div></div>
<?php require __DIR__ . '/views/footer.php'; ?>
<script>
const $ = id => document.getElementById(id), ids = {acs: ['acs_host','acs_port','acs_user','acs_pass'], mt: ['mt_host','mt_port','mt_user','mt_pass'], bot: ['bot_token','bot_chat']};
const vals = sec => Object.fromEntries(ids[sec].map(k => [k, $(k).value]));
const run = p => p.then(r => toast(r.message || 'Tersimpan')).catch(e => toast(e.message));
$('tabs').onclick = e => { const b = e.target.closest('[data-t]'); if (!b) return;
  document.querySelectorAll('.tab').forEach(t => t.classList.add('hidden')); $('t-' + b.dataset.t).classList.remove('hidden');
  document.querySelectorAll('#tabs button').forEach(x => x.className = 'px-4 py-2.5 -mb-px border rounded-t ' + (x === b ? 'bg-white border-b-white' : 'border-transparent text-blue-600')); };
document.querySelectorAll('[data-save]').forEach(b => b.onclick = () => run(api('config.php', {action: 'save', section: b.dataset.save, values: vals(b.dataset.save)})));
document.querySelectorAll('[data-test]').forEach(b => b.onclick = () => run(api('config.php', {action: 'save', section: b.dataset.test, values: vals(b.dataset.test)}).then(() => api('config.php', {action: 'test', section: b.dataset.test}))));
$('b-hook').onclick = () => run(api('config.php', {action: 'save', section: 'bot', values: vals('bot')}).then(() => api('config.php', {action: 'webhook'})));
$('b-creds').onclick = () => run(api('config.php', {action: 'creds', cur: $('cur').value, user: $('user').value, pw: $('pw').value, pw2: $('pw2').value}).then(r => { ['cur','pw','pw2'].forEach(k => $(k).value = ''); <?= $force ? 'setTimeout(() => location = "dashboard.php", 800);' : '' ?> return r; }));
api('config.php', {action: 'get'}).then(c => { $('user').value = <?= json_encode($_SESSION['user']) ?>; Object.values(ids).flat().forEach(k => { if ($(k)) { $(k).value = c[k] ?? ''; if (c[k + '_set']) $(k).placeholder = 'tersimpan - kosongkan jika tidak diubah'; } }); });
</script></body></html>
