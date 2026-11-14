@extends('layouts.app')
@section('title', 'Saldo')

@section('content')
<h1 class="mb-5 text-2xl font-semibold tracking-tight">Saldo</h1>

<div class="grid items-start gap-5 lg:grid-cols-[1fr_1.3fr]">
    <div class="min-w-0 space-y-5">
        <div class="rounded-2xl bg-ink p-6 text-white">
            <p class="text-sm text-white/60">Saldo kamu</p>
            <p class="num mt-1 text-4xl font-semibold tracking-tight">{{ rupiah($balance) }}</p>
        </div>

        @unless(config('enovaz.qris_enabled'))
        <div class="panel">
            <h2 class="font-semibold">Isi saldo</h2>
            <div class="mt-3 flex gap-3 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-800">
                <svg class="mt-0.5 size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>
                <p>QRIS sedang maintenance. Saldo diisi oleh admin: hubungi <b class="font-semibold">{{ config('enovaz.support.email') }}</b> dengan menyebut username <b class="font-semibold">{{ auth()->user()->username }}</b>.</p>
            </div>
        </div>
        @else
        <form method="POST" action="{{ route('balance.topup') }}" class="panel" data-once>
            @csrf
            <h2 class="font-bold">Isi saldo</h2>
            <div class="mt-4 grid grid-cols-2 gap-2">
                @foreach($presets as $p)
                    <label class="pay-option">
                        <input type="radio" name="preset" value="{{ $p }}" class="peer sr-only" data-fill="amount">
                        <span class="pay-card num items-center py-3 text-sm font-bold">{{ rupiah($p) }}</span>
                    </label>
                @endforeach
            </div>
            <label for="amount" class="label mt-4">Nominal</label>
            <input id="amount" name="amount" type="number" inputmode="numeric" min="{{ $min }}" max="{{ $max }}" step="1000" value="{{ old('amount') }}" class="input num" placeholder="{{ number_format($min, 0, ',', '.') }}" required>
            <p class="help">{{ rupiah($min) }} – {{ rupiah($max) }}</p>
            @error('amount')<p class="error">{{ $message }}</p>@enderror
            <button class="btn btn-primary mt-4 w-full py-3" data-loading="Membuat tagihan…">Bayar dengan QRIS</button>
        </form>
        @endunless
    </div>

    <div class="panel min-w-0">
        <h2 class="font-bold">Riwayat</h2>
        @if($entries->isEmpty())
            <p class="py-10 text-center text-sm text-muted">Belum ada mutasi.</p>
        @else
            <ul class="mt-3 divide-y divide-line">
                @foreach($entries as $e)
                    <li class="flex items-center justify-between gap-3 py-3">
                        <div class="min-w-0">
                            <p class="font-semibold">{{ $e->label() }}</p>
                            <p class="truncate text-xs text-muted">
                                {{ wib($e->created_at) }} WIB
                                @if($e->order) · <a href="{{ route('orders.show', $e->order->code) }}" class="font-mono hover:text-brand-700">{{ $e->order->code }}</a>@endif
                            </p>
                        </div>
                        <div class="num shrink-0 text-right">
                            <p class="font-bold {{ $e->amount > 0 ? 'text-emerald-600' : 'text-ink' }}">{{ $e->amount > 0 ? '+' : '−' }}{{ rupiah(abs($e->amount)) }}</p>
                            <p class="text-xs text-muted">{{ rupiah($e->balance_after) }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>
            <div class="mt-4">{{ $entries->links() }}</div>
        @endif
    </div>
</div>
@endsection
