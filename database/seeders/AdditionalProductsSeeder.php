<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdditionalProductsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach ([
                ['Claude', 'claude', 'uploads/products/claude-scene.png', 3],
                ['Grok', 'grok', 'uploads/products/grok-scene.png', 4],
                ['YouTube', 'youtube', 'uploads/products/youtube-scene.png', 5],
                ['Spotify', 'spotify', 'uploads/products/spotify-scene.png', 6],
                ['Duolingo', 'duolingo', 'uploads/products/duolingo-scene.png', 7],
                ['CapCut', 'capcut', 'uploads/products/capcut-scene.png', 8],
            ] as [$name, $slug, $image, $order]) {
                $categoryId = DB::table('categories')->where('slug', $slug)->value('id');
                if ($categoryId === null) {
                    $categoryId = DB::table('categories')->insertGetId([
                        'name' => $name,
                        'slug' => $slug,
                        'sort_order' => $order,
                        'active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $productId = DB::table('products')->where('slug', $slug)->value('id');
                if ($productId === null) {
                    $productId = DB::table('products')->insertGetId([
                        'category_id' => $categoryId,
                        'name' => $name,
                        'slug' => $slug,
                        'status' => 'coming_soon',
                        'art_theme' => 'violet',
                        'sort_order' => $order,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                if (! DB::table('product_variants')->where('product_id', $productId)->exists()) {
                    DB::table('product_variants')->insert([
                        'product_id' => $productId,
                        'name' => 'Paket utama',
                        'limit_per_customer' => 1,
                        'active' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                if (! DB::table('product_media')->where('product_id', $productId)->exists()) {
                    DB::table('product_media')->insert([
                        'product_id' => $productId,
                        'path' => $image,
                        'alt' => 'Logo '.$name,
                        'primary' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });
    }
}
