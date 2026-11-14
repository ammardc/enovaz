@extends('layouts.app')
@section('title', 'Produk')

@section('content')
@include('admin._nav', ['heading' => 'Produk & Stok', 'actionUrl' => route('admin.products.create'), 'actionLabel' => '+ Tambah produk'])

<div class="table-wrap">
    <table class="table">
        <thead><tr><th>Produk</th><th>Kategori</th><th class="text-right!">Harga</th><th class="text-right!">Stok</th><th class="text-right!">Terjual</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse($products as $p)
            <tr>
                <td class="font-semibold">{{ $p->name }}<span class="block text-xs font-normal text-muted">{{ $p->duration }}</span></td>
                <td>{{ $p->category }}</td>
                <td class="num text-right">{{ rupiah($p->price) }}</td>
                <td class="num text-right {{ $p->stock() === 0 ? 'text-red-600 font-bold' : ($p->stock() <= config('enovaz.low_stock') ? 'text-amber-600 font-bold' : '') }}">{{ $p->stock() }}</td>
                <td class="num text-right">{{ $p->sold() }}</td>
                <td><span class="pill {{ $p->is_active ? 'pill-paid' : 'pill-muted' }}">{{ $p->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                <td>
                    <div class="flex justify-end gap-1.5">
                        <a href="{{ route('admin.stock.index', $p) }}" class="btn btn-soft px-3 py-1.5">Stok</a>
                        <a href="{{ route('admin.products.edit', $p) }}" class="btn btn-ghost px-3 py-1.5">Edit</a>
                        <form method="POST" action="{{ route('admin.products.toggle', $p) }}">@csrf
                            <button class="btn btn-ghost px-3 py-1.5">{{ $p->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="py-10 text-center text-muted">Belum ada produk. <a href="{{ route('admin.products.create') }}" class="font-semibold text-brand-600">Tambah produk pertama</a></td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-6">{{ $products->links() }}</div>
@endsection
