@extends('layouts.app')
@section('title', 'Transaksi '.$order->code)

@section('content')
@include('admin._nav', ['heading' => 'Transaksi '.$order->code])
@php($payment = $order->payment)

<div class="grid items-start gap-5 lg:grid-cols-2">
    <div class="panel">
        <div class="flex items-center justify-between gap-2"><h2 class="font-bold">Rincian</h2><span class="pill {{ $order->statusClass() }}">{{ $order->statusLabel() }}</span></div>
        <dl class="num mt-4 grid grid-cols-[auto_1fr] gap-x-6 gap-y-2 text-sm">
            <dt class="text-muted">Pembeli</dt><dd class="font-semibold">{{ $order->user->username }} ({{ $order->user->name }})</dd>
            <dt class="text-muted">Dibuat</dt><dd class="font-semibold">{{ wib($order->created_at) }} WIB</dd>
            @foreach($order->items as $item)
                <dt class="text-muted">Produk</dt><dd class="font-semibold">{{ $item->product->name }} × {{ $item->quantity }} @ {{ rupiah($item->unit_price) }} · {{ $item->deliveries->count() }} diserahkan</dd>
            @endforeach
            <dt class="text-muted">Total produk</dt><dd class="font-semibold">{{ rupiah($order->total) }}</dd>
            @if($payment)
                <dt class="text-muted">ID transaksi Casaku</dt><dd class="font-mono font-semibold break-all">{{ $payment->gateway_transaction_id }}</dd>
                <dt class="text-muted">Nominal tagihan</dt><dd class="font-semibold">{{ rupiah($payment->expected_amount) }} (kode unik {{ $payment->uniqueCode() }})</dd>
                <dt class="text-muted">Status pembayaran</dt><dd class="font-semibold">{{ $payment->status }}</dd>
                <dt class="text-muted">Kedaluwarsa</dt><dd class="font-semibold">{{ wib($payment->expires_at) }} WIB</dd>
                <dt class="text-muted">Dibayar</dt><dd class="font-semibold">{{ $payment->paid_at ? wib($payment->paid_at).' WIB' : '-' }}</dd>
            @endif
        </dl>
    </div>

    <div class="panel">
        <h2 class="font-bold">Penanganan</h2>
        @if($payment && $payment->status === 'review_required')
            <p class="mt-2 text-sm text-muted">Pembayaran ini butuh keputusan admin, misalnya dibayar setelah tagihan kedaluwarsa atau nominalnya tidak cocok. Tindakan ini memerlukan konfirmasi password dan dicatat di audit log.</p>
            <form method="POST" action="{{ route('admin.orders.resolve', $order) }}" class="mt-4 space-y-3">
                @csrf
                <div>
                    <label for="note" class="label">Catatan (wajib)</label>
                    <textarea id="note" name="note" rows="3" class="input" maxlength="500" required placeholder="Contoh: pembeli transfer 2 menit setelah kedaluwarsa, bukti DANA dicek">{{ old('note') }}</textarea>
                    @error('note')<p class="error">{{ $message }}</p>@enderror
                </div>
                <div class="flex flex-wrap gap-2">
                    <button name="action" value="fulfil" class="btn btn-primary">Penuhi pesanan</button>
                    <button name="action" value="refund" class="btn btn-danger">Batalkan &amp; kembalikan dana</button>
                </div>
                <p class="text-xs text-muted">“Penuhi” hanya berhasil bila Casaku mencatat tagihan sebagai lunas. Admin tidak bisa menetapkan status lunas tanpa bukti pembayaran.</p>
            </form>
        @else
            <p class="mt-2 text-sm text-muted">Tidak ada tindakan yang diperlukan untuk transaksi ini.</p>
        @endif
    </div>
</div>
@endsection
