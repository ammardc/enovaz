<nav class="admin-tabs" aria-label="Panel admin">
    <a class="{{ request()->routeIs('admin.index') ? 'active' : '' }}" href="{{ route('admin.index') }}">Dashboard</a>
    <a class="{{ request()->routeIs('admin.products*','admin.variants*','admin.media*') ? 'active' : '' }}" href="{{ route('admin.products') }}">Produk</a>
    <a class="{{ request()->routeIs('admin.stock*') ? 'active' : '' }}" href="{{ route('admin.stock.index') }}">Stok</a>
    <a class="{{ request()->routeIs('admin.categories*') ? 'active' : '' }}" href="{{ route('admin.categories') }}">Kategori</a>
    <a class="{{ request()->routeIs('admin.orders') ? 'active' : '' }}" href="{{ route('admin.orders') }}">Pesanan</a>
    <a class="{{ request()->routeIs('admin.payments*') ? 'active' : '' }}" href="{{ route('admin.payments') }}">Pembayaran</a>
    <a class="{{ request()->routeIs('admin.customers*') ? 'active' : '' }}" href="{{ route('admin.customers') }}">Pelanggan</a>
    <a class="{{ request()->routeIs('admin.adjustments*') ? 'active' : '' }}" href="{{ route('admin.adjustments') }}">Koreksi saldo</a>
    <a class="{{ request()->routeIs('admin.reports','admin.reports.export') ? 'active' : '' }}" href="{{ route('admin.reports') }}">Laporan</a>
</nav>
