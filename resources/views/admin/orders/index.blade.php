@extends('layouts.app')
@section('title', 'Transaksi')

@section('content')
@include('admin._nav', ['heading' => 'Transaksi'])

<form method="GET" class="mb-4 flex flex-wrap gap-2">
    <input name="q" value="{{ $q }}" maxlength="32" placeholder="Cari no. pesanan" class="input w-56">
    <select name="status" class="input w-auto" data-autosubmit>
        <option value="">Semua status</option>
        @foreach(\App\Models\Order::LABELS as $k => [$label])
            <option value="{{ $k }}" @selected($status === $k)>{{ $label }}</option>
        @endforeach
    </select>
    <button class="btn btn-ghost">Terapkan</button>
</form>

<div class="table-wrap">
    <table class="table">
        <thead><tr><th>No. pesanan</th><th>Pembeli</th><th>Produk</th><th class="text-right!">Tagihan</th><th>Status</th><th>Waktu</th></tr></thead>
        <tbody>
        @forelse($orders as $o)
            <tr>
                <td><a href="{{ route('admin.orders.show', $o) }}" class="font-mono text-brand-700 hover:underline">{{ $o->code }}</a></td>
                <td>{{ $o->user->username }}</td>
                <td>{{ $o->title() }} <span class="text-xs text-muted">· {{ $o->methodLabel() }}</span></td>
                <td class="num text-right">{{ rupiah($o->payment?->expected_amount ?? $o->total) }}</td>
                <td><span class="pill {{ $o->statusClass() }}">{{ $o->statusLabel() }}</span></td>
                <td class="num">{{ wib($o->created_at, 'd M H.i') }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="py-10 text-center text-muted">Tidak ada transaksi.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-6">{{ $orders->links() }}</div>
@endsection
