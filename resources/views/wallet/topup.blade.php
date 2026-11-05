@extends('layouts.app')
@section('title', 'Top up saldo')

@section('content')
    <a class="back-link" href="{{ route('wallet.index') }}">@include('partials.icon', ['name' => 'back']) Kembali ke Wallet</a>
    <h1 class="page-title">Top up</h1>

        <div class="two-col" style="margin-top:26px">
            <div class="panel">
                <h2>Nominal top up</h2>
                <form method="post" action="{{ route('wallet.topup.store') }}" class="form-grid">
                    @csrf
                    <div class="form-field">
                        <label for="amount">Jumlah (Rp)</label>
                        <input class="form-control" id="amount" name="amount" type="number" min="{{ config('store.topup_min') }}" max="{{ config('store.topup_max') }}" step="1000" value="{{ old('amount', 50000) }}" required>
                        <div class="form-help">Min. Rp{{ number_format(config('store.topup_min'), 0, ',', '.') }} · maks. Rp{{ number_format(config('store.topup_max'), 0, ',', '.') }}</div>
                    </div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap">
                        @foreach([25000, 50000, 100000, 200000] as $quick)
                            <button class="chip" type="button" onclick="document.getElementById('amount').value={{ $quick }}">Rp{{ number_format($quick, 0, ',', '.') }}</button>
                        @endforeach
                    </div>
                    <button class="button" {{ !$qrisReady ? 'disabled' : '' }}>{{ $qrisReady ? 'Lanjut ke QRIS' : 'Belum tersedia' }}</button>
                </form>
            </div>
            <div class="panel">
                <h2>Bagaimana cara kerjanya?</h2>
                <div class="summary-line"><span>1.</span><strong>Pilih nominal</strong></div>
                <div class="summary-line"><span>2.</span><strong>Scan QRIS dan bayar tepat</strong></div>
                <div class="summary-line"><span>3.</span><strong>Saldo masuk setelah terverifikasi</strong></div>
            </div>
        </div>
@endsection
