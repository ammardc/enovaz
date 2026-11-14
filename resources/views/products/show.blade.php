@extends('layouts.app')
@section('title', $product->name)

@section('content')
@php($stock = $product->stock())
@php($max = min($stock, config('enovaz.max_quantity')))
<nav class="mb-4 text-sm text-muted">
    <a href="{{ route('home') }}" class="hover:text-brand-600">Beranda</a> / {{ $product->name }}
</nav>

@php($mark = $product->brandMark())
<div class="grid items-start gap-5 lg:grid-cols-[1fr_380px]">
    {{-- Info produk --}}
    <div class="panel min-w-0">
        <div class="flex items-center gap-4">
            <div class="size-16 shrink-0 overflow-hidden rounded-2xl border border-line">
                @include('partials.thumb', ['product' => $product, 'class' => 'size-full', 'text' => 'text-xl', 'compact' => true])
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-muted">{{ $product->category }}</p>
                <h1 class="text-2xl leading-tight font-semibold tracking-tight sm:text-3xl">{{ $product->name }}</h1>
                <p class="num mt-0.5 text-sm text-muted">{{ number_format($product->sold(), 0, ',', '.') }} terjual</p>
            </div>
        </div>

        <div class="mt-5 flex flex-wrap items-end gap-x-3 gap-y-1 border-y border-line py-4">
            <span class="num text-3xl font-semibold tracking-tight">{{ rupiah($product->price) }}</span>
            <span class="pb-1 text-sm text-muted">/ {{ $product->duration }}</span>
            <span class="ml-auto pb-1 text-sm font-semibold {{ $stock === 0 ? 'text-red-600' : ($stock <= config('enovaz.low_stock') ? 'text-amber-600' : 'text-emerald-700') }}">{{ $stock === 0 ? 'Stok habis' : 'Stok '.$stock }}</span>
        </div>

        <h2 class="mt-5 font-bold">Deskripsi</h2>
        <p class="mt-1.5 text-sm whitespace-pre-line text-muted">{{ $product->description }}</p>

        <h2 class="mt-5 font-bold">Garansi</h2>
        <ul class="mt-1.5 space-y-1.5 text-sm text-muted">
            <li class="flex gap-2"><svg class="mt-0.5 size-4 shrink-0 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5 9-10"/></svg>Berlaku selama {{ $product->duration }}.</li>
            <li class="flex gap-2"><svg class="mt-0.5 size-4 shrink-0 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5 9-10"/></svg>Jangan ubah email/password akun.</li>
        </ul>
    </div>

    {{-- Beli --}}
    <div class="panel min-w-0 lg:sticky lg:top-24">
        @if($stock === 0)
            <div class="rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800">Stok habis.</div>
        @elseif(auth()->check() && auth()->user()->isAdmin())
            <div class="rounded-xl bg-brand-50 px-4 py-3 text-sm text-brand-800">Admin tidak bisa membeli.</div>
            <a href="{{ route('admin.stock.index', $product) }}" class="btn btn-soft mt-3 w-full">Kelola stok</a>
        @else
            <form method="POST" action="{{ route('checkout.store', $product->slug) }}" data-once>
                @csrf
                <label for="quantity" class="label">Jumlah</label>
                <div class="inline-flex overflow-hidden rounded-xl border border-line" data-qty data-price="{{ $product->price }}" data-subtotal="subtotal">
                    <button type="button" class="h-10 w-10 bg-canvas text-lg" data-step="-1" aria-label="Kurangi">−</button>
                    <input id="quantity" name="quantity" type="number" min="1" max="{{ $max }}" value="{{ old('quantity', 1) }}" class="num h-10 w-14 text-center outline-none [appearance:textfield]" inputmode="numeric">
                    <button type="button" class="h-10 w-10 bg-canvas text-lg" data-step="1" aria-label="Tambah">+</button>
                </div>
                <p class="help">Maks. {{ $max }}</p>
                @error('quantity')<p class="error">{{ $message }}</p>@enderror

                <div class="my-4 flex items-center justify-between">
                    <span class="text-muted">Subtotal</span>
                    <b id="subtotal" class="num">{{ rupiah($product->price * (int) old('quantity', 1)) }}</b>
                </div>

                @auth
                    @php($qris = config('enovaz.qris_enabled'))
                    @php($method = $qris ? old('method', 'qris') : 'balance')
                    <p class="label">Bayar dengan</p>
                    <div class="mb-4 grid grid-cols-2 gap-2">
                        <label class="pay-option {{ $qris ? '' : 'cursor-not-allowed opacity-60' }}">
                            <input type="radio" name="method" value="qris" class="peer sr-only" @checked($method === 'qris') @disabled(! $qris)>
                            <span class="pay-card">
                                <img src="{{ asset('images/pay/qris.svg') }}" alt="" class="h-5 w-auto {{ $qris ? '' : 'grayscale' }}">
                                @if($qris)
                                    <span class="text-xs text-muted">Scan dari bank / e-wallet</span>
                                @else
                                    <span class="text-xs font-medium text-amber-700">Maintenance</span>
                                @endif
                            </span>
                        </label>
                        <label class="pay-option">
                            <input type="radio" name="method" value="balance" class="peer sr-only" @checked($method === 'balance')>
                            <span class="pay-card">
                                <span class="flex items-center gap-1.5 text-sm font-bold">
                                    <svg class="size-4 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"/><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"/></svg>
                                    Saldo
                                </span>
                                <span class="num text-xs text-muted">{{ rupiah(auth()->user()->balance) }}</span>
                            </span>
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary w-full py-3" data-loading="Memproses…">Beli</button>
                    <p class="mt-2 text-center text-xs text-muted"><a href="{{ route('balance.index') }}" class="font-semibold text-brand-600 hover:underline">Isi saldo</a></p>
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary w-full py-3">Masuk untuk beli</a>
                @endauth
            </form>
        @endif
    </div>
</div>

@if($related->isNotEmpty())
<section class="mt-10">
    <h2 class="mb-4 text-xl font-semibold tracking-tight">Produk serupa</h2>
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-[repeat(auto-fill,minmax(200px,1fr))] sm:gap-4">
        @foreach($related as $item)
            @include('partials.product-card', ['product' => $item])
        @endforeach
    </div>
</section>
@endif
@endsection
