@extends('layouts.app')
@section('title', 'Wallet')

@section('content')
    <h1 class="page-title">Wallet</h1>
    <div class="wallet-overview" style="margin-top:26px">
        <div class="balance-card">
            <div>
                <span>Saldo tersedia</span>
                <strong>Rp{{ number_format($wallet?->balance_idr ?? 0, 0, ',', '.') }}</strong>
            </div>
            <a href="{{ route('wallet.topup') }}">Top up</a>
        </div>
        <div class="wallet-actions">
            <a class="action-tile" href="{{ route('wallet.transactions') }}">Riwayat transaksi @include('partials.icon', ['name' => 'arrow'])</a>
            <a class="action-tile" href="{{ route('wallet.topups') }}">Riwayat top up @include('partials.icon', ['name' => 'arrow'])</a>
            @if(config('store.telegram_url'))
                <a class="action-tile" href="{{ config('store.telegram_url') }}" target="_blank" rel="noopener noreferrer">Contact support @include('partials.icon', ['name' => 'arrow'])</a>
            @endif
        </div>
    </div>
@endsection
