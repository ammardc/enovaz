@extends('layouts.app')
@section('title', 'Verifikasi 2 langkah')

@section('content')
<div class="mx-auto max-w-md">
    <div class="panel">
        <span class="grid size-12 place-items-center rounded-xl bg-brand-50 text-brand-700">
            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="20" x="5" y="2" rx="2"/><path d="M12 18h.01"/></svg>
        </span>
        <h1 class="mt-4 text-2xl font-semibold tracking-tight">Verifikasi 2 langkah</h1>
        <p class="mt-1 mb-6 text-sm text-muted">Masukkan kode 6 digit dari aplikasi authenticator di HP-mu.</p>

        <form method="POST" action="{{ route('login.2fa') }}" class="space-y-4" data-once>
            @csrf
            <div>
                <label for="code" class="label">Kode</label>
                <input id="code" name="code" class="input num text-center text-2xl tracking-[0.5em]" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9 ]*" maxlength="7" required autofocus placeholder="••••••">
                @error('code')<p class="error">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="btn btn-primary w-full py-3">Verifikasi</button>
        </form>

        <p class="mt-5 text-center text-sm text-muted"><a href="{{ route('login') }}" class="font-semibold text-brand-600">Kembali ke halaman masuk</a></p>
    </div>
</div>
@endsection
