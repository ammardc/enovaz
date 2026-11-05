@extends('layouts.app')
@section('title','Admin Kategori')
@section('content')
<span class="eyebrow">Panel pengelola</span><h1 class="page-title">Kategori</h1>
<p class="page-subtitle">Atur urutan dan visibilitas chip katalog.</p>
@include('admin._tabs')
<div class="two-col">
    <div class="panel"><h2>Kategori aktif</h2><div class="form-grid">
        @foreach($categories as $category)
            <form method="post" action="{{ route('admin.categories.save',$category->id) }}" class="category-row">
                @csrf
                <label class="category-name"><span>Nama</span><input class="form-control" name="name" value="{{ $category->name }}" required></label>
                <label><span>Urutan</span><input class="form-control" name="sort_order" type="number" min="0" value="{{ $category->sort_order }}" required></label>
                <label><span>Status</span><select class="form-control" name="active"><option value="1" @selected($category->active)>Aktif</option><option value="0" @selected(!$category->active)>Nonaktif</option></select></label>
                <button class="button secondary">Simpan</button>
            </form>
        @endforeach
    </div></div>
    <div class="panel"><h2>Tambah kategori</h2><form method="post" action="{{ route('admin.categories.create') }}" class="form-grid">
        @csrf
        <div class="form-field"><label>Nama</label><input class="form-control" name="name" required></div>
        <div class="form-field"><label>Urutan</label><input class="form-control" name="sort_order" type="number" min="0" value="10" required></div>
        <button class="button">Tambah kategori</button>
    </form></div>
</div>
@endsection
