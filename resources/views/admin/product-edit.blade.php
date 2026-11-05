@extends('layouts.app')
@section('title', 'Atur '.$product->name)

@section('content')
    <a class="back-link" href="{{ route('admin.products') }}">@include('partials.icon', ['name' => 'back']) Kembali ke produk</a>
    <h1 class="page-title">{{ $product->name }}</h1>
    @include('admin._tabs')
    @include('admin._product-media-summary')
    <form method="post" action="{{ route('admin.products.save', $product->id) }}" class="two-col">
        @csrf
        <div class="panel form-grid">
            <h2>Informasi produk</h2>
            <div class="form-field">
                <label for="product-name">Nama produk</label>
                <input class="form-control" id="product-name" name="name" value="{{ old('name', $product->name) }}" required>
            </div>
            <div class="form-field">
                <label for="product-category">Kategori</label>
                <select class="form-control" id="product-category" name="category_id">
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-field">
                <label for="product-status">Status</label>
                <select class="form-control" id="product-status" name="status">
                    @foreach(['draft' => 'Draft', 'coming_soon' => 'Segera hadir', 'published' => 'Published', 'hidden' => 'Sembunyi'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $product->status) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-field">
                <label for="product-badge">Badge</label>
                <select class="form-control" id="product-badge" name="badge">
                    <option value="">Tanpa badge</option>
                    <option value="HOT" @selected(old('badge', $product->badge) === 'HOT')>HOT</option>
                    <option value="NEW" @selected(old('badge', $product->badge) === 'NEW')>NEW</option>
                </select>
            </div>
            <div class="form-field">
                <label for="product-description">Deskripsi</label>
                <textarea class="form-control" id="product-description" name="description" rows="5">{{ old('description', $product->description) }}</textarea>
            </div>
            <div class="form-field">
                <label for="product-instructions">Cara pakai</label>
                <textarea class="form-control" id="product-instructions" name="usage_instructions" rows="3">{{ old('usage_instructions', $product->usage_instructions) }}</textarea>
            </div>
        </div>
        <div class="panel form-grid">
            <h2>Paket utama</h2>
            <div class="form-field"><label for="variant-name">Nama paket</label><input class="form-control" id="variant-name" name="variant_name" value="{{ old('variant_name', $variant->name) }}" required></div>
            <div class="form-field"><label for="variant-price">Harga (Rp)</label><input class="form-control" id="variant-price" name="price_idr" type="number" min="1000" value="{{ old('price_idr', $variant->price_idr) }}"></div>
            <div class="form-field"><label for="variant-limit">Batas per customer</label><input class="form-control" id="variant-limit" name="limit_per_customer" type="number" min="1" max="20" value="{{ old('limit_per_customer', $variant->limit_per_customer) }}" required></div>
            <div class="notice">Stok tersedia: <strong>{{ $stock }}</strong>. <a class="text-link" href="{{ route('admin.stock', $variant->id) }}">Kelola stok</a></div>
            <button class="button full">Simpan produk</button>
        </div>
    </form>
@endsection
