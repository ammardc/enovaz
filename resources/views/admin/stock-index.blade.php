@extends('layouts.app')
@section('title','Admin Stok')
@section('content')
<span class="eyebrow">Panel pengelola</span><h1 class="page-title">Stok</h1>
<p class="page-subtitle">Pilih paket untuk menambah atau mengatur stok.</p>
@include('admin._tabs')
<div class="panel">
    <div class="table-scroll"><table class="data-table"><thead><tr><th>Produk</th><th>Paket</th><th>Tersedia</th><th>Aksi</th></tr></thead><tbody>
        @forelse($packages as $package)
        <tr><td><strong>{{ $package->product_name }}</strong></td><td>{{ $package->variant_name }}</td><td>{{ $package->available }}</td>
            <td><a class="button secondary" href="{{ route('admin.stock',$package->id) }}">Tambah stok</a></td></tr>
        @empty<tr><td colspan="4">Belum ada paket.</td></tr>@endforelse
    </tbody></table></div>
    <div class="pagination">{{ $packages->withQueryString()->links() }}</div>
</div>
@endsection
