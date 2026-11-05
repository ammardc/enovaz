@extends('layouts.app')
@section('title', 'Sesi habis')

@section('content')
    @php
        $previous = url()->previous();
        $returnTo = parse_url($previous, PHP_URL_HOST) === request()->getHost() ? $previous : route('shop');
    @endphp
    <div class="auth-wrap">
        <div class="panel">
            <h1>Sesi habis</h1>
            <p>Halaman terlalu lama terbuka. Buka ulang formulir, lalu coba lagi.</p>
            <a class="button full" href="{{ $returnTo }}">Buka ulang formulir</a>
        </div>
    </div>
@endsection
