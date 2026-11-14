<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\Product;
use Illuminate\Http\Request;

/** Beranda sekaligus katalog: pencarian, filter kategori, dan urutan. */
class HomeController extends Controller
{
    private const SORTS = ['populer', 'terbaru', 'termurah', 'termahal'];

    public function index(Request $request)
    {
        $q = mb_substr(trim((string) $request->query('q', '')), 0, 60);
        $category = mb_substr(trim((string) $request->query('kategori', '')), 0, 50);
        $sort = in_array($request->query('urut'), self::SORTS, true) ? $request->query('urut') : 'populer';

        $query = Product::active()->withStock()->withSold();

        if ($q !== '') {
            // Nilai diikat sebagai parameter oleh query builder; wildcard dari pengguna di-escape.
            $like = '%'.addcslashes($q, '%_\\').'%';
            $query->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('category', 'like', $like));
        }
        if ($category !== '') {
            $query->where('category', $category);
        }

        // Produk kosong selalu di akhir. Kolom urutan dari daftar tetap, bukan dari input.
        $query->orderByRaw('CASE WHEN stock_count > 0 THEN 0 ELSE 1 END');
        match ($sort) {
            'terbaru' => $query->latest(),
            'termurah' => $query->orderBy('price'),
            'termahal' => $query->orderByDesc('price'),
            default => $query->orderByDesc('sold_count')->latest(),
        };

        $products = $query->paginate(24)->withQueryString();

        $all = Product::active()->get(['id', 'category', 'image_path', 'created_at']);
        $categories = $all->groupBy('category')->map(fn ($group, $name) => (object) [
            'name' => $name,
            'mark' => $group->sortBy('id')->map->brandMark()->filter()->first(),
        ])->sortBy('name')->values();

        // Pembelian terakhir (data asli) untuk notifikasi kecil di pojok
        $lastPurchase = Delivery::with(['orderItem.product', 'orderItem.order.user:id,username'])
            ->latest('delivered_at')
            ->first();

        return view('home', compact('products', 'categories', 'q', 'category', 'sort', 'lastPurchase'));
    }
}
