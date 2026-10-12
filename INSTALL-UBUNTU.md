# Instalasi GACS Dashboard di Ubuntu (Proxmox)

Panduan untuk **Ubuntu 24.04 LTS** (VM maupun LXC di Proxmox) dengan Nginx, PHP-FPM, dan MariaDB.

> Ganti `10.0.0.50` dengan IP server dashboard dan `10.0.0.19` dengan IP GenieACS Anda.
> Panduan ini belum diuji di server nyata; uji perintahnya di server Anda.

## Daftar isi
0. [Siapkan VM atau LXC](#0-siapkan-vm-atau-lxc-di-proxmox)
1. [Pasang paket](#1-pasang-paket)
2. [Siapkan database](#2-siapkan-database)
3. [Ambil kode aplikasi](#3-ambil-kode-aplikasi)
4. [Konfigurasi Nginx](#4-konfigurasi-nginx)
5. [Jalankan instalasi](#5-jalankan-instalasi)
6. [Kunci setelah instalasi](#6-kunci-setelah-instalasi)
7. [Hubungkan layanan](#7-hubungkan-layanan)
8. [Cron sinkronisasi](#8-cron-sinkronisasi)
9. [Firewall](#9-firewall)
10. [HTTPS dan Telegram](#10-https-dan-telegram-opsional)
11. [Pemecahan masalah](#pemecahan-masalah)

## 0. Siapkan VM atau LXC di Proxmox
- Spesifikasi cukup: 1 vCPU, 1 GB RAM, disk 10 GB.
- Beri IP statis (netplan atau DHCP reservation di router).
- Server harus bisa menjangkau GenieACS (port 7557) dan MikroTik (port 8728). Uji setelah login SSH:

```bash
curl -s "http://10.0.0.19:7557/devices?projection=_id&limit=1"
nc -zv IP_MIKROTIK 8728
```

## 1. Pasang paket
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx mariadb-server php8.3-fpm php8.3-cli php8.3-mysql php8.3-curl php8.3-mbstring php8.3-xml unzip git composer
```
Pada Ubuntu 22.04, tambahkan PPA PHP lebih dulu: `sudo add-apt-repository ppa:ondrej/php && sudo apt update`.

## 2. Siapkan database
```bash
sudo mysql_secure_installation
sudo mariadb
```
Di prompt MariaDB (ganti password dengan yang kuat):
```sql
CREATE DATABASE gacs CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'gacs'@'localhost' IDENTIFIED BY 'PASSWORD_KUAT';
GRANT ALL PRIVILEGES ON gacs.* TO 'gacs'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

## 3. Ambil kode aplikasi
Lewat GitHub:
```bash
sudo git clone https://github.com/USERNAME/gacs-dashboard.git /var/www/gacs
```
Atau lewat ZIP (kirim dengan `scp` lebih dulu):
```bash
sudo mkdir -p /var/www/gacs
sudo unzip GACS-Dashboard.zip -d /tmp
sudo cp -a /tmp/GACS-Dashboard/. /var/www/gacs/
```
Pasang dependensi (opsional, untuk uji login API MikroTik) dan beri izin sementara agar `init.php` bisa menulis `.env`:
```bash
cd /var/www/gacs && sudo composer install --no-dev
sudo chown -R www-data:www-data /var/www/gacs
```

## 4. Konfigurasi Nginx
```bash
sudo nano /etc/nginx/sites-available/gacs
```
Isi file:
```nginx
server {
    listen 80 default_server;
    server_name _;
    root /var/www/gacs;
    index index.php;

    location / { try_files $uri $uri/ =404; }
    location ~ ^/(config|lib|cron|views|vendor)/ { deny all; }
    location ~ /\.(?!well-known) { deny all; }
    location ~* \.(sql|md|json|lock)$ { deny all; }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
}
```
Aktifkan:
```bash
sudo ln -s /etc/nginx/sites-available/gacs /etc/nginx/sites-enabled/gacs
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx
```
Lebih suka Apache? Pasang `apache2 libapache2-mod-php8.3`, jalankan `a2enmod rewrite`, dan set `AllowOverride All` pada `/var/www/gacs` agar `.htaccess` bawaan berfungsi.

## 5. Jalankan instalasi
1. Buka `http://10.0.0.50/init.php`.
2. Isi host `localhost`, port `3306`, database `gacs`, user `gacs`, dan password dari langkah 2.
3. Klik **Instal**, lalu login dengan `user1234` / `mostech`. Anda wajib mengganti password pada login pertama.

## 6. Kunci setelah instalasi
```bash
sudo rm /var/www/gacs/init.php
sudo chown -R root:www-data /var/www/gacs
sudo find /var/www/gacs -type d -exec chmod 750 {} \;
sudo find /var/www/gacs -type f -exec chmod 640 {} \;
```
Agar `git pull` tidak mengembalikan `init.php`, blokir juga di Nginx dengan menambahkan `location = /init.php { deny all; }` di dalam blok `server`.

## 7. Hubungkan layanan
Di menu **Konfigurasi**:
- **ACS Config**: host `10.0.0.19`, port `7557`, lalu *Test Connection*.
- **MikroTik Config**: host dan port `8728`, lalu *Test Connection*.
- **Bot Config**: Bot Token dan Chat ID.

Sesuaikan konstanta `MAP` di `lib/GenieACS.php` dengan path parameter perangkat Anda, lalu `sudo systemctl reload php8.3-fpm`.

### Penyesuaian untuk GenieACS asli
1. Jalankan `sudo -u www-data php /var/www/gacs/cron/probe.php` (atau tambahkan nomor seri ONU di belakangnya). Skrip menampilkan path parameter sebenarnya (RX, suhu, SSID, PPPoE, IP, MAC); salin ke konstanta `MAP` di `lib/GenieACS.php`.
2. Di ACS Config, isi **Batas status online** minimal 2x interval *periodic inform* ONU. Nilai terlalu kecil membuat semua ONU tampak offline di antara inform dan membanjiri Telegram dengan notifikasi.
3. Pastikan jam server dashboard sinkron (`timedatectl`), karena status online dihitung dari waktu inform terakhir.

## 8. Cron sinkronisasi
```bash
sudo touch /var/log/gacs-sync.log && sudo chown www-data /var/log/gacs-sync.log
sudo crontab -u www-data -e
```
Tambahkan baris:
```
*/5 * * * * php /var/www/gacs/cron/sync.php >> /var/log/gacs-sync.log 2>&1
```
Uji manual: `sudo -u www-data php /var/www/gacs/cron/sync.php`.
Isi data contoh tanpa GenieACS: `sudo -u www-data php /var/www/gacs/cron/seed_demo.php`.

## 9. Firewall
```bash
sudo ufw allow OpenSSH
sudo ufw allow 80,443/tcp
sudo ufw enable
```

## 10. HTTPS dan Telegram (opsional)
- **Hanya di LAN**: `http://10.0.0.50` sudah cukup. Notifikasi Telegram ONU offline tetap berjalan lewat cron karena itu pengiriman keluar.
- **Perintah bot (`/status`, `/offline`)** memakai webhook, dan Telegram mewajibkan HTTPS publik. Arahkan domain ke server, buka port 80 dan 443, lalu:
  ```bash
  sudo apt install certbot python3-certbot-nginx
  sudo certbot --nginx -d domain-anda.com
  ```
  Setelah itu klik *Set Webhook* di Bot Config. Alternatif tanpa membuka port: Cloudflare Tunnel.

## Pemecahan masalah
| Gejala | Yang dicek |
|---|---|
| 502 Bad Gateway | `systemctl status php8.3-fpm` dan path socket di Nginx |
| Halaman kosong / error 500 | `sudo tail -f /var/log/nginx/error.log` |
| Test Connection ACS gagal | ulangi tes `curl` langkah 0, cek firewall server GenieACS |
| Dashboard kosong | jalankan `cron/sync.php` manual, cek log `/var/log/gacs-sync.log` |

**Update aplikasi:** `cd /var/www/gacs && sudo git pull`. File `.env` tidak tertimpa karena masuk `.gitignore`.

## Checklist keamanan
`init.php` dihapus · password default diganti · HTTPS aktif bila diakses dari internet · port 7557 dan 8728 dibatasi firewall hanya untuk server dashboard.
