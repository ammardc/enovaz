<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        foreach ([['ChatGPT', 'chatgpt', 1], ['Gemini', 'gemini', 2]] as [$name, $slug, $order]) {
            $category = \Illuminate\Support\Facades\DB::table('categories')->where('slug', $slug)->first();
            if (! $category) {
                $id = \Illuminate\Support\Facades\DB::table('categories')->insertGetId([
                    'name' => $name, 'slug' => $slug, 'sort_order' => $order, 'active' => true,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            } else $id = $category->id;
            $productName = $slug === 'chatgpt' ? 'ChatGPT Plus' : 'Gemini Pro';
            $productSlug = $slug === 'chatgpt' ? 'chatgpt-plus' : 'gemini-pro';
            $product = \Illuminate\Support\Facades\DB::table('products')->where('slug', $productSlug)->first();
            if (! $product) {
                $productId = \Illuminate\Support\Facades\DB::table('products')->insertGetId([
                    'category_id' => $id, 'name' => $productName, 'slug' => $productSlug,
                    'status' => 'coming_soon', 'art_theme' => $slug === 'chatgpt' ? 'violet' : 'sky',
                    'sort_order' => $order, 'created_at' => now(), 'updated_at' => now(),
                ]);
                \Illuminate\Support\Facades\DB::table('product_variants')->insert([
                    'product_id' => $productId, 'name' => 'Paket utama', 'limit_per_customer' => 1,
                    'active' => false, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
        $this->call(AdditionalProductsSeeder::class);
        foreach ([
            ['faq','Pertanyaan umum','Produk dan harga akan tersedia setelah rincian paket selesai diverifikasi. Hubungi kanal bantuan resmi yang tercantum di situs jika ada pertanyaan.'],
            ['ketentuan-pembelian','Ketentuan pembelian','Ketentuan pembelian akan diterbitkan sebelum toko menerima pembayaran.'],
            ['privasi','Kebijakan privasi','Kebijakan privasi akan diterbitkan sebelum toko menerima pembayaran.'],
            ['bantuan-refund','Bantuan & refund','Kebijakan bantuan dan refund akan diterbitkan sebelum toko menerima pembayaran.'],
        ] as [$slug,$title,$body]) {
            if (! \Illuminate\Support\Facades\DB::table('content_pages')->where('slug',$slug)->exists()) {
                \Illuminate\Support\Facades\DB::table('content_pages')->insert([
                    'slug'=>$slug,'title'=>$title,'body'=>$body,'version'=>'draft-1','published'=>true,
                    'created_at'=>now(),'updated_at'=>now(),
                ]);
            }
        }

        User::factory()->create([
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'role' => 'user',
            'is_active' => true,
        ]);
    }
}
