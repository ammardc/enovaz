@extends('layouts.app')
@section('title', $product->name)

@section('content')
<div class="page-shell">
    <div class="max-w-4xl mx-auto">
        <!-- Back Link -->
        <a href="{{ route('shop') }}" class="inline-flex items-center gap-2 text-[#6b2cdd] hover:text-[#5520c0] font-medium mb-8 transition-colors">
            ← Kembali ke produk
        </a>

        <div class="grid md:grid-cols-2 gap-12">
            <!-- Product Image -->
            <div class="flex items-start">
                <div class="w-full rounded-2xl overflow-hidden bg-gray-100">
                    @if($product->media_path)
                        <img
                            src="{{ asset($product->media_path) }}"
                            alt="{{ $product->name }}"
                            class="w-full h-auto object-cover"
                        >
                    @else
                        <div class="w-full aspect-square bg-gradient-to-br from-[#f3e8ff] to-[#e9d5ff] flex items-center justify-center">
                            <span class="text-9xl">{{ $product->art_theme === 'sky' ? '🌥️' : '✦' }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Product Details -->
            <div class="flex flex-col space-y-6">
                <!-- Title & Description -->
                <div>
                    <h1 class="text-4xl font-bold text-gray-900 mb-4">{{ $product->name }}</h1>
                    @if(trim((string) $product->description) !== '')
                        <p class="text-lg text-gray-600 leading-relaxed">{{ $product->description }}</p>
                    @endif
                </div>

                <!-- Usage Instructions -->
                @if($product->status === 'published' && $product->usage_instructions)
                    <details class="group">
                        <summary class="cursor-pointer flex items-center gap-2 text-lg font-semibold text-gray-900 hover:text-[#6b2cdd] transition-colors">
                            <span class="inline-block transition-transform group-open:rotate-180">▶</span>
                            Cara Pakai
                        </summary>
                        <div class="mt-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
                            <p class="text-gray-700 whitespace-pre-line">{{ $product->usage_instructions }}</p>
                        </div>
                    </details>
                @endif

                <!-- Packages & Pricing -->
                <x-card class="space-y-6">
                    @if($variants->count() > 1)
                        <h2 class="text-2xl font-bold text-gray-900">Pilih Paket</h2>
                    @endif

                    <div class="space-y-4">
                        @foreach($variants as $variant)
                            <div class="flex items-end justify-between p-4 border border-gray-200 rounded-lg hover:border-[#6b2cdd] hover:bg-[#f3e8ff]/30 transition-colors">
                                <div class="flex-1">
                                    @if($variants->count() > 1 || $variant->name !== 'Paket utama')
                                        <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ $variant->name }}</h3>
                                    @endif
                                    <div class="text-3xl font-bold text-[#6b2cdd] mb-2">
                                        @if($variant->price_idr === null)
                                            <span class="text-gray-400">Harga menyusul</span>
                                        @else
                                            Rp{{ number_format($variant->price_idr, 0, ',', '.') }}
                                        @endif
                                    </div>
                                    <p class="text-sm text-gray-500">{{ $variant->stock_count }} stok tersedia</p>
                                </div>

                                @if($product->status === 'published' && $variant->active && $variant->stock_count > 0 && $variant->price_idr !== null)
                                    <a href="{{ route('checkout.show', $variant->id) }}" class="ml-4">
                                        <x-button variant="primary">
                                            Beli Sekarang
                                        </x-button>
                                    </a>
                                @else
                                    <x-button variant="secondary" disabled class="ml-4">
                                        Belum Tersedia
                                    </x-button>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <!-- Status Badge -->
                    <div class="pt-4 border-t border-gray-200">
                        @if($product->status === 'coming_soon')
                            <x-alert variant="warning">
                                <span>⏳</span>
                                <span>Produk ini akan segera tersedia</span>
                            </x-alert>
                        @elseif($product->stock_count === 0)
                            <x-alert variant="error">
                                <span>✕</span>
                                <span>Stok habis, silakan cek kembali nanti</span>
                            </x-alert>
                        @else
                            <x-alert variant="success">
                                <span>✓</span>
                                <span>Produk tersedia dan siap dipesan</span>
                            </x-alert>
                        @endif
                    </div>
                </x-card>

                <!-- CTA for Non-Auth Users -->
                @guest
                    <x-alert variant="info">
                        <span>ℹ️</span>
                        <div>
                            <p class="font-semibold">Masuk untuk membeli</p>
                            <p class="text-sm mt-1">Anda perlu login terlebih dahulu untuk melakukan pembelian</p>
                            <div class="flex gap-2 mt-3">
                                <a href="{{ route('login') }}" class="inline">
                                    <x-button variant="primary" size="sm">Masuk</x-button>
                                </a>
                                <a href="{{ route('register') }}" class="inline">
                                    <x-button variant="secondary" size="sm">Daftar</x-button>
                                </a>
                            </div>
                        </div>
                    </x-alert>
                @endguest
            </div>
        </div>
    </div>
</div>
@endsection
