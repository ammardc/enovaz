<?php

namespace App\Http\Controllers;

use App\Models\Product;

class ProductController extends Controller
{
    public function show(string $slug)
    {
        $product = Product::active()->withStock()->withSold()->where('slug', $slug)->firstOrFail();

        $related = Product::active()->withStock()->withSold()
            ->where('category', $product->category)
            ->whereKeyNot($product->id)
            ->limit(4)->get();

        return view('products.show', compact('product', 'related'));
    }
}
