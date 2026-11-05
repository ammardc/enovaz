# E-Novaz Store

Toko produk digital berbasis Laravel 12, Blade, Tailwind/Vite, dan database. Tampilan mengikuti pola referensi mobile yang diberikan: katalog dua kolom, chip kategori, kartu saldo, serta navigasi Shop · Orders · Wallet. Produk awal adalah **ChatGPT Plus** dan **Gemini Pro**. Keduanya disimpan sebagai contoh **Segera hadir**, tanpa harga dan stok nyata.

## Status saat ini

- Katalog, pencarian, filter, detail produk, akun email dengan verifikasi/reset, Google OAuth melalui Socialite, Orders, Wallet, top up QRIS, checkout saldo/QRIS, pengiriman stok terenkripsi, refund, dan panel admin sudah memiliki alur kode.
- Admin memiliki produk/varian/kategori/media/stok, konten berversi, pembayaran, pelanggan, koreksi saldo dengan persetujuan admin kedua, laporan dan ekspor CSV per periode, audit, serta TOTP dua langkah.
- `PAYMENTS_ENABLED=false` dan `CASAKU_LIVE_VERIFIED=false` secara default. **Tidak ada pembayaran live yang dibuka.** Respons Casaku live, listener, webhook di domain nyata, dan transaksi uang masuk belum diuji.
- Data paket, harga, stok yang berhak dijual, kebijakan final, SMTP, Google OAuth, Telegram resmi, dan hosting produksi belum tersedia. Jangan menyalakan penjualan sebelum seluruhnya diisi dan diuji.
- Bagian PRD yang masih perlu dilengkapi sebelum klaim rilis penuh: undangan/izin admin granular dan pengelolaan sesi, kebijakan refund parsial, backup/restore teruji, pengukuran performa nyata, pengujian konkurensi di MySQL, serta uji browser seluruh status yang diminta. Dashboard listener Casaku masih memerlukan validasi respons merchant nyata.

## Menjalankan lokal di Windows + XAMPP

Gunakan PHP 8.2+ dengan `pdo_sqlite`/`pdo_mysql`, OpenSSL, mbstring, fileinfo, ZIP, dan GD. Composer lokal yang diunduh saat pembuatan ada di `../composer.phar`.

```powershell
cd C:\Users\user\Documents\enovazz\store
& 'C:\xampp\php\php.exe' -d extension=php_zip.dll ..\composer.phar install
npm ci
Copy-Item .env.example .env
& 'C:\xampp\php\php.exe' artisan key:generate
& 'C:\xampp\php\php.exe' -r 'echo base64_encode(random_bytes(32));'
```

Masukkan hasil perintah terakhir ke `INVENTORY_KEY` **di file `.env` lokal**. Jangan kirim kunci ini di chat atau commit ke Git. Untuk lokal, `DB_CONNECTION=sqlite`; buat `database/database.sqlite` jika belum ada. Setelah itu:

```powershell
& 'C:\xampp\php\php.exe' artisan migrate --seed
npm run build
& 'C:\xampp\php\php.exe' -d extension=php_gd.dll artisan serve --host=127.0.0.1 --port=8000
```

Buka `http://127.0.0.1:8000`. Jalankan queue dan scheduler di terminal terpisah untuk alur pembayaran:

```powershell
& 'C:\xampp\php\php.exe' artisan queue:work
& 'C:\xampp\php\php.exe' artisan schedule:work
```

Untuk admin pertama: daftar akun, verifikasi email melalui tautan yang dikirim SMTP (di lokal, `MAIL_MAILER=log` menulis ke `storage/logs/laravel.log`), lalu di terminal server jalankan `php artisan store:owner email@domain.tld`. Buka `/admin` dan aktifkan TOTP. Proses promosi owner hanya boleh dilakukan oleh operator server yang berwenang.

## Konfigurasi produksi

Gunakan VPS/hosting PHP-FPM + Nginx, MySQL/MariaDB InnoDB, HTTPS, worker queue, scheduler, dan backup yang diuji restore. Document root harus `store/public`. Atur `.env` server dengan `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://domain-anda`, `DB_CONNECTION=mysql`, SMTP, Google client ID/secret/redirect, Telegram resmi, `INVENTORY_KEY`, dan kunci Casaku. Rahasia tidak boleh masuk repository. Gunakan `php artisan config:cache`, `route:cache`, migrasi, worker/scheduler supervisi, serta pembatasan cache publik untuk akun, tagihan, wallet, pesanan, dan admin.

Casaku menggunakan API QRIS v2, `x-license-key`, webhook HMAC pada **raw body**, dan pengecekan status server. Adapter merujuk [portal resmi Casaku](https://casaku.id/docs); field respons final tetap harus dicocokkan dengan akun merchant Anda. Saat QRIS diuji live, setel `CASAKU_LIVE_VERIFIED=true` **hanya setelah** invoice order dan top up nyata terbukti masuk, webhook cocok, listener sehat, duplikat aman, serta rekonsiliasi berjalan. Setelah semua syarat lain siap, baru setel `PAYMENTS_ENABLED=true`. Bila layanan dibuka lalu ada masalah, matikan flag itu untuk menghentikan invoice baru; rekonsiliasi invoice lama tetap dapat berjalan selama kredensial Casaku tersedia.

Sebelum menerbitkan produk, admin harus mengisi deskripsi, akses, region, durasi, harga, aturan refund, instruksi, stok, dan kebijakan situs final. Seeder tidak akan menimpa konten yang sudah diedit. Setelah login dan 2FA, buka `/admin/stok`, pilih paket, lalu tambah satu unit melalui formulir atau impor CSV berheader `code,account,password,instructions`; file CSV tidak boleh diletakkan di `public`.

## Pemeriksaan

```powershell
& 'C:\xampp\php\php.exe' artisan test
npm run build
& 'C:\xampp\php\php.exe' artisan view:cache
```

Tes lokal memakai SQLite dan respons Casaku tiruan. Transaksi live serta uji konkurensi MySQL tetap wajib sebelum produksi.
