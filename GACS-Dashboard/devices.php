<?php
require __DIR__ . '/config/config.php'; require_login();
$title = 'Perangkat'; $active = 'devices'; require __DIR__ . '/views/header.php'; ?>
<div class="card">
  <div class="flex flex-wrap items-center gap-2 px-5 py-3.5 border-b"><h2 class="text-sm font-semibold mr-1">Daftar Perangkat</h2>
    <span class="badge bg-slate-600" id="bT"></span><span class="badge bg-green-700" id="bOn"></span><span class="badge bg-red-600" id="bOff"></span>
    <button class="btn btn-accent ml-auto" onclick="load()"><i class="fa-solid fa-rotate"></i> Segarkan</button></div>
  <div class="p-5">
    <div class="flex flex-wrap gap-2 mb-4" id="tabs"></div>
    <div class="flex flex-wrap items-center gap-3 mb-4 text-sm">
      <input id="q" class="inp !w-full sm:!w-[290px]" placeholder="Cari SN, MAC, atau IP...">
      <label class="flex items-center gap-2">Tampilkan <select id="per" class="inp !w-auto !py-1.5"><option>20</option><option>50</option><option>100</option></select> per halaman</label>
      <span class="sm:ml-auto text-slate-500 text-[13px]" id="info"></span></div>
    <div class="overflow-x-auto"><table class="tbl w-full"><thead><tr><th>SN</th><th>MAC</th><th>TIPE</th><th>IP</th><th>SSID</th><th>PPPOE</th><th>RX</th><th>SUHU</th><th>KLIEN</th><th>STATUS</th><th>AKSI</th></tr></thead><tbody id="rows"></tbody></table></div>
    <div class="flex justify-between items-center mt-4 text-sm"><button class="btn btn-gray" id="prev">Sebelumnya</button><span id="pg"></span><button class="btn btn-gray" id="next">Berikutnya</button></div>
  </div></div>
<?php require __DIR__ . '/views/footer.php'; ?>
<script>
const st = {type: 'onu', page: 1}, $ = id => document.getElementById(id); let timer, last = {};
async function load() {
  try {
    const p = new URLSearchParams({type: st.type, page: st.page, per: $('per').value, q: $('q').value.trim()});
    const j = last = await api('devices.php?' + p);
    $('rows').innerHTML = j.rows.map(d => `<tr>${rowHtml(d)}</tr>`).join('') || '<tr><td colspan="11" class="text-center text-slate-400 !py-8">Tidak ada perangkat.</td></tr>';
    $('bT').textContent = `TOTAL [${j.online + j.offline}]`; $('bOn').textContent = `ONLINE [${j.online}]`; $('bOff').textContent = `OFFLINE [${j.offline}]`;
    $('tabs').innerHTML = Object.entries(j.counts).map(([t, n]) => `<button data-t="${t}" class="px-4 py-1.5 rounded-full text-sm font-semibold ${t === st.type ? 'bg-indigo-600 text-white' : 'bg-white border text-indigo-600'}">${t.toUpperCase()} <span class="ml-1 text-xs opacity-80">${n}</span></button>`).join('');
    const pages = Math.max(1, Math.ceil(j.total / j.per));
    $('info').textContent = `Menampilkan ${j.total ? (j.page - 1) * j.per + 1 : 0}-${Math.min(j.page * j.per, j.total)} dari ${j.total} item`;
    $('pg').textContent = `Halaman ${j.page} dari ${pages}`; $('prev').disabled = j.page <= 1; $('next').disabled = j.page >= pages;
  } catch (e) { toast(e.message); }
}
$('tabs').onclick = e => { const b = e.target.closest('[data-t]'); if (b) { st.type = b.dataset.t; st.page = 1; load(); } };
$('q').oninput = () => { clearTimeout(timer); timer = setTimeout(() => { st.page = 1; load(); }, 300); };
$('per').onchange = () => { st.page = 1; load(); };
$('prev').onclick = () => { st.page--; load(); }; $('next').onclick = () => { st.page++; load(); };
load();
</script></body></html>
