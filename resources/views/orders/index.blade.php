@extends('layouts.app')
@section('title', 'Pesanan Saya')

@section('content')
<nav class="mb-4 text-sm text-muted"><a href="{{ route('home') }}" class="hover:text-brand-600">Beranda</a> / Pesanan Saya</nav>
<h1 class="mb-5 text-2xl font-semibold tracking-tight">Pesanan Saya</h1>

@if($orders->isEmpty())
    <div class="panel py-12 text-center text-muted">Belum ada pesanan. <a href="{{ route('home') }}" class="font-semibold text-brand-600">Mulai belanja</a></div>
@else
    <div class="space-y-3">
        @foreach($orders as $order)
            @php($item = $order->items->first())
            <a href="{{ $order->status === 'pending' ? route('payments.show', $order->code) : route('orders.show', $order->code) }}"
               class="grid grid-cols-[56px_1fr] items-center gap-4 rounded-2xl border border-line bg-white p-3.5 hover:border-brand-400 sm:grid-cols-[56px_1fr_auto]">
                <div class="overflow-hidden rounded-xl">
                    @if($item)
                        @include('partials.thumb', ['product' => $item->product, 'class' => 'aspect-square', 'text' => 'text-lg', 'compact' => true])
                    @else
                        <div class="grid aspect-square place-items-center bg-brand-50 text-brand-600"><svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"/><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"/></svg></div>
                    @endif
                </div>
                <div class="min-w-0">
                    <h2 class="truncate font-bold">{{ $order->title() }}</h2>
                    <p class="font-mono text-xs text-muted">{{ $order->code }}</p>
                    <p class="text-xs text-muted">{{ wib($order->created_at) }} WIB · {{ $order->methodLabel() }}</p>
                </div>
                <div class="col-span-2 flex items-center justify-between gap-2 sm:col-span-1 sm:flex-col sm:items-end">
                    <b class="num">{{ rupiah($order->payment?->expected_amount ?? $order->total) }}</b>
                    <span class="pill {{ $order->statusClass() }}">{{ $order->statusLabel() }}</span>
                </div>
            </a>
        @endforeach
    </div>
    <div class="mt-6">{{ $orders->links() }}</div>
@endif
@endsection
