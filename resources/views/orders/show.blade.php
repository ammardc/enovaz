@extends('layouts.app')
@section('title', 'Pesanan '.$order->code)

@section('content')
@php($payment = $order->payment)
<nav class="mb-4 text-sm text-muted"><a href="{{ route('orders.index') }}" class="hover:text-brand-600">Pesanan Saya</a> / <span class="font-mono">{{ $order->code }}</span></nav>

<div class="grid items-start gap-5 lg:grid-cols-2">
    <div class="panel">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h1 class="text-xl font-semibold tracking-tight">{{ $order->isTopup() ? 'Isi saldo' : 'Pesanan' }}</h1>
            <span class="pill {{ $order->statusClass() }}">{{ $order->statusLabel() }}</span>
        </div>

        @foreach($order->items as $item)
            <div class="mt-4 grid grid-cols-[56px_1fr] items-center gap-4">
                <div class="overflow-hidden rounded-xl">@include('partials.thumb', ['product' => $item->product, 'class' => 'aspect-square', 'text' => 'text-lg', 'compact' => true])</div>
                <div><h2 class="font-bold">{{ $item->product->name }} × {{ $item->quantity }}</h2><p class="text-sm text-muted">{{ $item->product->duration }} · {{ rupiah($item->unit_price) }} / akun</p></div>
            </div>
        @endforeach

        <dl class="num mt-5 grid grid-cols-[auto_1fr] gap-x-6 gap-y-2 text-sm">
            <dt class="text-muted">No. pesanan</dt><dd class="font-mono font-semibold">{{ $order->code }}</dd>
            <dt class="text-muted">Dibuat</dt><dd class="font-semibold">{{ wib($order->created_at) }} WIB</dd>
            @if($payment)
                <dt class="text-muted">Harga</dt><dd class="font-semibold">{{ rupiah($payment->base_amount) }}</dd>
                <dt class="text-muted">Kode unik</dt><dd class="font-semibold">{{ rupiah($payment->uniqueCode()) }}</dd>
                <dt class="text-muted">Total</dt><dd class="font-semibold tracking-tight">{{ rupiah($payment->expected_amount) }}</dd>
                @if($payment->paid_at)<dt class="text-muted">Dibayar</dt><dd class="font-semibold">{{ wib($payment->paid_at) }} WIB</dd>@endif
            @endif
            @if(! $payment)
                <dt class="text-muted">Total</dt><dd class="font-semibold tracking-tight">{{ rupiah($order->total) }}</dd>
            @endif
            <dt class="text-muted">Metode</dt><dd class="font-semibold">{{ $order->methodLabel() }}</dd>
        </dl>
    </div>

    <div class="panel">
        @if($order->isTopup())
        <h2 class="text-lg font-semibold tracking-tight">Saldo</h2>
        @if($order->status === 'paid')
            <div class="mt-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800">Saldo bertambah {{ rupiah($payment?->expected_amount ?? $order->total) }}.</div>
            <a href="{{ route('balance.index') }}" class="btn btn-soft mt-3">Lihat saldo</a>
        @elseif($order->status === 'expired')
            <div class="mt-4 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800">Tagihan kedaluwarsa. Sudah bayar? Tekan cek lagi atau hubungi admin.</div>
        @elseif($order->status === 'review_required')
            <div class="mt-4 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700">Pembayaran sedang ditinjau admin.</div>
        @else
            <div class="mt-4 rounded-xl bg-slate-100 px-4 py-3 text-sm text-slate-700">Dibatalkan.</div>
        @endif
        @else
        <h2 class="text-lg font-semibold tracking-tight">Akun produk</h2>

        @if($order->status === 'paid')
            @if($credentials)
                <div class="mt-4 space-y-3">
                    @foreach($credentials as $i => $c)
                        <div class="rounded-xl bg-canvas p-4">
                            <p class="mb-2 text-xs font-bold tracking-wide text-muted uppercase">Akun {{ $i + 1 }}{{ $c['product'] ? ' · '.$c['product'] : '' }}</p>
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="min-w-0"><span class="block text-xs text-muted">Email / username</span><code id="email-{{ $i }}" class="font-mono text-sm break-all">{{ $c['email'] }}</code></div>
                                <button type="button" class="btn btn-soft" data-copy="{{ $c['email'] }}" data-copy-target="email-{{ $i }}">Salin</button>
                            </div>
                            <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                                <div class="min-w-0"><span class="block text-xs text-muted">Password</span><code id="pass-{{ $i }}" class="font-mono text-sm break-all">{{ $c['password'] }}</code></div>
                                <button type="button" class="btn btn-soft" data-copy="{{ $c['password'] }}" data-copy-target="pass-{{ $i }}">Salin</button>
                            </div>
                        </div>
                    @endforeach
                </div>
                <p class="mt-3 text-xs text-muted">Jangan bagikan halaman ini.</p>
            @else
                <div class="mt-4 rounded-xl bg-canvas p-4">
                    <p class="flex items-start gap-3 text-sm text-muted">
                        <svg class="mt-0.5 size-5 shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M12 1a5 5 0 0 0-5 5v4H5v13h14V10h-2V6a5 5 0 0 0-5-5zm-3 9V6a3 3 0 0 1 6 0v4z"/></svg>
                        Pastikan tidak ada yang melihat layarmu.
                    </p>
                    <form method="POST" action="{{ route('orders.reveal', $order->code) }}" class="mt-3">
                        @csrf
                        <button type="submit" class="btn btn-primary">Tampilkan akun</button>
                    </form>
                </div>
            @endif
        @elseif($order->status === 'expired')
            <div class="mt-4 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800">Tagihan kedaluwarsa. Sudah bayar? Hubungi admin.</div>
        @elseif($order->status === 'review_required')
            <div class="mt-4 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700">Pembayaran sedang ditinjau admin.</div>
        @else
            <div class="mt-4 rounded-xl bg-slate-100 px-4 py-3 text-sm text-slate-700">Pesanan ini dibatalkan.</div>
        @endif
        @endif

        <h3 class="mt-6 font-bold">Ada kendala?</h3>
        <p class="mt-1 text-sm text-muted">{{ config('enovaz.support.email') }} · sertakan <span class="font-mono">{{ $order->code }}</span></p>
    </div>
</div>
@endsection
