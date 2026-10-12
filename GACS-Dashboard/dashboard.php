<?php
require __DIR__ . '/config/config.php'; require_login();
$title = 'Dashboard'; $active = 'dashboard'; require __DIR__ . '/views/header.php'; ?>
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-6" id="stats"></div>
<div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-6">
  <div class="card"><div class="flex justify-between items-center px-5 py-3.5 border-b"><h2 class="text-sm font-semibold">Ringkasan Perangkat</h2><button class="btn btn-accent" onclick="load()"><i class="fa-solid fa-rotate"></i> Segarkan</button></div><div class="p-5 h-[240px]"><canvas id="c1"></canvas></div></div>
  <div class="card"><div class="flex justify-between items-center px-5 py-3.5 border-b"><h2 class="text-sm font-semibold">Kekuatan Sinyal Uplink</h2><button class="btn btn-accent" onclick="load()"><i class="fa-solid fa-rotate"></i> Segarkan</button></div><div class="p-5 h-[240px]"><canvas id="c2"></canvas></div></div>
</div>
<div class="card"><div class="px-5 py-3.5 border-b"><h2 class="text-sm font-semibold">Aktivitas Perangkat Terbaru</h2></div>
  <div class="p-5 overflow-x-auto"><table class="tbl w-full"><thead><tr><th>SN</th><th>MAC</th><th>TIPE</th><th>IP</th><th>SSID</th><th>PPPOE</th><th>RX</th><th>SUHU</th><th>KLIEN</th><th>STATUS</th><th>AKSI</th></tr></thead><tbody id="recent"></tbody></table></div></div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<?php require __DIR__ . '/views/footer.php'; ?>
<script>
let ch = [];
async function load() {
  try {
    const s = await api('stats.php');
    document.getElementById('stats').innerHTML = [['Total Perangkat', s.total, '#6366f1'], ['Online', s.online, '#22c55e'], ['Offline', s.offline, '#ef4444'], ['Rata-rata Uptime', s.uptime + '%', '#f59e0b']]
      .map(([l, v, c]) => `<div class="card px-4 py-3.5 border-t-2" style="border-top-color:${c}"><div class="text-xl font-bold">${esc(v)}</div><div class="text-[11px] text-slate-500">${l}</div></div>`).join('');
    document.getElementById('recent').innerHTML = s.recent.map(d => `<tr>${rowHtml(d)}</tr>`).join('') || '<tr><td colspan="11" class="text-center text-slate-400 !py-8">Belum ada data. Jalankan cron/sync.php.</td></tr>';
    ch.forEach(c => c.destroy());
    const opt = t => ({maintainAspectRatio: false, cutout: '52%', plugins: {legend: {position: 'bottom', labels: {boxWidth: 24, boxHeight: 10, font: {size: 10}}}, title: {display: true, text: t, font: {size: 11}}}});
    ch = [new Chart(c1, {type: 'doughnut', data: {labels: ['Online', 'Offline'], datasets: [{data: [s.online, s.offline], backgroundColor: ['#48d1a0', '#ef6a5e']}]}, options: opt('Distribusi Status Perangkat')}),
          new Chart(c2, {type: 'doughnut', data: {labels: ['Excellent', 'Good', 'Fair', 'Poor', 'No Signal'], datasets: [{data: s.signal, backgroundColor: ['#48d1a0', '#55a7e6', '#f6c92b', '#ef6a5e', '#9ca3af']}]}, options: opt('Distribusi Sinyal PON')})];
  } catch (e) { toast(e.message); }
}
load(); setInterval(load, 60000);
</script></body></html>
