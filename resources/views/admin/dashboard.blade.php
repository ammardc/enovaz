@extends('layouts.app')
@section('title', 'Dashboard Admin')

@section('content')
@include('admin._nav', ['heading' => 'Ringkasan', 'actionUrl' => route('admin.products.create'), 'actionLabel' => '+ Tambah produk'])

{{-- Perlu tindakan --}}
@if($stats['review'] > 0)
    <a href="{{ route('admin.orders.index', ['status' => 'review_required']) }}" class="mb-5 flex items-center gap-3 rounded-[10px] border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 hover:bg-red-100">
        <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>
        <span><b class="font-semibold">{{ $stats['review'] }} pembayaran perlu review.</b> Tangani sekarang →</span>
    </a>
@endif

{{-- Angka utama --}}
<div class="num grid grid-cols-2 gap-3 lg:grid-cols-4">
    <div class="stat">
        <p class="stat-label">Penjualan hari ini</p>
        <p class="stat-value">{{ rupiah($stats['revenue_today']) }}</p>
        @if($stats['revenue_delta'] === null)
            <p class="stat-note">Kemarin belum ada penjualan</p>
        @else
            <p class="stat-note {{ $stats['revenue_delta'] >= 0 ? 'text-emerald-700' : 'text-red-600' }}">{{ $stats['revenue_delta'] >= 0 ? '▲' : '▼' }} {{ abs($stats['revenue_delta']) }}% <span class="text-muted">vs kemarin</span></p>
        @endif
    </div>
    <div class="stat">
        <p class="stat-label">Pesanan lunas hari ini</p>
        <p class="stat-value">{{ $stats['paid_today'] }}</p>
        <p class="stat-note {{ $stats['paid_delta'] > 0 ? 'text-emerald-700' : ($stats['paid_delta'] < 0 ? 'text-red-600' : 'text-muted') }}">{{ $stats['paid_delta'] > 0 ? '▲ '.$stats['paid_delta'] : ($stats['paid_delta'] < 0 ? '▼ '.abs($stats['paid_delta']) : 'Sama') }} <span class="text-muted">vs kemarin</span></p>
    </div>
    <div class="stat">
        <p class="stat-label">Isi saldo hari ini</p>
        <p class="stat-value">{{ rupiah($stats['topup_today']) }}</p>
        <p class="stat-note text-muted">Total saldo pembeli {{ rupiah($stats['balances']) }}</p>
    </div>
    <div class="stat">
        <p class="stat-label">Stok tersedia</p>
        <p class="stat-value">{{ number_format($stats['stock'], 0, ',', '.') }}</p>
        <p class="stat-note text-muted">{{ $stats['pending'] }} tagihan menunggu · {{ $stats['buyers'] }} pembeli</p>
    </div>
</div>

<div class="mt-5 grid items-start gap-5 lg:grid-cols-[1.7fr_1fr]">
    {{-- Grafik penjualan 14 hari --}}
    @php($W = 700)
    @php($H = 240)
    @php($L = 56)
    @php($R = 8)
    @php($T = 22)
    @php($B = 26)
    @php($pw = $W - $L - $R)
    @php($ph = $H - $T - $B)
    @php($n = count($chart['days']))
    @php($slot = $pw / $n)
    @php($bw = min(24, $slot * 0.62))
    @php($peakIndex = collect($chart['days'])->search(fn ($d) => $d['total'] === max(array_column($chart['days'], 'total'))))
    <section class="panel min-w-0">
        <div class="flex flex-wrap items-end justify-between gap-2">
            <div>
                <h2 class="font-semibold">Penjualan 14 hari terakhir</h2>
                <p class="text-sm text-muted">Pesanan produk lunas, QRIS dan saldo</p>
            </div>
            <p class="num text-right"><span class="block text-xs text-muted">Total 14 hari</span><span class="text-lg font-semibold tracking-tight">{{ rupiah($chart['total']) }}</span></p>
        </div>

        <div class="-mx-1 mt-4 overflow-x-auto px-1 [scrollbar-width:thin]" data-scroll-end>
        <svg viewBox="0 0 {{ $W }} {{ $H }}" class="block h-auto w-full min-w-[560px]" role="img" aria-label="Grafik penjualan harian 14 hari terakhir. Total {{ rupiah($chart['total']) }}.">
            {{-- grid + sumbu Y --}}
            @foreach($chart['ticks'] as $tick)
                @php($y = $T + $ph * (1 - $tick / $chart['max']))
                <line x1="{{ $L }}" x2="{{ $W - $R }}" y1="{{ $y }}" y2="{{ $y }}" class="{{ $tick === 0 ? 'stroke-ink/25' : 'stroke-line' }}" stroke-width="1"/>
                <text x="{{ $L - 8 }}" y="{{ $y + 4 }}" text-anchor="end" class="fill-muted text-[11px]">{{ $tick === 0 ? '0' : rupiah_short($tick) }}</text>
            @endforeach

            @foreach($chart['days'] as $i => $d)
                @php($x = $L + $i * $slot + ($slot - $bw) / 2)
                @php($h = $ph * $d['total'] / $chart['max'])
                @php($y = $T + $ph - $h)
                @php($isToday = $i === $n - 1)
                @php($r = min(4, $h, $bw / 2))
                @if($h > 0)
                    <path d="M{{ $x }},{{ $T + $ph }} V{{ $y + $r }} Q{{ $x }},{{ $y }} {{ $x + $r }},{{ $y }} H{{ $x + $bw - $r }} Q{{ $x + $bw }},{{ $y }} {{ $x + $bw }},{{ $y + $r }} V{{ $T + $ph }} Z" class="{{ $isToday ? 'fill-brand-600' : 'fill-brand-300' }}"/>
                @endif
                {{-- label nilai hanya untuk puncak --}}
                @if($i === $peakIndex && $d['total'] > 0)
                    <text x="{{ $x + $bw / 2 }}" y="{{ $y - 7 }}" text-anchor="middle" class="fill-ink text-[11px] font-semibold">{{ rupiah_short($d['total']) }}</text>
                @endif
                {{-- sumbu X: tanggal selang-seling, hari ini selalu --}}
                @if($isToday || ($n - 1 - $i) % 2 === 0)
                    <text x="{{ $x + $bw / 2 }}" y="{{ $H - 8 }}" text-anchor="middle" class="{{ $isToday ? 'fill-ink font-semibold' : 'fill-muted' }} text-[11px]">{{ $isToday ? 'Hari ini' : $d['date']->format('d/m') }}</text>
                @endif
                {{-- area sentuh lebih besar dari batang --}}
                <rect x="{{ $L + $i * $slot }}" y="{{ $T }}" width="{{ $slot }}" height="{{ $ph }}" class="chart-hit" tabindex="0"
                      data-tip="{{ $d['date']->translatedFormat('l, d M') }}&#10;{{ rupiah($d['total']) }} · {{ $d['count'] }} pesanan"/>
            @endforeach
        </svg>
        </div>

        <details class="mt-3 text-sm">
            <summary class="cursor-pointer text-muted hover:text-ink">Lihat sebagai tabel</summary>
            <div class="mt-2 max-h-64 overflow-auto rounded-lg border border-line">
                <table class="num w-full text-sm">
                    <thead class="sticky top-0 bg-canvas text-left text-xs text-muted"><tr><th class="px-3 py-2 font-medium">Tanggal</th><th class="px-3 py-2 text-right font-medium">Pesanan</th><th class="px-3 py-2 text-right font-medium">Penjualan</th></tr></thead>
                    <tbody>
                    @foreach(array_reverse($chart['days']) as $d)
                        <tr class="border-t border-line"><td class="px-3 py-1.5">{{ $d['date']->translatedFormat('D, d M') }}</td><td class="px-3 py-1.5 text-right">{{ $d['count'] }}</td><td class="px-3 py-1.5 text-right">{{ rupiah($d['total']) }}</td></tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </details>
    </section>

    {{-- Produk terlaris --}}
    <section class="panel min-w-0">
        <h2 class="font-semibold">Terlaris 30 hari</h2>
        @if($top->isEmpty())
            <p class="py-8 text-center text-sm text-muted">Belum ada penjualan.</p>
        @else
            <ol class="mt-3 space-y-3.5">
                @foreach($top as $row)
                    @php($p = $topProducts->get($row->product_id))
                    @continue(! $p)
                    <li class="flex items-center gap-3">
                        <div class="size-9 shrink-0 overflow-hidden rounded-lg border border-line">@include('partials.thumb', ['product' => $p, 'class' => 'size-full', 'text' => 'text-xs', 'compact' => true])</div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-baseline justify-between gap-2 text-sm">
                                <a href="{{ route('admin.stock.index', $p) }}" class="truncate font-medium hover:text-brand-700">{{ $p->name }}</a>
                                <span class="num shrink-0 font-semibold">{{ rupiah($row->revenue) }}</span>
                            </div>
                            <svg viewBox="0 0 100 4" preserveAspectRatio="none" class="mt-1.5 block h-1 w-full" aria-hidden="true">
                                <rect width="100" height="4" rx="2" class="fill-canvas"/>
                                <rect width="{{ max(2, round($row->revenue / $topMax * 100, 1)) }}" height="4" rx="2" class="fill-brand-500"/>
                            </svg>
                            <p class="num mt-1 text-xs text-muted">{{ $row->qty }} terjual</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        @endif
    </section>
</div>

<div class="mt-5 grid items-start gap-5 lg:grid-cols-[1.7fr_1fr]">
    {{-- Pesanan terbaru --}}
    <section class="panel min-w-0 p-0!">
        <div class="flex items-center justify-between px-5 pt-5 sm:px-6">
            <h2 class="font-semibold">Pesanan terbaru</h2>
            <a href="{{ route('admin.orders.index') }}" class="text-sm text-muted hover:text-ink">Semua ›</a>
        </div>
        <ul class="mt-2 divide-y divide-line">
            @forelse($recent as $o)
                @php($item = $o->items->first())
                <li>
                    <a href="{{ route('admin.orders.show', $o) }}" class="flex items-center gap-3 px-5 py-3 hover:bg-canvas sm:px-6">
                        <div class="size-9 shrink-0 overflow-hidden rounded-lg border border-line">
                            @if($item)
                                @include('partials.thumb', ['product' => $item->product, 'class' => 'size-full', 'text' => 'text-xs', 'compact' => true])
                            @else
                                <span class="grid size-full place-items-center bg-brand-50 text-brand-600"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"/><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"/></svg></span>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-baseline justify-between gap-2">
                                <p class="truncate text-sm font-medium">{{ $o->title() }}</p>
                                <p class="num shrink-0 text-sm font-semibold">{{ rupiah($o->total) }}</p>
                            </div>
                            <div class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-1">
                                <span class="pill {{ $o->statusClass() }}">{{ $o->statusLabel() }}</span>
                                <span class="truncate text-xs text-muted">{{ $o->user->username }} · {{ $o->methodLabel() }} · {{ $o->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    </a>
                </li>
            @empty
                <li class="px-6 py-10 text-center text-sm text-muted">Belum ada pesanan.</li>
            @endforelse
        </ul>
    </section>

    {{-- Stok menipis --}}
    <section class="panel min-w-0">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold">Stok menipis</h2>
            <a href="{{ route('admin.products.index') }}" class="text-sm text-muted hover:text-ink">Produk ›</a>
        </div>
        <div class="mt-3 space-y-2">
            @forelse($lowStock as $p)
                <a href="{{ route('admin.stock.index', $p) }}" class="flex items-center gap-3 rounded-lg border border-line px-3 py-2.5 text-sm hover:border-ink/15">
                    <div class="size-8 shrink-0 overflow-hidden rounded-md">@include('partials.thumb', ['product' => $p, 'class' => 'size-full', 'text' => 'text-xs', 'compact' => true])</div>
                    <span class="min-w-0 flex-1 truncate font-medium">{{ $p->name }}</span>
                    <span class="num shrink-0 text-xs font-semibold {{ $p->stock() === 0 ? 'text-red-600' : 'text-amber-600' }}">{{ $p->stock() === 0 ? 'Habis' : 'Sisa '.$p->stock() }}</span>
                </a>
            @empty
                <div class="flex items-center gap-2.5 rounded-lg bg-emerald-50 px-3 py-3 text-sm text-emerald-800">
                    <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5 9-10"/></svg>
                    Semua produk punya stok lebih dari {{ config('enovaz.low_stock') }}.
                </div>
            @endforelse
        </div>
    </section>
</div>
@endsection
