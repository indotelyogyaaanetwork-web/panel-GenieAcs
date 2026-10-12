<?php
require __DIR__ . '/config/config.php'; require_login();
$title = 'Peta Topologi'; $active = 'map'; require __DIR__ . '/views/header.php'; ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
<div class="card"><div class="flex flex-wrap items-center gap-2 px-5 py-3.5 border-b"><h2 class="text-sm font-semibold mr-auto">Topologi Jaringan</h2>
  <button class="btn btn-gray" id="add"><i class="fa-solid fa-pen"></i> Gambar garis</button>
  <button class="btn btn-accent" id="save"><i class="fa-regular fa-floppy-disk"></i> Simpan topologi</button></div>
  <div id="map" style="height:65vh" class="rounded-b-lg"></div></div>
<p class="text-xs text-slate-500 mt-2">Seret titik sudut untuk mengubah garis; klik kanan sudut untuk menghapus. Seret penanda perangkat untuk menyimpan koordinatnya.</p>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Leaflet.Editable/1.2.0/Leaflet.Editable.min.js"></script>
<?php require __DIR__ . '/views/footer.php'; ?>
<script>
const map = L.map('map', {editable: true}).setView([-7.8, 110.4], 11), lines = [];
L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom: 19, attribution: '&copy; OpenStreetMap'}).addTo(map);
const addLine = (pts, name = 'Link') => { const l = L.polyline(pts, {color: '#4f46e5', weight: 4}).addTo(map); l.name = name; l.enableEdit(); l.on('contextmenu', () => { map.removeLayer(l); lines.splice(lines.indexOf(l), 1); }); lines.push(l); return l; };
(async () => {
  const j = await api('topology.php');
  j.lines.forEach(l => addLine(l.points, l.name));
  j.nodes.forEach(n => L.marker([n.lat, n.lng], {draggable: true, title: n.serial_number}).addTo(map).bindPopup(`${esc(n.serial_number)} (${esc(n.type)}) - ${esc(n.status)}`)
    .on('dragend', e => { const p = e.target.getLatLng(); api('topology.php', {action: 'setpos', id: +n.id, lat: p.lat, lng: p.lng}).then(() => toast('Posisi disimpan')); }));
  const all = [...j.nodes.map(n => [n.lat, n.lng]), ...j.lines.flatMap(l => l.points)]; if (all.length) map.fitBounds(all, {padding: [30, 30]});
})().catch(e => toast(e.message));
document.getElementById('add').onclick = () => {
  map.once('editable:drawing:commit', e => { e.layer.name = 'Link'; e.layer.on('contextmenu', () => { map.removeLayer(e.layer); lines.splice(lines.indexOf(e.layer), 1); }); lines.push(e.layer); });
  map.editTools.startPolyline(undefined, {color: '#4f46e5', weight: 4});
};
document.getElementById('save').onclick = () => api('topology.php', {action: 'save', lines: lines.map(l => ({name: l.name || 'Link', points: l.getLatLngs().map(p => [p.lat, p.lng])}))}).then(() => toast('Topologi disimpan')).catch(e => toast(e.message));
</script></body></html>
