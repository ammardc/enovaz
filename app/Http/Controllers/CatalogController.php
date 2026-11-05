<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CatalogController extends Controller
{
    public function index(Request $request)
    {
        $categories = DB::table('categories')->whereNull('deleted_at')->where('active', true)->orderBy('sort_order')->get();
        $products = DB::table('products as p')
            ->join('categories as c', 'c.id', '=', 'p.category_id')
            ->whereNull('p.deleted_at')->whereNull('c.deleted_at')
            ->where('c.active', true)->whereIn('p.status', ['published', 'coming_soon'])
            ->when($request->filled('kategori'), fn ($q) => $q->where('c.slug', $request->string('kategori')))
            ->when($request->filled('q'), fn ($q) => $q->where(function ($where) use ($request) {
                $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim($request->string('q'))).'%';
                $where->where('p.name', 'like', $term)->orWhere('p.description', 'like', $term)
                    ->orWhereExists(function ($sub) use ($term) {
                        $sub->selectRaw('1')->from('product_variants as sv')
                            ->whereColumn('sv.product_id','p.id')->where('sv.name','like',$term);
                    });
            }))
            ->select('p.*', 'c.name as category_name')
            ->selectSub(function ($q) {
                $q->from('product_media as m')->select('m.path')->whereColumn('m.product_id','p.id')
                    ->orderByDesc('m.primary')->orderByDesc('m.id')->limit(1);
            }, 'media_path')
            ->selectSub(function ($q) {
                $q->from('product_variants as v')->selectRaw('MIN(v.price_idr)')->whereColumn('v.product_id', 'p.id')->where('v.active', true);
            }, 'from_price')
            ->selectSub(function ($q) {
                $q->from('inventory_units as i')->join('product_variants as v', 'v.id', '=', 'i.variant_id')
                    ->selectRaw('COUNT(*)')->whereColumn('v.product_id', 'p.id')->where('v.active', true)->where('i.status', 'available');
            }, 'stock_count')
            ->orderBy('p.sort_order')->paginate(12)->withQueryString();
        $balance = auth()->check() ? (int) DB::table('wallet_accounts')->where('user_id', auth()->id())->value('balance_idr') : null;
        return view('shop.index', compact('categories', 'products', 'balance'));
    }

    public function show(string $slug)
    {
        $product = DB::table('products')->where('slug', $slug)->whereNull('deleted_at')->whereIn('status', ['published','coming_soon'])->first();
        abort_unless($product, 404);
        $product->media_path = DB::table('product_media')->where('product_id',$product->id)->orderByDesc('primary')->orderByDesc('id')->value('path');
        $variants = DB::table('product_variants as v')->where('v.product_id', $product->id)
            ->select('v.*')->selectSub(function ($q) {
                $q->from('inventory_units')->selectRaw('COUNT(*)')->whereColumn('variant_id', 'v.id')->where('status', 'available');
            }, 'stock_count')->orderBy('v.id')->get();
        return view('shop.show', compact('product', 'variants'));
    }

    public function page(string $slug)
    {
        $page = DB::table('content_pages')->where('slug', $slug)->where('published', true)->first();
        abort_unless($page, 404);
        return view('content.page', compact('page'));
    }
}
