@extends('layouts.app')
@section('title','Admin Produk')
@section('content')
<span class="eyebrow">Panel pengelola</span><h1 class="page-title">Produk</h1>
<p class="page-subtitle">Atur detail produk, paket, gambar, dan stok.</p>
@include('admin._tabs')
<div class="two-col">
    <div class="panel admin-products-panel"><div class="table-scroll admin-products-table"><table class="data-table"><thead><tr><th>Produk</th><th>Kategori</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
        @foreach($products as $row)
        <tr><td><strong>{{ $row->name }}</strong></td><td>{{ $row->category_name }}</td><td><span class="status-pill">{{ ['coming_soon'=>'Segera hadir','published'=>'Tayang','hidden'=>'Disembunyikan','draft'=>'Draft'][$row->status] ?? $row->status }}</span></td>
            <td><a class="text-link" href="{{ route('admin.products.edit',$row->id) }}">Atur</a> · <a class="text-link" href="{{ route('admin.products.variants',$row->id) }}">Paket</a> · <a class="text-link" href="{{ route('admin.stock.index',['produk'=>$row->id]) }}">Stok</a> · <a class="text-link" href="{{ route('admin.products.media.edit',$row->id) }}">Gambar</a></td></tr>
        @endforeach
    </tbody></table></div>
    <div class="admin-product-list">
        @foreach($products as $row)
            <article class="admin-product-item">
                <div class="admin-product-meta"><strong>{{ $row->name }}</strong><span>{{ $row->category_name }}</span><span class="status-pill">{{ ['coming_soon'=>'Segera hadir','published'=>'Tayang','hidden'=>'Disembunyikan','draft'=>'Draft'][$row->status] ?? $row->status }}</span></div>
                <div class="admin-product-actions">
                    <a href="{{ route('admin.products.edit',$row->id) }}">Atur</a>
                    <a href="{{ route('admin.products.variants',$row->id) }}">Paket</a>
                    <a href="{{ route('admin.stock.index',['produk'=>$row->id]) }}">Stok</a>
                    <a href="{{ route('admin.products.media.edit',$row->id) }}">Gambar</a>
                </div>
            </article>
        @endforeach
    </div></div>
    <div class="panel"><h2>Produk baru</h2><form method="post" action="{{ route('admin.products.create') }}" class="form-grid">
        @csrf
        <div class="form-field"><label>Nama produk</label><input class="form-control" name="name" required></div>
        <div class="form-field"><label>Kategori</label><select class="form-control" name="category_id">@foreach(\Illuminate\Support\Facades\DB::table('categories')->whereNull('deleted_at')->get() as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select></div>
        <button class="button">Buat draft</button>
    </form><p class="small-note" style="margin-top:12px">Produk baru tetap draft sampai detail paket dan stok lengkap.</p></div>
</div>
@endsection
