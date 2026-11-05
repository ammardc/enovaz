<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <meta name="theme-color" content="#6b2bda"><meta name="description" content="E-Novaz — pilihan produk digital dengan detail jelas dan belanja mudah.">
    <title>@yield('title', 'E-Novaz') · E-Novaz</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body>
<header class="site-header"><div class="header-inner">
    <a class="brand" href="{{ route('shop') }}" aria-label="E-Novaz beranda"><img src="{{ asset('logo.png') }}" alt="Logo E-Novaz"><span>E-<strong>Novaz</strong></span></a>
    @unless(request()->routeIs('login','register','password.*'))
    <nav class="desktop-nav" aria-label="Navigasi utama">
        <a class="{{ request()->routeIs('shop','products.*') ? 'active' : '' }}" href="{{ route('shop') }}">Shop</a>
        <a class="{{ request()->routeIs('orders.*') ? 'active' : '' }}" href="{{ route('orders.index') }}">Orders</a>
        <a class="{{ request()->routeIs('wallet.*') ? 'active' : '' }}" href="{{ route('wallet.index') }}">Wallet</a>
    </nav>
    <div class="header-actions">
        <a class="icon-button" href="{{ route('shop') }}#catalog-search" aria-label="Cari produk">@include('partials.icon',['name'=>'search'])</a>
        @auth
        @if(in_array(auth()->user()->role,['owner','admin','support']))<a class="admin-link" href="{{ route('admin.index') }}"><svg class="admin-link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2 20 5v6c0 5-3.5 8.5-8 11-4.5-2.5-8-6-8-11V5l8-3Z"/><path d="m12 7 .8 2.2 2.2.8-2.2.8-.8 2.2-.8-2.2-2.2-.8 2.2-.8L12 7Z"/></svg><span>Admin</span></a>@endif
        <a class="profile-link" href="{{ route('profile') }}" aria-label="Akun saya">@include('partials.icon',['name'=>'user'])</a>
        @else<a class="login-button" href="{{ route('login') }}">Masuk</a>@endauth
    </div>
    @endunless
</div></header>
<main class="page-shell">
    @if(session('success'))<div class="flash success" role="status">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="flash error" role="alert">{{ $errors->first() }}</div>@endif
    @yield('content')
</main>

@unless(request()->routeIs('login','register','password.*'))
<nav class="mobile-nav" aria-label="Navigasi utama">
    <a class="{{ request()->routeIs('shop','products.*') ? 'active' : '' }}" href="{{ route('shop') }}">@include('partials.icon',['name'=>'shop'])<span>Shop</span></a>
    <a class="{{ request()->routeIs('orders.*') ? 'active' : '' }}" href="{{ route('orders.index') }}">@include('partials.icon',['name'=>'orders'])<span>Orders</span></a>
    <a class="{{ request()->routeIs('wallet.*') ? 'active' : '' }}" href="{{ route('wallet.index') }}">@include('partials.icon',['name'=>'wallet'])<span>Wallet</span></a>
</nav>
@endunless
@stack('scripts')
</body></html>
