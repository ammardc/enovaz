@extends('layouts.app')
@section('title', $product->exists ? 'Edit produk' : 'Produk baru')

@section('content')
@include('admin._nav', ['heading' => $product->exists ? 'Edit: '.$product->name : 'Produk baru'])

<form method="POST" enctype="multipart/form-data"
      action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}"
      class="panel max-w-2xl space-y-4" data-once>
    @csrf
    @if($product->exists) @method('PUT') @endif

    <div>
        <label for="name" class="label">Nama produk</label>
        <input id="name" name="name" value="{{ old('name', $product->name) }}" class="input" maxlength="120" required>
        @error('name')<p class="error">{{ $message }}</p>@enderror
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="category" class="label">Kategori</label>
            <input id="category" name="category" value="{{ old('category', $product->category) }}" class="input" maxlength="50" placeholder="Desain, Streaming, Edukasi…" required>
            @error('category')<p class="error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="duration" class="label">Durasi</label>
            <input id="duration" name="duration" value="{{ old('duration', $product->duration) }}" class="input" maxlength="50" placeholder="1 bulan" required>
            @error('duration')<p class="error">{{ $message }}</p>@enderror
        </div>
    </div>
    <div>
        <label for="price" class="label">Harga (Rp)</label>
        <input id="price" name="price" type="number" min="1000" step="1" value="{{ old('price', $product->price) }}" class="input num" required>
        @error('price')<p class="error">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="description" class="label">Deskripsi dan ketentuan</label>
        <textarea id="description" name="description" rows="6" class="input" maxlength="5000" required>{{ old('description', $product->description) }}</textarea>
        @error('description')<p class="error">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="image" class="label">Gambar (opsional)</label>
        <input id="image" name="image" type="file" accept="image/png,image/jpeg,image/webp" class="input">
        <p class="help">JPG, PNG, atau WEBP, maksimal 1 MB. Tanpa gambar, thumbnail memakai warna kategori.</p>
        @error('image')<p class="error">{{ $message }}</p>@enderror
    </div>
    <label class="flex items-center gap-2 text-sm font-semibold">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" class="size-4 accent-brand-600" @checked(old('is_active', $product->is_active))>
        Tampilkan di katalog
    </label>
    <p class="rounded-xl bg-amber-50 px-3 py-2.5 text-xs text-amber-800">Pastikan sumber dan izin distribusi produk sudah diperiksa sebelum diaktifkan.</p>
    <div class="flex gap-2">
        <button type="submit" class="btn btn-primary">{{ $product->exists ? 'Simpan perubahan' : 'Buat produk' }}</button>
        <a href="{{ route('admin.products.index') }}" class="btn btn-ghost">Batal</a>
    </div>
</form>
@endsection
