@extends('layouts.app')
@section('title','Daftar')
@section('content')
<div class="auth-wrap">
    <div class="panel">
        <h1>Daftar</h1>
        <form method="post" action="{{ route('register') }}" class="form-grid">
            @csrf
            <div class="form-field"><label for="name">Nama</label><input class="form-control" id="name" name="name" value="{{ old('name') }}" autocomplete="name" required></div>
            <div class="form-field"><label for="username">Username</label><input class="form-control" id="username" name="username" value="{{ old('username') }}" autocomplete="username" required><div class="form-help">Hanya huruf, angka, underscore dan dash.</div></div>
            <div class="form-field"><label for="email">Email</label><input class="form-control" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required></div>
            <div class="form-field"><label for="password">Kata sandi</label><input class="form-control" id="password" name="password" type="password" autocomplete="new-password" required><div class="form-help">Minimal 16 karakter, huruf dan angka.</div></div>
            <div class="form-field"><label for="password_confirmation">Ulangi kata sandi</label><input class="form-control" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required></div>
            <button class="button full">Daftar</button>
        </form>
        <div class="auth-switch">Sudah punya akun? <a class="text-link" href="{{ route('login') }}">Masuk</a></div>
    </div>
</div>
@endsection
