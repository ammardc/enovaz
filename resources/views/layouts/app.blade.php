<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('title')@yield('title') · @endif E-Novaz</title>
    <meta name="description" content="E-Novaz: toko akun premium. Bayar pakai saldo, akun langsung masuk ke pesananmu.">
    <link rel="icon" type="image/png" href="{{ asset('images/logo-icon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex flex-col">
@php($user = auth()->user())

<header class="sticky top-0 z-30 border-b border-line bg-white/95 backdrop-blur">
    <div class="container-x flex items-center gap-4 py-3">
        <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2.5" aria-label="E-Novaz beranda">
            <img src="{{ asset('images/logo-icon.png') }}" alt="" class="size-10 rounded-xl">
            <span class="text-xl font-extrabold tracking-tight">E-<span class="text-brand-600">Novaz</span></span>
        </a>

        <form action="{{ route('home') }}" method="GET" class="hidden flex-1 md:flex" role="search">
            <div class="flex w-full items-center gap-2 rounded-full border border-line bg-canvas py-1 pr-1 pl-4">
                <input type="search" name="q" value="{{ request('q') }}" maxlength="60" placeholder="Cari produk" class="w-full bg-transparent py-1.5 text-sm outline-none" aria-label="Cari produk">
                <button class="btn btn-primary rounded-full px-4 py-1.5">Cari</button>
            </div>
        </form>

        <nav class="ml-auto hidden items-center gap-1 md:flex">
            @guest
                <a href="{{ route('login') }}" class="btn btn-ghost">Masuk</a>
                <a href="{{ route('register') }}" class="btn btn-primary">Daftar</a>
            @else
                @if($user->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="rounded-lg px-3 py-2 text-sm font-medium text-muted hover:bg-brand-50 hover:text-ink">Dashboard Admin</a>
                @else
                    <a href="{{ route('orders.index') }}" class="grid size-10 place-items-center rounded-xl text-ink hover:bg-brand-50 hover:text-brand-700 {{ request()->routeIs('orders.*') ? 'bg-brand-50 text-brand-700' : '' }}" title="Pesanan Saya" aria-label="Pesanan Saya">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                    </a>
                @endif
                <a href="{{ route('profile.show') }}" class="ml-1 flex items-center gap-2 rounded-full border py-1 pr-3 pl-1 text-sm font-medium hover:border-brand-400 {{ request()->routeIs('profile.*') ? 'border-brand-400 bg-brand-50' : 'border-line' }}" title="Profil">
                    <span class="grid size-7 place-items-center rounded-full bg-brand-100 text-xs font-semibold text-brand-700">{{ strtoupper(substr($user->username, 0, 1)) }}</span>
                    {{ $user->username }}
                </a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-ghost">Keluar</button></form>
            @endguest
        </nav>

        <button id="menu-toggle" class="btn btn-ghost ml-auto px-3 md:hidden" aria-label="Buka menu">
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        </button>
    </div>

    <div id="mobile-menu" class="hidden border-t border-line md:hidden">
        <div class="container-x flex flex-col gap-2 py-3">
            <form action="{{ route('home') }}" method="GET" role="search" class="flex gap-2">
                <input type="search" name="q" value="{{ request('q') }}" maxlength="60" placeholder="Cari produk" class="input" aria-label="Cari produk">
                <button class="btn btn-primary">Cari</button>
            </form>
            @guest
                <a href="{{ route('login') }}" class="rounded-lg px-2 py-2 font-medium">Masuk</a>
                <a href="{{ route('register') }}" class="btn btn-primary">Daftar</a>
            @else
                <a href="{{ $user->isAdmin() ? route('admin.dashboard') : route('orders.index') }}" class="rounded-lg px-2 py-2 font-medium">{{ $user->isAdmin() ? 'Dashboard Admin' : 'Pesanan Saya' }}</a>
                @unless($user->isAdmin())
                    <a href="{{ route('balance.index') }}" class="num flex justify-between rounded-lg px-2 py-2 font-medium">Saldo <b>{{ rupiah($user->balance) }}</b></a>
                @endunless
                <a href="{{ route('profile.show') }}" class="rounded-lg px-2 py-2 font-medium">Profil</a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-ghost w-full">Keluar ({{ $user->username }})</button></form>
            @endguest
        </div>
    </div>
</header>

<main class="container-x flex-1 py-6 sm:py-8">
    @if(session('status'))
        <div class="mb-5 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" role="status">{{ session('status') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-5 rounded-xl bg-red-50 px-4 py-3 text-sm font-medium text-red-700" role="alert">{{ session('error') }}</div>
    @endif

    @yield('content')
</main>

<footer class="mt-10 border-t border-line bg-white">
    <div class="container-x grid gap-8 py-10 text-sm sm:grid-cols-2 lg:grid-cols-[1.6fr_1fr_1fr_1.3fr]">
        <div>
            <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                <img src="{{ asset('images/logo-icon.png') }}" alt="" class="size-9 rounded-xl">
                <span class="text-lg font-extrabold">E-<span class="text-brand-600">Novaz</span></span>
            </a>
            <p class="mt-3 text-muted">Akun premium, bayar pakai saldo.</p>
        </div>
        <div>
            <h4 class="mb-3 text-xs font-bold tracking-wider text-muted uppercase">Navigasi</h4>
            <ul class="space-y-2">
                <li><a href="{{ route('home') }}" class="hover:text-brand-600">Beranda</a></li>
                <li><a href="{{ route('orders.index') }}" class="hover:text-brand-600">Pesanan Saya</a></li>
            </ul>
        </div>
        <div>
            <h4 class="mb-3 text-xs font-bold tracking-wider text-muted uppercase">Kontak</h4>
            <ul class="space-y-2 text-muted">
                <li>{{ config('enovaz.support.email') }}</li>
                <li>{{ config('enovaz.support.hours') }}</li>
            </ul>
        </div>
        <div>
            <h4 class="mb-3 text-xs font-bold tracking-wider text-muted uppercase">Pembayaran</h4>
            <div class="flex flex-wrap gap-2">
                @if(config('enovaz.qris_enabled'))
                    <span class="grid h-10 w-[84px] place-items-center rounded-xl border border-line bg-white px-3"><img src="{{ asset('images/pay/qris.svg') }}" alt="QRIS" class="max-h-5 w-auto"></span>
                @else
                    <span class="flex h-10 items-center gap-2 rounded-xl border border-dashed border-line px-3 opacity-60" title="QRIS sedang maintenance"><img src="{{ asset('images/pay/qris.svg') }}" alt="QRIS" class="max-h-4 w-auto grayscale"><span class="text-[11px] font-medium text-muted">Maintenance</span></span>
                @endif
                <span class="flex h-10 items-center gap-1.5 rounded-xl border border-line bg-white px-3 text-sm font-bold"><svg class="size-4 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"/><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"/></svg>Saldo</span>
            </div>
        </div>
    </div>
</footer>
</body>
</html>
