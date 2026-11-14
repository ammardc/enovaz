@extends('layouts.app')
@section('title', 'Masuk')

@section('content')
<div class="mx-auto max-w-md">
    <div class="panel">
        <h1 class="mb-6 text-2xl font-semibold tracking-tight">Masuk</h1>

        <form method="POST" action="{{ route('login') }}" class="space-y-4" data-once>
            @csrf
            <div>
                <label for="username" class="label">Username</label>
                <input id="username" name="username" value="{{ old('username') }}" class="input" autocomplete="username" maxlength="32" required autofocus>
                @error('username')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password" class="label">Password</label>
                <input id="password" name="password" type="password" class="input" autocomplete="current-password" maxlength="128" required>
                @error('password')<p class="error">{{ $message }}</p>@enderror
            </div>
            @include('partials.captcha')
            <button type="submit" class="btn btn-primary w-full py-3">Masuk</button>
        </form>

        <p class="mt-5 text-center text-sm text-muted">Belum punya akun? <a href="{{ route('register') }}" class="font-semibold text-brand-600">Daftar</a></p>
    </div>
</div>
@endsection
