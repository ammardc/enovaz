@extends('layouts.app')
@section('title', $product->name)

@section('content')
    <a class="back-link" href="{{ route('shop') }}">@include('partials.icon', ['name' => 'back']) Kembali ke produk</a>
    <div class="detail-grid">
        <div class="product-art detail-art {{ $product->media_path ? 'has-photo' : ($product->art_theme === 'sky' ? 'sky' : 'violet') }} {{ in_array($product->slug, ['chatgpt-plus', 'gemini-pro', 'claude', 'grok', 'youtube', 'spotify', 'duolingo', 'capcut'], true) ? 'photo-cover' : '' }}">
            @if($product->media_path)
                <img class="product-photo" src="{{ asset($product->media_path) }}" alt="{{ $product->name }}">
            @else
                <div class="art-panel"><span>{{ $product->art_theme === 'sky' ? 'G' : '✦' }}</span></div>
            @endif
        </div>
        <div class="detail-copy">
            <h1>{{ $product->name }}</h1>
            @if(trim((string) $product->description) !== '')
                <p class="product-description">{{ $product->description }}</p>
            @endif
            @if($product->status === 'published' && $product->usage_instructions)
                <details class="product-terms">
                    <summary>Cara pakai</summary>
                    <p>{{ $product->usage_instructions }}</p>
                </details>
            @endif
            <div class="info-card product-packages">
                @if($variants->count() > 1)
                    <h2>Pilih paket</h2>
                @endif
                @foreach($variants as $variant)
                    <div class="variant-row">
                        <div>
                            @if($variants->count() > 1 || $variant->name !== 'Paket utama')
                                <strong>{{ $variant->name }}</strong>
                            @endif
                            <div class="price detail-price {{ $variant->price_idr === null ? 'placeholder' : '' }}">{{ $variant->price_idr === null ? 'Harga menyusul' : 'Rp'.number_format($variant->price_idr, 0, ',', '.') }}</div>
                            <div class="small muted" style="margin-top:6px">{{ $variant->stock_count }} stok</div>
                        </div>
                        @if($product->status === 'published' && $variant->active && $variant->stock_count > 0 && $variant->price_idr !== null)
                            <a class="button" href="{{ route('checkout.show', $variant->id) }}">Beli sekarang</a>
                        @else
                            <button class="button" disabled>Belum tersedia</button>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
