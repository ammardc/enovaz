@extends('layouts.app')
@section('title', 'Daftar')

@section('content')
<div class="mx-auto max-w-md">
    <div class="panel">
        <h1 class="mb-6 text-2xl font-semibold tracking-tight">Daftar</h1>

        <form method="POST" action="{{ route('register') }}" class="space-y-4" data-once>
            @csrf
            <div>
                <label for="name" class="label">Nama</label>
                <input id="name" name="name" value="{{ old('name') }}" class="input" autocomplete="name" maxlength="100" required>
                @error('name')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="username" class="label">Username</label>
                <input id="username" name="username" value="{{ old('username') }}" class="input" autocomplete="username" minlength="3" maxlength="32" pattern="[a-z0-9._]+" required>
                <p class="help">Huruf kecil, angka, titik, atau _</p>
                @error('username')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password" class="label">Password</label>
                <input id="password" name="password" type="password" class="input" autocomplete="new-password" minlength="{{ config('enovaz.password_min.pembeli') }}" maxlength="128" required>
                <p class="help">Min. {{ config('enovaz.password_min.pembeli') }} karakter, huruf + angka. Kalimat panjang lebih aman.</p>
                @error('password')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password_confirmation" class="label">Ulangi password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" class="input" autocomplete="new-password" maxlength="128" required>
            </div>
            @include('partials.captcha')
            <button type="submit" class="btn btn-primary w-full py-3">Daftar</button>
        </form>

        <p class="mt-5 text-center text-sm text-muted">Sudah punya akun? <a href="{{ route('login') }}" class="font-semibold text-brand-600">Masuk</a></p>
    </div>
</div>
@endsection
