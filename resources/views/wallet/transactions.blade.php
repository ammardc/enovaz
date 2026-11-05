@extends('layouts.app')
@section('title', 'Riwayat transaksi')

@section('content')
    <a class="back-link" href="{{ route('wallet.index') }}">@include('partials.icon', ['name' => 'back']) Kembali ke Wallet</a>
    <h1 class="page-title">Riwayat transaksi</h1>
    <div class="panel" style="margin-top:26px">
        @forelse($ledger as $entry)
            <div class="ledger-entry">
                <div>
                    <strong>{{ ['topup' => 'Top up', 'purchase' => 'Pembelian', 'refund' => 'Refund', 'adjustment' => 'Koreksi admin'][$entry->kind] ?? $entry->kind }}</strong>
                    <small>{{ $entry->reference }} · {{ \Carbon\Carbon::parse($entry->created_at)->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB</small>
                </div>
                <div style="text-align:right">
                    <div class="ledger-amount {{ $entry->amount_idr >= 0 ? 'plus' : '' }}">{{ $entry->amount_idr >= 0 ? '+' : '−' }}Rp{{ number_format(abs($entry->amount_idr), 0, ',', '.') }}</div>
                    <small>Sisa Rp{{ number_format($entry->balance_after_idr, 0, ',', '.') }}</small>
                </div>
            </div>
        @empty
            <p class="small-note">Belum ada transaksi.</p>
        @endforelse
    </div>
    <div class="pagination">{{ $ledger->links() }}</div>
@endsection
