# E-Novaz

Toko produk digital dengan pendekatan **Security by Design**. Proyek Akhir Pra-UTS Kelompok 2, RKS 2-A, Politeknik Negeri Cilacap.

- **Backend:** PHP 8.2+, Laravel 12
- **Tampilan:** Blade + Tailwind CSS 4 (Vite)
- **Database:** MariaDB (InnoDB), 8 tabel ERD laporan + 1 tabel `balance_ledger` untuk saldo
- **Pembayaran:** saldo deposit (diisi admin). QRIS DANA via Casaku sudah ada di kode tetapi sedang **maintenance** (`QRIS_ENABLED=false`)

## Fitur

**Pembeli**
- Daftar dan masuk dengan username + password (`/daftar`, `/masuk`). Password minimal 12 karakter (admin 16).
- Katalog, pencarian, filter kategori, dan detail produk.
- Beli langsung pakai saldo: stok dikunci, saldo dipotong, dan akun diserahkan dalam satu transaksi database.
- Riwayat pesanan dan tombol buka kredensial setelah lunas.
- Saldo deposit dan riwayat mutasi. Saldo ditambah admin di menu Admin > Pengguna (isi saldo via QRIS sedang maintenance).
- Profil: ubah nama dan ganti password (wajib password lama; perangkat lain otomatis keluar).
- CAPTCHA gambar buatan sendiri (PHP GD, tanpa layanan pihak ketiga) di login dan daftar; sekali pakai, kedaluwarsa 2 menit, jawaban hanya disimpan sebagai HMAC di sesi.
- 2FA (TOTP RFC 6238, tanpa library/layanan luar) wajib untuk admin: login = password + CAPTCHA + kode 6 digit authenticator. Rahasia terenkripsi (CredentialVault, AAD per user), kode tidak bisa dipakai ulang, 5 kode salah = ulang dari awal. Setel 2FA butuh konfirmasi ulang password. HP hilang: `php artisan enovaz:2fa-reset <username>` (hanya dari terminal server).

**Admin**
- Ringkasan penjualan.
- Produk: tambah, edit, nonaktifkan.
- Stok: tambah massal, disimpan terenkripsi.
- Transaksi, termasuk penanganan status `review_required`.
- Kelola role pengguna, tambah saldo pembeli, dan lihat audit log.
- 2FA wajib untuk akun admin.

## Menjalankan di laptop Windows (XAMPP)

Syarat: **XAMPP** (PHP 8.2+), **Node.js LTS**, **Git**. Composer tidak wajib (diunduh otomatis).

1. Buka terminal di folder project, jalankan **`.\setup.bat -OwnDb`** (disarankan: database mandiri di folder project,
   tidak perlu MySQL XAMPP menyala dan tidak perlu password root XAMPP).
   Tanpa `-OwnDb`, skrip memakai MySQL XAMPP: nyalakan dulu di XAMPP Control Panel dan masukkan password root bila ditanya.
2. Skrip mengaktifkan ekstensi PHP (dengan cadangan `php.ini`), memasang dependensi, membangun tampilan,
   membuat `.env` dengan kunci & password acak, membuat database + akun terbatas, migrasi, seed, dan GRANT.
   Password admin ditampilkan sekali di akhir (juga tersimpan di `.env`).
3. Klik dua kali **`jalankan.bat`**, buka http://localhost:8000 (alamat untuk laptop lain di jaringan ikut ditampilkan).

Pakai data dari tim: taruh `.env` dari tim di folder project, lalu `.\setup.bat -OwnDb -Dump _untuk-tim\enovaz_db.sql`.
`.env` harus sama dengan pembuat dump, karena stok dienkripsi dengan `CREDENTIAL_KEY`.

| Skrip | Fungsi |
|---|---|
| `setup.bat` | Pasang / perbarui (aman dijalankan berulang). Opsi: `-XamppPath D:\xampp`, `-Dump file.sql`, `-OwnDb` |
| `setup.bat -OwnDb` | Database mandiri di folder `.mariadb` (port 3308, hanya 127.0.0.1): tidak perlu MySQL XAMPP menyala, tidak perlu password root XAMPP. `jalankan.bat` menyalakannya otomatis |
| `jalankan.bat` | Server di `0.0.0.0:8000` + scheduler. Port lain: `jalankan.bat 8080` |
| `backup.bat` | Dump database ke `backups\` (akun migrator, bukan root) |
| `restore.bat file.sql` | Pulihkan dump (otomatis backup isi lama dulu, minta konfirmasi `YA`) |

`.env`, `backups/`, dan `_untuk-tim/` tidak ikut ke GitHub (lihat `.gitignore`).

### Mode simulasi pembayaran

Dengan `CASAKU_DRIVER=fake` dan `APP_ENV=local`, halaman bayar menampilkan tombol **"Simulasi: tandai sudah dibayar"**. Alur lengkapnya bisa diuji tanpa uang sungguhan: checkout, simulasi bayar, cek status, lalu buka akun. Mode ini otomatis ditolak bila `APP_ENV=production`.

### Mengaktifkan Casaku sungguhan

```env
CASAKU_DRIVER=live
CASAKU_LICENSE_KEY=...        # dari dashboard Casaku, hanya disimpan di server
CASAKU_QR_ID=...              # ID QRIS DANA E-Novaz
CASAKU_PACKAGE_IDS=id.dana
CASAKU_WEBHOOK_SECRET=...     # opsional, untuk webhook
```

URL webhook (opsional): `https://domain-kamu/webhooks/casaku`.

Adapter memakai endpoint dari dokumentasi publik Casaku:
- `POST /api/generate/v2/qris`
- `POST /api/generate/check-status`
- `POST /api/generate/cancel-status`

Sebelum dipakai untuk transaksi nyata, cocokkan nama field respons dengan respons asli akun merchant kalian di `app/Services/Casaku/HttpCasakuGateway.php`. Pastikan juga aplikasi listener Android Casaku aktif.

### Pengujian

```bash
php artisan test
```

Tes di `tests/Feature/SecurityTest.php` mencakup:
- Manipulasi role saat daftar.
- Hash Argon2id.
- Penguncian login.
- IDOR pada pesanan.
- Akses admin oleh pembeli.
- Pemalsuan status bayar dan pemrosesan idempoten.
- Pelepasan reservasi dan pembayaran terlambat yang masuk review.
- Ciphertext yang dipindah atau diubah.
- Webhook palsu.

## Pemetaan ke laporan

| Ancaman (Bab 3) | Kontrol di kode |
|---|---|
| SQL Injection | Query builder Eloquent (parameter terikat), wildcard LIKE di-escape, kolom urutan dari daftar tetap (`ProductController`) |
| IDOR detail pesanan | `Controller::ownedOrder()`: `WHERE code = ? AND user_id = ?` dari sesi; selain pemilik mendapat 404 |
| Paparan kredensial | Password login Argon2id (`config/hashing.php`). Kredensial produk dienkripsi XChaCha20-Poly1305 dengan nonce unik, tag, dan AAD per produk (`CredentialVault`), kunci terpisah `CREDENTIAL_KEY`. Isi kredensial tidak pernah dicatat |
| Pemalsuan status bayar | Status hanya dari API Casaku (`PaymentService::verify`), ID transaksi dicocokkan, nominal webhook dicocokkan, HMAC webhook diverifikasi |
| Pemeriksaan berulang | `fulfil()` mengunci baris pembayaran (`lockForUpdate`), keluar bila sudah `paid`, dan `deliveries.digital_account_id` UNIQUE |
| Excessive privilege DBMS | `database/sql/grants.sql`: akun runtime `enovaz_app` per tabel, tanpa DDL/GRANT, `audit_logs` dan `deliveries` tanpa UPDATE/DELETE |
| Manipulasi / pemakaian ganda saldo | Saldo hanya diubah `BalanceService` di dalam transaksi dengan `SELECT ... FOR UPDATE` pada baris user. `CHECK (balance >= 0)` di DB. Ledger `balance_ledger` append-only: akun `enovaz_app` hanya SELECT, INSERT. `UNIQUE(order_id, type)` menolak kredit/debit ganda untuk pesanan yang sama. Kolom `balance` tidak fillable. Harga diambil dari DB, bukan dari form. Saldo top-up baru masuk setelah Casaku mencatat lunas |
| Race condition stok terakhir | Transaksi InnoDB + `SELECT ... FOR UPDATE` saat reservasi dan penyerahan, cek ulang status |
| Penebakan password / sesi | 5 percobaan per username+IP per menit, throttle per IP, `session()->regenerate()` setelah login, invalidate saat logout, `AuthenticateSession` mengeluarkan sesi lain setelah ganti password, cookie HttpOnly (Secure via `SESSION_SECURE_COOKIE=true` di HTTPS) |
| Manipulasi role | `role` tidak fillable, selalu `pembeli` saat daftar; middleware `role:` di setiap rute; ubah role butuh autentikasi ulang dan dicatat |

Kontrol tambahan:
- Header keamanan (CSP, HSTS, X-Frame-Options) di production.
- `Cache-Control: no-store` di halaman kredensial dan pembayaran.
- Maksimal 3 tagihan menggantung per pembeli (mencegah penimbunan stok).
- Admin tidak bisa memenuhi pesanan bila Casaku tidak mencatat lunas.

## Perbedaan kecil dari rancangan laporan

- `products`: tambah kolom `slug`, `category`, dan `image_path` untuk URL, kategori ala marketplace, dan gambar.
- `orders`: tambah kolom `code`, nomor pesanan yang tampil ke pembeli. Akses tetap dibatasi pemilik.
- `payments`: tambah kolom `qr_string` untuk menampilkan QR, dan status `refunded`.
- `audit_logs`: tambah kolom `ip` dan `meta` (JSON, tanpa rahasia).
- `users`: tambah kolom `balance` (saldo saat ini, `CHECK >= 0`).
- `orders`: tambah kolom `kind` (`purchase`/`topup`) dan `payment_method` (`qris`/`balance`). Isi saldo memakai alur pesanan + pembayaran QRIS yang sama.
- Tabel baru `balance_ledger`: riwayat mutasi saldo (jenis, nominal +/-, saldo sesudah). Append-only.
- Keluhan pesanan belum punya tabel sendiri. Pembeli menghubungi admin lewat email dengan nomor pesanan, dan admin menangani lewat status `review_required`.
