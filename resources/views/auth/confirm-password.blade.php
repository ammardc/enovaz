@extends('layouts.app')
@section('title', 'Konfirmasi password')

@section('content')
<div class="mx-auto max-w-md">
    <div class="panel">
        <h1 class="text-2xl font-semibold tracking-tight">Konfirmasi password</h1>
        <p class="mt-1 mb-6 text-muted">Masukkan password untuk lanjut.</p>

        <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4" data-once>
            @csrf
            <div>
                <label for="password" class="label">Password</label>
                <input id="password" name="password" type="password" class="input" autocomplete="current-password" maxlength="128" required autofocus>
                @error('password')<p class="error">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="btn btn-primary w-full py-3">Konfirmasi</button>
        </form>
    </div>
</div>
@endsection
