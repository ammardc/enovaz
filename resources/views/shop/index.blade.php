@extends('layouts.app')
@section('title','Shop')
@section('content')
<div class="page-shell">
    <div class="max-w-7xl mx-auto">
        <!-- Page Header -->
        <div class="mb-12">
            <h1 class="text-4xl font-bold text-gray-900 mb-2">Produk Digital</h1>
            <p class="text-lg text-gray-600">Koleksi produk digital terbaik untuk kebutuhan Anda</p>
        </div>

        <!-- Balance Card (Auth Only) -->
        @auth
            <x-card hoverable class="mb-8 bg-gradient-to-r from-[#6b2cdd] to-[#8b5cf6] text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white/80 mb-1">Saldo Anda</p>
                        <h3 class="text-3xl font-bold">Rp{{ number_format($balance ?? 0,0,',','.') }}</h3>
                    </div>
                    <x-button variant="secondary" href="{{ route('wallet.topup') }}" class="bg-white text-[#6b2cdd] hover:bg-gray-100">
                        Top up Saldo
                    </x-button>
                </div>
            </x-card>
        @endauth

        <!-- Search & Filter Section -->
        <div class="mb-8 space-y-6">
            <!-- Search Form -->
            <form class="search-form" action="{{ route('shop') }}" method="get" role="search">
                <div class="flex gap-2">
                    <x-input
                        name="q"
                        type="search"
                        placeholder="Cari produk digital..."
                        value="{{ request('q') }}"
                        class="flex-1"
                    />
                    <x-button variant="primary" type="submit">
                        Cari
                    </x-button>
                </div>
                @if(request('kategori'))
                    <input type="hidden" name="kategori" value="{{ request('kategori') }}">
                @endif
            </form>

            <!-- Category Filter -->
            <div class="flex flex-wrap gap-2">
                <x-button
                    variant="{{ !request('kategori') ? 'primary' : 'secondary' }}"
                    size="sm"
                    href="{{ route('shop',array_filter(['q'=>request('q')])) }}"
                >
                    Semua Kategori
                </x-button>

                @foreach($categories as $category)
                    <x-button
                        variant="{{ request('kategori') === $category->slug ? 'primary' : 'secondary' }}"
                        size="sm"
                        href="{{ route('shop',array_filter(['kategori'=>$category->slug,'q'=>request('q')])) }}"
                    >
                        {{ $category->name }}
                    </x-button>
                @endforeach
            </div>
        </div>

        <!-- Products Grid -->
        @forelse($products as $product)
            @if($loop->first)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @endif

            <a href="{{ route('products.show',$product->slug) }}" class="group">
                <x-card hoverable class="h-full flex flex-col overflow-hidden">
                    <!-- Product Image -->
                    <div class="relative mb-4 bg-gray-100 rounded-lg overflow-hidden h-48">
                        @if($product->media_path)
                            <img
                                src="{{ asset($product->media_path) }}"
                                alt="{{ $product->name }}"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                loading="lazy"
                            >
                        @else
                            <div class="w-full h-full bg-gradient-to-br from-[#f3e8ff] to-[#e9d5ff] flex items-center justify-center">
                                <span class="text-5xl">{{ $product->art_theme === 'sky' ? '🌥️' : '✦' }}</span>
                            </div>
                        @endif

                        <!-- Badge -->
                        <div class="absolute top-3 right-3">
                            @if($product->status === 'coming_soon')
                                <x-badge variant="warning">Segera Hadir</x-badge>
                            @elseif($product->badge)
                                <x-badge variant="success">{{ $product->badge }}</x-badge>
                            @else
                                <x-badge variant="primary">Digital</x-badge>
                            @endif
                        </div>
                    </div>

                    <!-- Product Info -->
                    <div class="flex-1 flex flex-col">
                        <h3 class="text-lg font-bold text-gray-900 mb-2 group-hover:text-[#6b2cdd] transition-colors">
                            {{ $product->name }}
                        </h3>

                        <!-- Stock Status -->
                        <div class="mb-4 flex-1">
                            @if($product->stock_count > 0)
                                <x-badge variant="success">
                                    ✓ Tersedia · {{ $product->stock_count }} stok
                                </x-badge>
                            @elseif($product->status === 'published')
                                <x-badge variant="error">
                                    Stok habis
                                </x-badge>
                            @else
                                <x-badge variant="warning">
                                    ⚠ Belum tersedia
                                </x-badge>
                            @endif
                        </div>

                        <!-- Price & CTA -->
                        <div class="flex items-end justify-between pt-4 border-t border-gray-200">
                            <div>
                                @if($product->from_price === null)
                                    <p class="text-sm text-gray-500">Harga menyusul</p>
                                @else
                                    <p class="text-2xl font-bold text-[#6b2cdd]">
                                        Rp{{ number_format($product->from_price,0,',','.') }}
                                    </p>
                                @endif
                            </div>
                            <div class="text-[#6b2cdd] group-hover:translate-x-1 transition-transform">
                                →
                            </div>
                        </div>
                    </div>
                </x-card>
            </a>

            @if($loop->last)
                </div>
            @endif
        @empty
            <!-- Empty State -->
            <x-alert variant="warning">
                <span>🔍</span>
                <div>
                    <p class="font-semibold text-gray-900">Tidak ada produk yang ditemukan</p>
                    <p class="text-sm text-gray-600 mt-1">Coba gunakan kata kunci atau kategori lain</p>
                </div>
            </x-alert>
        @endforelse

        <!-- Pagination -->
        @if($products->count() > 0)
            <div class="mt-12 flex justify-center">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
