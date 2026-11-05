@extends('layouts.app')
@section('title','Stok '.$package->product_name)
@section('content')
<a class="back-link" href="{{ route('admin.stock.index') }}">@include('partials.icon',['name'=>'back']) Semua stok</a>
<span class="eyebrow">Panel pengelola</span><h1 class="page-title">Stok {{ $package->product_name }}</h1>
<p class="page-subtitle">Paket {{ $package->name }} · Unit rahasia tersimpan terenkripsi.</p>
@include('admin._tabs')
<div class="metric-grid">@foreach(['available'=>'Tersedia','reserved'=>'Dipesan','delivered'=>'Terkirim','quarantined'=>'Ditahan'] as $key=>$label)<div class="metric"><span>{{ $label }}</span><strong>{{ $counts[$key] ?? 0 }}</strong></div>@endforeach</div>
<div class="two-col" style="margin-top:22px">
    <div class="panel"><h2>Unit stok</h2><div class="list-rows">
        @forelse($units as $unit)<div class="option-row" style="display:block"><div style="display:flex;justify-content:space-between;gap:12px"><div><strong>#{{ $unit->id }}</strong><div class="small muted">ID {{ substr($unit->fingerprint,0,10) }}… · {{ $unit->created_at }}</div></div><span class="status-pill {{ $unit->status }}">{{ $unit->status }}</span></div>
            @if(in_array($unit->status,['available','disabled','quarantined']))<details style="margin-top:10px"><summary class="text-link" style="cursor:pointer">Ubah status</summary><form method="post" action="{{ route('admin.stock.status',[$package->id,$unit->id]) }}" class="form-grid" style="margin-top:12px">@csrf<div class="form-field"><label>Status baru</label><select class="form-control" name="status"><option value="disabled">Nonaktif</option><option value="quarantined">Karantina</option><option value="available">Tersedia</option></select></div><div class="form-field"><label>Alasan</label><input class="form-control" name="reason" required></div><button class="button secondary">Simpan status</button></form></details>@endif
            @if(auth()->user()->role==='owner')<details style="margin-top:10px"><summary class="text-link" style="cursor:pointer">Lihat rahasia unit</summary><form method="post" action="{{ route('admin.stock.reveal',[$package->id,$unit->id]) }}" class="form-grid" style="margin-top:12px">@csrf<div class="form-field"><label>Alasan akses</label><input class="form-control" name="reason" required></div><button class="button secondary">Tampilkan satu kali</button></form></details>@endif
        </div>@empty<p class="small-note">Belum ada unit stok.</p>@endforelse
    </div><div class="pagination">{{ $units->links() }}</div></div>
    <div class="list-rows">
    @if(in_array(auth()->user()->role,['owner','admin']))
    <div class="panel"><h2>Tambah satu stok</h2>
        <form method="post" action="{{ route('admin.stock.create',$package->id) }}" class="form-grid">@csrf
            <div class="form-field"><label for="stock-code">Kode</label><input class="form-control" id="stock-code" name="code" autocomplete="off"><div class="form-help">Isi kode atau akun.</div></div>
            <div class="form-field"><label for="stock-account">Akun</label><input class="form-control" id="stock-account" name="account" autocomplete="off"></div>
            <div class="form-field"><label for="stock-password">Kata sandi akun</label><input class="form-control" id="stock-password" name="password" type="password" autocomplete="new-password"><div class="form-help">Wajib jika akun diisi.</div></div>
            <div class="form-field"><label for="stock-instructions">Instruksi</label><textarea class="form-control" id="stock-instructions" name="instructions" rows="3"></textarea></div>
            <button class="button">Simpan stok</button>
        </form>
    </div>
    <div class="panel"><h2>Impor banyak stok (CSV)</h2><p class="small-note">Header wajib: <code>code,account,password,instructions</code>. Satu baris adalah satu unit. Maksimal 500 baris, 1 MB. Jangan simpan file stok di folder publik.</p>
        @if(session('preview'))<div class="notice" style="margin:15px 0">{{ session('preview')['valid'] }} baris valid. {{ count(session('preview')['errors']) }} masalah. {{ implode(' ',array_slice(session('preview')['errors'],0,3)) }}</div>@endif
        <form method="post" action="{{ route('admin.stock.import',$package->id) }}" enctype="multipart/form-data" class="form-grid">@csrf<div class="form-field"><label for="file">File CSV</label><input class="form-control" type="file" id="file" name="file" accept=".csv,.txt,text/csv" required></div><div style="display:flex;gap:10px"><button class="button secondary" name="action" value="preview">Periksa file</button><button class="button" name="action" value="import">Impor stok</button></div></form>
    </div>
    @endif
    </div>
</div>
@endsection
