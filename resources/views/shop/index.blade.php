@extends('layouts.app')
@section('title','Shop')
@section('content')
<div class="catalog-heading"><h1>Produk</h1></div>
@auth
<div class="balance-card"><div><span>Saldo</span><strong>Rp{{ number_format($balance ?? 0,0,',','.') }}</strong></div><a href="{{ route('wallet.topup') }}">Top up saldo</a></div>
@endauth
<div class="toolbar catalog-toolbar" id="catalog-search"><form class="search-form" action="{{ route('shop') }}" method="get" role="search">@include('partials.icon',['name'=>'search'])<input name="q" value="{{ request('q') }}" type="search" placeholder="Cari produk" aria-label="Cari produk">@if(request('kategori'))<input type="hidden" name="kategori" value="{{ request('kategori') }}">@endif</form></div>
<div class="chips" aria-label="Kategori produk"><a class="chip {{ !request('kategori') ? 'active' : '' }}" href="{{ route('shop',array_filter(['q'=>request('q')])) }}">Semua</a>@foreach($categories as $category)<a class="chip {{ request('kategori') === $category->slug ? 'active' : '' }}" href="{{ route('shop',array_filter(['kategori'=>$category->slug,'q'=>request('q')])) }}">{{ $category->name }}</a>@endforeach</div>
<div class="product-grid" style="margin-top:16px">
@forelse($products as $product)
<a class="product-card" href="{{ route('products.show',$product->slug) }}" aria-label="Lihat {{ $product->name }}">
    <div class="product-art {{ $product->media_path ? 'has-photo' : ($product->art_theme === 'sky' ? 'sky' : 'violet') }} {{ in_array($product->slug, ['chatgpt-plus', 'gemini-pro', 'claude', 'grok', 'youtube', 'spotify', 'duolingo', 'capcut'], true) ? 'photo-cover' : '' }}">
        <span class="badge">{{ $product->status === 'coming_soon' ? 'SEGERA HADIR' : ($product->badge ?: 'DIGITAL') }}</span>
        @if($product->media_path)<img class="product-photo" src="{{ asset($product->media_path) }}" alt="" loading="lazy">@else<div class="art-panel"><span>{{ $product->art_theme === 'sky' ? 'G' : '✦' }}</span></div>@endif
    </div>
    <div class="product-body"><h3>{{ $product->name }}</h3><div class="stock {{ $product->stock_count > 0 ? 'available' : '' }}">{{ $product->stock_count > 0 ? 'Tersedia · '.$product->stock_count.' stok' : ($product->status === 'published' ? 'Stok habis' : 'Belum tersedia') }}</div><div class="price-row"><span class="price {{ $product->from_price === null ? 'placeholder' : '' }}">{{ $product->from_price === null ? 'Harga menyusul' : 'Rp'.number_format($product->from_price,0,',','.') }}</span><span class="card-arrow">@include('partials.icon',['name'=>'arrow'])</span></div></div>
</a>
@empty<div class="empty-state"><strong>Belum ada produk yang cocok.</strong><p>Coba kata kunci atau kategori lain.</p></div>@endforelse
</div>
<div class="pagination">{{ $products->links() }}</div>
@endsection
