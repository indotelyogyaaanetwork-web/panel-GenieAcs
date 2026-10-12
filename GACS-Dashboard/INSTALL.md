# Panduan Instalasi GACS Dashboard v1.1.0-beta

## 1. Persyaratan
PHP 8.3+ (mysqli, json, curl, mbstring, xml, openssl), MySQL 5.7+/MariaDB 10.3+, Apache 2.4+ atau Nginx, Composer 2.x (opsional, untuk uji login API MikroTik).

## 2. Upload & dependensi
1. Upload seluruh isi folder ke document root (cPanel: `public_html`, CloudPanel/aaPanel: root situs).
2. (Opsional) `composer install --no-dev` di folder aplikasi.
3. Pastikan folder aplikasi dan `config/` dapat ditulis oleh PHP selama instalasi (untuk `.env` dan `config/installed.lock`).

## 3. Database
Buat database + user lewat cPanel "MySQL Databases", phpMyAdmin, atau CloudPanel/aaPanel. Catat host, nama DB, user, dan password.
Tidak perlu impor manual: `init.php` mengimpor `database.sql` otomatis (boleh juga diimpor manual lewat phpMyAdmin).

## 4. Konfigurasi
Cara mudah: buka `https://domain-anda/init.php`, isi data database, klik **Instal**. File `.env` (termasuk `APP_KEY` acak) dibuat otomatis.
Cara manual: salin `.env.example` ke `.env`, isi nilainya, isi `APP_KEY` dengan string acak 64 karakter (`php -r "echo bin2hex(random_bytes(32));"`).

## 5. Login pertama
Login default: **user1234 / mostech**. Aplikasi memaksa Anda mengganti password di Konfigurasi > Site Config sebelum bisa memakai halaman lain.
**Segera hapus `init.php` dari server.** (`init.php` menolak berjalan lagi setelah `config/installed.lock` ada, tetapi tetap hapus.)

## 6. Hubungkan layanan (Konfigurasi)
- **ACS Config**: host (tanpa `http://`), port 7557, user/password GenieACS → *Test Connection*.
- **MikroTik Config**: host, port 8728, user/password → *Test Connection*.
- **Bot Config**: Bot Token dari @BotFather + Chat ID → *Set Webhook* (wajib HTTPS).
Nilai password/token disimpan terenkripsi (AES-256-GCM) di tabel `settings`.

## 7. Sinkronisasi data (cron)
`*/5 * * * * php /path/ke/GACS-Dashboard/cron/sync.php`
(cPanel: Cron Jobs; CloudPanel/aaPanel: menu Cron). Skrip ini mengisi tabel `devices` dari GenieACS dan mengirim notifikasi Telegram saat ONU offline/online.
Uji tampilan tanpa GenieACS: `php cron/seed_demo.php`.

## 8. Sesuaikan parameter TR-069
Edit konstanta `MAP` di `lib/GenieACS.php` agar sesuai path parameter perangkat Anda (SN, MAC, IP, SSID, PPPoE, RX, suhu). Cek path aslinya di UI GenieACS.

## 9. Nginx (pengganti .htaccess)
```
location ~ ^/(config|lib|cron|views|vendor)/ { deny all; }
location ~ /\.(env|git) { deny all; }
location ~* \.(sql|md)$ { deny all; }
location ~ \.php$ { include fastcgi_params; fastcgi_pass unix:/run/php/php8.3-fpm.sock; fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; }
```

## 10. Checklist keamanan
`init.php` dihapus · password default diganti · HTTPS aktif · `.env` tidak bisa diakses dari web · port 7557/8728 dibatasi firewall hanya untuk server dashboard.
