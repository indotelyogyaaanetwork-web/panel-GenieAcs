<?php
require __DIR__ . '/../config/config.php'; require_login(true);
$s = rows("SELECT COUNT(*) total, COALESCE(SUM(status='online'),0) online,
  COALESCE(SUM(status='online' AND rx_signal>=-15),0) exc,
  COALESCE(SUM(status='online' AND rx_signal<-15 AND rx_signal>=-20),0) good,
  COALESCE(SUM(status='online' AND rx_signal<-20 AND rx_signal>=-23),0) fair,
  COALESCE(SUM(status='online' AND rx_signal<-23),0) poor FROM devices WHERE type='onu'")[0];
$total = (int)$s['total']; $on = (int)$s['online'];
json_out([
  'total' => $total, 'online' => $on, 'offline' => $total - $on, 'uptime' => $total ? round($on / $total * 100) : 0,
  'signal' => [(int)$s['exc'], (int)$s['good'], (int)$s['fair'], (int)$s['poor'], $total - $on],
  'recent' => rows("SELECT id,serial_number,type,mac,ip,ssid,pppoe,rx_signal,temp,clients,status FROM devices ORDER BY last_online DESC, id DESC LIMIT 5"),
]);
