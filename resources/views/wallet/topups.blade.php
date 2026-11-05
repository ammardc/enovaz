@extends('layouts.app')
@section('title', 'Riwayat top up')

@section('content')
    <a class="back-link" href="{{ route('wallet.index') }}">@include('partials.icon', ['name' => 'back']) Kembali ke Wallet</a>
    <h1 class="page-title">Riwayat top up</h1>
    <div class="panel" style="margin-top:26px">
        @forelse($topups as $invoice)
            <a class="ledger-entry" href="{{ route('payments.show', $invoice->reference) }}">
                <div>
                    <strong>Rp{{ number_format($invoice->base_amount_idr, 0, ',', '.') }}</strong>
                    <small>{{ $invoice->reference }} · {{ \Carbon\Carbon::parse($invoice->created_at)->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB</small>
                </div>
                <span class="status-pill {{ $invoice->status }}">{{ ['pending' => 'Menunggu', 'paid' => 'Selesai', 'expired' => 'Kedaluwarsa', 'review' => 'Perlu pemeriksaan'][$invoice->status] ?? $invoice->status }}</span>
            </a>
        @empty
            <p class="small-note">Belum ada top up.</p>
        @endforelse
    </div>
    <div class="pagination">{{ $topups->links() }}</div>
@endsection
