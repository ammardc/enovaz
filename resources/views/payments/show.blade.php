@extends('layouts.app')
@section('title', 'Pembayaran '.$order->code)

@section('content')
@php($payment = $order->payment)
@php($amount = number_format($payment->expected_amount, 0, ',', '.'))
@php($item = $order->items->first())
<nav class="mb-4 text-sm text-muted"><a href="{{ route('orders.index') }}" class="hover:text-brand-600">Pesanan Saya</a> / Pembayaran</nav>

<div class="grid items-start gap-5 lg:grid-cols-[1fr_1.1fr]">
    <div class="panel text-center">
        <div class="mb-4 flex items-center justify-center gap-4">
            <img src="{{ asset('images/pay/qris.svg') }}" alt="QRIS" class="h-6 w-auto">
        </div>
        <div class="mx-auto w-full max-w-[280px] rounded-2xl border border-line bg-white p-2 [&_svg]:h-auto [&_svg]:w-full">
            {!! $qr !!}
        </div>
        @if($simulation)
            <p class="mt-2 text-xs text-amber-700">Mode simulasi.</p>
        @endif
        <p class="mt-5 text-sm text-muted">Total bayar</p>
        <div class="num text-4xl font-semibold tracking-tight">Rp{{ substr($amount, 0, -3) }}<span class="text-brand-600 underline decoration-2 underline-offset-4">{{ substr($amount, -3) }}</span></div>
        <p class="mt-2 text-sm text-muted">Bayar sesuai nominal.</p>
        <span class="num mt-4 inline-flex rounded-full bg-amber-50 px-3 py-1 text-sm font-bold text-amber-700" data-expires="{{ $payment->expires_at->toIso8601String() }}">Berlaku {{ $payment->expires_at->diffForHumans(null, true) }}</span>
    </div>

    <div class="panel">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h1 class="text-xl font-semibold tracking-tight">Pembayaran</h1>
            <span class="pill {{ $order->statusClass() }}">{{ $order->statusLabel() }}</span>
        </div>

        <dl class="num mt-4 grid grid-cols-[auto_1fr] gap-x-6 gap-y-2 text-sm">
            <dt class="text-muted">No. pesanan</dt><dd class="font-mono font-semibold">{{ $order->code }}</dd>
            <dt class="text-muted">{{ $order->isTopup() ? 'Untuk' : 'Produk' }}</dt><dd class="font-semibold">{{ $order->title() }}</dd>
            <dt class="text-muted">Harga</dt><dd class="font-semibold">{{ rupiah($payment->base_amount) }}</dd>
            <dt class="text-muted">Kode unik</dt><dd class="font-semibold">{{ rupiah($payment->uniqueCode()) }}</dd>
            <dt class="text-muted">Total</dt><dd class="text-base font-semibold tracking-tight">{{ rupiah($payment->expected_amount) }}</dd>
            <dt class="text-muted">Batas bayar</dt><dd class="font-semibold">{{ wib($payment->expires_at, 'H.i') }} WIB</dd>
        </dl>

        <ol class="mt-5 space-y-2.5 text-sm text-muted">
            <li class="flex gap-2.5"><span class="grid size-6 shrink-0 place-items-center rounded-full bg-brand-100 text-xs font-bold text-brand-700">1</span>Buka aplikasi bank / e-wallet.</li>
            <li class="flex gap-2.5"><span class="grid size-6 shrink-0 place-items-center rounded-full bg-brand-100 text-xs font-bold text-brand-700">2</span>Scan QR, bayar sesuai nominal.</li>
            <li class="flex gap-2.5"><span class="grid size-6 shrink-0 place-items-center rounded-full bg-brand-100 text-xs font-bold text-brand-700">3</span>Tekan “Cek pembayaran”.</li>
        </ol>

        <form method="POST" action="{{ route('payments.check', $order->code) }}" class="mt-6" data-once>
            @csrf
            <button type="submit" class="btn btn-primary w-full py-3" data-loading="Memeriksa pembayaran…">Cek pembayaran</button>
        </form>

        @if($simulation)
            <form method="POST" action="{{ route('payments.simulate', $order->code) }}" class="mt-2">
                @csrf
                <button type="submit" class="btn btn-ghost w-full">Simulasi: tandai sudah dibayar</button>
            </form>
        @endif

    </div>
</div>
@endsection
