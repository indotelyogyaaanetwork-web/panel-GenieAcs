const CSRF = document.querySelector('meta[name=csrf]')?.content;
const esc = s => String(s ?? '-').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
async function api(path, body) {
  const r = await fetch('api/' + path, {method: body ? 'POST' : 'GET', credentials: 'same-origin',
    headers: {'Content-Type': 'application/json', 'X-CSRF-Token': CSRF}, body: body ? JSON.stringify(body) : undefined});
  const j = await r.json().catch(() => ({error: 'Respons tidak valid'}));
  if (!r.ok) throw new Error(j.error || 'Terjadi kesalahan');
  return j;
}
function toast(m) { const t = document.getElementById('toast'); t.textContent = m; t.classList.remove('hidden'); setTimeout(() => t.classList.add('hidden'), 2800); }
// Ambang RX (dBm): >= -20 hijau, -20..-23 kuning, < -23 merah
const rxColor = v => v == null ? '#9ca3af' : v >= -20 ? '#198754' : v >= -23 ? '#f5b324' : '#dc3545';
function rowHtml(d) {
  const rx = d.rx_signal == null ? '-' : `<span class="badge" style="background:${rxColor(+d.rx_signal)}">${(+d.rx_signal).toFixed(2)} DBM</span>`;
  const on = d.status === 'online';
  return `<td><a href="device-detail.php?id=${+d.id}">${esc(d.serial_number)}</a></td><td>${esc(d.mac)}</td><td>${esc(d.type).toUpperCase()}</td><td>${esc(d.ip)}</td><td>${esc(d.ssid)}</td><td>${esc(d.pppoe)}</td><td>${rx}</td>`
    + `<td>${d.temp == null ? '-' : (+d.temp).toFixed(1) + '°C'}</td><td><span class="badge" style="background:${d.clients > 0 ? '#0d6efd' : '#6b7280'}">${+d.clients}</span></td>`
    + `<td><span class="text-[11px] font-bold px-2 py-1 rounded ${on ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-700'}">${on ? 'ON' : 'OFF'}</span></td>`
    + `<td><button class="btn btn-accent !px-2" title="Reboot" data-act="reboot" data-id="${+d.id}"><i class="fa-solid fa-bolt"></i></button></td>`;
}
document.addEventListener('click', async e => {
  const b = e.target.closest('[data-act]'); if (!b) return;
  if (b.dataset.act === 'reboot' && !confirm('Reboot perangkat ini?')) return;
  try { await api('action.php', {action: b.dataset.act, id: +b.dataset.id}); toast('Perintah ' + b.dataset.act + ' dikirim'); }
  catch (err) { toast(err.message); }
});
