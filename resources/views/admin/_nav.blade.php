<div class="mb-6 flex flex-wrap items-end justify-between gap-3">
    <div>
        <p class="text-xs font-medium tracking-wide text-muted uppercase">Admin</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight">{{ $heading }}</h1>
    </div>
    @isset($actionUrl)<a href="{{ $actionUrl }}" class="btn btn-primary rounded-lg font-medium">{{ $actionLabel }}</a>@endisset
</div>
<nav class="mb-6 flex gap-1 overflow-x-auto border-b border-line [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
    @foreach([
        'admin.dashboard' => 'Ringkasan',
        'admin.orders.index' => 'Transaksi',
        'admin.products.index' => 'Produk & Stok',
        'admin.users.index' => 'Pengguna',
        'admin.audit.index' => 'Audit log',
    ] as $route => $label)
        @php($active = request()->routeIs($route) || ($route !== 'admin.dashboard' && request()->routeIs(str_replace('.index', '.*', $route))) || ($route === 'admin.products.index' && request()->routeIs('admin.stock.*')))
        <a href="{{ route($route) }}" class="border-b-2 px-4 py-2.5 text-sm font-medium whitespace-nowrap {{ $active ? 'border-ink text-ink' : 'border-transparent text-muted hover:text-ink' }}">{{ $label }}</a>
    @endforeach
</nav>
