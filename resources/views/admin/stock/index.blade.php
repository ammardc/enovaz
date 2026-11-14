@extends('layouts.app')
@section('title', 'Stok '.$product->name)

@section('content')
@include('admin._nav', ['heading' => 'Stok: '.$product->name])

<div class="num mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
    @foreach(['available' => ['Tersedia', 'text-emerald-700'], 'reserved' => ['Direservasi', 'text-amber-600'], 'sold' => ['Terjual', 'text-ink'], 'disabled' => ['Nonaktif', 'text-muted']] as $k => [$label, $color])
        <div class="panel p-4"><span class="text-xs font-medium text-muted">{{ $label }}</span><b class="mt-0.5 block text-xl font-extrabold {{ $color }}">{{ $counts[$k] ?? 0 }}</b></div>
    @endforeach
</div>

<div class="grid items-start gap-5 lg:grid-cols-[1fr_1.4fr]">
    <form method="POST" action="{{ route('admin.stock.store', $product) }}" class="panel space-y-3" data-once>
        @csrf
        <h2 class="font-bold">Tambah stok akun</h2>
        <p class="text-sm text-muted">Satu akun per baris dengan format <code class="rounded bg-canvas px-1 font-mono">email|password</code>. Maksimal 200 baris. Setiap nilai dienkripsi sebelum disimpan.</p>
        <textarea name="lines" rows="9" class="input font-mono" placeholder="akun1@contoh.com|Passw0rd1&#10;akun2@contoh.com|Passw0rd2" autocomplete="off" spellcheck="false" required></textarea>
        @error('lines')<p class="error">{{ $message }}</p>@enderror
        <button type="submit" class="btn btn-primary w-full" data-loading="Mengenkripsi…">Simpan stok terenkripsi</button>
    </form>

    <div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>ID</th><th>Status</th><th>Reservasi s/d</th><th>Ditambahkan</th><th></th></tr></thead>
                <tbody>
                @forelse($accounts as $a)
                    <tr>
                        <td class="num font-mono">#{{ $a->id }}</td>
                        <td>
                            @php($map = ['available' => ['Tersedia', 'pill-paid'], 'reserved' => ['Direservasi', 'pill-wait'], 'sold' => ['Terjual', 'pill-muted'], 'disabled' => ['Nonaktif', 'pill-review']])
                            <span class="pill {{ $map[$a->status][1] }}">{{ $map[$a->status][0] }}</span>
                        </td>
                        <td class="num">{{ $a->reserved_until ? wib($a->reserved_until, 'd M H.i') : '-' }}</td>
                        <td class="num">{{ wib($a->created_at, 'd M Y') }}</td>
                        <td class="text-right">
                            @if($a->status === 'available')
                                <form method="POST" action="{{ route('admin.stock.disable', [$product, $a]) }}" data-confirm="Nonaktifkan akun #{{ $a->id }}?">@csrf
                                    <button class="btn btn-ghost px-3 py-1.5">Nonaktifkan</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-10 text-center text-muted">Belum ada stok untuk produk ini.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <p class="mt-2 text-xs text-muted">Isi kredensial tidak ditampilkan di panel admin. Pembeli membukanya sendiri dari detail pesanan setelah lunas.</p>
        <div class="mt-4">{{ $accounts->links() }}</div>
    </div>
</div>
@endsection
