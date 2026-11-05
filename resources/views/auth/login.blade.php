@extends('layouts.app')
@section('title','Masuk')
@section('content')
<div class="auth-wrap">
    <div class="panel">
        <h1>Masuk</h1>
        <form method="post" action="{{ route('login') }}" class="form-grid">
            @csrf
            <div class="form-field"><label for="username">Username</label><input class="form-control" id="username" name="username" type="text" value="{{ old('username') }}" autocomplete="username" required></div>
            <div class="form-field"><label for="password">Kata sandi</label><input class="form-control" id="password" name="password" type="password" autocomplete="current-password" required></div>
            <div class="auth-options"><label><input name="remember" type="checkbox"> Ingat saya</label><a class="text-link" href="{{ route('password.request') }}">Lupa kata sandi?</a></div>
            <button class="button full">Masuk</button>
        </form>
        @if(config('services.google.client_id') && config('services.google.client_secret'))
            <div class="divider">atau</div><a class="button secondary full" href="{{ route('google.redirect') }}">Lanjutkan dengan Google</a>
        @endif
        <div class="auth-switch">Belum punya akun? <a class="text-link" href="{{ route('register') }}">Daftar</a></div>
    </div>
</div>
@endsection
