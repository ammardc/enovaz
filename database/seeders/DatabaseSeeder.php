<?php

namespace Database\Seeders;

use App\Models\DigitalAccount;
use App\Models\Product;
use App\Models\User;
use App\Services\CredentialVault;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAdmin();
        $this->seedProducts();
    }

    private function seedAdmin(): void
    {
        $username = strtolower((string) env('ADMIN_USERNAME', 'admin'));

        if (User::where('username', $username)->exists()) {
            $this->command?->info("Admin '{$username}' sudah ada, dilewati.");

            return;
        }

        $password = (string) env('ADMIN_PASSWORD', '');
        $generated = false;
        if (strlen($password) < (int) config('enovaz.password_min.admin', 16)) {
            $password = Str::password(20, symbols: false);
            $generated = true;
        }

        $admin = new User;
        $admin->name = 'Administrator';
        $admin->username = $username;
        $admin->password_hash = $password;
        $admin->role = User::ROLE_ADMIN; // ditetapkan lewat tindakan administratif (seeder), bukan formulir publik
        $admin->save();

        $this->command?->info("Admin dibuat: {$username}");
        if ($generated) {
            $this->command?->warn("Password admin acak (simpan sekarang, tidak ditampilkan lagi): {$password}");
        }
    }

    /** Katalog contoh. Ganti dengan produk yang sumber dan izin distribusinya sudah diperiksa. */
    private function seedProducts(): void
    {
        if (Product::exists()) {
            return;
        }

        // Data DUMMY untuk demo: harga, durasi, dan stok bukan penawaran sungguhan.
        // Logo dari Simple Icons (database/seeders/logos), merek milik pemiliknya masing-masing.
        // [nama, kategori, harga, durasi, deskripsi, logo, stok]
        $items = [
            ['ChatGPT Plus', 'AI Tools', 85000, '1 bulan', 'Akses model GPT terbaru, batas pesan lebih tinggi, analisis file, dan pembuatan gambar.', 'openai', 12],
            ['Gemini Advanced', 'AI Tools', 75000, '1 bulan', 'Model Gemini paling canggih dari Google, terintegrasi dengan Gmail dan Docs, plus penyimpanan 2 TB.', 'googlegemini', 8],
            ['Claude Pro', 'AI Tools', 90000, '1 bulan', 'Batas penggunaan lebih besar, akses model terbaru, dan fitur Projects untuk kerja dokumen panjang.', 'claude', 6],
            ['Perplexity Pro', 'AI Tools', 60000, '1 bulan', 'Mesin jawab berbasis AI dengan pencarian Pro tanpa batas dan pilihan beberapa model.', 'perplexity', 3],
            ['YouTube Premium', 'Streaming', 25000, '1 bulan', 'Tonton tanpa iklan, putar di latar belakang, unduh video, dan YouTube Music Premium.', 'youtube', 15],
            ['Netflix Premium', 'Streaming', 45000, '1 bulan', 'Streaming film dan serial kualitas Ultra HD, satu profil pribadi.', 'netflix', 0],
            ['Spotify Premium', 'Streaming', 20000, '1 bulan', 'Musik tanpa iklan, mode offline, dan lewati lagu tanpa batas.', 'spotify', 10],
            ['Canva Pro', 'Desain', 30000, '1 bulan', 'Template premium, hapus latar sekali klik, brand kit, dan aset tanpa watermark.', 'canva', 9],
            ['Duolingo Super', 'Edukasi', 35000, '1 bulan', 'Belajar bahasa tanpa iklan, nyawa tak terbatas, dan latihan kesalahan pribadi.', 'duolingo', 7],
            ['Grammarly Premium', 'Produktivitas', 40000, '1 bulan', 'Koreksi tata bahasa, nada, dan kejelasan tulisan bahasa Inggris tingkat lanjut.', 'grammarly', 5],
            ['Notion Plus', 'Produktivitas', 30000, '1 bulan', 'Halaman dan blok tanpa batas, riwayat versi 30 hari, dan unggah file tanpa batas.', 'notion', 0],
            ['Zoom Pro', 'Produktivitas', 50000, '1 bulan', 'Rapat hingga 30 jam, 100 peserta, dan rekaman cloud 5 GB.', 'zoom', 4],
            ['NordVPN', 'VPN & Keamanan', 55000, '3 bulan', 'VPN cepat dengan ribuan server di 100+ negara, tanpa log, hingga 10 perangkat.', 'nordvpn', 8],
        ];

        $vault = app(CredentialVault::class);
        $canEncrypt = filled(config('enovaz.credential_key'));
        $disk = Storage::disk('public');

        foreach ($items as [$name, $category, $price, $duration, $desc, $logo, $stock]) {
            $imagePath = null;
            $source = __DIR__."/logos/{$logo}.svg";
            if (is_file($source)) {
                $imagePath = "products/{$logo}.svg";
                $disk->put($imagePath, file_get_contents($source));
            }

            $product = Product::create([
                'name' => $name,
                'slug' => Str::slug($name),
                'category' => $category,
                'description' => $desc,
                'price' => $price,
                'duration' => $duration,
                'is_active' => true,
                'image_path' => $imagePath,
            ]);

            if (! $canEncrypt) {
                continue;
            }

            // Stok contoh (bukan akun asli). Beberapa produk sengaja 0 untuk tampilan "Stok habis".
            for ($n = 1; $n <= $stock; $n++) {
                $email = Str::slug($name).'.demo'.$n.'@contoh.test';
                $pass = Str::password(12, symbols: false);
                DigitalAccount::create([
                    'product_id' => $product->id,
                    'email_enc' => $vault->encrypt($email, CredentialVault::aad($product->id, 'email')),
                    'password_enc' => $vault->encrypt($pass, CredentialVault::aad($product->id, 'password')),
                    'status' => DigitalAccount::AVAILABLE,
                ]);
            }
        }

        if (! $canEncrypt) {
            $this->command?->warn('CREDENTIAL_KEY kosong: produk dibuat tanpa stok. Isi kuncinya lalu tambah stok dari panel admin.');
        }
    }
}
